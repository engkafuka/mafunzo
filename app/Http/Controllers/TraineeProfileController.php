<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\EducationBackground;
use App\Models\TrainingApplication;
use App\Support\NewApplicantRegistrationRules;
use App\Support\PaginationHelper;
use App\Support\ProfilePhotoStorage;
use App\Support\TrainedPersonRegistrationRules;
use App\Support\TraineeProfileUpdater;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class TraineeProfileController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->role !== 'trainee') {
            return redirect()->route('dashboard');
        }

        $user->load('educationBackgrounds');

        $legacyApplication = $user->isTrainedPerson()
            ? $user->trainingApplications()
                ->where('application_type', 'legacy_expert')
                ->latest()
                ->first()
            : null;

        $courses = Course::orderByDesc('session_year')->orderBy('name')->get();

        $educationBackgrounds = $user->educationBackgrounds()
            ->orderByDesc('created_at')
            ->paginate(PaginationHelper::PER_PAGE)
            ->withQueryString();

        return view('trainee.profile', compact('user', 'educationBackgrounds', 'legacyApplication', 'courses'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'trainee') {
            return redirect()->route('dashboard');
        }

        if ($user->isTrainedPerson()) {
            return $this->updateTrainedPersonProfile($request, $user);
        }

        return $this->updateNewApplicantProfile($request, $user);
    }

    public function showCertificate(Request $request, EducationBackground $educationBackground): Response|RedirectResponse
    {
        $user = $request->user();
        $canView = $educationBackground->user_id === $user->id
            || in_array($user->role, ['super_admin', 'admin', 'staff'], true);

        if (! $canView || ! $educationBackground->certificate_path) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($educationBackground->certificate_path)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($educationBackground->certificate_path);
        $ext = strtolower(pathinfo($educationBackground->certificate_path, PATHINFO_EXTENSION));
        $mimes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.basename($educationBackground->certificate_path).'"',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->role !== 'trainee') {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'level' => ['required', 'string', 'in:'.implode(',', array_keys(EducationBackground::levelOptions()))],
            'program' => ['required', 'string', 'in:'.implode(',', array_keys(EducationBackground::programOptions()))],
            'program_other' => ['required_if:program,others', 'nullable', 'string', 'max:255'],
            'institution' => ['required', 'string', 'max:255'],
            'certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], ValidationRules::requiredMessages(), [
            'level' => __('level'),
            'program' => __('program'),
            'program_other' => __('program specification'),
            'institution' => __('institution'),
            'certificate' => __('certificate'),
        ]);

        $path = $request->file('certificate')->store('certificates', 'local');

        EducationBackground::create([
            'user_id' => $user->id,
            'level' => $request->level,
            'program' => $request->program,
            'program_other' => $request->program === 'others' ? $request->program_other : null,
            'institution' => $request->institution,
            'certificate_path' => $path,
        ]);

        $user->update(['profile_completed_at' => now()]);

        return redirect()->route('trainee.profile.edit')->with('status', __('Education background added.'));
    }

    private function updateNewApplicantProfile(Request $request, $user): RedirectResponse
    {
        $rules = array_merge(
            NewApplicantRegistrationRules::personalRules($user->id),
            NewApplicantRegistrationRules::educationRules(certificatesRequired: false),
            ['profile_photo' => ProfilePhotoStorage::rules($user->hasProfilePhoto() ? false : true)],
        );

        $validated = $request->validate(
            $rules,
            ValidationRules::registrationMessages(),
            NewApplicantRegistrationRules::attributeNames(),
        );

        NewApplicantRegistrationRules::validateEducationRows($request, certificatesRequired: false);

        DB::transaction(function () use ($request, $validated, $user) {
            TraineeProfileUpdater::updatePersonalDetails($user, $validated);
            TraineeProfileUpdater::updateProfilePhoto($user, $request);
            $user->update(['profile_completed_at' => now()]);
            TraineeProfileUpdater::syncEducationBackgrounds($user, $request, $validated['education']);
        });

        return redirect()->route('trainee.profile.edit')->with('status', __('Profile updated successfully.'));
    }

    private function updateTrainedPersonProfile(Request $request, $user): RedirectResponse
    {
        $legacyApplication = $user->trainingApplications()
            ->where('application_type', 'legacy_expert')
            ->latest()
            ->first();

        if (! $legacyApplication) {
            return redirect()->route('trainee.profile.edit')
                ->with('error', __('Your previous training record could not be found. Please contact WRRB staff.'));
        }

        $rules = array_merge(
            NewApplicantRegistrationRules::personalRules($user->id),
            TrainedPersonRegistrationRules::trainingRules(certificatesRequired: false),
            ['profile_photo' => ProfilePhotoStorage::rules($user->hasProfilePhoto() ? false : true)],
        );

        $validated = $request->validate(
            $rules,
            ValidationRules::registrationMessages(),
            array_merge(NewApplicantRegistrationRules::attributeNames(), TrainedPersonRegistrationRules::attributeNames()),
        );

        TrainedPersonRegistrationRules::validateTrainingCertificate(
            $request,
            (bool) $legacyApplication->certificate_path,
        );

        DB::transaction(function () use ($request, $validated, $user, $legacyApplication) {
            TraineeProfileUpdater::updatePersonalDetails($user, $validated);
            TraineeProfileUpdater::updateProfilePhoto($user, $request);
            TraineeProfileUpdater::updateLegacyTrainingApplication($user, $request, $validated, $legacyApplication);
            $user->update(['profile_completed_at' => now()]);
        });

        return redirect()->route('trainee.profile.edit')->with('status', __('Profile updated successfully.'));
    }
}
