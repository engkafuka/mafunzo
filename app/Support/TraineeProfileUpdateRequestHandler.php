<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TraineeProfileUpdateRequestHandler
{
    public static function handle(Request $request, User $user): void
    {
        if ($user->isTrainedPerson()) {
            self::handleTrainedPerson($request, $user);

            return;
        }

        self::handleNewApplicant($request, $user);
    }

    private static function handleNewApplicant(Request $request, User $user): void
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
            TraineeProfileUpdater::syncAllTrainingApplicationsFromProfile($user, $validated);
        });
    }

    private static function handleTrainedPerson(Request $request, User $user): void
    {
        $legacyApplication = $user->trainingApplications()
            ->where('application_type', 'legacy_expert')
            ->latest()
            ->first();

        if (! $legacyApplication) {
            throw new \RuntimeException(__('Your previous training record could not be found. Please contact WRRB staff.'));
        }

        $rules = array_merge(
            NewApplicantRegistrationRules::personalRules($user->id),
            NewApplicantRegistrationRules::educationRules(certificatesRequired: false, includeWrrb: true),
            ['profile_photo' => ProfilePhotoStorage::rules($user->hasProfilePhoto() ? false : true)],
        );

        $validated = $request->validate(
            $rules,
            ValidationRules::registrationMessages(),
            NewApplicantRegistrationRules::attributeNames(),
        );

        NewApplicantRegistrationRules::validateEducationRows(
            $request,
            certificatesRequired: false,
            requireWrrbCertificate: true,
        );

        DB::transaction(function () use ($request, $validated, $user) {
            TraineeProfileUpdater::updatePersonalDetails($user, $validated);
            TraineeProfileUpdater::updateProfilePhoto($user, $request);
            TraineeProfileUpdater::syncEducationBackgrounds($user, $request, $validated['education']);
            $user->update(['profile_completed_at' => now()]);
            TraineeProfileUpdater::syncAllTrainingApplicationsFromProfile($user, $validated);
        });
    }
}
