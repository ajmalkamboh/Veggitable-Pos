<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CleanOldBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'veggitable:clean-backups {--days=30 : Purge backups older than this number of days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge outdated database backup archives according to retention policy';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $days = (int) $this->option('days');
        $this->info("Purging database backups older than {$days} days...");

        $count = $backupService->cleanOldBackups($days);
        $this->info("Done! Purged {$count} expired backup archive(s).");

        return Command::SUCCESS;
    }
}
