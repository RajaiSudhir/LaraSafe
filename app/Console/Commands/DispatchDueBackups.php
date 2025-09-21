<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Backup;
use App\Jobs\BackupProjectJob;

class DispatchDueBackups extends Command
{
    protected $signature = 'backups:dispatch-due';
    protected $description = 'Dispatch backups for projects whose backup date is due';

    public function handle(): int
    {
        $today = Carbon::today();
        $backups = Backup::where('backup_frequency', '!=', null)->whereDate('next_backup_date', '<=', $today)->get();

        foreach ($backups as $backup) {
            BackupProjectJob::dispatch($backup);

            // calculate the next date
            $next = match ($backup->backup_frequency) {
                'daily' => $today->copy()->addDay(),
                'weekly' => $today->copy()->addWeek(),
                'monthly' => $today->copy()->addMonth(),
                default => $today->copy()->addDay(),
            };

            $backup->update(['next_backup_date' => $next]);

            $this->info("Backup job dispatched for {$backup->project->name}");
        }

        return Command::SUCCESS;
    }
}
