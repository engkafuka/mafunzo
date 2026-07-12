<?php

namespace App\Support;

use App\Models\EducationBackground;
use App\Models\TrainingApplication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TraineeProfileUpdater
{
    public static function updateProfilePhoto(User $user, Request $request): void
    {
        if (! $request->hasFile('profile_photo')) {
            return;
        }

        $path = ProfilePhotoStorage::storeForUser($user, $request->file('profile_photo'));
        $user->update([
            'profile_photo_path' => $path,
            'profile_photo_uploaded_at' => now(),
        ]);
    }

    public static function updatePersonalDetails(User $user, array $validated): void
    {
        $name = trim($validated['first_name'].' '.($validated['middle_name'] ?? '').' '.$validated['last_name']);

        $user->update([
            'name' => $name,
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'region' => $validated['region'],
            'district' => $validated['district'],
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'position' => $validated['position'],
            'company_or_private' => $validated['company_or_private'],
            'company_name' => $validated['company_or_private'] === 'company' ? $validated['company_name'] : null,
            'company_address' => $validated['company_or_private'] === 'company' ? $validated['company_address'] : null,
        ]);
    }

    public static function syncEducationBackgrounds(User $user, Request $request, array $educationRows): void
    {
        $keptIds = [];

        foreach ($educationRows as $index => $education) {
            $certificatePath = null;
            $existing = null;

            if (! empty($education['id'])) {
                $existing = EducationBackground::query()
                    ->where('user_id', $user->id)
                    ->whereKey($education['id'])
                    ->first();

                if (! $existing) {
                    continue;
                }

                $certificatePath = $existing->certificate_path;
            }

            if ($request->hasFile("education.$index.certificate")) {
                if ($existing?->certificate_path) {
                    Storage::disk('local')->delete($existing->certificate_path);
                }

                $certificatePath = $request->file("education.$index.certificate")->store('certificates', 'local');
            }

            $payload = [
                'level' => $education['level'],
                'program' => $education['program'],
                'program_other' => $education['program'] === 'others' ? ($education['program_other'] ?? null) : null,
                'institution' => $education['institution'],
                'certificate_path' => $certificatePath,
            ];

            if ($existing) {
                $existing->update($payload);
                $keptIds[] = $existing->id;
            } else {
                $created = EducationBackground::create([
                    'user_id' => $user->id,
                    ...$payload,
                ]);
                $keptIds[] = $created->id;
            }
        }

        $user->educationBackgrounds()
            ->whereNotIn('id', $keptIds)
            ->get()
            ->each(function (EducationBackground $background) {
                if ($background->certificate_path) {
                    Storage::disk('local')->delete($background->certificate_path);
                }
                $background->delete();
            });
    }

    public static function updateLegacyTrainingApplication(User $user, Request $request, array $validated, TrainingApplication $legacyApplication): void
    {
        $certificatePath = $legacyApplication->certificate_path;

        if ($request->hasFile('training_certificate')) {
            if ($legacyApplication->certificate_path) {
                Storage::disk('local')->delete($legacyApplication->certificate_path);
            }

            $certificatePath = $request->file('training_certificate')->store('certificates/legacy', 'local');
        }

        $legacyApplication->update([
            'course_id' => $validated['course_id'],
            'trained_year' => $validated['trained_year'],
            'certificate_number' => $validated['certificate_number'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'region' => $validated['region'],
            'district' => $validated['district'],
            'company_or_private' => $validated['company_or_private'],
            'company_name' => $validated['company_or_private'] === 'company' ? $validated['company_name'] : null,
            'company_address' => $validated['company_or_private'] === 'company' ? $validated['company_address'] : null,
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'position' => $validated['position'],
            'certificate_path' => $certificatePath,
        ]);
    }
}
