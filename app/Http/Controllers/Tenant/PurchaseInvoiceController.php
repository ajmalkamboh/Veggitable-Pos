<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\FinancialAccount;
use App\Models\PartyLedgerEntry;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PurchaseInvoiceController extends Controller
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Display a listing of purchase invoices.
     */
    public function index(Request $request): View
    {
        Gate::authorize('purchases.view');

        $tenant = $this->context->getTenant();

        $query = PurchaseInvoice::where('tenant_id', $tenant->id)
            ->with(['supplier', 'branch', 'creator']);

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('date', '<=', $endDate);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('supplier_invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->searchBilingual($search);
                    });
            });
        }

        $invoices = $query->latest('date')->latest('id')->paginate(15)->withQueryString();

        $suppliers = Supplier::where('tenant_id', $tenant->id)->orderBy('name')->get();

        // Metrics
        $totalPurchases = PurchaseInvoice::where('tenant_id', $tenant->id)->where('status', 'received')->sum('total_amount');
        $totalUnpaid = PurchaseInvoice::where('tenant_id', $tenant->id)->where('status', 'received')->whereIn('payment_status', ['unpaid', 'partial'])->sum(DB::raw('total_amount - paid_amount'));

        return view('tenant.purchases.index', compact('invoices', 'suppliers', 'totalPurchases', 'totalUnpaid', 'tenant'));
    }

    /**
     * Show form for creating a new purchase invoice.
     */
    public function create(): View
    {
        Gate::authorize('purchases.create');

        $tenant = $this->context->getTenant();

        $suppliers = Supplier::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();
        $units = Unit::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $branches = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $accounts = FinancialAccount::where('tenant_id', $tenant->id)->where('is_active', true)->get();

        $nextInvoiceNo = PurchaseInvoice::generateInvoiceNumber($tenant);

        return view('tenant.purchases.create', compact('tenant', 'suppliers', 'products', 'units', 'branches', 'accounts', 'nextInvoiceNo'));
    }

    /**
     * Store a newly created purchase invoice.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('purchases.create');

        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'supplier_invoice_no' => ['nullable', 'string', 'max:50'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mandi_fee' => ['nullable', 'numeric', 'min:0'],
            'freight_charges' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'financial_account_id' => ['nullable', 'required_if:paid_amount,>0', 'exists:financial_accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'image', 'max:2048'],

            // Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.gross_weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.tare_weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.lot_number' => ['nullable', 'string', 'max:50'],
        ]);

        $supplier = Supplier::where('tenant_id', $tenant->id)->findOrFail($validated['supplier_id']);

        return DB::transaction(function () use ($tenant, $supplier, $validated, $request) {
            $invoiceNo = PurchaseInvoice::generateInvoiceNumber($tenant);

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('purchases', 'public');
            }

            // 1. Calculate Items and Subtotal
            $subtotal = 0.00;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity'];
                $gross = isset($item['gross_weight']) && $item['gross_weight'] !== null ? (float) $item['gross_weight'] : null;
                $tare = isset($item['tare_weight']) && $item['tare_weight'] !== null ? (float) $item['tare_weight'] : null;
                $netWeight = ($gross !== null && $tare !== null) ? max(0, $gross - $tare) : null;

                $unitPrice = (float) $item['unit_price'];

                // If net weight is specified, calculate line total by net weight, else by quantity
                $lineTotal = round($netWeight !== null && $netWeight > 0 ? $netWeight * $unitPrice : $qty * $unitPrice, 2);
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'tenant_id' => $tenant->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'quantity' => $qty,
                    'gross_weight' => $gross,
                    'tare_weight' => $tare,
                    'net_weight' => $netWeight,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineTotal,
                    'lot_number' => $item['lot_number'] ?? 'LOT-'.now()->format('ymd').'-'.rand(100, 999),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 2. Financial Charges & Total
            $commRate = (float) ($validated['commission_rate'] ?? $supplier->commission_rate);
            $commissionAmount = round(($subtotal * $commRate) / 100, 2);
            $mandiFee = (float) ($validated['mandi_fee'] ?? 0.00);
            $freight = (float) ($validated['freight_charges'] ?? 0.00);
            $other = (float) ($validated['other_charges'] ?? 0.00);
            $discount = (float) ($validated['discount'] ?? 0.00);

            $totalAmount = round($subtotal + $commissionAmount + $mandiFee + $freight + $other - $discount, 2);
            $paidAmount = min($totalAmount, (float) ($validated['paid_amount'] ?? 0.00));

            $paymentStatus = 'unpaid';
            if ($paidAmount >= $totalAmount && $totalAmount > 0) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            }

            // 3. Create Purchase Invoice
            $invoice = PurchaseInvoice::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $validated['branch_id'],
                'supplier_id' => $supplier->id,
                'invoice_no' => $invoiceNo,
                'supplier_invoice_no' => $validated['supplier_invoice_no'] ?? null,
                'date' => $validated['date'],
                'due_date' => $validated['due_date'] ?? now()->addDays($supplier->payment_terms_days)->toDateString(),
                'status' => 'received',
                'payment_status' => $paymentStatus,
                'subtotal' => $subtotal,
                'commission_rate' => $commRate,
                'commission_amount' => $commissionAmount,
                'mandi_fee' => $mandiFee,
                'freight_charges' => $freight,
                'other_charges' => $other,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'financial_account_id' => $paidAmount > 0 ? $validated['financial_account_id'] : null,
                'notes' => $validated['notes'] ?? null,
                'attachment_path' => $attachmentPath,
                'created_by' => auth()->id(),
            ]);

            // Save items
            foreach ($itemsData as &$itemData) {
                $itemData['purchase_invoice_id'] = $invoice->id;
            }
            PurchaseInvoiceItem::insert($itemsData);

            // 4. Update Supplier Ledger (Credit total invoice amount)
            $newSupplierBalance = $supplier->current_balance + $totalAmount;
            $supplier->update(['current_balance' => $newSupplierBalance]);

            PartyLedgerEntry::create([
                'tenant_id' => $tenant->id,
                'party_type' => 'supplier',
                'party_id' => $supplier->id,
                'date' => $validated['date'],
                'reference_type' => 'purchase_invoice',
                'reference_id' => $invoice->id,
                'reference_no' => $invoice->invoice_no,
                'description' => __('Mandi Purchase Invoice: ').$invoice->invoice_no,
                'debit' => 0.00,
                'credit' => $totalAmount,
                'balance' => $newSupplierBalance,
                'created_by' => auth()->id(),
            ]);

            // 5. If immediate payment was made, process payment debit
            if ($paidAmount > 0 && ! empty($validated['financial_account_id'])) {
                $account = FinancialAccount::where('tenant_id', $tenant->id)->findOrFail($validated['financial_account_id']);
                $account->decrement('current_balance', $paidAmount);

                $newSupplierBalance -= $paidAmount;
                $supplier->update(['current_balance' => $newSupplierBalance]);

                PartyLedgerEntry::create([
                    'tenant_id' => $tenant->id,
                    'party_type' => 'supplier',
                    'party_id' => $supplier->id,
                    'date' => $validated['date'],
                    'reference_type' => 'payment',
                    'reference_id' => $invoice->id,
                    'reference_no' => 'PAY-'.$invoice->invoice_no,
                    'description' => __('Payment on purchase invoice via ').$account->name,
                    'debit' => $paidAmount,
                    'credit' => 0.00,
                    'balance' => $newSupplierBalance,
                    'created_by' => auth()->id(),
                ]);
            }

            AuditLog::log(
                event: 'purchase.created',
                action: "Created purchase invoice '{$invoice->invoice_no}' from supplier '{$supplier->name}' for {$tenant->currency} {$totalAmount}",
                auditable: $invoice
            );

            return redirect()->route('tenant.purchases.show', $invoice)
                ->with('success', __('Purchase invoice created and inventory lot records updated successfully.'));
        });
    }

    /**
     * Display the specified purchase invoice.
     */
    public function show(PurchaseInvoice $purchaseInvoice): View
    {
        Gate::authorize('purchases.view');

        $tenant = $this->context->getTenant();
        abort_if((int) $purchaseInvoice->tenant_id !== (int) $tenant->id, 404);

        $purchaseInvoice->load(['supplier', 'branch', 'items.product', 'items.unit', 'financialAccount', 'creator']);

        return view('tenant.purchases.show', compact('purchaseInvoice', 'tenant'));
    }

    /**
     * Printable purchase voucher / Mandi receiving slip.
     */
    public function print(PurchaseInvoice $purchaseInvoice): View
    {
        Gate::authorize('purchases.view');

        $tenant = $this->context->getTenant();
        abort_if((int) $purchaseInvoice->tenant_id !== (int) $tenant->id, 404);

        $purchaseInvoice->load(['supplier', 'branch', 'items.product', 'items.unit', 'creator']);

        return view('tenant.purchases.print', compact('purchaseInvoice', 'tenant'));
    }

    /**
     * Cancel purchase invoice and reverse ledger impacts.
     */
    public function destroy(PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        Gate::authorize('purchases.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $purchaseInvoice->tenant_id !== (int) $tenant->id, 404);

        if ($purchaseInvoice->status === 'cancelled') {
            return back()->with('error', __('Invoice is already cancelled.'));
        }

        return DB::transaction(function () use ($tenant, $purchaseInvoice) {
            $supplier = $purchaseInvoice->supplier;

            // Reverse supplier credit: debit invoice total
            $newSupplierBalance = $supplier->current_balance - $purchaseInvoice->total_amount;

            // If it had a payment, refund to account and credit supplier
            if ($purchaseInvoice->paid_amount > 0 && $purchaseInvoice->financial_account_id) {
                $account = FinancialAccount::where('tenant_id', $tenant->id)->find($purchaseInvoice->financial_account_id);
                if ($account) {
                    $account->increment('current_balance', $purchaseInvoice->paid_amount);
                }
                $newSupplierBalance += $purchaseInvoice->paid_amount;
            }

            $supplier->update(['current_balance' => $newSupplierBalance]);

            PartyLedgerEntry::create([
                'tenant_id' => $tenant->id,
                'party_type' => 'supplier',
                'party_id' => $supplier->id,
                'date' => now()->toDateString(),
                'reference_type' => 'adjustment',
                'reference_id' => $purchaseInvoice->id,
                'reference_no' => 'CNCL-'.$purchaseInvoice->invoice_no,
                'description' => __('Cancellation reversal of invoice: ').$purchaseInvoice->invoice_no,
                'debit' => $purchaseInvoice->total_amount,
                'credit' => $purchaseInvoice->paid_amount,
                'balance' => $newSupplierBalance,
                'created_by' => auth()->id(),
            ]);

            $purchaseInvoice->update([
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
            ]);

            AuditLog::log(
                event: 'purchase.cancelled',
                action: "Cancelled purchase invoice '{$purchaseInvoice->invoice_no}'",
                auditable: $purchaseInvoice
            );

            return redirect()->route('tenant.purchases.index')->with('success', __('Purchase invoice cancelled and balances reversed.'));
        });
    }
}
