<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\BackupService;
use Illuminate\Console\Command;

class CreateDatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'veggitable:backup 
                            {--tenant= : Optional tenant slug for isolated tenant data export}
                            {--no-compress : Do not gzip compress backup archive}
                            {--clean : Clean backups older than retention policy after backup}
                            {--keep=30 : Number of days to retain backups when cleaning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a full platform database backup or tenant-specific data export with SHA-256 integrity verification';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $tenantSlug = $this->option('tenant');
        $compress = ! $this->option('no-compress');
        $clean = $this->option('clean');
        $keepDays = (int) $this->option('keep');

        $this->info('Starting Veggitable SaaS Backup Generation...');

        try {
            if ($tenantSlug) {
                $tenant = Tenant::where('slug', $tenantSlug)->first();
                if (! $tenant) {
                    $this->error("Tenant with slug [{$tenantSlug}] not found.");

                    return Command::FAILURE;
                }

                $this->line("Exporting data for tenant: <comment>{$tenant->name}</comment> ({$tenant->slug})...");
                $result = $backupService->exportTenantData($tenant, $compress);
            } else {
                $this->line('Generating full platform database snapshot...');
                $result = $backupService->createBackup(null, $compress);
            }

            $this->newLine();
            $this->info('✓ Backup created successfully!');
            $this->table(
                ['Property', 'Value'],
                [
                    ['File Name', $result['filename']],
                    ['File Path', $result['path']],
                    ['Size', $result['formatted_size']],
                    ['Type', $result['type']],
                    ['Compressed', $result['compressed'] ? 'Yes (gzip)' : 'No (raw)'],
                    ['SHA-256 Checksum', $result['sha256']],
                    ['Created At', $result['created_at']],
                ]
            );

            if ($clean) {
                $this->line("Purging backups older than {$keepDays} days...");
                $purged = $backupService->cleanOldBackups($keepDays);
                $this->info("Cleaned {$purged} expired backup archive(s).");
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backup generation failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
