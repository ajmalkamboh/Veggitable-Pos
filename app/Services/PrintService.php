<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\InventoryStock;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesInvoicePayment;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\WastageRecord;

class PrintService
{
    /**
     * Format 80mm/58mm thermal receipt data.
     */
    public function formatThermalReceipt(SalesInvoice $invoice): array
    {
        $invoice->loadMissing(['items.product', 'customer', 'branch', 'creator', 'payments']);
        $tenant = $invoice->tenant;

        $items = $invoice->items->map(function ($item) {
            return [
                'name' => $item->product?->name ?? 'Produce Item',
                'urdu_name' => $item->product?->name_ur ?? $item->product?->urdu_name ?? '',
                'quantity' => (float) $item->quantity,
                'unit' => $item->product?->stockUnit?->code ?? 'kg',
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount_amount,
                'subtotal' => (float) $item->subtotal,
            ];
        });

        $customerBalance = 0.0;
        if ($invoice->customer) {
            $customerBalance = (float) $invoice->customer->current_balance;
        }

        return [
            'invoice_no' => $invoice->invoice_no,
            'date' => $invoice->date ? $invoice->date->format('d/m/Y') : now()->format('d/m/Y'),
            'time' => $invoice->created_at ? $invoice->created_at->format('h:i A') : now()->format('h:i A'),
            'branch_name' => $invoice->branch?->name ?? 'Main Outlet',
            'branch_address' => $invoice->branch?->address ?? $tenant->address,
            'branch_phone' => $invoice->branch?->phone ?? $tenant->phone,
            'cashier_name' => $invoice->creator?->name ?? 'Cashier',
            'customer_name' => $invoice->customer?->name ?? 'Walk-in Retail',
            'customer_phone' => $invoice->customer?->phone ?? '',
            'items' => $items,
            'subtotal' => (float) $invoice->subtotal,
            'discount_amount' => (float) $invoice->discount_amount,
            'tax_amount' => (float) $invoice->tax_amount,
            'rounding_amount' => (float) $invoice->rounding_amount,
            'total_amount' => (float) $invoice->total_amount,
            'paid_amount' => (float) $invoice->paid_amount,
            'due_amount' => (float) $invoice->due_amount,
            'change_amount' => (float) $invoice->change_amount,
            'customer_balance' => $customerBalance,
            'payment_status' => $invoice->payment_status,
            'payment_method' => $invoice->payment_method ?? 'cash',
        ];
    }

    /**
     * Format A4 Sales and Tax Invoice.
     */
    public function formatA4SalesInvoice(SalesInvoice $invoice): array
    {
        $invoice->loadMissing(['items.product', 'customer', 'branch', 'creator', 'payments']);

        return [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'branch' => $invoice->branch,
            'customer' => $invoice->customer,
            'items' => $invoice->items,
            'payments' => $invoice->payments,
        ];
    }

    /**
     * Format A4 Mandi Procurement & Purchase Invoice.
     */
    public function formatA4PurchaseInvoice(PurchaseInvoice $invoice): array
    {
        $invoice->loadMissing(['items.product', 'supplier', 'branch', 'creator']);

        return [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'branch' => $invoice->branch,
            'supplier' => $invoice->supplier,
            'items' => $invoice->items,
        ];
    }

