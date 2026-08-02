<?php

namespace App\Http\Controllers;

use App\Models\LicenseChangeRequest;
use App\Models\LicenseNomination;
use App\Support\LicenseChangeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LicenseChangeRequestController extends Controller
{
    public function store(Request $request, LicenseNomination $nomination): RedirectResponse
    {
        $user = $request->user();
        if (! $user || $nomination->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'change_type' => ['required', 'in:update,release'],
            'proposed_final_position' => ['nullable', 'string', 'max:100'],
            'proposed_organization_name' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            LicenseChangeRequestService::create(
                $nomination,
                LicenseChangeRequest::REQUESTED_BY_TRAINEE,
                $validated,
                $user,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('licensing.nominations.show', $nomination)
            ->with('status', __('Change request submitted. WRRB staff will review it.'));
    }

    public function cancel(Request $request, LicenseChangeRequest $changeRequest): RedirectResponse
    {
        $user = $request->user();
        $changeRequest->loadMissing('nomination');

        if (! $user || $changeRequest->nomination?->user_id !== $user->id) {
            abort(403);
        }

        try {
            LicenseChangeRequestService::cancelByRequester($changeRequest, $user);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('licensing.nominations.show', $changeRequest->nomination)
            ->with('status', __('Change request cancelled.'));
    }
}
