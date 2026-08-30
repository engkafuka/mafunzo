<?php

namespace App\Support\Interview;

use App\Models\InterviewQuestion;
use App\Models\InterviewResult;
use App\Models\InterviewScore;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Support\Collection;

class InterviewScoringService
{

    public function ensureScoreRows(InterviewSession $session): void
    {
        $session->loadMissing(['panelists', 'questionSet.activeQuestions']);

        foreach ($session->panelists as $panelist) {
            foreach ($session->questionSet->activeQuestions as $question) {
                InterviewScore::firstOrCreate(
                    [
                        'session_id' => $session->id,
                        'question_id' => $question->id,
                        'panelist_user_id' => $panelist->user_id,
                    ],
                    ['status' => 'draft']
                );
            }
        }
    }

    public function saveDraftScores(
        InterviewSession $session,
        User $panelist,
        array $scores,
        array $comments = []
    ): void {
        if ($session->isLockedForScoring($panelist)) {
            throw new \RuntimeException('Your scores are already submitted and locked.');
        }

        if (! in_array($session->status, [
            InterviewSession::STATUS_SCORING,
            InterviewSession::STATUS_IN_PROGRESS,
        ], true)) {
            throw new \RuntimeException('This session is not open for scoring.');
        }

        $assignment = $session->panelists()->where('user_id', $panelist->id)->firstOrFail();
        $questions = $session->questionSet->activeQuestions()->get()->keyBy('id');

        foreach ($scores as $questionId => $value) {
            $question = $questions->get((int) $questionId);

            if (! $question) {
                continue;
            }

            $scoreValue = $value === '' || $value === null ? null : (float) $value;

            if ($scoreValue !== null && ($scoreValue < 0 || $scoreValue > $question->max_mark)) {
                throw new \InvalidArgumentException(
                    "Score for question {$question->id} must be between 0 and {$question->max_mark}."
                );
            }

            InterviewScore::updateOrCreate(
                [
                    'session_id' => $session->id,
                    'question_id' => $question->id,
                    'panelist_user_id' => $panelist->id,
                ],
                [
                    'score' => $scoreValue,
                    'comment' => $comments[$questionId] ?? null,
                    'status' => 'draft',
                ]
            );
        }

        InterviewAuditLogger::log(
            'score_draft_saved',
            'Panelist saved draft scores',
            $panelist,
            $session->id,
            ['panelist_id' => $assignment->id]
        );
    }

    public function submitPanelistScores(InterviewSession $session, User $panelist): void
    {
        if ($session->isLockedForScoring($panelist)) {
            throw new \RuntimeException('Scores already submitted.');
        }

        $questions = $session->questionSet->activeQuestions;
        $saved = $session->scores()
            ->where('panelist_user_id', $panelist->id)
            ->get()
            ->keyBy('question_id');

        foreach ($questions as $question) {
            $row = $saved->get($question->id);

            if (! $row || $row->score === null) {
                throw new \RuntimeException('Please score all questions before submitting.');
            }
        }

        $session->scores()
            ->where('panelist_user_id', $panelist->id)
            ->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

        $assignment = $session->panelists()->where('user_id', $panelist->id)->firstOrFail();
        $assignment->update([
            'submission_status' => 'submitted',
            'submitted_at' => now(),
        ]);

        InterviewAuditLogger::log(
            'panelist_scores_submitted',
            'Panelist submitted scores',
            $panelist,
            $session->id
        );

        $session->refresh();

        if ($session->allPanelistsSubmitted()) {
            $this->consolidateResults($session);
        }
    }

    public function consolidateResults(InterviewSession $session): InterviewResult
    {
        $session->load(['questionSet.activeQuestions', 'scores', 'panelists.user']);

        $questions = $session->questionSet->activeQuestions;
        $maxPossible = (float) $questions->sum('max_mark');
        $perQuestion = [];
        $totalAverage = 0.0;
        $varianceFlags = [];

        foreach ($questions as $question) {
            $submitted = $session->scores
                ->where('question_id', $question->id)
                ->where('status', 'submitted')
                ->values();

            $values = $submitted->pluck('score')->map(fn ($s) => (float) $s);
            $average = $values->isEmpty() ? 0.0 : round($values->avg(), 2);
            $totalAverage += $average;

            $min = $values->min();
            $max = $values->max();
            $spread = $values->count() > 1 ? ($max - $min) : 0;

            if ($spread > config('interview.variance_threshold')) {
                $varianceFlags[] = [
                    'question_id' => $question->id,
                    'spread' => $spread,
                ];
            }

            $perQuestion[] = [
                'question_id' => $question->id,
                'category' => $question->category,
                'question_text' => $question->question_text,
                'max_mark' => $question->max_mark,
                'average_score' => $average,
                'panelist_scores' => $submitted->map(fn (InterviewScore $s) => [
                    'panelist' => $s->panelist->name,
                    'score' => (float) $s->score,
                    'comment' => $s->comment,
                ])->values()->all(),
            ];
        }

        $percentage = $maxPossible > 0
            ? round(($totalAverage / $maxPossible) * 100, 2)
            : 0.0;

        $passed = $percentage >= (float) $session->pass_mark;

        $result = InterviewResult::updateOrCreate(
            ['session_id' => $session->id],
            [
                'total_score' => round($totalAverage, 2),
                'max_possible_score' => $maxPossible,
                'percentage' => $percentage,
                'passed' => $passed,
                'recommendation' => $passed ? 'recommend' : 'do_not_recommend',
                'decision_status' => 'pending',
                'calculation_snapshot' => [
                    'questions' => $perQuestion,
                    'variance_flags' => $varianceFlags,
                    'calculated_at' => now()->toIso8601String(),
                ],
            ]
        );

        $session->update(['status' => InterviewSession::STATUS_UNDER_REVIEW]);

        InterviewAuditLogger::log(
            'results_consolidated',
            'Interview results consolidated after all panelists submitted',
            null,
            $session->id,
            ['percentage' => $percentage, 'passed' => $passed]
        );

        return $result;
    }

    public function scoresVisibleTo(User $user, InterviewSession $session): Collection
    {
        if ($user->hasInterviewRole('admin', 'chair', 'approver', 'viewer') || $user->isSuperAdmin()) {
            if ($session->allPanelistsSubmitted() || in_array($session->status, [
                InterviewSession::STATUS_UNDER_REVIEW,
                InterviewSession::STATUS_COMPLETED,
            ], true)) {
                return $session->scores()->with(['question', 'panelist'])->get();
            }
        }

        return $session->scores()
            ->where('panelist_user_id', $user->id)
            ->with(['question'])
            ->get();
    }
}
