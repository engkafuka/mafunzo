<?php

namespace App\Http\Controllers;

use App\Models\TrainingApplication;
use App\Support\ListReturn;
use App\Support\TraineeProfileUpdateRequestHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffTraineeProfileController extends Controller
{
    public function edit(TrainingApplication $application): View
    {
        $user = $application->user;
        abort_unless($user && $user->role === 'trainee', 404);

        $user->load('educationBackgrounds');

        $legacyApplication = $user->isTrainedPerson()
            ? $user->trainingApplications()
                ->with('course')
                ->where('application_type', 'legacy_expert')
                ->latest()
                ->first()
            : null;

        return view('trainee.profile', [
            'user' => $user,
            'legacyApplication' => $legacyApplication,
            'pageTitle' => __('Edit trainee profile'),
            'formAction' => route('app-management.applications.trainee-profile.update', $application),
            'cancelUrl' => ListReturn::preserve(route('app-management.applications.show', $application)),
            'staffIntro' => __('Update trainee personal details and education. Changes are saved to the trainee account and synced to all active applications (including applied position).'),
        ]);
    }

    public function update(Request $request, TrainingApplication $application): RedirectResponse
    {
        $user = $application->user;
        abort_unless($user && $user->role === 'trainee', 404);

        try {
            TraineeProfileUpdateRequestHandler::handle($request, $user);
        } catch (\RuntimeException $exception) {
            return ListReturn::redirectPreserving(route('app-management.applications.trainee-profile.edit', $application))
                ->with('error', $exception->getMessage());
        }

        return ListReturn::redirectPreserving(route('app-management.applications.show', $application))
            ->with('status', __('Profile updated successfully.'));
    }
}
