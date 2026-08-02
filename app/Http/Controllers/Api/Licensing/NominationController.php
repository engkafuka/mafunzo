<?php

namespace App\Http\Controllers\Api\Licensing;

use App\Http\Controllers\Controller;
use App\Models\LicenseNomination;
use App\Support\LicenseNominationService;
use App\Support\LicensingTrainedStaffQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NominationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'registration_number' => ['required', 'string', 'max:100'],
            'final_position' => ['required', 'string', 'max:100'],
            'licensing_application_id' => ['required', 'string', 'max:100'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'callback_url' => ['nullable', 'url', 'max:500'],
        ]);

        if (LicensingTrainedStaffQuery::normalizePosition($validated['final_position']) === null) {
            return response()->json([
                'message' => __('Invalid final position for licensing.'),
                'eligible_positions' => config('licensing.eligible_positions'),
            ], 422);
        }

        $nomination = LicenseNominationService::create($validated);

        return response()->json([
            'message' => __('Nomination created. The trainee has been notified.'),
            'data' => $nomination->toApiArray(),
        ], 201);
    }

    public function show(LicenseNomination $nomination): JsonResponse
    {
        LicenseNominationService::expireIfNeeded($nomination);

        return response()->json([
            'data' => $nomination->fresh()->toApiArray(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'licensing_application_id' => ['required', 'string', 'max:100'],
        ]);

        $items = LicenseNomination::query()
            ->where('licensing_application_id', $request->string('licensing_application_id')->toString())
            ->orderByDesc('id')
            ->get()
            ->each(fn (LicenseNomination $n) => LicenseNominationService::expireIfNeeded($n))
            ->map(fn (LicenseNomination $n) => $n->fresh()->toApiArray())
            ->values();

        return response()->json(['data' => $items]);
    }

    public function issue(Request $request, LicenseNomination $nomination): JsonResponse
    {
        $validated = $request->validate([
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_valid_from' => ['required', 'date'],
            'license_valid_until' => ['required', 'date', 'after_or_equal:license_valid_from'],
        ]);

        $nomination = LicenseNominationService::issueLicense($nomination, $validated);

        return response()->json([
            'message' => __('License recorded. Staff member remains reserved until validity ends.'),
            'data' => $nomination->toApiArray(),
        ]);
    }

    public function cancel(LicenseNomination $nomination): JsonResponse
    {
        $before = $nomination->status;
        $nomination = LicenseNominationService::cancel($nomination);

        $message = match ($nomination->status) {
            LicenseNomination::STATUS_REVOKED => __('License revoked. Staff member is available again.'),
            LicenseNomination::STATUS_CANCELLED => __('Nomination cancelled. Reservation released if it was accepted.'),
            default => __('No cancellable reservation or license on this nomination.'),
        };

        if ($before === $nomination->status
            && ! in_array($nomination->status, [
                LicenseNomination::STATUS_CANCELLED,
                LicenseNomination::STATUS_REVOKED,
            ], true)
        ) {
            return response()->json([
                'message' => $message,
                'data' => $nomination->toApiArray(),
            ], 422);
        }

        return response()->json([
            'message' => $message,
            'data' => $nomination->toApiArray(),
        ]);
    }
}
