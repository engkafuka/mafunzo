<?php

namespace App\Http\Controllers\Api\Licensing;

use App\Http\Controllers\Controller;
use App\Models\LicenseChangeRequest;
use App\Models\LicenseNomination;
use App\Support\LicenseChangeRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChangeRequestController extends Controller
{
    public function index(LicenseNomination $nomination): JsonResponse
    {
        $items = $nomination->changeRequests()
            ->orderByDesc('id')
            ->get()
            ->map(fn (LicenseChangeRequest $r) => $r->toApiArray())
            ->values();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, LicenseNomination $nomination): JsonResponse
    {
        $validated = $request->validate([
            'change_type' => ['required', 'in:update,release'],
            'proposed_final_position' => ['nullable', 'string', 'max:100'],
            'proposed_organization_name' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $changeRequest = LicenseChangeRequestService::create(
            $nomination,
            LicenseChangeRequest::REQUESTED_BY_COMPANY,
            $validated,
        );

        return response()->json([
            'message' => __('Change request submitted for WRRB approval.'),
            'data' => $changeRequest->toApiArray(),
        ], 201);
    }

    public function show(LicenseChangeRequest $changeRequest): JsonResponse
    {
        return response()->json([
            'data' => $changeRequest->toApiArray(),
        ]);
    }

    public function cancel(LicenseChangeRequest $changeRequest): JsonResponse
    {
        $changeRequest = LicenseChangeRequestService::cancelByRequester($changeRequest);

        return response()->json([
            'message' => __('Change request cancelled.'),
            'data' => $changeRequest->toApiArray(),
        ]);
    }
}
