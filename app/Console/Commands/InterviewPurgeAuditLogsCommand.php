<?php

namespace App\Console\Commands;

use App\Models\InterviewAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class InterviewPurgeAuditLogsCommand extends Command
{
    protected $signature = 'interview:purge-audit-logs
                            {--from= : Start datetime (Y-m-d H:i), app timezone}
                            {--to= : End datetime (Y-m-d H:i), app timezone}
                            {--role= : Only logs by users with this role (e.g. super_admin)}
                            {--dry-run : List matching rows without deleting}';

    protected $description = 'Delete interview audit extract rows in a datetime range.';

    public function handle(): int
    {
        $fromInput = $this->option('from');
        $toInput = $this->option('to');
        $role = $this->option('role');
        $dryRun = (bool) $this->option('dry-run');

        if (! is_string($fromInput) || trim($fromInput) === '' || ! is_string($toInput) || trim($toInput) === '') {
            $this->error('Both --from and --to are required (format: Y-m-d H:i).');

            return self::FAILURE;
        }

        $timezone = config('app.timezone', 'UTC');

        try {
            $from = Carbon::parse(trim($fromInput), $timezone)->startOfMinute();
            $to = Carbon::parse(trim($toInput), $timezone)->endOfMinute();
        } catch (\Throwable $e) {
            $this->error('Invalid datetime: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($to->lessThan($from)) {
            $this->error('--to must be after --from.');

            return self::FAILURE;
        }

        $query = InterviewAuditLog::query()
            ->with(['user:id,name,email,role', 'session:id,session_code'])
            ->whereBetween('created_at', [$from, $to]);

        if (is_string($role) && trim($role) !== '') {
            $roleFilter = trim($role);
            $query->whereHas('user', fn ($userQuery) => $userQuery->where('role', $roleFilter));
        }

        $rows = $query->orderBy('created_at')->get();

        $this->line('Timezone: '.$timezone);
        $this->line('Range: '.$from->format('Y-m-d H:i:s').' → '.$to->format('Y-m-d H:i:s'));
        if (is_string($role) && trim($role) !== '') {
            $this->line('User role filter: '.trim($role));
        }
        $this->line('Matching rows: '.$rows->count());

        if ($rows->isEmpty()) {
            $this->warn('No audit log rows matched.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Timestamp', 'Action', 'User', 'Session', 'Description'],
            $rows->map(fn (InterviewAuditLog $log) => [
                $log->id,
                $log->created_at?->format('Y-m-d H:i:s'),
                $log->action,
                $log->user ? $log->user->name.' ('.$log->user->role.')' : '—',
                $log->session?->session_code ?? '—',
                \Illuminate\Support\Str::limit($log->description, 60),
            ])->all()
        );

        if ($dryRun) {
            $this->info('[DRY RUN] No rows deleted.');

            return self::SUCCESS;
        }

        $deleted = InterviewAuditLog::query()
            ->whereIn('id', $rows->pluck('id'))
            ->delete();

        $this->info("Deleted {$deleted} audit log row(s).");

        return self::SUCCESS;
    }
}
