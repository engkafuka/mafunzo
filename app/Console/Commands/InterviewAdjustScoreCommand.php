<?php

namespace App\Console\Commands;

use App\Models\InterviewQuestion;
use App\Models\InterviewScore;
use App\Models\InterviewSession;
use App\Support\Interview\InterviewCompanyReport;
use App\Support\Interview\InterviewScoringService;
use Illuminate\Console\Command;

class InterviewAdjustScoreCommand extends Command
{
    protected $signature = 'interview:adjust-score
                            {session : Interview session code, e.g. INT-20260907-DQQP}
                            {--target-percentage= : Target consolidated percentage}
                            {--target-total= : Target consolidated total score}
                            {--recommendation= : Optional recommendation after adjustment (recommend, do_not_recommend, conditional)}
                            {--question= : Question sort order to adjust (auto-picks if omitted)}
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

        $question = $this->resolveQuestion($session, $questionSortOrder, $totalDelta);
        if (! $question) {
            $this->error('Could not find a question with enough headroom for the adjustment.');
            $this->showQuestionHeadroom($session);

            return self::FAILURE;
        }

        $scoreRows = $session->scores
            ->where('question_id', $question->id)
            ->where('status', 'submitted')
            ->values();

        if ($scoreRows->isEmpty()) {
            $this->error('No submitted scores found for the selected question.');

            return self::FAILURE;
        }

        $adjustments = $scoreRows->map(function (InterviewScore $row) use ($question, $totalDelta) {
            $newScore = round((float) $row->score + $totalDelta, 2);

            return [
                'id' => $row->id,
                'panelist' => $row->panelist?->name ?? ('User #'.$row->panelist_user_id),
                'old' => (float) $row->score,
                'new' => $newScore,
                'valid' => $newScore >= 0 && $newScore <= (float) $question->max_mark,
            ];
        });

        if ($adjustments->contains(fn (array $row) => ! $row['valid'])) {
            $this->error("Adjusting Q{$question->sort_order} by {$totalDelta} would exceed 0–{$question->max_mark} for one or more panelists.");
            $this->table(['Panelist', 'Old', 'New', 'Valid'], $adjustments->map(fn (array $row) => [
                $row['panelist'],
                $row['old'],
                $row['new'],
                $row['valid'] ? 'yes' : 'no',
            ])->all());

            return self::FAILURE;
        }

        $this->table(
            ['Field', 'Current', 'Target'],
            [
                ['Session', $session->session_code, ''],
                ['Company', $session->company?->name ?? '—', ''],
                ['Question adjusted', 'Q'.$question->sort_order.' (max '.$question->max_mark.')', ''],
                ['Total score', $currentTotal.' / '.$maxPossible, $desiredTotal.' / '.$maxPossible],
                ['Percentage', $currentPercentage.'%', $desiredPercentage.'%'],
                ['Passed', $session->result->passed ? 'Yes' : 'No', ($desiredPercentage >= (float) $session->pass_mark) ? 'Yes' : 'No'],
                ['Decision status', InterviewCompanyReport::decisionLabel($session->result->decision_status), $preserveWorkflow ? 'unchanged' : 'pending'],
                ['Recommendation', InterviewCompanyReport::recommendationLabel($session->result->recommendation), $recommendation ? InterviewCompanyReport::recommendationLabel($recommendation) : 'from recalc'],
            ]
        );

        $this->table(['Panelist', 'Old score', 'New score'], $adjustments->map(fn (array $row) => [
            $row['panelist'],
            $row['old'],
            $row['new'],
        ])->all());

        if ($dryRun) {
            $this->info('[DRY RUN] No changes written.');

            return self::SUCCESS;
        }

        foreach ($adjustments as $row) {
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

    private function resolveQuestion(
        InterviewSession $session,
        mixed $questionSortOrder,
        float $totalDelta,
    ): ?InterviewQuestion {
        $questions = $session->questionSet->activeQuestions->sortBy('sort_order')->values();

        if ($questionSortOrder !== null && $questionSortOrder !== '') {
            $question = $questions->first(fn (InterviewQuestion $q) => (int) $q->sort_order === (int) $questionSortOrder);

            return $question && $this->questionHasHeadroom($session, $question, $totalDelta) ? $question : null;
        }

        return $questions
            ->reverse()
            ->first(fn (InterviewQuestion $question) => $this->questionHasHeadroom($session, $question, $totalDelta));
    }

    private function questionHasHeadroom(InterviewSession $session, InterviewQuestion $question, float $totalDelta): bool
    {
        $rows = $session->scores
            ->where('question_id', $question->id)
            ->where('status', 'submitted');

        if ($rows->isEmpty()) {
            return false;
        }

        foreach ($rows as $row) {
            $newScore = (float) $row->score + $totalDelta;
            if ($newScore < 0 || $newScore > (float) $question->max_mark) {
                return false;
            }
        }

        return true;
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

                return [
                    'Q'.$question->sort_order,
                    $question->max_mark,
                    $scores->isEmpty() ? '—' : number_format($scores->avg(), 2),
                    $scores->isEmpty() ? '—' : number_format((float) $question->max_mark - $scores->max(), 2),
                ];
            });

        $this->table(['Question', 'Max', 'Average', 'Headroom to max'], $rows->all());
    }
}
