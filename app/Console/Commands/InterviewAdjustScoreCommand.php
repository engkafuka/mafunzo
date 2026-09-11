<?php

namespace App\Console\Commands;

use App\Models\InterviewQuestion;
use App\Models\InterviewScore;
use App\Models\InterviewSession;
use App\Support\Interview\InterviewCompanyReport;
use App\Support\Interview\InterviewScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class InterviewAdjustScoreCommand extends Command
{
    protected $signature = 'interview:adjust-score
                            {session : Interview session code, e.g. INT-20260907-DQQP}
                            {--target-percentage= : Target consolidated percentage}
                            {--target-total= : Target consolidated total score}
                            {--recommendation= : Optional recommendation after adjustment (recommend, do_not_recommend, conditional)}
                            {--question= : Restrict adjustment to one question sort order}
                            {--preserve-workflow : Keep decision status and reviewer fields (default when omitted)}
                            {--reset-workflow : Reset decision status to pending and auto recommendation}
                            {--no-audit : Do not write new interview audit log entries}
                            {--dry-run : Show planned changes without writing}';

    protected $description = 'Adjust submitted panel scores to reach a target consolidated result, then recalculate.';

    /** @var list<string> */
    private const ALLOWED_RECOMMENDATIONS = ['recommend', 'do_not_recommend', 'conditional'];

    public function handle(InterviewScoringService $scoringService): int
    {
        $sessionCode = trim($this->argument('session'));
        $dryRun = (bool) $this->option('dry-run');
        $noAudit = (bool) $this->option('no-audit');
        $preserveWorkflow = ! (bool) $this->option('reset-workflow');
        $recommendation = $this->option('recommendation');
        $targetPercentage = $this->option('target-percentage');
        $targetTotal = $this->option('target-total');
        $questionSortOrder = $this->option('question');

        if ($targetPercentage === null && $targetTotal === null) {
            $this->error('Provide --target-percentage or --target-total.');

            return self::FAILURE;
        }

        if ($recommendation !== null && ! in_array($recommendation, self::ALLOWED_RECOMMENDATIONS, true)) {
            $this->error('Recommendation must be one of: '.implode(', ', self::ALLOWED_RECOMMENDATIONS));

            return self::FAILURE;
        }

        $session = InterviewSession::query()
            ->with(['company', 'result', 'questionSet.activeQuestions', 'scores.panelist'])
            ->where('session_code', $sessionCode)
            ->first();

        if (! $session) {
            $this->error("Session not found: {$sessionCode}");

            return self::FAILURE;
        }

        if (! $session->result) {
            $this->error('This session has no consolidated result yet.');

            return self::FAILURE;
        }

        $maxPossible = (float) $session->questionSet->activeQuestions->sum('max_mark');
        if ($maxPossible <= 0) {
            $this->error('Question set has no max marks configured.');

            return self::FAILURE;
        }

        $currentTotal = (float) $session->result->total_score;
        $currentPercentage = (float) $session->result->percentage;

        $desiredTotal = $targetTotal !== null
            ? round((float) $targetTotal, 2)
            : round(((float) $targetPercentage / 100) * $maxPossible, 2);

        $desiredPercentage = round(($desiredTotal / $maxPossible) * 100, 2);
        $totalDelta = round($desiredTotal - $currentTotal, 2);

        if ($totalDelta === 0.0) {
            $this->warn('Session is already at the target total.');

            return $this->applyRecommendationOnly($session, $recommendation, $dryRun);
        }

        $plan = $this->buildAdjustmentPlan($session, $totalDelta, $questionSortOrder);
        if ($plan === null) {
            $this->error('Could not distribute the score adjustment across submitted panel scores.');
            $this->line('Needed total change: '.($totalDelta >= 0 ? '+' : '').$totalDelta);
            $this->showQuestionHeadroom($session);

            return self::FAILURE;
        }

        $this->table(
            ['Field', 'Current', 'Target'],
            [
                ['Session', $session->session_code, ''],
                ['Company', $session->company?->name ?? '—', ''],
                ['Total score', $currentTotal.' / '.$maxPossible, $desiredTotal.' / '.$maxPossible],
                ['Percentage', $currentPercentage.'%', $desiredPercentage.'%'],
                ['Passed', $session->result->passed ? 'Yes' : 'No', ($desiredPercentage >= (float) $session->pass_mark) ? 'Yes' : 'No'],
                ['Decision status', InterviewCompanyReport::decisionLabel($session->result->decision_status), $preserveWorkflow ? 'unchanged' : 'pending'],
                ['Recommendation', InterviewCompanyReport::recommendationLabel($session->result->recommendation), $recommendation ? InterviewCompanyReport::recommendationLabel($recommendation) : 'from recalc'],
            ]
        );

        $this->table(
            ['Question', 'Panelist', 'Old score', 'New score', 'Change'],
            $plan->map(fn (array $row) => [
                'Q'.$row['sort_order'],
                $row['panelist'],
                $row['old'],
                $row['new'],
                ($row['change'] >= 0 ? '+' : '').number_format($row['change'], 2),
            ])->all()
        );

        if ($dryRun) {
            $this->info('[DRY RUN] No changes written.');

            return self::SUCCESS;
        }

        foreach ($plan as $row) {
            InterviewScore::query()->whereKey($row['id'])->update(['score' => $row['new']]);
        }

        $session->refresh()->load(['questionSet.activeQuestions', 'scores.panelist', 'result']);

        $result = $scoringService->consolidateResults($session, $preserveWorkflow, ! $noAudit);

        if ($recommendation !== null) {
            $result->update(['recommendation' => $recommendation]);
        }

        $result->refresh();

        $this->info('Score adjustment complete.');
        $this->line('New total: '.$result->total_score.' / '.$result->max_possible_score.' ('.$result->percentage.'%)');
        $this->line('Passed: '.($result->passed ? 'Yes' : 'No'));
        $this->line('Recommendation: '.InterviewCompanyReport::recommendationLabel($result->recommendation));

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, array{id: int, sort_order: int, panelist: string, old: float, new: float, change: float}>|null
     */
    private function buildAdjustmentPlan(
        InterviewSession $session,
        float $totalDelta,
        mixed $questionSortOrder,
    ): ?Collection {
        $remaining = abs($totalDelta);
        $direction = $totalDelta >= 0 ? 1 : -1;
        $pending = collect();

        $questions = $session->questionSet->activeQuestions->sortBy('sort_order')->values();

        if ($questionSortOrder !== null && $questionSortOrder !== '') {
            $questions = $questions->filter(
                fn (InterviewQuestion $question) => (int) $question->sort_order === (int) $questionSortOrder
            )->values();

            if ($questions->isEmpty()) {
                return null;
            }
        }

        while ($remaining > 0.009) {
            $bestQuestion = null;
            $bestApply = 0.0;

            foreach ($questions as $question) {
                $apply = $this->maxApplicableDelta($session, $question, $direction, $remaining);
                if ($apply > $bestApply) {
                    $bestApply = $apply;
                    $bestQuestion = $question;
                }
            }

            if (! $bestQuestion || $bestApply <= 0) {
                return null;
            }

            $rows = $session->scores
                ->where('question_id', $bestQuestion->id)
                ->where('status', 'submitted');

            foreach ($rows as $row) {
                $old = (float) $row->score;
                $change = round($direction * $bestApply, 2);
                $new = round($old + $change, 2);

                $pending->push([
                    'id' => $row->id,
                    'sort_order' => (int) $bestQuestion->sort_order,
                    'panelist' => $row->panelist?->name ?? ('User #'.$row->panelist_user_id),
                    'old' => $old,
                    'new' => $new,
                    'change' => $change,
                ]);

                $row->score = $new;
            }

            $remaining = round($remaining - $bestApply, 2);
        }

        return $pending->isEmpty() ? null : $pending;
    }

    private function maxApplicableDelta(
        InterviewSession $session,
        InterviewQuestion $question,
        int $direction,
        float $remaining,
    ): float {
        $rows = $session->scores
            ->where('question_id', $question->id)
            ->where('status', 'submitted');

        if ($rows->isEmpty()) {
            return 0.0;
        }

        $limits = $rows->map(function (InterviewScore $row) use ($question, $direction) {
            $score = (float) $row->score;
            $maxMark = (float) $question->max_mark;

            return $direction > 0
                ? max(0.0, $maxMark - $score)
                : max(0.0, $score);
        });

        $headroom = round((float) $limits->min(), 2);

        return max(0.0, min($remaining, $headroom));
    }

    private function applyRecommendationOnly(InterviewSession $session, ?string $recommendation, bool $dryRun): int
    {
        if ($recommendation === null) {
            return self::SUCCESS;
        }

        $this->line('Will set recommendation to '.InterviewCompanyReport::recommendationLabel($recommendation).'.');

        if ($dryRun) {
            return self::SUCCESS;
        }

        $session->result?->update(['recommendation' => $recommendation]);

        return self::SUCCESS;
    }

    private function showQuestionHeadroom(InterviewSession $session): void
    {
        $rows = $session->questionSet->activeQuestions
            ->sortBy('sort_order')
            ->map(function (InterviewQuestion $question) use ($session) {
                $scores = $session->scores
                    ->where('question_id', $question->id)
                    ->where('status', 'submitted')
                    ->pluck('score')
                    ->map(fn ($score) => (float) $score);

                $minHeadroomUp = $scores->isEmpty()
                    ? null
                    : $scores->map(fn (float $score) => (float) $question->max_mark - $score)->min();

                return [
                    'Q'.$question->sort_order,
                    $question->max_mark,
                    $scores->isEmpty() ? '—' : number_format($scores->avg(), 2),
                    $minHeadroomUp === null ? '—' : number_format($minHeadroomUp, 2),
                ];
            });

        $this->table(['Question', 'Max', 'Average', 'Min headroom up'], $rows->all());
    }
}
