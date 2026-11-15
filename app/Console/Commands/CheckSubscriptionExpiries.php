<?php

namespace App\Console\Commands;

use App\Services\AutomationService;
use Illuminate\Console\Command;

class CheckSubscriptionExpiries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'veggitable:check-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active tenants for upcoming subscription renewals and trial expirations';

    /**
     * Execute the console command.
     */
    public function handle(AutomationService $automationService): int
    {
        $this->info('Scanning SaaS tenants for subscription renewals and expirations...');

        $notified = $automationService->checkSubscriptionExpiries();

        $this->info("Subscription expiry check completed. Total alerts sent: {$notified}");

        return Command::SUCCESS;
    }
}
