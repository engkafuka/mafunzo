<?php

namespace App\Support;

use App\Models\LicenseChangeRequest;
use App\Models\LicenseNomination;
use App\Models\User;
use App\Notifications\TraineeStatusNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LicenseChangeRequestService
{
    public static function assertCanRequestChange(LicenseNomination $nomination): void
    {
        LicenseNominationService::expireIfNeeded($nomination);

        if (! in_array($nomination->status, [
            LicenseNomination::STATUS_ACCEPTED,
            LicenseNomination::STATUS_LICENSED,
        ], true)) {
            throw ValidationException::withMessages([
                'nomination' => __('Change requests are only allowed for accepted or licensed engagements.'),
            ]);
        }

        if ($nomination->status === LicenseNomination::STATUS_LICENSED && ! $nomination->isLicenseActive()) {
            throw ValidationException::withMessages([
                'nomination' => __('This license is no longer within its validity period.'),
            ]);
        }
    }

    /**
     * @param  array{
     *     change_type: string,
     *     proposed_final_position?: string|null,
     *     proposed_organization_name?: string|null,
     *     reason?: string|null,
     * }  $payload
     */
    public static function create(
        LicenseNomination $nomination,
        string $requestedBy,
        array $payload,
        ?User $actor = null,
    ): LicenseChangeRequest {
        self::assertCanRequestChange($nomination);

        if (LicenseChangeRequest::query()
            ->where('license_nomination_id', $nomination->id)
            ->where('status', LicenseChangeRequest::STATUS_PENDING)
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'change_request' => __('A pending change request already exists for this nomination. Wait for WRRB review.'),
            ]);
        }

        $changeType = $payload['change_type'] ?? LicenseChangeRequest::TYPE_UPDATE;
        if (! in_array($changeType, [
            LicenseChangeRequest::TYPE_UPDATE,
            LicenseChangeRequest::TYPE_RELEASE,
        ], true)) {
            throw ValidationException::withMessages([
                'change_type' => __('Invalid change type.'),
            ]);
        }

        $proposedPosition = null;
        $proposedOrg = null;

        if ($changeType === LicenseChangeRequest::TYPE_UPDATE) {
            $rawPosition = $payload['proposed_final_position'] ?? null;
            if (filled($rawPosition)) {
                $proposedPosition = LicensingTrainedStaffQuery::normalizePosition((string) $rawPosition);
                if ($proposedPosition === null) {
                    throw ValidationException::withMessages([
                        'proposed_final_position' => __('Invalid proposed position.'),
                    ]);
                }
            }

            if (array_key_exists('proposed_organization_name', $payload)) {
                $proposedOrg = trim((string) ($payload['proposed_organization_name'] ?? ''));
                $proposedOrg = $proposedOrg === '' ? null : $proposedOrg;
            }

            $positionChanging = $proposedPosition !== null
                && $proposedPosition !== $nomination->final_position;
            $orgChanging = array_key_exists('proposed_organization_name', $payload)
                && $proposedOrg !== $nomination->organization_name;

            if (! $positionChanging && ! $orgChanging) {
                throw ValidationException::withMessages([
                    'change_request' => __('Provide a new position and/or organization name that differs from the current values.'),
                ]);
            }
        }

        $request = LicenseChangeRequest::create([
            'license_nomination_id' => $nomination->id,
            'requested_by_user_id' => $actor?->id,
            'requested_by' => $requestedBy,
            'change_type' => $changeType,
            'current_final_position' => $nomination->final_position,
            'proposed_final_position' => $changeType === LicenseChangeRequest::TYPE_UPDATE
                ? ($proposedPosition ?? $nomination->final_position)
                : null,
            'current_organization_name' => $nomination->organization_name,
            'proposed_organization_name' => $changeType === LicenseChangeRequest::TYPE_UPDATE
                ? (array_key_exists('proposed_organization_name', $payload)
                    ? $proposedOrg
                    : $nomination->organization_name)
                : null,
            'reason' => $payload['reason'] ?? null,
            'status' => LicenseChangeRequest::STATUS_PENDING,
        ]);

        self::notifyTraineeSubmitted($request);
        self::sendChangeCallback($request->fresh(['nomination']));

        return $request->fresh(['nomination']);
    }

    public static function approve(LicenseChangeRequest $request, User $reviewer, ?string $notes = null): LicenseChangeRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'change_request' => __('This change request is no longer pending.'),
            ]);
        }

        $nomination = $request->nomination;
        if (! $nomination) {
            throw ValidationException::withMessages([
                'change_request' => __('Nomination not found.'),
            ]);
        }

        self::assertCanRequestChange($nomination);

        if ($request->change_type === LicenseChangeRequest::TYPE_RELEASE) {
            LicenseNominationService::cancel($nomination);
        } else {
            $updates = [];
            if ($request->proposed_final_position
                && $request->proposed_final_position !== $nomination->final_position
            ) {
                $updates['final_position'] = $request->proposed_final_position;
            }
            if ($request->proposed_organization_name !== $request->current_organization_name) {
                $updates['organization_name'] = $request->proposed_organization_name;
            }
            if ($updates !== []) {
                $nomination->update($updates);
                LicenseNominationService::sendCallback($nomination->fresh());
            }
        }

        $request->update([
            'status' => LicenseChangeRequest::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        $fresh = $request->fresh(['nomination', 'reviewer']);
        self::notifyTraineeDecision($fresh, approved: true);
        self::sendChangeCallback($fresh);

        return $fresh;
    }

    public static function reject(LicenseChangeRequest $request, User $reviewer, ?string $notes = null): LicenseChangeRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'change_request' => __('This change request is no longer pending.'),
            ]);
        }

        $request->update([
            'status' => LicenseChangeRequest::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        $fresh = $request->fresh(['nomination', 'reviewer']);
        self::notifyTraineeDecision($fresh, approved: false);
        self::sendChangeCallback($fresh);

        return $fresh;
    }

    public static function cancelByRequester(LicenseChangeRequest $request, ?User $actor = null): LicenseChangeRequest
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'change_request' => __('This change request is no longer pending.'),
            ]);
        }

        if ($actor
            && $request->requested_by === LicenseChangeRequest::REQUESTED_BY_TRAINEE
            && $request->requested_by_user_id !== $actor->id
        ) {
            abort(403);
        }

        $request->update([
            'status' => LicenseChangeRequest::STATUS_CANCELLED,
            'reviewed_at' => now(),
            'review_notes' => __('Cancelled by requester.'),
        ]);

        $fresh = $request->fresh(['nomination']);
        self::sendChangeCallback($fresh);

        return $fresh;
    }

    protected static function notifyTraineeSubmitted(LicenseChangeRequest $request): void
    {
        $request->loadMissing('nomination.user');
        $user = $request->nomination?->user;
        if (! $user) {
            return;
        }

        $user->notify(new TraineeStatusNotification(
            __('License change request submitted'),
            __('A change request (:summary) is awaiting WRRB approval.', [
                'summary' => $request->summaryLabel(),
            ]),
            route('licensing.nominations.show', $request->nomination),
            __('View nomination'),
        ));
    }

    protected static function notifyTraineeDecision(LicenseChangeRequest $request, bool $approved): void
    {
        $request->loadMissing('nomination.user');
        $user = $request->nomination?->user;
        if (! $user) {
            return;
        }

        $user->notify(new TraineeStatusNotification(
            $approved ? __('License change approved') : __('License change rejected'),
            $approved
                ? __('WRRB approved your change request: :summary.', ['summary' => $request->summaryLabel()])
                : __('WRRB rejected your change request: :summary.', ['summary' => $request->summaryLabel()]),
            route('licensing.nominations.show', $request->nomination),
            __('View nomination'),
        ));
    }

    public static function sendChangeCallback(LicenseChangeRequest $request): void
    {
        $request->loadMissing('nomination');
        $url = $request->nomination?->callback_url;
        if (! filled($url)) {
            return;
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'event' => 'license_change_request',
                    'change_request' => $request->toApiArray(),
                    'nomination' => $request->nomination?->toApiArray(),
                ]);

            if (! $response->successful()) {
                Log::warning('License change request callback failed', [
                    'change_request_id' => $request->id,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('License change request callback exception', [
                'change_request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
