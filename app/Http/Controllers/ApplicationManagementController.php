<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\LicenseChangeRequest;
use App\Models\TrainingApplication;
use App\Models\User;
use App\Notifications\TraineeStatusNotification;
use App\Support\CertificateDateFormatter;
use App\Support\CertificateSignatureStorage;
use App\Support\PaginationHelper;
use App\Support\QrCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationManagementController extends Controller
{
    /**
     * Dashboard: links to review applications, attendance, exams, certificates.
     */
    public function index(): View
    {
        $stats = [
            'pending_registrations' => User::where('role', 'trainee')->where('registration_status', 'pending')->count(),
            'pending_review' => TrainingApplication::where('application_review_status', 'pending')
                ->where('status', 'payment_completed')
                ->whereNotNull('course_id')
                ->where('application_type', '!=', 'legacy_expert')
                ->count(),
            'pending_payment_verify' => TrainingApplication::whereNull('payment_verified_at')
                ->whereIn('status', ['pending_payment', 'payment_completed'])
                ->whereNotNull('course_id')
                ->where('application_type', '!=', 'legacy_expert')
                ->count(),
            'pending_licensing_changes' => LicenseChangeRequest::where('status', LicenseChangeRequest::STATUS_PENDING)->count(),
        ];
        return view('application-management.index', compact('stats'));
    }

    /**
     * List applications for review and payment verification.
     */
    public function applications(Request $request): View
    {
        $query = TrainingApplication::with(['course', 'user'])
            ->where('status', '!=', 'pending_registration')
            ->whereNotNull('course_id')
            ->where('application_type', '!=', 'legacy_expert')
            ->orderByDesc('created_at');

        if ($request->filled('status_filter')) {
            if ($request->status_filter === 'pending_review') {
                $query->where('application_review_status', 'pending')->where('status', 'payment_completed');
            } elseif ($request->status_filter === 'pending_payment') {
                $query->whereNull('payment_verified_at')
                    ->whereIn('status', ['pending_payment', 'payment_completed']);
            }
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('q')) {
            $term = '%'.addcslashes(trim($request->string('q')->toString()), '%_\\').'%';
            $query->where(function ($qry) use ($term) {
                $qry->where('registration_number', 'like', $term)
                    ->orWhere('control_number', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('middle_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhereHas('course', fn ($course) => $course->where('name', 'like', $term));
            });
        }

        $applications = $query->paginate(PaginationHelper::PER_PAGE)->withQueryString();
        $courses = Course::orderBy('name')->get();

        return view('application-management.applications', compact('applications', 'courses'));
    }

    /**
     * Single application detail (review, verify actions). Includes applicant info and education with certificates.
     */
    public function applicationShow(TrainingApplication $application): View
    {
        $application->load(['course', 'user.educationBackgrounds']);
        return view('application-management.application-show', compact('application'));
    }

    /**
     * Approve or reject application.
     */
    public function applicationReview(Request $request, TrainingApplication $application): RedirectResponse
    {
        $request->validate(['action' => 'required|in:approve,reject']);

        $updates = [
            'application_review_status' => $request->action === 'approve' ? 'approved' : 'rejected',
            'application_reviewed_at' => now(),
        ];

        // Account verification is no longer a separate staff step.
        if ($request->action === 'approve' && $application->account_verified_at === null) {
            $updates['account_verified_at'] = now();
        }

        $application->update($updates);

        return redirect()->route('app-management.applications.show', $application)
            ->with('status', $request->action === 'approve' ? __('Application approved.') : __('Application rejected.'));
    }

    /**
     * Staff issues / updates the 12-digit payment control number.
     */
    public function updateControlNumber(Request $request, TrainingApplication $application): RedirectResponse
    {
        if ($application->status === 'pending_registration') {
            return redirect()->route('app-management.applications.show', $application)
                ->with('error', __('This application is not ready for a control number yet.'));
        }

        if ($application->payment_verified_at) {
            return redirect()->route('app-management.applications.show', $application)
                ->with('error', __('Control number cannot be changed after payment has been verified.'));
        }

        $validated = $request->validate([
            'control_number' => TrainingApplication::controlNumberRules($application->id),
        ], [], [
            'control_number' => __('control number'),
        ]);

        $application->update([
            'control_number' => $validated['control_number'],
        ]);

        $application->loadMissing(['user', 'course']);
        if ($application->user) {
            $application->user->notify(new TraineeStatusNotification(
                __('Control number issued'),
                __('Your payment control number for :course is ready. Use it to complete payment.', [
                    'course' => $application->course?->name ?? __('your course'),
                ]),
                route('training.payment', $application),
                __('Open payment page'),
            ));
        }

        return redirect()->route('app-management.applications.show', $application)
            ->with('status', __('Control number saved.'));
    }

    /**
     * Mark payment as verified (and set status to payment_completed if not already).
     * Also confirms account verification when still missing.
     */
    public function verifyPayment(TrainingApplication $application): RedirectResponse
    {
        if ($application->status === 'pending_registration') {
            return redirect()->route('app-management.applications.show', $application)
                ->with('error', __('This application is not ready for payment verification yet.'));
        }

        if (! $application->hasControlNumber()) {
            return redirect()->route('app-management.applications.show', $application)
                ->with('error', __('Enter a 12-digit control number before verifying payment.'));
        }

        if (! preg_match('/^\d{12}$/', (string) $application->control_number)) {
            return redirect()->route('app-management.applications.show', $application)
                ->with('error', __('Control number must be exactly 12 digits before verifying payment.'));
        }

        $this->markPaymentVerified($application);
        $this->notifyTraineePaymentVerified($application);

        return redirect()->route('app-management.applications.show', $application)->with('status', __('Payment verified.'));
    }

    private function markPaymentVerified(TrainingApplication $application): void
    {
        $updates = [
            'payment_verified_at' => now(),
            'status' => 'payment_completed',
            'payment_completed_at' => $application->payment_completed_at ?? now(),
        ];

        if ($application->account_verified_at === null) {
            $updates['account_verified_at'] = now();
        }

        if (! TrainingApplication::isOfficialRegistrationNumber($application->registration_number)) {
            $updates['registration_number'] = TrainingApplication::registrationNumberFor($application);
        }

        $application->update($updates);
    }

    private function notifyTraineePaymentVerified(TrainingApplication $application): void
    {
        $application->loadMissing(['user', 'course']);
        if (! $application->user) {
            return;
        }

        $application->user->notify(new TraineeStatusNotification(
            __('Payment verified'),
            __('Your payment for :course has been verified by staff. Your registration number is ready.', [
                'course' => $application->course?->name ?? __('your course'),
            ]),
            route('training.confirmation', $application),
            __('View registration number'),
        ));
    }

    /**
     * Attendance: list sessions, create session with QR.
     */
    public function attendance(Request $request): View
    {
        $query = AttendanceSession::with('course')->orderByDesc('session_date');
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        $sessions = $query->paginate(PaginationHelper::PER_PAGE)->withQueryString();
        $courses = Course::orderBy('name')->get();
        return view('application-management.attendance', compact('sessions', 'courses'));
    }

    /**
     * Create attendance session and show QR code.
     */
    public function attendanceCreate(Request $request): RedirectResponse|View
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'session_date' => 'required|date',
        ]);

        $session = AttendanceSession::create([
            'course_id' => $request->course_id,
            'name' => $request->name,
            'session_date' => $request->session_date,
            'qr_token' => AttendanceSession::generateQrToken(),
        ]);

        return redirect()->route('app-management.attendance.show', $session);
    }

    /**
     * Show session and QR code for scanning.
     */
    public function attendanceShow(Request $request, AttendanceSession $session): View
    {
        $session->load('course');
        $attendanceRecords = $session->attendanceRecords()
            ->with('trainingApplication')
            ->orderByDesc('scanned_at')
            ->paginate(PaginationHelper::PER_PAGE)
            ->withQueryString();
        $scanUrl = url('/attendance/scan?token=' . $session->qr_token);

        return view('application-management.attendance-show', compact('session', 'scanUrl', 'attendanceRecords'));
    }

    /**
     * Trainee/staff scan page: enter token or scan QR to record attendance.
     * If user is logged in as trainee, we match by their training applications for this course.
     */
    public function attendanceScanPage(Request $request): View|RedirectResponse
    {
        $token = $request->query('token');
        $session = $token ? AttendanceSession::with('course')->where('qr_token', $token)->first() : null;
        return view('application-management.attendance-scan', compact('session', 'token'));
    }

    /**
     * Submit attendance scan (by registration number or token + user's application).
     */
    public function attendanceScanSubmit(Request $request): RedirectResponse
    {
        $request->validate([
            'qr_token' => 'required|string',
            'registration_number' => 'required|string',
        ]);

        $session = AttendanceSession::where('qr_token', $request->qr_token)->first();
        if (! $session) {
            return back()->withErrors(['qr_token' => __('Invalid or expired session.')]);
        }

        $application = TrainingApplication::where('registration_number', $request->registration_number)->first();
        if (! $application) {
            return back()->withErrors(['registration_number' => __('Registration number not found.')]);
        }
        if ($application->course_id != $session->course_id) {
            return back()->withErrors(['registration_number' => __('This registration is not for this course.')]);
        }

        $exists = AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('training_application_id', $application->id)->exists();
        if ($exists) {
            return back()->with('status', __('Attendance already recorded.'));
        }

        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'training_application_id' => $application->id,
            'scanned_at' => now(),
        ]);

        return back()->with('status', __('Attendance recorded successfully.'));
    }

    /**
     * Certificates: list eligible trainees, generate certificate.
     */
    public function certificates(Request $request): View
    {
        $query = TrainingApplication::with('course')
            ->where('status', 'payment_completed')
            ->where('application_review_status', 'approved')
            ->where(function ($q) {
                $q->whereNotNull('account_verified_at')
                    ->orWhereNotNull('payment_verified_at');
            })
            ->whereNotNull('payment_verified_at')
            ->whereNotNull('course_id')
            ->where('exam_passed', true);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        $applications = $query->orderBy('registration_number')->paginate(PaginationHelper::PER_PAGE)->withQueryString();
        $courses = Course::orderBy('name')->get();
        $signatureUrl = CertificateSignatureStorage::url();

        return view('application-management.certificates', compact('applications', 'courses', 'signatureUrl'));
    }

    /**
     * Upload Managing Director signature used on certificates.
     */
    public function uploadCertificateSignature(Request $request): RedirectResponse
    {
        $request->validate([
            'md_signature' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], [], [
            'md_signature' => __('Managing Director signature'),
        ]);

        CertificateSignatureStorage::store($request->file('md_signature'));

        return redirect()
            ->route('app-management.certificates')
            ->with('status', __('Managing Director signature uploaded.'));
    }

    /**
     * Generate / view certificate for one application.
     */
    public function certificateShow(TrainingApplication $application): View
    {
        if (! $application->isEligibleForCertificate()) {
            abort(403, __('This trainee is not eligible for a certificate yet.'));
        }

        $application->loadMissing('course');

        $fullName = trim(collect([
            $application->first_name,
            $application->middle_name,
            $application->last_name,
        ])->filter()->implode(' '));

        $issuedAt = $application->certificate_issued_at ?? now();

        return view('application-management.certificate-view', [
            'application' => $application,
            'fullName' => $fullName,
            'organization' => config('certificate.organization'),
            'title' => config('certificate.title'),
            'awardedTo' => config('certificate.awarded_to'),
            'completionLine' => __(config('certificate.completion_line'), [
                'course' => $application->course?->name ?? __('Warehouse Management Training'),
            ]),
            'dateLine' => CertificateDateFormatter::longEnglish($issuedAt),
            'mdTitle' => config('certificate.md_title'),
            'govtLogoUrl' => asset(config('certificate.govt_logo')),
            'boardLogoUrl' => asset(config('certificate.board_logo')),
            'signatureUrl' => CertificateSignatureStorage::url(),
            'qrDataUri' => QrCodeGenerator::pngDataUri(
                'CSN.'.($application->registration_number ?? $application->id),
                160
            ),
        ]);
    }

    /**
     * Mark certificate as issued (optional: store path if we generate PDF later).
     */
    public function certificateIssue(TrainingApplication $application): RedirectResponse
    {
        if (! $application->isEligibleForCertificate()) {
            return redirect()->route('app-management.certificates')->with('error', __('Not eligible for certificate.'));
        }
        $application->update(['certificate_issued_at' => now()]);
        return redirect()->route('app-management.certificates')->with('status', __('Certificate issued.'));
    }
}
