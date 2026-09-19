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

    public static function syncTrainingApplication(TrainingApplication $application, array $validated): void
    {
        $application->update([
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
        ]);
    }

    /**
     * Push profile personal fields (including applied position) to every linked application.
     * Does not change assigned_position, exam, payment, or course fields.
     */
    public static function syncAllTrainingApplicationsFromProfile(User $user, array $validated): void
    {
        TrainingApplication::query()
            ->where('user_id', $user->id)
            ->where('application_review_status', '!=', 'rejected')
            ->orderBy('id')
            ->each(fn (TrainingApplication $application) => self::syncTrainingApplication($application, $validated));
    }

    /**
     * @return array<string, mixed>
     */
    public static function personalSnapshotFromUser(User $user): array
    {
        return $user->applicationSnapshotAttributes();
    }

    public static function applicationMatchesPersonalSnapshot(TrainingApplication $application, array $snapshot): bool
    {
        foreach ($snapshot as $key => $expected) {
            if (! self::personalFieldValuesEqual($key, $application->getAttribute($key), $expected)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool Whether the application row was updated
     */
    public static function syncTrainingApplicationFromUser(TrainingApplication $application, User $user): bool
    {
        $snapshot = self::personalSnapshotFromUser($user);

        if (self::applicationMatchesPersonalSnapshot($application, $snapshot)) {
            return false;
        }

        self::syncTrainingApplication($application, $snapshot);

        return true;
    }

    private static function personalFieldValuesEqual(string $key, mixed $actual, mixed $expected): bool
    {
        if ($key === 'date_of_birth') {
            $actualDate = $actual instanceof \DateTimeInterface
                ? $actual->format('Y-m-d')
                : (is_string($actual) && $actual !== '' ? $actual : null);
            $expectedDate = $expected instanceof \DateTimeInterface
                ? $expected->format('Y-m-d')
                : (is_string($expected) && $expected !== '' ? $expected : null);

            return $actualDate === $expectedDate;
        }

        return self::normalizePersonalScalar($actual) === self::normalizePersonalScalar($expected);
    }

    private static function normalizePersonalScalar(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
