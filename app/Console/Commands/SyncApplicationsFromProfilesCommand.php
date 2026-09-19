<?php

namespace App\Console\Commands;

use App\Models\TrainingApplication;
use App\Support\TraineeProfileUpdater;
use Illuminate\Console\Command;

class SyncApplicationsFromProfilesCommand extends Command
{
    protected $signature = 'applications:sync-from-profiles
                            {--dry-run : List applications that differ from the trainee profile without updating}
                            {--user= : Only applications for this trainee user ID}
                            {--application= : Only this training application ID}
                            {--include-rejected : Also sync applications with rejected review status}';

    protected $description = 'Copy current trainee profile personal fields onto linked training applications (one-time fix).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $userId = $this->option('user');
        $applicationId = $this->option('application');
        $includeRejected = (bool) $this->option('include-rejected');

        $query = TrainingApplication::query()
            ->whereNotNull('user_id')
            ->whereHas('user', fn ($q) => $q->where('role', 'trainee'))
            ->with('user')
            ->orderBy('id');

        if (! $includeRejected) {
            $query->where('application_review_status', '!=', 'rejected');
        }

        if ($userId !== null && $userId !== '') {
            $query->where('user_id', (int) $userId);
        }

        if ($applicationId !== null && $applicationId !== '') {
            $query->whereKey((int) $applicationId);
        }

        $applications = $query->get();

        if ($applications->isEmpty()) {
            $this->warn('No matching applications found.');

            return self::SUCCESS;
        }

        $mismatchCount = 0;
        $updatedCount = 0;

        foreach ($applications as $application) {
            $user = $application->user;
            if (! $user) {
                continue;
            }

            $snapshot = TraineeProfileUpdater::personalSnapshotFromUser($user);

            if (TraineeProfileUpdater::applicationMatchesPersonalSnapshot($application, $snapshot)) {
                continue;
            }

            $mismatchCount++;

            $label = sprintf(
                'Application #%d (%s) — user #%d %s',
                $application->id,
                $application->registration_number ?? $application->control_number ?? 'no reg',
                $user->id,
                trim($user->name),
            );

            if ($dryRun) {
                $this->line($label);

                continue;
            }

            TraineeProfileUpdater::syncTrainingApplicationFromUser($application, $user);
            $updatedCount++;
            $this->line('Updated: '.$label);
        }

        if ($dryRun) {
            $this->info("Dry run: {$mismatchCount} application(s) would be updated out of {$applications->count()} checked.");

            return self::SUCCESS;
        }

        $skipped = $applications->count() - $updatedCount;
        $this->info("Updated {$updatedCount} application(s). Skipped {$skipped} already in sync.");

        return self::SUCCESS;
    }
}
