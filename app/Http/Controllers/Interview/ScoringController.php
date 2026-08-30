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

class ScoringController extends Controller
{
    public function __construct(
        private InterviewScoringService $scoringService
    ) {}

    public function edit(InterviewSession $session, Request $request): View
    {
        $user = $request->user();
        $assignment = $session->panelists()->where('user_id', $user->id)->firstOrFail();

        abort_unless(in_array($session->status, [
            InterviewSession::STATUS_SCORING,
            InterviewSession::STATUS_IN_PROGRESS,
        ], true), 403);

        $session->load(['questionSet.activeQuestions', 'company']);
        $this->scoringService->ensureScoreRows($session);

        $scores = $session->scores()
            ->where('panelist_user_id', $user->id)
            ->get()
            ->keyBy('question_id');

        $locked = $assignment->submission_status === 'submitted';

        return view('interview.scoring.edit', compact('session', 'scores', 'locked', 'assignment'));
    }

    public function update(Request $request, InterviewSession $session): RedirectResponse
    {
        $user = $request->user();
        $session->panelists()->where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
            'comments' => ['nullable', 'array'],
            'comments.*' => ['nullable', 'string', 'max:2000'],
            'submit' => ['nullable', 'boolean'],
        ]);

        try {
            $this->scoringService->saveDraftScores(
                $session,
                $user,
                $data['scores'],
                $data['comments'] ?? []
            );

            if ($request->boolean('submit')) {
                $this->scoringService->submitPanelistScores($session, $user);
            }
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->boolean('submit')) {
            return redirect()->route('interview.sessions.show', $session)->with('status', __('Your scores have been submitted and locked.'));
        }

        return back()->with('status', __('Draft scores saved.'));
    }
}
