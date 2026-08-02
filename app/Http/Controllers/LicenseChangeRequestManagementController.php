<?php

namespace App\Http\Controllers;

use App\Models\LicenseChangeRequest;
use App\Support\LicenseChangeRequestService;
use App\Support\PaginationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LicenseChangeRequestManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = LicenseChangeRequest::query()
            ->with(['nomination.user', 'requestedByUser', 'reviewer'])
            ->orderByDesc('id');

        $status = $request->query('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(PaginationHelper::PER_PAGE)->appends($request->query());

        return view('application-management.licensing-change-requests.index', [
            'requests' => $requests,
            'statusFilter' => $status,
        ]);
    }

    public function show(LicenseChangeRequest $changeRequest): View
    {
        $changeRequest->load(['nomination.user', 'requestedByUser', 'reviewer']);

        return view('application-management.licensing-change-requests.show', [
            'changeRequest' => $changeRequest,
        ]);
    }

    public function approve(Request $request, LicenseChangeRequest $changeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            LicenseChangeRequestService::approve(
                $changeRequest,
                $request->user(),
                $validated['review_notes'] ?? null,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('app-management.licensing-change-requests.show', $changeRequest)
            ->with('status', __('Change request approved and applied.'));
    }

    public function reject(Request $request, LicenseChangeRequest $changeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'max:2000'],
        ]);

        try {
            LicenseChangeRequestService::reject(
                $changeRequest,
                $request->user(),
                $validated['review_notes'],
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('app-management.licensing-change-requests.index')
            ->with('status', __('Change request rejected.'));
    }
}
