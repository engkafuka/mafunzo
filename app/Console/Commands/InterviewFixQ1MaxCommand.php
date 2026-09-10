<?php

namespace App\Console\Commands;

use App\Models\InterviewQuestion;
use App\Models\InterviewScore;
use App\Models\InterviewSession;
use App\Support\Interview\InterviewAuditLogger;
use App\Support\Interview\InterviewScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InterviewFixQ1MaxCommand extends Command
{
    protected $signature = 'interview:fix-q1-max
                            {--date=2026-09-07 : Interview date (Y-m-d) for affected sessions}
                            {--session= : Optional single session code instead of date filter}
                            {--from=90 : Current incorrect Q1 max mark}
                            {--to=20 : Correct Q1 max mark}
                            {--sort-order=1 : Question sort order to fix (default Q1)}
                            {--dry-run : Show changes without writing}';

    protected $description = 'Fix Q1 max mark, scale stored scores proportionally, and recalculate interview results.';

    public function handle(InterviewScoringService $scoringService): int
    {
        $fromMax = (int) $this->option('from');
        $toMax = (int) $this->option('to');
        $sortOrder = (int) $this->option('sort-order');
        $dryRun = (bool) $this->option('dry-run');

        if ($fromMax <= 0 || $toMax <= 0 || $fromMax === $toMax) {
            $this->error('Invalid --from / --to values.');

            return self::FAILURE;
        }

        $scaleFactor = $toMax / $fromMax;

        $sessions = $this->resolveSessions();
        if ($sessions->isEmpty()) {
            $this->warn('No matching interview sessions found.');

            return self::SUCCESS;
        }

        $questionIds = $this->resolveQuestionIds($sessions, $sortOrder, $fromMax);
        if ($questionIds->isEmpty()) {
            $this->error("No Q{$sortOrder} questions with max_mark={$fromMax} found for these sessions.");

            return self::FAILURE;
        }

        $scoreQuery = InterviewScore::query()
            ->whereIn('question_id', $questionIds)
            ->whereIn('session_id', $sessions->pluck('id'))
            ->whereNotNull('score');

        $scoreCount = (clone $scoreQuery)->count();

        $flips = $this->predictPassFailFlips($sessions, $questionIds, $scaleFactor);

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Scale Q{$sortOrder} scores × {$toMax}/{$fromMax} (".round($scaleFactor, 4).')');
        $this->line('Sessions: '.$sessions->count());
        $this->line('Question IDs: '.$questionIds->implode(', '));
        $this->line('Scores to scale: '.$scoreCount);
        $this->line('Pass/Fail flips after recalc: '.$flips->count());

        $this->table(
            ['Session', 'Company', 'Status', 'Decision', 'Old %', 'New %', 'Old', 'New'],
            $flips->take(30)->map(fn (array $row) => [
                $row['session_code'],
                $row['company'],
                $row['status'],
                $row['decision_status'],
                $row['old_percentage'],
                $row['new_percentage'],
                $row['old_passed'] ? 'Pass' : 'Fail',
                $row['new_passed'] ? 'Pass' : 'Fail',
            ])->all()
        );

        if ($flips->count() > 30) {
            $this->line('… and '.($flips->count() - 30).' more');
        }

        if ($dryRun) {
            $this->comment('No changes written. Re-run without --dry-run to apply.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Apply Q1 max correction and recalculate all listed sessions?', true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($sessions, $questionIds, $fromMax, $toMax, $scaleFactor, $scoringService, $scoreQuery) {
            InterviewQuestion::query()
                ->whereIn('id', $questionIds)
                ->where('max_mark', $fromMax)
                ->update(['max_mark' => $toMax]);

            foreach ((clone $scoreQuery)->cursor() as $score) {
                $score->update([
                    'score' => round((float) $score->score * $scaleFactor, 2),
                ]);
            }

            foreach ($sessions as $session) {
                if ($session->allPanelistsSubmitted()) {
                    $scoringService->consolidateResults($session, preserveWorkflow: true);
                }
            }
        });

        InterviewAuditLogger::log(
            'q1_max_corrected',
            "Q{$sortOrder} max mark corrected from {$fromMax} to {$toMax}; scores scaled and results recalculated",
            null,
            null,
            [
                'date' => $this->option('date'),
                'session' => $this->option('session'),
                'from_max' => $fromMax,
                'to_max' => $toMax,
                'scale_factor' => $scaleFactor,
                'question_ids' => $questionIds->values()->all(),
                'sessions' => $sessions->pluck('session_code')->all(),
                'scores_scaled' => $scoreCount,
                'pass_fail_flips' => $flips->count(),
            ]
        );

        $this->info('Done. Q1 max corrected, scores scaled, results recalculated.');

        if ($flips->where(fn ($f) => $f['decision_status'] !== 'pending')->isNotEmpty()) {
            $this->warn('Some sessions with confirmed/approved decisions had pass/fail changes — review those manually.');
        }

        return self::SUCCESS;
    }

    /** @return Collection<int, InterviewSession> */
    private function resolveSessions(): Collection
    {
        $query = InterviewSession::query()
            ->with(['company:id,name', 'questionSet.questions', 'result'])
            ->where('status', '!=', InterviewSession::STATUS_CANCELLED);

        if ($code = $this->option('session')) {
            $query->where('session_code', $code);
        } else {
            $query->whereDate('interview_date', $this->option('date'));
        }

        return $query->orderBy('session_code')->get();
    }

    /** @return Collection<int, int> */
    private function resolveQuestionIds(Collection $sessions, int $sortOrder, int $fromMax): Collection
    {
        return $sessions
            ->map(fn (InterviewSession $session) => $session->questionSet?->questions
                ->first(fn ($q) => (int) $q->sort_order === $sortOrder && (int) $q->max_mark === $fromMax)
                ?->id)
            ->filter()
            ->unique()
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function predictPassFailFlips(
        Collection $sessions,
        Collection $questionIds,
        float $scaleFactor
    ): Collection {
        $flips = collect();

        foreach ($sessions as $session) {
            if (! $session->result) {
                continue;
            }

            $newPercentage = $this->simulatePercentage($session, $questionIds, $scaleFactor, $toMax = (int) $this->option('to'), $fromMax = (int) $this->option('from'));
            $oldPassed = (bool) $session->result->passed;
            $newPassed = $newPercentage >= (float) $session->pass_mark;

            if ($oldPassed === $newPassed) {
                continue;
            }

            $flips->push([
                'session_code' => $session->session_code,
                'company' => $session->company?->name ?? '—',
                'status' => $session->status,
                'decision_status' => $session->result->decision_status,
                'old_percentage' => number_format((float) $session->result->percentage, 2).'%',
                'new_percentage' => number_format($newPercentage, 2).'%',
                'old_passed' => $oldPassed,
                'new_passed' => $newPassed,
            ]);
        }

        return $flips;
    }

    /** @param Collection<int, int> $questionIds */
    private function simulatePercentage(
        InterviewSession $session,
        Collection $questionIds,
        float $scaleFactor,
        int $toMax,
        int $fromMax
    ): float {
        $session->loadMissing(['questionSet.activeQuestions', 'scores']);

        $totalAverage = 0.0;

        foreach ($session->questionSet->activeQuestions as $question) {
            $submitted = $session->scores
                ->where('question_id', $question->id)
                ->where('status', 'submitted');

            $values = $submitted->pluck('score')->map(function ($score) use ($question, $questionIds, $scaleFactor) {
                $value = (float) $score;

                if ($questionIds->contains($question->id)) {
                    $value = round($value * $scaleFactor, 2);
                }

                return $value;
            });

            $totalAverage += $values->isEmpty() ? 0.0 : round($values->avg(), 2);
        }

        $maxPossible = (float) $session->questionSet->activeQuestions->sum(
            fn ($q) => $questionIds->contains($q->id) ? $toMax : (int) $q->max_mark
        );

        return $maxPossible > 0
            ? round(($totalAverage / $maxPossible) * 100, 2)
            : 0.0;
    }
}
