<?php

namespace App\Support;

use App\Models\TrainingApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TraineeProfileUpdateRequestHandler
{
    public static function handle(Request $request, User $user, ?TrainingApplication $syncApplication = null): void
    {
        if ($user->isTrainedPerson()) {
            self::handleTrainedPerson($request, $user, $syncApplication);

            return;
        }

        self::handleNewApplicant($request, $user, $syncApplication);
    }

    private static function handleNewApplicant(Request $request, User $user, ?TrainingApplication $syncApplication): void
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

        DB::transaction(function () use ($request, $validated, $user, $syncApplication) {
            TraineeProfileUpdater::updatePersonalDetails($user, $validated);
            TraineeProfileUpdater::updateProfilePhoto($user, $request);
            $user->update(['profile_completed_at' => now()]);
            TraineeProfileUpdater::syncEducationBackgrounds($user, $request, $validated['education']);
            if ($syncApplication) {
                TraineeProfileUpdater::syncTrainingApplication($syncApplication, $validated);
            }
        });
    }

    private static function handleTrainedPerson(Request $request, User $user, ?TrainingApplication $syncApplication): void
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

        DB::transaction(function () use ($request, $validated, $user, $legacyApplication, $syncApplication) {
            TraineeProfileUpdater::updatePersonalDetails($user, $validated);
            TraineeProfileUpdater::updateProfilePhoto($user, $request);
            TraineeProfileUpdater::updateLegacyTrainingApplication($user, $request, $validated, $legacyApplication);
            TraineeProfileUpdater::syncEducationBackgrounds($user, $request, $validated['education']);
            $user->update(['profile_completed_at' => now()]);
            if ($syncApplication) {
                TraineeProfileUpdater::syncTrainingApplication($syncApplication, $validated);
            }
        });
    }
}
