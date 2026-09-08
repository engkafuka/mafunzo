<?php

namespace App\Console\Commands;

use App\Models\InterviewResult;
use App\Models\InterviewSession;
use App\Support\Interview\InterviewAuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InterviewSetPassMarkCommand extends Command
{
    protected $signature = 'interview:set-pass-mark
                            {pass_mark=60 : Pass mark percentage to apply}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Set interview session pass marks and recalculate result pass/fail flags (for live data correction).';

    public function handle(): int
    {
        $passMark = round((float) $this->argument('pass_mark'), 2);
        $dryRun = (bool) $this->option('dry-run');

        if ($passMark < 0 || $passMark > 100) {
            $this->error('Pass mark must be between 0 and 100.');

            return self::FAILURE;
        }

        $sessionsToUpdate = InterviewSession::query()
            ->where(function ($q) use ($passMark) {
                $q->whereNull('pass_mark')
                    ->orWhere('pass_mark', '!=', $passMark);
            })
            ->count();

        $results = InterviewResult::query()
            ->whereNotNull('percentage')
            ->with('session:id,session_code,pass_mark')
            ->get();

        $wouldFlip = $results->filter(function (InterviewResult $result) use ($passMark) {
            $shouldPass = (float) $result->percentage >= $passMark;

            return (bool) $result->passed !== $shouldPass;
        });

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Target pass mark: {$passMark}%");
        $this->line("Sessions needing pass_mark update: {$sessionsToUpdate}");
        $this->line('Results that would change Pass/Fail: '.$wouldFlip->count());

        if ($wouldFlip->isNotEmpty()) {
            $this->table(
                ['Session', 'Percentage', 'Old', 'New', 'Decision'],
                $wouldFlip->take(30)->map(fn (InterviewResult $r) => [
                    $r->session?->session_code ?? '#'.$r->session_id,
                    number_format((float) $r->percentage, 2).'%',
                    $r->passed ? 'Pass' : 'Fail',
                    ((float) $r->percentage >= $passMark) ? 'Pass' : 'Fail',
                    $r->decision_status,
                ])->all()
            );

            if ($wouldFlip->count() > 30) {
                $this->line('… and '.($wouldFlip->count() - 30).' more');
            }
        }

        if ($dryRun) {
            $this->comment('No changes written. Re-run without --dry-run to apply.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Apply pass mark {$passMark}% to all interview sessions and recalculate results?", true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($passMark, $results) {
            InterviewSession::query()->update(['pass_mark' => $passMark]);

            foreach ($results as $result) {
                $shouldPass = (float) $result->percentage >= $passMark;
                $updates = [
                    'passed' => $shouldPass,
                ];

                // Only auto-adjust recommendation while still pending chair review.
                if ($result->decision_status === 'pending') {
                    $updates['recommendation'] = $shouldPass ? 'recommend' : 'do_not_recommend';
                }

                $result->update($updates);
            }
        });

        InterviewAuditLogger::log(
            'pass_mark_bulk_updated',
            "Interview pass mark set to {$passMark}% for all sessions; results recalculated",
            null,
            null,
            [
                'pass_mark' => $passMark,
                'sessions_updated' => InterviewSession::query()->count(),
                'results_recalculated' => $results->count(),
                'pass_fail_flips' => $wouldFlip->count(),
            ]
        );

        $this->info('Done. All session pass marks are now '.$passMark.'%. Results recalculated.');
        $this->comment('New sessions will also default to '.config('interview.default_pass_mark').'% from config.');

        return self::SUCCESS;
    }
}
