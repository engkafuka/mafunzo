<?php

namespace App\Http\Controllers;

use App\Models\TrainingApplication;
use App\Models\User;
use App\Support\PaginationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('application-management.registrations.show', compact('user', 'legacyApplication'));
    }

    public function approve(User $user): RedirectResponse
    {
        if ($user->role !== 'trainee' || $user->registration_status !== 'pending') {
            return redirect()->route('app-management.registrations.index')->with('error', __('This registration cannot be approved.'));
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
                $legacyApplication->update([
                    'status' => 'payment_completed',
                    'application_review_status' => 'approved',
                    'application_reviewed_at' => now(),
                    'account_verified_at' => now(),
                    'payment_verified_at' => now(),
                    'payment_completed_at' => now(),
                    'exam_passed' => true,
                    'exam_uploaded_at' => now(),
                    'exam_results_published_at' => now(),
                    'registration_number' => $legacyApplication->legacy_registration_number,
                    'certificate_issued_at' => now(),
                ]);
            }

            $user->update(['profile_completed_at' => now()]);
        }

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

        return redirect()->route('app-management.registrations.index')->with('status', __('Registration rejected.'));
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
