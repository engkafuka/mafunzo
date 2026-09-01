<?php

namespace App\Support;

use App\Models\LicenseNomination;
use App\Models\TrainingApplication;
use App\Models\User;
use App\Notifications\TraineeStatusNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LicenseNominationService
{
    public static function create(array $payload): LicenseNomination
    {
        $registrationNumber = trim((string) ($payload['registration_number'] ?? ''));
        $finalPosition = LicensingTrainedStaffQuery::normalizePosition($payload['final_position'] ?? null);
        $licensingApplicationId = trim((string) ($payload['licensing_application_id'] ?? ''));

        if ($registrationNumber === '' || $finalPosition === null || $licensingApplicationId === '') {
            throw ValidationException::withMessages([
                'registration_number' => __('A valid registration number, final position, and licensing application id are required.'),
            ]);
        }

        if (LicenseNomination::registrationIsReserved($registrationNumber)) {
            throw ValidationException::withMessages([
                'registration_number' => __('This staff member is already reserved or licensed with another company until that engagement ends.'),
            ]);
        }

        $application = LicensingTrainedStaffQuery::baseQuery()
            ->where('registration_number', $registrationNumber)
            ->where('assigned_position', $finalPosition)
            ->first();

        if (! $application || ! $application->user_id) {
            throw ValidationException::withMessages([
                'registration_number' => __('No eligible trained staff found for this registration number and position.'),
            ]);
        }

        $existing = LicenseNomination::query()
            ->where('licensing_application_id', $licensingApplicationId)
            ->where('registration_number', $registrationNumber)
            ->where('final_position', $finalPosition)
            ->where('status', LicenseNomination::STATUS_PENDING)
            ->latest('id')
            ->first();

        if ($existing && $existing->isPending()) {
            return $existing;
        }

        $days = max(1, (int) config('licensing.nomination_expires_days', 7));

        $nomination = LicenseNomination::create([
            'user_id' => $application->user_id,
            'training_application_id' => $application->id,
            'registration_number' => $registrationNumber,
            'final_position' => $finalPosition,
            'licensing_application_id' => $licensingApplicationId,
            'organization_name' => $payload['organization_name'] ?? null,
            'callback_url' => $payload['callback_url'] ?? null,
            'status' => LicenseNomination::STATUS_PENDING,
            'expires_at' => now()->addDays($days),
        ]);

        self::notifyTrainee($nomination);

        return $nomination;
    }

    public static function notifyTrainee(LicenseNomination $nomination): void
    {
        $nomination->loadMissing('user');
        if (! $nomination->user) {
            return;
        }

        $org = $nomination->organization_name ?: __('a licensing application');

        $nomination->user->notify(new TraineeStatusNotification(
            __('License nomination'),
            __('You were nominated as :position for :org. Please accept or reject this request.', [
                'position' => $nomination->finalPositionLabel(),
                'org' => $org,
            ]),
            route('licensing.nominations.show', $nomination),
            __('Respond now'),
        ));
    }

    public static function accept(LicenseNomination $nomination, User $actor): LicenseNomination
    {
        return self::respond($nomination, $actor, LicenseNomination::STATUS_ACCEPTED);
    }

    public static function reject(LicenseNomination $nomination, User $actor): LicenseNomination
    {
        return self::respond($nomination, $actor, LicenseNomination::STATUS_REJECTED);
    }

    public static function respond(LicenseNomination $nomination, User $actor, string $status): LicenseNomination
    {
        if ($nomination->user_id !== $actor->id) {
            abort(403);
        }

        self::expireIfNeeded($nomination);

        if (! $nomination->isPending()) {
            throw ValidationException::withMessages([
                'nomination' => __('This nomination is no longer pending.'),
            ]);
        }

        if ($status === LicenseNomination::STATUS_ACCEPTED
            && LicenseNomination::userIsReserved($nomination->user_id)
        ) {
            throw ValidationException::withMessages([
                'nomination' => __('You already accepted another nomination. Wait until that license is issued, rejected, or cancelled before accepting a different one.'),
            ]);
        }

        $nomination->update([
            'status' => $status,
            'responded_at' => now(),
        ]);

        self::sendCallback($nomination->fresh());

        return $nomination->fresh();
    }

    public static function expireIfNeeded(LicenseNomination $nomination): void
    {
        if ($nomination->status === LicenseNomination::STATUS_PENDING
            && $nomination->expires_at
            && $nomination->expires_at->isPast()
        ) {
            $nomination->update(['status' => LicenseNomination::STATUS_EXPIRED]);
            $nomination->refresh();
        }
    }

    /**
     * Licensing system: mark license issued after trainee accepted.
     *
     * @param  array{license_number?: string|null, license_valid_from: string, license_valid_until: string}  $payload
     */
    public static function issueLicense(LicenseNomination $nomination, array $payload): LicenseNomination
    {
        self::expireIfNeeded($nomination);

        if ($nomination->status !== LicenseNomination::STATUS_ACCEPTED) {
            throw ValidationException::withMessages([
                'nomination' => __('Only an accepted nomination can be marked as licensed.'),
            ]);
        }

        $nomination->update([
            'status' => LicenseNomination::STATUS_LICENSED,
            'license_number' => $payload['license_number'] ?? $nomination->license_number,
            'license_issued_at' => now(),
            'license_valid_from' => $payload['license_valid_from'],
            'license_valid_until' => $payload['license_valid_until'],
        ]);

        $fresh = $nomination->fresh();
        self::notifyLicenseIssued($fresh);
        self::sendCallback($fresh);

        return $fresh;
    }

    public static function notifyLicenseIssued(LicenseNomination $nomination): void
    {
        $nomination->loadMissing('user');
        if (! $nomination->user) {
            return;
        }

        $org = $nomination->organization_name ?: __('the organization');

        $nomination->user->notify(new TraineeStatusNotification(
            __('License issued'),
            __('Your license as :position for :org is active until :until.', [
                'position' => $nomination->finalPositionLabel(),
                'org' => $org,
                'until' => $nomination->license_valid_until?->format('Y-m-d') ?? __('further notice'),
            ]),
            route('licensing.nominations.show', $nomination),
            __('View details'),
        ));
    }

    /**
     * Cancel pending or accepted reservation, or revoke an active license (licensing system).
     */
    public static function cancel(LicenseNomination $nomination): LicenseNomination
    {
        self::expireIfNeeded($nomination);

        if (in_array($nomination->status, [
            LicenseNomination::STATUS_PENDING,
            LicenseNomination::STATUS_ACCEPTED,
        ], true)) {
            $nomination->update([
                'status' => LicenseNomination::STATUS_CANCELLED,
                'responded_at' => $nomination->responded_at ?? now(),
            ]);
            self::sendCallback($nomination->fresh());
        } elseif ($nomination->status === LicenseNomination::STATUS_LICENSED && $nomination->isLicenseActive()) {
            $nomination->update([
                'status' => LicenseNomination::STATUS_REVOKED,
                'responded_at' => now(),
            ]);
            self::sendCallback($nomination->fresh());
        }

        return $nomination->fresh();
    }

    public static function sendCallback(LicenseNomination $nomination): void
    {
        if (! filled($nomination->callback_url)) {
            return;
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->asJson()
                ->post($nomination->callback_url, $nomination->toApiArray());

            if ($response->successful()) {
                $nomination->update([
                    'callback_sent_at' => now(),
                    'callback_error' => null,
                ]);
            } else {
                $nomination->update([
                    'callback_error' => 'HTTP '.$response->status().': '.mb_substr($response->body(), 0, 500),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('License nomination callback failed', [
                'nomination_id' => $nomination->id,
                'error' => $e->getMessage(),
            ]);
            $nomination->update([
                'callback_error' => mb_substr($e->getMessage(), 0, 500),
            ]);
        }
    }
}