    /**
     * Format Customer Statement of Account with chronological running ledger balance.
     */
    public function formatCustomerStatement(Customer $customer, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $tenant = $customer->tenant;

        $invoicesQuery = SalesInvoice::where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->whereNotIn('status', ['cancelled']);

        $paymentsQuery = SalesInvoicePayment::where('sales_invoice_payments.tenant_id', $tenant->id)
            ->join('sales_invoices', 'sales_invoice_payments.sales_invoice_id', '=', 'sales_invoices.id')
            ->where('sales_invoices.customer_id', $customer->id);

        if ($dateFrom) {
            $invoicesQuery->whereDate('sales_invoices.date', '>=', $dateFrom);
            $paymentsQuery->whereDate('sales_invoice_payments.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $invoicesQuery->whereDate('sales_invoices.date', '<=', $dateTo);
            $paymentsQuery->whereDate('sales_invoice_payments.created_at', '<=', $dateTo);
        }

        $invoices = $invoicesQuery->orderBy('date', 'asc')->get();
        $totalBilled = (float) $invoices->sum('total_amount');
        $totalPaid = (float) $invoices->sum('paid_amount');
        $outstandingBalance = (float) $customer->current_balance;

        return [
            'customer' => $customer,
            'tenant' => $tenant,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'invoices' => $invoices,
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'outstanding_balance' => $outstandingBalance,
        ];
    }

    /**
     * Format Mandi Supplier / Commission Agent Statement.
     */
    public function formatSupplierStatement(Supplier $supplier, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $tenant = $supplier->tenant;

        $query = PurchaseInvoice::where('tenant_id', $tenant->id)
            ->where('supplier_id', $supplier->id)
            ->whereNotIn('status', ['cancelled']);

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        $purchases = $query->orderBy('date', 'asc')->get();
        $totalPurchases = (float) $purchases->sum('total_amount');
        $totalPaid = (float) $purchases->sum('paid_amount');
        $totalCommission = (float) $purchases->sum('commission_amount');
        $totalMandiFees = (float) $purchases->sum('mandi_fee');
        $currentPayable = (float) $supplier->current_balance;

        return [
            'supplier' => $supplier,
            'tenant' => $tenant,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'purchases' => $purchases,
            'total_purchases' => $totalPurchases,
            'total_paid' => $totalPaid,
            'total_commission' => $totalCommission,
            'total_mandi_fees' => $totalMandiFees,
            'current_payable' => $currentPayable,
        ];
    }

    /**
     * Format Payment Voucher (Cash/Bank Outflow).
     */
    public function formatPaymentVoucher(Expense $expense): array
    {
        $expense->loadMissing(['branch', 'category', 'financialAccount', 'creator', 'approver']);

        return [
            'expense' => $expense,
            'tenant' => $expense->tenant,
            'branch' => $expense->branch,
            'voucher_no' => $expense->expense_no,
            'date' => $expense->date,
            'payee' => $expense->payee_name ?? 'Expense Beneficiary',
            'category' => $expense->category?->name ?? $expense->category_name ?? 'General Overhead',
            'amount' => (float) $expense->amount,
            'amount_in_words' => $this->numberToWords((float) $expense->amount),
            'payment_method' => $expense->payment_method,
            'account' => $expense->financialAccount?->name ?? 'Cash Till',
            'title' => $expense->title,
            'notes' => $expense->description,
            'creator' => $expense->creator?->name,
            'approver' => $expense->approver?->name,
        ];
    }

    /**
     * Format Receipt Voucher (Cash/Bank Inflow).
     */
    public function formatReceiptVoucher(SalesInvoicePayment $payment): array
    {
        $payment->loadMissing(['invoice.customer', 'invoice.branch', 'invoice.creator']);
        $invoice = $payment->invoice;

        return [
            'payment' => $payment,
            'tenant' => $invoice->tenant,
            'branch' => $invoice->branch,
            'receipt_no' => 'RCP-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT),
            'date' => $payment->created_at ?? now(),
            'invoice_no' => $invoice->invoice_no,
            'payer' => $invoice->customer?->name ?? 'Retail Customer',
            'amount' => (float) $payment->amount,
            'amount_in_words' => $this->numberToWords((float) $payment->amount),
            'payment_method' => $payment->payment_method,
            'transaction_reference' => $payment->transaction_reference,
            'cashier' => $invoice->creator?->name ?? 'Cashier',
        ];
    }

    /**
     * Format A4 Inventory Valuation and Stock Count Sheet.
     */
    public function formatStockReport(Tenant $tenant, ?int $branchId = null): array
    {
        $query = InventoryStock::where('inventory_stocks.tenant_id', $tenant->id)
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->join('branches', 'inventory_stocks.branch_id', '=', 'branches.id');

        if ($branchId) {
            $query->where('inventory_stocks.branch_id', $branchId);
        }

        $stocks = $query->select(
            'products.name',
            'products.code',
            'products.name_ur as urdu_name',
            'branches.name as branch_name',
            'inventory_stocks.quantity',
            'inventory_stocks.reorder_level',
            'products.cost_price',
            'products.selling_price'
        )->orderBy('products.name')->get();

        $totalValuationCost = $stocks->sum(function ($s) {
            return (float) $s->quantity * (float) $s->cost_price;
        });

        $totalValuationRetail = $stocks->sum(function ($s) {
            return (float) $s->quantity * (float) $s->selling_price;
        });

        $branch = $branchId ? Branch::where('tenant_id', $tenant->id)->find($branchId) : null;

        return [
            'tenant' => $tenant,
            'branch' => $branch,
            'stocks' => $stocks,
            'total_items' => $stocks->count(),
            'total_quantity' => $stocks->sum('quantity'),
            'total_valuation_cost' => round($totalValuationCost, 2),
            'total_valuation_retail' => round($totalValuationRetail, 2),
            'report_date' => now()->format('d M Y, h:i A'),
        ];
    }

    /**
     * Format A4 Spoilage & Wastage Loss Audit Sheet.
     */
    public function formatWastageReport(Tenant $tenant, ?int $branchId = null, ?string $from = null, ?string $to = null): array
    {
        $query = WastageRecord::where('tenant_id', $tenant->id)
            ->with(['branch', 'creator', 'approver', 'items.product']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($from) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('date', '<=', $to);
        }

        $records = $query->orderBy('date', 'desc')->get();
        $totalLossQty = (float) $records->sum('total_quantity');
        $totalLossCost = (float) $records->sum('total_loss_cost');

        $branch = $branchId ? Branch::where('tenant_id', $tenant->id)->find($branchId) : null;

        return [
            'tenant' => $tenant,
            'branch' => $branch,
            'records' => $records,
            'from' => $from,
            'to' => $to,
            'total_records' => $records->count(),
            'total_loss_qty' => $totalLossQty,
            'total_loss_cost' => round($totalLossCost, 2),
            'report_date' => now()->format('d M Y, h:i A'),
        ];
    }

    /**
     * Convert numeric amount to words (Rupees).
     */
    public function numberToWords(float $amount): string
    {
        $number = (int) $amount;
        if ($number === 0) {
            return 'Zero Rupees Only';
        }

        $f = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $words = ucwords($f->format($number));

        $paise = (int) round(($amount - $number) * 100);
        if ($paise > 0) {
            return sprintf('%s Rupees and %s Paisas Only', $words, ucwords($f->format($paise)));
        }

        return $words.' Rupees Only';
    }
}
