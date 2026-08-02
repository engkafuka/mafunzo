<?php

namespace App\Http\Controllers;

use App\Models\LicenseNomination;
use App\Support\LicenseNominationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseNominationController extends Controller
{
    public function show(Request $request, LicenseNomination $nomination): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user || $nomination->user_id !== $user->id) {
            abort(403);
        }

        LicenseNominationService::expireIfNeeded($nomination);

        $nomination->load(['changeRequests' => fn ($q) => $q->orderByDesc('id')]);

        return view('licensing.nomination-show', [
            'nomination' => $nomination->fresh(['changeRequests']),
            'eligiblePositions' => config('licensing.eligible_positions', []),
        ]);
    }

    public function accept(Request $request, LicenseNomination $nomination): RedirectResponse
    {
        $user = $request->user();
        if (! $user || $nomination->user_id !== $user->id) {
            abort(403);
        }

        LicenseNominationService::accept($nomination, $user);

        return redirect()
            ->route('licensing.nominations.show', $nomination)
            ->with('status', __('You accepted this license nomination.'));
    }

    public function reject(Request $request, LicenseNomination $nomination): RedirectResponse
    {
        $user = $request->user();
        if (! $user || $nomination->user_id !== $user->id) {
            abort(403);
        }

        LicenseNominationService::reject($nomination, $user);

        return redirect()
            ->route('licensing.nominations.show', $nomination)
            ->with('status', __('You rejected this license nomination.'));
    }
}
