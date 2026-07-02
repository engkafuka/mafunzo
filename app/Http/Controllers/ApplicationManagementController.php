<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\TrainingApplication;
use App\Models\User;
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
            'pending_review' => TrainingApplication::where('application_review_status', 'pending')->where('status', 'payment_completed')->count(),
            'pending_account_verify' => TrainingApplication::whereNull('account_verified_at')->where('status', 'payment_completed')->count(),
            'pending_payment_verify' => TrainingApplication::whereNotNull('payment_completed_at')->where('status', 'pending_payment')->count(),
        ];
        return view('application-management.index', compact('stats'));
    }

    /**
     * List applications for review, verify account, verify payment.
     */
    public function applications(Request $request): View
    {
        $query = TrainingApplication::with(['course', 'user'])
            ->where('status', '!=', 'pending_registration')
            ->orderByDesc('created_at');

        if ($request->filled('status_filter')) {
            if ($request->status_filter === 'pending_review') {
                $query->where('application_review_status', 'pending')->where('status', 'payment_completed');
            } elseif ($request->status_filter === 'pending_account') {
                $query->whereNull('account_verified_at')->where('status', 'payment_completed');
            } elseif ($request->status_filter === 'pending_payment') {
                $query->where('status', 'pending_payment');
            }
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $applications = $query->paginate(15)->withQueryString();
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

        $application->update([
            'application_review_status' => $request->action === 'approve' ? 'approved' : 'rejected',
            'application_reviewed_at' => now(),
        ]);

        return redirect()->route('app-management.applications.show', $application)
            ->with('status', $request->action === 'approve' ? __('Application approved.') : __('Application rejected.'));
    }

    /**
     * Mark account as verified.
     */
    public function verifyAccount(TrainingApplication $application): RedirectResponse
    {
        $application->update(['account_verified_at' => now()]);
        return redirect()->route('app-management.applications.show', $application)->with('status', __('Account verified.'));
    }

    /**
     * Mark payment as verified (and set status to payment_completed if not already).
     */
    public function verifyPayment(TrainingApplication $application): RedirectResponse
    {
        $application->update([
            'payment_verified_at' => now(),
            'status' => 'payment_completed',
            'payment_completed_at' => $application->payment_completed_at ?? now(),
        ]);
        return redirect()->route('app-management.applications.show', $application)->with('status', __('Payment verified.'));
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
        $sessions = $query->paginate(15)->withQueryString();
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
    public function attendanceShow(AttendanceSession $session): View
    {
        $session->load(['course', 'attendanceRecords.trainingApplication']);
        $scanUrl = url('/attendance/scan?token=' . $session->qr_token);
        return view('application-management.attendance-show', compact('session', 'scanUrl'));
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
     * Exam results: list applications by course, upload results (score, pass/fail).
     */
    public function examResults(Request $request): View
    {
        $courseId = $request->query('course_id');
        $courses = Course::orderBy('name')->get();
        $applications = collect();
        if ($courseId) {
            $applications = TrainingApplication::with('course')
                ->where('course_id', $courseId)
                ->where('status', 'payment_completed')
                ->orderBy('registration_number')
                ->get();
        }
        return view('application-management.exam-results', compact('applications', 'courses', 'courseId'));
    }

    /**
     * Save exam results (scores and pass/fail per application).
     */
    public function examResultsSave(Request $request): RedirectResponse
    {
        $request->validate([
            'results' => 'required|array',
            'results.*.id' => 'required|exists:training_applications,id',
            'results.*.exam_score' => 'nullable|numeric|min:0|max:100',
            'results.*.exam_passed' => 'nullable|boolean',
        ]);

        $courseId = null;
        foreach ($request->results as $row) {
            $app = TrainingApplication::find($row['id'] ?? 0);
            if (! $app) {
                continue;
            }
            $courseId = $app->course_id;
            $app->update([
                'exam_score' => isset($row['exam_score']) && $row['exam_score'] !== '' ? (float) $row['exam_score'] : null,
                'exam_passed' => isset($row['exam_passed']) && $row['exam_passed'] !== '' ? (bool) (int) $row['exam_passed'] : null,
                'exam_uploaded_at' => now(),
            ]);
        }

        return redirect()->route('app-management.exam-results', ['course_id' => $courseId])
            ->with('status', __('Exam results saved.'));
    }

    /**
     * Certificates: list eligible trainees, generate certificate.
     */
    public function certificates(Request $request): View
    {
        $query = TrainingApplication::with('course')
            ->where('status', 'payment_completed')
            ->where('application_review_status', 'approved')
            ->whereNotNull('account_verified_at')
            ->whereNotNull('payment_verified_at')
            ->where('exam_passed', true);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        $applications = $query->orderBy('registration_number')->paginate(20)->withQueryString();
        $courses = Course::orderBy('name')->get();
        return view('application-management.certificates', compact('applications', 'courses'));
    }

    /**
     * Generate / view certificate for one application.
     */
    public function certificateShow(TrainingApplication $application): View
    {
        if (! $application->isEligibleForCertificate()) {
            abort(403, __('This trainee is not eligible for a certificate yet.'));
        }
        return view('application-management.certificate-view', compact('application'));
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
