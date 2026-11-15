<?php

namespace App\Console\Commands;

use App\Services\LaunchReadinessService;
use Illuminate\Console\Command;

class LaunchReadinessCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'veggitable:launch-readiness';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit system environment, PHP runtime, database, security, and caching readiness for production SaaS launch';

    /**
     * Execute the console command.
     */
    public function handle(LaunchReadinessService $service): int
    {
        $this->info('====================================================================');
        $this->info('  VEGGITABLE SAAS: PRODUCTION LAUNCH READINESS AUDIT  ');
        $this->info('====================================================================');
        $this->newLine();

        $diagnostics = $service->runDiagnostics();
        $specs = $diagnostics['server_specs'];

        $this->line("PHP Version: <comment>{$specs['php_version']}</comment> | Laravel: <comment>{$specs['laravel_version']}</comment> | OS: <comment>{$specs['os']}</comment>");
        $this->line("Memory Limit: <comment>{$specs['memory_limit']}</comment> | Max Execution Time: <comment>{$specs['max_execution_time']}</comment>");
        $this->newLine();

        $tableRows = [];
        foreach ($diagnostics['checks'] as $key => $check) {
            $statusIcon = match ($check['status']) {
                'pass' => '<info>✓ PASS</info>',
                'warn' => '<comment>⚠ WARN</comment>',
                'fail' => '<error>✗ FAIL</error>',
            };

            $tableRows[] = [
                $check['name'],
                $statusIcon,
                $check['detail'],
            ];
        }

        $this->table(['Readiness Component', 'Status', 'Diagnostic Evaluation'], $tableRows);
        $this->newLine();

        $summary = $diagnostics['summary'];
        $this->info("Audit Summary: {$summary['passed']} passed, {$summary['warnings']} warnings, {$summary['failures']} failed. Score: {$summary['score_percent']}%");

        if ($diagnostics['status'] === 'fail') {
            $this->error('✗ CRITICAL ISSUES DETECTED: Application is NOT ready for production.');

            return Command::FAILURE;
        }

        if ($diagnostics['status'] === 'warn') {
            $this->warn('⚠ WARNINGS DETECTED: Application can operate but requires review of the warnings above.');

            return Command::SUCCESS;
        }

        $this->info('✓ SUCCESS: All system components are fully verified and ready for commercial SaaS launch!');

        return Command::SUCCESS;
    }
}
