<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\SalesInvoicePayment;
use App\Models\Supplier;
use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected PrintService $printService
    ) {}

    /**
     * 80mm / 58mm Thermal Sales Receipt.
     */
    public function salesThermal(SalesInvoice $salesInvoice): View
    {
        $tenant = $this->context->getTenant();
        abort_if((int) $salesInvoice->tenant_id !== (int) $tenant->id, 404);

        $receiptData = $this->printService->formatThermalReceipt($salesInvoice);

        return view('tenant.printing.sales_thermal', compact('receiptData', 'tenant', 'salesInvoice'));
    }

    /**
     * A4 Sales and Tax Invoice.
     */
    public function salesA4(SalesInvoice $salesInvoice): View
    {
        $tenant = $this->context->getTenant();
        abort_if((int) $salesInvoice->tenant_id !== (int) $tenant->id, 404);

        $invoiceData = $this->printService->formatA4SalesInvoice($salesInvoice);

        return view('tenant.printing.sales_a4', array_merge($invoiceData, ['tenant' => $tenant]));
    }

    /**
     * A4 Mandi Procurement and Wholesale Purchase Invoice.
     */
    public function purchaseA4(PurchaseInvoice $purchaseInvoice): View
    {
        $tenant = $this->context->getTenant();
        abort_if((int) $purchaseInvoice->tenant_id !== (int) $tenant->id, 404);

        $invoiceData = $this->printService->formatA4PurchaseInvoice($purchaseInvoice);

        return view('tenant.printing.purchase_a4', array_merge($invoiceData, ['tenant' => $tenant]));
    }

    /**
     * Customer Statement of Account Ledger.
     */
    public function customerStatement(Customer $customer, Request $request): View
    {
        $tenant = $this->context->getTenant();
        abort_if((int) $customer->tenant_id !== (int) $tenant->id, 404);

        $statementData = $this->printService->formatCustomerStatement(
            $customer,
            $request->get('date_from'),
            $request->get('date_to')
        );

        return view('tenant.printing.customer_statement', $statementData);
    }

    /**
     * Mandi Commission Agent / Supplier Statement.
     */
    public function supplierStatement(Supplier $supplier, Request $request): View
    {
        $tenant = $this->context->getTenant();
        abort_if((int) $supplier->tenant_id !== (int) $tenant->id, 404);

        $statementData = $this->printService->formatSupplierStatement(
            $supplier,
            $request->get('date_from'),
            $request->get('date_to')
        );

        return view('tenant.printing.supplier_statement', $statementData);
    }

    /**
     * Cash / Bank Payment Outflow Voucher.
     */
    public function paymentVoucher(Expense $expense): View
    {
        $tenant = $this->context->getTenant();
        abort_if((int) $expense->tenant_id !== (int) $tenant->id, 404);

        $voucherData = $this->printService->formatPaymentVoucher($expense);

        return view('tenant.printing.payment_voucher', $voucherData);
    }

    /**
     * Cash / Bank Money Receipt Voucher.
     */
    public function receiptVoucher(SalesInvoicePayment $payment): View
    {
        $tenant = $this->context->getTenant();
        abort_if((int) $payment->tenant_id !== (int) $tenant->id, 404);

        $receiptData = $this->printService->formatReceiptVoucher($payment);

        return view('tenant.printing.receipt_voucher', $receiptData);
    }

    /**
     * A4 Inventory Valuation and Physical Stock Count Sheet.
     */
    public function stockReport(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;

        $reportData = $this->printService->formatStockReport($tenant, $branchId);

        return view('tenant.printing.stock_sheet', $reportData);
    }

    /**
     * A4 Spoilage & Wastage Loss Audit Sheet.
     */
    public function wastageReport(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;

        $reportData = $this->printService->formatWastageReport(
            $tenant,
            $branchId,
            $request->get('date_from'),
            $request->get('date_to')
        );

        return view('tenant.printing.wastage_sheet', $reportData);
    }
}
