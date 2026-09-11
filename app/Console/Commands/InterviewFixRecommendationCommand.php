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
                            {--remove-note= : Remove this exact text from chair notes}
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
        $removeNote = $this->option('remove-note');

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

        $recommendationChanging = $result->recommendation !== $recommendation;
        $notesChanging = is_string($removeNote) && trim($removeNote) !== '';

        if (! $recommendationChanging && ! $notesChanging) {
            $this->warn('Recommendation is already set to '.InterviewCompanyReport::recommendationLabel($recommendation).'. Nothing to do.');

            return self::SUCCESS;
        }

        if ($recommendationChanging && in_array($result->decision_status, ['approved', 'rejected'], true) && ! $force) {
            $this->error('Final decision is already '.$result->decision_status.'. Use --force only if you understand the workflow impact.');

            return self::FAILURE;
        }

        if ($recommendationChanging && $recommendation === 'do_not_recommend' && $result->passed) {
            $this->warn('This session is marked Pass but recommendation will be set to Do not recommend.');
        }

        if ($recommendationChanging && $recommendation === 'recommend' && ! $result->passed) {
            $this->warn('This session is marked Fail but recommendation will be set to Recommend approval.');
        }

        if ($recommendationChanging) {
            $this->info(($dryRun ? '[DRY RUN] ' : '').'Will change recommendation: '
                .InterviewCompanyReport::recommendationLabel($result->recommendation)
                .' → '
                .InterviewCompanyReport::recommendationLabel($recommendation));
        }

        if ($notesChanging) {
            $strippedNotes = $this->stripNoteText((string) $result->chair_notes, trim($removeNote));
            $this->line(($dryRun ? '[DRY RUN] ' : '').'Will remove note text from chair notes.');
            if ($dryRun) {
                $this->line('Chair notes after: '.($strippedNotes === '' ? '—' : $strippedNotes));
            }
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        $updates = [];
        $previousRecommendation = $result->recommendation;

        if ($recommendationChanging) {
            $updates['recommendation'] = $recommendation;
        }

        $chairNotes = (string) $result->chair_notes;

        if ($notesChanging) {
            $chairNotes = $this->stripNoteText($chairNotes, trim($removeNote));
        }

        if (is_string($note) && trim($note) !== '') {
            $append = trim($note);
            $chairNotes = trim($chairNotes) === ''
                ? $append
                : trim($chairNotes)."\n".$append;
        }

        if ($notesChanging || (is_string($note) && trim($note) !== '')) {
            $updates['chair_notes'] = trim($chairNotes) === '' ? null : trim($chairNotes);
        }

        if ($updates === []) {
            return self::SUCCESS;
        }

        $result->update($updates);

        InterviewAuditLogger::log(
            $notesChanging && ! $recommendationChanging ? 'chair_notes_corrected' : 'recommendation_corrected',
            $notesChanging && ! $recommendationChanging
                ? 'Chair notes corrected via artisan command'
                : 'Chair recommendation corrected via artisan command',
            null,
            $session->id,
            array_filter([
                'from' => $recommendationChanging ? $previousRecommendation : null,
                'to' => $recommendationChanging ? $recommendation : null,
                'removed_note' => $notesChanging ? trim($removeNote) : null,
                'decision_status' => $result->decision_status,
            ]),
        );

        $this->info('Interview review data updated for '.$session->session_code.'.');

        return self::SUCCESS;
    }

    private function stripNoteText(string $notes, string $removeText): string
    {
        $cleaned = str_replace($removeText, '', $notes);
        $cleaned = preg_replace("/\r\n|\r|\n/", "\n", $cleaned) ?? $cleaned;
        $cleaned = preg_replace("/\n{2,}/", "\n", $cleaned) ?? $cleaned;

        return trim($cleaned);
    }
}
