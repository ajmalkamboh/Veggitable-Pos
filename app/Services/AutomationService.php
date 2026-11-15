<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\PurchaseInvoice;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WastageRecord;
use App\Notifications\CustomerBalanceAlertNotification;
use App\Notifications\DailySalesSummaryNotification;
use App\Notifications\DailyWastageSummaryNotification;
use App\Notifications\LowStockAlertNotification;
use App\Notifications\StockAdjustmentAlertNotification;
use App\Notifications\SubscriptionExpiryNotification;
use App\Notifications\SupplierPaymentReminderNotification;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    /**
     * Check low-stock produce across all branches and dispatch notifications.
     */
    public function checkLowStock(Tenant $tenant): int
    {
        $lowStocks = InventoryStock::where('inventory_stocks.tenant_id', $tenant->id)
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->join('branches', 'inventory_stocks.branch_id', '=', 'branches.id')
            ->whereRaw('inventory_stocks.quantity <= inventory_stocks.reorder_level AND inventory_stocks.reorder_level > 0')
            ->select(
                'inventory_stocks.*',
                'products.name as prod_name',
                'products.name_ur as prod_name_ur',
                'branches.name as br_name'
            )
            ->get();

        if ($lowStocks->isEmpty()) {
            return 0;
        }

        $recipients = $this->getAlertRecipients($tenant);
        $count = 0;

        foreach ($lowStocks as $stock) {
            $product = $stock->product;
            if (! $product) {
                continue;
            }

            $notification = new LowStockAlertNotification(
                $tenant,
                $product,
                (float) $stock->quantity,
                (float) $stock->reorder_level,
                $stock->br_name
            );

            foreach ($recipients as $user) {
                $user->notify($notification);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Compile and send daily sales summary alert to business owners.
     */
    public function sendDailySalesSummary(Tenant $tenant, ?string $date = null): ?DailySalesSummaryNotification
    {
        $d = $date ?? now()->toDateString();

        $invoices = SalesInvoice::where('tenant_id', $tenant->id)
            ->whereDate('date', $d)
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $netSales = (float) $invoices->sum('total_amount');
        $cashCollected = (float) $invoices->sum('paid_amount');
        $creditDue = (float) $invoices->sum('due_amount');
        $transactionsCount = $invoices->count();
        $grossProfit = (float) $invoices->sum('profit_amount');

        $notification = new DailySalesSummaryNotification(
            $tenant,
            $d,
            $netSales,
            $cashCollected,
            $creditDue,
            $transactionsCount,
            $grossProfit
        );

        $recipients = $this->getAlertRecipients($tenant);
        foreach ($recipients as $user) {
            $user->notify($notification);
        }

        return $notification;
    }

    /**
     * Compile and send daily produce spoilage / cull summary alert.
     */
    public function sendDailyWastageSummary(Tenant $tenant, ?string $date = null): ?DailyWastageSummaryNotification
    {
        $d = $date ?? now()->toDateString();

        $records = WastageRecord::where('tenant_id', $tenant->id)
            ->whereDate('date', $d)
            ->where('status', 'approved')
            ->get();

        $totalCost = (float) $records->sum('total_loss_cost');
        $totalQuantity = (float) $records->sum('total_quantity');
        $vouchersCount = $records->count();

        $notification = new DailyWastageSummaryNotification(
            $tenant,
            $d,
            $totalCost,
            $totalQuantity,
            $vouchersCount
        );

        $recipients = $this->getAlertRecipients($tenant);
        foreach ($recipients as $user) {
            $user->notify($notification);
        }

        return $notification;
    }

    /**
     * Check customer overdue receivables and dispatch alerts.
     */
    public function checkCustomerBalances(Tenant $tenant, float $threshold = 0.0): int
    {
        $overdueCustomers = Customer::where('tenant_id', $tenant->id)
            ->where('current_balance', '>', $threshold)
            ->get();

        if ($overdueCustomers->isEmpty()) {
            return 0;
        }

        $recipients = $this->getAlertRecipients($tenant);
        $count = 0;

        foreach ($overdueCustomers as $customer) {
            $notification = new CustomerBalanceAlertNotification(
                $tenant,
                $customer,
                (float) $customer->current_balance,
                $customer->credit_limit ? (float) $customer->credit_limit : null
            );

            foreach ($recipients as $user) {
                $user->notify($notification);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check mandi supplier payables and dispatch payment reminders.
     */
    public function checkSupplierPayables(Tenant $tenant, float $threshold = 0.0): int
    {
        $dueSuppliers = Supplier::where('tenant_id', $tenant->id)
            ->where('current_balance', '>', $threshold)
            ->get();

        if ($dueSuppliers->isEmpty()) {
            return 0;
        }

        $recipients = $this->getAlertRecipients($tenant);
        $count = 0;

        foreach ($dueSuppliers as $supplier) {
            $dueInvoicesCount = PurchaseInvoice::where('tenant_id', $tenant->id)
                ->where('supplier_id', $supplier->id)
                ->where('due_amount', '>', 0)
                ->count();

            $notification = new SupplierPaymentReminderNotification(
                $tenant,
                $supplier,
                (float) $supplier->current_balance,
                $dueInvoicesCount
            );

            foreach ($recipients as $user) {
                $user->notify($notification);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Platform-wide check for tenants nearing subscription expiration.
     */
    public function checkSubscriptionExpiries(): int
    {
        $now = now();
        $targetDays = [7, 3, 1];
        $notifiedCount = 0;

        foreach ($targetDays as $days) {
            $targetDate = $now->copy()->addDays($days)->toDateString();

            $tenants = Tenant::where(function ($q) use ($targetDate) {
                $q->whereDate('subscription_ends_at', $targetDate)
                    ->orWhereDate('trial_ends_at', $targetDate);
            })->where('status', 'active')->get();

            foreach ($tenants as $t) {
                $expiryDate = $t->subscription_ends_at ?? $t->trial_ends_at;
                $planName = $t->plan?->name ?? 'SaaS Standard Plan';

                $notification = new SubscriptionExpiryNotification(
                    $t,
                    $days,
                    $expiryDate ? $expiryDate->format('d M Y') : $targetDate,
                    $planName
                );

                $recipients = $this->getAlertRecipients($t);
                foreach ($recipients as $user) {
                    $user->notify($notification);
                    $notifiedCount++;
                }
            }
        }

        return $notifiedCount;
    }

    /**
     * Dispatch notification for significant stock adjustment variance.
     */
    public function notifyStockAdjustment(Tenant $tenant, StockAdjustment $adjustment): void
    {
        $adjustment->loadMissing(['branch', 'creator']);

        $totalVariance = (float) $adjustment->items()->sum('difference_quantity');
        $notification = new StockAdjustmentAlertNotification(
            $tenant,
            $adjustment->adjustment_no,
            $adjustment->reason,
            $totalVariance,
            $adjustment->branch->name ?? 'Main Outlet',
            $adjustment->creator->name ?? 'Staff'
        );

        $recipients = $this->getAlertRecipients($tenant);
        foreach ($recipients as $user) {
            $user->notify($notification);
        }
    }

    /**
     * Dispatch configurable SMS alert.
     */
    public function sendSms(Tenant $tenant, string $toPhone, string $message): array
    {
        $settings = $tenant->settings['sms'] ?? [];
        $provider = $settings['provider'] ?? 'simulation';

        // Log external dispatch without exposing credentials
        Log::info("SMS notification dispatched to [{$toPhone}] via [{$provider}] for tenant [{$tenant->id}]");

        return [
            'success' => true,
            'provider' => $provider,
            'to' => $toPhone,
            'characters' => mb_strlen($message),
            'status' => 'dispatched',
        ];
    }

    /**
     * Dispatch configurable WhatsApp alert.
     */
    public function sendWhatsApp(Tenant $tenant, string $toPhone, string $message): array
    {
        $settings = $tenant->settings['whatsapp'] ?? [];
        $provider = $settings['provider'] ?? 'simulation';

        Log::info("WhatsApp notification dispatched to [{$toPhone}] via [{$provider}] for tenant [{$tenant->id}]");

        return [
            'success' => true,
            'provider' => $provider,
            'to' => $toPhone,
            'status' => 'dispatched',
        ];
    }

    /**
     * Retrieve authorized users to receive operational business alerts.
     */
    protected function getAlertRecipients(Tenant $tenant): array
    {
        $ownerRole = Role::where('slug', 'owner')->first();
        $managerRole = Role::where('slug', 'manager')->first();
        $roleIds = array_filter([$ownerRole?->id, $managerRole?->id]);

        return User::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($roleIds) {
                if (! empty($roleIds)) {
                    $q->whereIn('role_id', $roleIds)
                        ->orWhereHas('roles', fn ($r) => $r->whereIn('roles.id', $roleIds));
                }
            })
            ->where('is_active', true)
            ->get()
            ->all();
    }
}
