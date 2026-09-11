<?php

namespace App\Console\Commands;

use App\Models\InterviewSession;
use App\Support\Interview\InterviewAuditLogger;
use App\Support\Interview\InterviewCompanyReport;
use Illuminate\Console\Command;

class InterviewFixRecommendationCommand extends Command
{
    protected $signature = 'interview:fix-recommendation
                            {session : Interview session code, e.g. INT-20260910-NNVF}
                            {recommendation : recommend, do_not_recommend, or conditional}
                            {--note= : Optional note appended to chair notes}
                            {--force : Allow change after final approval or rejection}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Correct a chair recommendation on a single interview session.';

    /** @var list<string> */
    private const ALLOWED = ['recommend', 'do_not_recommend', 'conditional'];

    public function handle(): int
    {
        $sessionCode = trim($this->argument('session'));
        $recommendation = trim($this->argument('recommendation'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $note = $this->option('note');

        if (! in_array($recommendation, self::ALLOWED, true)) {
            $this->error('Recommendation must be one of: '.implode(', ', self::ALLOWED));

            return self::FAILURE;
        }

        $session = InterviewSession::query()
            ->with(['company', 'result'])
            ->where('session_code', $sessionCode)
            ->first();

        if (! $session) {
            $this->error("Session not found: {$sessionCode}");

            return self::FAILURE;
        }

        $result = $session->result;
        if (! $result) {
            $this->error('This session has no consolidated result yet.');

            return self::FAILURE;
        }

        $this->table(
            ['Field', 'Current value'],
            [
                ['Session', $session->session_code],
                ['Company', $session->company?->name ?? '—'],
                ['Outcome', $result->passed ? 'Pass' : 'Fail'],
                ['Percentage', $result->percentage.'%'],
                ['Recommendation', InterviewCompanyReport::recommendationLabel($result->recommendation)],
                ['Decision status', InterviewCompanyReport::decisionLabel($result->decision_status)],
            ]
        );

        if ($result->recommendation === $recommendation) {
            $this->warn('Recommendation is already set to '.InterviewCompanyReport::recommendationLabel($recommendation).'. Nothing to do.');

            return self::SUCCESS;
        }

        if (in_array($result->decision_status, ['approved', 'rejected'], true) && ! $force) {
            $this->error('Final decision is already '.$result->decision_status.'. Use --force only if you understand the workflow impact.');

            return self::FAILURE;
        }

        if ($recommendation === 'do_not_recommend' && $result->passed) {
            $this->warn('This session is marked Pass but recommendation will be set to Do not recommend.');
        }

        if ($recommendation === 'recommend' && ! $result->passed) {
            $this->warn('This session is marked Fail but recommendation will be set to Recommend approval.');
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Will change recommendation: '
            .InterviewCompanyReport::recommendationLabel($result->recommendation)
            .' → '
            .InterviewCompanyReport::recommendationLabel($recommendation));

        if ($dryRun) {
            return self::SUCCESS;
        }

        $updates = ['recommendation' => $recommendation];

        if (is_string($note) && trim($note) !== '') {
            $existingNotes = trim((string) $result->chair_notes);
            $append = trim($note);
            $updates['chair_notes'] = $existingNotes === ''
                ? $append
                : $existingNotes."\n".$append;
        }

        $previousRecommendation = $result->recommendation;

        $result->update($updates);

        InterviewAuditLogger::log(
            'recommendation_corrected',
            'Chair recommendation corrected via artisan command',
            null,
            $session->id,
            [
                'from' => $previousRecommendation,
                'to' => $recommendation,
                'decision_status' => $result->decision_status,
            ]
        );

        $this->info('Recommendation updated for '.$session->session_code.'.');

        return self::SUCCESS;
    }
}
