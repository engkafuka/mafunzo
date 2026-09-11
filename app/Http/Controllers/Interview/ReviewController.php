<?php

namespace App\Http\Controllers\Interview;

use App\Http\Controllers\Controller;
use App\Models\InterviewSession;
use App\Support\Interview\InterviewPdfExporter;
use App\Support\Interview\InterviewScoringService;
use App\Support\Interview\InterviewSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(
        private InterviewSessionService $sessionService,
        private InterviewScoringService $scoringService
    ) {}

    public function show(InterviewSession $session, Request $request): View
    {
        abort_unless(in_array($session->status, [
            InterviewSession::STATUS_UNDER_REVIEW,
            InterviewSession::STATUS_COMPLETED,
        ], true), 403);

        $user = $request->user();

        if (! $user->hasInterviewRole('admin', 'chair', 'approver', 'viewer')) {
            abort_unless(
                $session->panelists()->where('user_id', $user->id)->where('is_chair', true)->exists(),
                403
            );
        }

        $session->load(['company', 'questionSet', 'result.reviewer', 'panelists.user']);
        $consolidatedScores = $this->scoringService->scoresVisibleTo($user, $session);

        return view('interview.review.show', compact('session', 'consolidatedScores'));
    }

    public function confirm(Request $request, InterviewSession $session): RedirectResponse
    {
        abort_unless($request->user()->hasInterviewRole('admin', 'chair'), 403);

        $data = $request->validate([
            'recommendation' => ['required', 'in:recommend,do_not_recommend,conditional'],
            'chair_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->sessionService->confirmReview(
                $session,
                $request->user(),
                $data['recommendation'],
                $data['chair_notes'] ?? null
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Chair review recorded.'));
    }

    public function approve(Request $request, InterviewSession $session): RedirectResponse
    {
        abort_unless($request->user()->hasInterviewRole('admin', 'approver'), 403);

        $data = $request->validate([
            'approved' => ['required', 'boolean'],
        ]);

        try {
            $this->sessionService->approveDecision($session, $request->user(), (bool) $data['approved']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $data['approved'] ? __('Decision approved.') : __('Decision rejected.'));
    }

    public function exportPdf(InterviewSession $session, Request $request)
    {
        abort_unless(in_array($session->status, [
            InterviewSession::STATUS_UNDER_REVIEW,
            InterviewSession::STATUS_COMPLETED,
        ], true), 403);

        abort_unless($session->result, 404);

        if (! $request->user()->hasInterviewRole('admin', 'chair', 'approver', 'viewer')) {
            abort_unless(
                $session->panelists()->where('user_id', $request->user()->id)->exists(),
                403
            );
        }

        return InterviewPdfExporter::export($session);
    }
}
