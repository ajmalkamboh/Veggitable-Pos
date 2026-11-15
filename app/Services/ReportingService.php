<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\InventoryStock;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Tenant;
use App\Models\WastageRecord;

class ReportingService
{
    /**
     * Compile real-time executive dashboard KPIs and visual analytics.
     */
    public function getDashboardMetrics(Tenant $tenant, ?int $branchId = null): array
    {
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();

        // 1. Sales metrics
        $salesQuery = SalesInvoice::where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['cancelled']);
        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        }

        $todaySales = (clone $salesQuery)->whereDate('date', $today)->sum('total_amount');
        $todayTransactionsCount = (clone $salesQuery)->whereDate('date', $today)->count();
        $todayCashCollected = (clone $salesQuery)->whereDate('date', $today)->sum('paid_amount');
        $todayCreditDue = (clone $salesQuery)->whereDate('date', $today)->selectRaw('SUM(total_amount - paid_amount) as due')->value('due') ?? 0.0;
        $monthSales = (clone $salesQuery)->whereDate('date', '>=', $startOfMonth)->sum('total_amount');
        $monthGrossProfit = (clone $salesQuery)->whereDate('date', '>=', $startOfMonth)->selectRaw('SUM(total_amount - cost_amount) as gp')->value('gp') ?? 0.0;

        // 2. Mandi Procurement & Purchases
        $purchaseQuery = PurchaseInvoice::where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['cancelled']);
        if ($branchId) {
            $purchaseQuery->where('branch_id', $branchId);
        }

        $todayPurchases = (clone $purchaseQuery)->whereDate('date', $today)->sum('total_amount');
        $monthPurchases = (clone $purchaseQuery)->whereDate('date', '>=', $startOfMonth)->sum('total_amount');

        // 3. Inventory Valuation & Low Stock
        $stockQuery = InventoryStock::where('inventory_stocks.tenant_id', $tenant->id)
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id');
        if ($branchId) {
            $stockQuery->where('inventory_stocks.branch_id', $branchId);
        }

        $inventoryValuation = (float) (clone $stockQuery)
            ->selectRaw('SUM(inventory_stocks.quantity * COALESCE(NULLIF(inventory_stocks.cost_price, 0), products.cost_price, 0)) as val')
            ->value('val') ?? 0.0;

        $lowStockItems = (clone $stockQuery)
            ->whereRaw('inventory_stocks.quantity <= inventory_stocks.reorder_level AND inventory_stocks.reorder_level > 0')
            ->select('products.name', 'products.code', 'inventory_stocks.quantity', 'inventory_stocks.reorder_level')
            ->limit(5)
            ->get();

        $lowStockCount = (clone $stockQuery)
            ->whereRaw('inventory_stocks.quantity <= inventory_stocks.reorder_level AND inventory_stocks.reorder_level > 0')
            ->count();

        // 4. Spoilage & Wastage Loss
        $wastageQuery = WastageRecord::where('tenant_id', $tenant->id)->where('status', 'approved');
        if ($branchId) {
            $wastageQuery->where('branch_id', $branchId);
        }

        $todayWastageLoss = (clone $wastageQuery)->whereDate('date', $today)->sum('total_loss_cost');
        $todayWastageQty = (clone $wastageQuery)->whereDate('date', $today)->sum('total_quantity');
        $monthWastageLoss = (clone $wastageQuery)->whereDate('date', '>=', $startOfMonth)->sum('total_loss_cost');

        // 5. Operating Expenses
        $expenseQuery = Expense::where('tenant_id', $tenant->id)->where('status', 'approved');
        if ($branchId) {
            $expenseQuery->where('branch_id', $branchId);
        }

        $todayExpenses = (clone $expenseQuery)->whereDate('date', $today)->sum('amount');
        $monthExpenses = (clone $expenseQuery)->whereDate('date', '>=', $startOfMonth)->sum('amount');

        // 6. Cash Drawers & Bank Reserves
        $accountQuery = FinancialAccount::where('tenant_id', $tenant->id)->where('is_active', true);
        if ($branchId) {
            $accountQuery->where('branch_id', $branchId);
        }
        $cashAndBankReserves = $accountQuery->sum('current_balance');

        // 7. Recent Transactions
        $recentSales = (clone $salesQuery)->with(['customer', 'branch'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        $recentPurchases = (clone $purchaseQuery)->with(['supplier', 'branch'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        // 8. 7-Day Sales & Purchase Trend
        $trend7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $label = now()->subDays($i)->format('D, d M');

            $s = SalesInvoice::where('tenant_id', $tenant->id)
                ->whereNotIn('status', ['cancelled'])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('date', $d)
                ->sum('total_amount');

            $p = PurchaseInvoice::where('tenant_id', $tenant->id)
                ->whereNotIn('status', ['cancelled'])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('date', $d)
                ->sum('total_amount');

            $trend7Days[] = [
                'date' => $d,
                'label' => $label,
                'sales' => (float) $s,
                'purchases' => (float) $p,
            ];
        }

        // 9. Top-Selling Produce (last 30 days)
        $topProduce = SalesInvoiceItem::where('sales_invoice_items.tenant_id', $tenant->id)
            ->join('products', 'sales_invoice_items.product_id', '=', 'products.id')
            ->join('sales_invoices', 'sales_invoice_items.sales_invoice_id', '=', 'sales_invoices.id')
            ->whereNotIn('sales_invoices.status', ['cancelled'])
            ->whereDate('sales_invoices.date', '>=', now()->subDays(30)->toDateString())
            ->when($branchId, fn ($q) => $q->where('sales_invoices.branch_id', $branchId))
            ->groupBy('products.id', 'products.name', 'products.code')
            ->selectRaw('products.id, products.name, products.code, SUM(sales_invoice_items.quantity) as total_qty, SUM(sales_invoice_items.subtotal) as total_rev')
            ->orderBy('total_rev', 'desc')
            ->limit(5)
            ->get();

        return [
            'todaySales' => round((float) $todaySales, 2),
            'todayTransactionsCount' => $todayTransactionsCount,
            'todayCashCollected' => round((float) $todayCashCollected, 2),
            'todayCreditDue' => round((float) $todayCreditDue, 2),
            'monthSales' => round((float) $monthSales, 2),
            'monthGrossProfit' => round((float) $monthGrossProfit, 2),
            'todayPurchases' => round((float) $todayPurchases, 2),
            'monthPurchases' => round((float) $monthPurchases, 2),
            'inventoryValuation' => round($inventoryValuation, 2),
            'lowStockCount' => $lowStockCount,
            'lowStockItems' => $lowStockItems,
            'todayWastageLoss' => round((float) $todayWastageLoss, 2),
            'todayWastageQty' => round((float) $todayWastageQty, 2),
            'monthWastageLoss' => round((float) $monthWastageLoss, 2),
            'todayExpenses' => round((float) $todayExpenses, 2),
            'monthExpenses' => round((float) $monthExpenses, 2),
            'cashAndBankReserves' => round((float) $cashAndBankReserves, 2),
            'recentSales' => $recentSales,
            'recentPurchases' => $recentPurchases,
            'trend7Days' => $trend7Days,
            'topProduce' => $topProduce,
        ];
    }

    /**
     * Sales Analytics report.
     */
    public function getSalesReport(Tenant $tenant, array $filters = []): array
    {
        $query = SalesInvoice::where('tenant_id', $tenant->id)
            ->with(['customer', 'branch']);

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $invoices = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->get();

        $grossSales = (float) $invoices->sum('subtotal');
        $discounts = (float) $invoices->sum('discount_amount');
        $tax = (float) $invoices->sum('tax_amount');
        $netSales = (float) $invoices->sum('total_amount');
        $paid = (float) $invoices->sum('paid_amount');
        $due = (float) $invoices->sum(fn ($i) => $i->due_amount);
        $totalCost = (float) $invoices->sum('cost_amount');
        $grossProfit = (float) $invoices->sum(fn ($i) => $i->profit_amount);
        $marginPct = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 1) : 0.0;

        return [
            'invoices' => $invoices,
            'gross_sales' => $grossSales,
            'discounts' => $discounts,
            'tax' => $tax,
            'net_sales' => $netSales,
            'paid' => $paid,
            'due' => $due,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'margin_pct' => $marginPct,
        ];
    }

    /**
     * Purchase & Mandi Procurement report.
     */
    public function getPurchaseReport(Tenant $tenant, array $filters = []): array
    {
        $query = PurchaseInvoice::where('tenant_id', $tenant->id)
            ->with(['supplier', 'branch']);

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        $purchases = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->get();

        $subtotal = (float) $purchases->sum('subtotal');
        $commission = (float) $purchases->sum('commission_amount');
        $mandiFee = (float) $purchases->sum('mandi_fee');
        $freight = (float) $purchases->sum('freight_charges');
        $totalAmount = (float) $purchases->sum('total_amount');
        $paid = (float) $purchases->sum('paid_amount');
        $due = (float) $purchases->sum(fn ($p) => $p->due_amount);

        return [
            'purchases' => $purchases,
            'subtotal' => $subtotal,
            'commission' => $commission,
            'mandi_fee' => $mandiFee,
            'freight' => $freight,
            'total_amount' => $totalAmount,
            'paid' => $paid,
            'due' => $due,
        ];
    }

    /**
     * Daily Cash Register Z-Report summary.
     */
    public function getDayEndRegisterReport(Tenant $tenant, ?int $branchId = null, ?string $date = null): array
    {
        $d = $date ?? now()->toDateString();

        $salesCash = SalesInvoice::where('tenant_id', $tenant->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('date', $d)
            ->whereNotIn('status', ['cancelled'])
            ->sum('paid_amount');

        $purchaseCashPaid = PurchaseInvoice::where('tenant_id', $tenant->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('date', $d)
            ->whereNotIn('status', ['cancelled'])
            ->sum('paid_amount');

        $expensesCashPaid = Expense::where('tenant_id', $tenant->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', 'approved')
            ->whereDate('date', $d)
            ->sum('amount');

        $netCashFlow = round($salesCash - ($purchaseCashPaid + $expensesCashPaid), 2);

        return [
            'date' => $d,
            'sales_cash_in' => round((float) $salesCash, 2),
            'purchase_cash_out' => round((float) $purchaseCashPaid, 2),
            'expense_cash_out' => round((float) $expensesCashPaid, 2),
            'net_cash_flow' => $netCashFlow,
        ];
    }
}
