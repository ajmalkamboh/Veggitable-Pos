<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\AutomationService;
use Illuminate\Console\Command;

class SendDailyBusinessSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'veggitable:daily-summaries {--tenant= : Specific tenant ID to process} {--date= : Specific date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile and dispatch daily sales and wastage summaries for active SaaS vegetable marts';

    /**
     * Execute the console command.
     */
    public function handle(AutomationService $automationService): int
    {
        $tenantId = $this->option('tenant');
        $date = $this->option('date') ?? now()->toDateString();

        $query = Tenant::where('status', 'active');
        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $tenants = $query->get();
        $this->info("Processing daily summaries for {$tenants->count()} active tenants on date: {$date}");

        foreach ($tenants as $tenant) {
            $this->line("Processing tenant: {$tenant->name} (ID: {$tenant->id})...");

            // 1. Daily sales summary
            $salesNotif = $automationService->sendDailySalesSummary($tenant, $date);
            $this->line(' - Sales summary sent: Net Sales Rs. '.number_format($salesNotif->netSales, 2));

            // 2. Daily wastage summary
            $wastageNotif = $automationService->sendDailyWastageSummary($tenant, $date);
            $this->line(' - Wastage summary sent: Loss Rs. '.number_format($wastageNotif->totalLossCost, 2));

            // 3. Low stock check
            $lowStockCount = $automationService->checkLowStock($tenant);
            $this->line(" - Low-stock alerts dispatched: {$lowStockCount}");
        }

        $this->info('Daily business summaries and low stock scans finished successfully.');

        return Command::SUCCESS;
    }
}
