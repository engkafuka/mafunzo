<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\TrainingApplication;
use App\Models\User;
use App\Notifications\TraineeStatusNotification;
use App\Support\PaginationHelper;
use App\Support\TrainedPersonRegistrationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class RegistrationVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('role', 'trainee')
            ->whereIn('registration_status', ['pending', 'rejected'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('registration_status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('registration_category', $request->category);
        }

        $registrations = $query->paginate(PaginationHelper::PER_PAGE)->withQueryString();

        $pendingCount = User::where('role', 'trainee')->where('registration_status', 'pending')->count();

        return view('application-management.registrations.index', compact('registrations', 'pendingCount'));
    }

    public function show(User $user): View|RedirectResponse
    {
        if ($user->role !== 'trainee') {
            return redirect()->route('app-management.registrations.index');
        }

        $user->load(['educationBackgrounds', 'trainingApplications.course']);

        $legacyApplication = $user->trainingApplications()
            ->where('application_type', 'legacy_expert')
            ->latest()
            ->first();

        $priorCourses = Course::optionsForPriorTraining();

        return view('application-management.registrations.show', compact('user', 'legacyApplication', 'priorCourses'));
    }

    public function approve(User $user): RedirectResponse
    {
        if ($user->role !== 'trainee' || $user->registration_status !== 'pending') {
            return redirect()->route('app-management.registrations.index')->with('error', __('This registration cannot be approved.'));
        }

        if ($user->isTrainedPerson()) {
            $legacyApplication = $user->trainingApplications()
                ->where('application_type', 'legacy_expert')
                ->latest()
                ->first();

            if (! $legacyApplication || ! $legacyApplication->course_id) {
                return redirect()
                    ->route('app-management.registrations.show', $user)
                    ->with('error', __('Link the course previously trained before approving this registration.'));
            }
        }

        $user->update([
            'registration_status' => 'approved',
            'registration_reviewed_at' => now(),
            'registration_reviewed_by' => auth()->id(),
            'registration_rejection_reason' => null,
        ]);

        if ($user->isTrainedPerson()) {
            $legacyApplication = $user->trainingApplications()
                ->where('application_type', 'legacy_expert')
                ->latest()
                ->first();

            if ($legacyApplication) {
                DB::transaction(function () use ($legacyApplication) {
                    $legacyApplication = TrainingApplication::query()
                        ->whereKey($legacyApplication->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    // Skip payment; wait for staff exam scores + publish before certificate / ID card.
                    $legacyApplication->update([
                        'status' => 'payment_completed',
                        'application_review_status' => 'approved',
                        'application_reviewed_at' => now(),
                        'account_verified_at' => now(),
                        'payment_verified_at' => now(),
                        'payment_completed_at' => now(),
                        'exam_passed' => null,
                        'exam_score' => null,
                        'exam_uploaded_at' => null,
                        'exam_results_published_at' => null,
                        'certificate_issued_at' => null,
                        'registration_number' => $legacyApplication->registration_number
                            ?: TrainingApplication::registrationNumberFor($legacyApplication),
                    ]);
                });
            }

            $user->update(['profile_completed_at' => now()]);
        }

        $message = $user->isTrainedPerson()
            ? __('Your registration has been approved. WRRB staff will record your examination scores for your prior course. You can also apply for new training when ready.')
            : __('Your registration has been approved. You can now complete your profile and apply for training.');

        $user->notify(new TraineeStatusNotification(
            __('Registration approved'),
            $message,
            route('dashboard'),
            __('Open dashboard'),
        ));

        return redirect()->route('app-management.registrations.show', $user)->with('status', __('Registration approved.'));
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'trainee' || $user->registration_status !== 'pending') {
            return redirect()->route('app-management.registrations.index')->with('error', __('This registration cannot be rejected.'));
        }

        $request->validate([
            'registration_rejection_reason' => ['required', 'string', 'max:1000'],
        ], [], [
            'registration_rejection_reason' => __('rejection reason'),
        ]);

        $user->update([
            'registration_status' => 'rejected',
            'registration_reviewed_at' => now(),
            'registration_reviewed_by' => auth()->id(),
            'registration_rejection_reason' => $request->registration_rejection_reason,
        ]);

        $user->notify(new TraineeStatusNotification(
            __('Registration rejected'),
            __('Your registration was not approved. Please update your application and resubmit.'),
            route('registration.resubmit'),
            __('Update application'),
        ));

        return redirect()->route('app-management.registrations.index')->with('status', __('Registration rejected.'));
    }

    /**
     * Link or update the prior course on a previously trained registration
     * (needed for older records created before course selection existed).
     */
    public function updatePriorCourse(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'trainee' || ! $user->isTrainedPerson()) {
            return redirect()->route('app-management.registrations.index');
        }

        $validated = $request->validate(
            TrainedPersonRegistrationRules::trainingRules(),
            [],
            TrainedPersonRegistrationRules::attributeNames(),
        );

        $legacyApplication = $user->trainingApplications()
            ->where('application_type', 'legacy_expert')
            ->latest()
            ->first();

        if (! $legacyApplication) {
            return redirect()
                ->route('app-management.registrations.show', $user)
                ->with('error', __('No previously trained application record was found.'));
        }

        if ($legacyApplication->hasPublishedExamResults()) {
            return redirect()
                ->route('app-management.registrations.show', $user)
                ->with('error', __('Cannot change the prior course after examination results are published.'));
        }

        $course = Course::query()->findOrFail($validated['prior_course_id']);

        $legacyApplication->update([
            'course_id' => $course->id,
            'trained_year' => $course->session_year,
        ]);

        return redirect()
            ->route('app-management.registrations.show', $user)
            ->with('status', __('Prior course linked. Staff can record examination scores for this course after approval.'));
    }

    public function trainingCertificate(Request $request, TrainingApplication $application): Response|RedirectResponse
    {
        if ($application->application_type !== 'legacy_expert' || ! in_array($request->user()->role, ['super_admin', 'admin', 'staff'], true)) {
            abort(404);
        }

        if (! $application->certificate_path || ! Storage::disk('local')->exists($application->certificate_path)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($application->certificate_path);
        $ext = strtolower(pathinfo($application->certificate_path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($application->certificate_path).'"',
        ]);
    }
}
