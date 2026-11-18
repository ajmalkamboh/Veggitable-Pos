<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\InventoryStock;
use App\Models\PartyLedgerEntry;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\PrintService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreditSaleController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected PrintService $printService
    ) {}

    /**
     * Display a listing of credit customer sales.
     */
    public function index(Request $request): View
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)->get();
        $customers = Customer::where('tenant_id', $tenant->id)->orderBy('name')->get();

        $query = SalesInvoice::with(['customer', 'branch', 'creator', 'items.product'])
            ->where('tenant_id', $tenant->id)
            ->where('payment_method', 'credit');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('name_ur', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        // Summary KPI Metrics
        $metricQuery = SalesInvoice::where('tenant_id', $tenant->id)
            ->where('payment_method', 'credit');

        if ($request->filled('branch_id')) {
            $metricQuery->where('branch_id', $request->branch_id);
        }

        $totalCreditSales = (clone $metricQuery)->sum('total_amount');
        $totalPaid = (clone $metricQuery)->sum('paid_amount');
        $totalOutstanding = max(0, $totalCreditSales - $totalPaid);
        $totalInvoicesCount = (clone $metricQuery)->count();

        return view('tenant.credit-sales.index', compact(
            'invoices',
            'branches',
            'customers',
            'totalCreditSales',
            'totalPaid',
            'totalOutstanding',
            'totalInvoicesCount',
            'tenant'
        ));
    }

    /**
     * Show the form for creating a new credit customer sale.
     */
    public function create(): View
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $customers = Customer::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('tenant_id', $tenant->id)->where('is_active', true)->with('stockUnit')->orderBy('name')->get();
        $units = Unit::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $accounts = FinancialAccount::where('tenant_id', $tenant->id)->where('is_active', true)->get();

        // Get current branch stocks for quick inventory indicator
        $activeBranchId = $branches->first()?->id ?? 1;
        $stocks = InventoryStock::where('tenant_id', $tenant->id)
            ->pluck('quantity', 'product_id');

        $nextInvoiceNo = SalesInvoice::generateInvoiceNumber($tenant);

        return view('tenant.credit-sales.create', compact(
            'tenant',
            'branches',
            'customers',
            'products',
            'units',
            'accounts',
            'stocks',
            'nextInvoiceNo'
        ));
    }

    /**
     * Store a newly created credit sale invoice.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'date' => ['required', 'date'],
            'delivery_charges' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'financial_account_id' => ['nullable', 'required_if:paid_amount,>0', 'exists:financial_accounts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Produce Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $customer = Customer::where('tenant_id', $tenant->id)->findOrFail($validated['customer_id']);
        $branch = Branch::where('tenant_id', $tenant->id)->findOrFail($validated['branch_id']);

        return DB::transaction(function () use ($tenant, $customer, $branch, $validated) {
            // 1. Calculate Items, Subtotal, and Unit Costs
            $subtotal = 0.00;
            $totalCost = 0.00;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $product = Product::where('tenant_id', $tenant->id)->findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $lineSubtotal = round($qty * $price, 2);
                $unitCost = (float) ($product->cost_price ?? 0.00);

                $subtotal += $lineSubtotal;
                $totalCost += round($qty * $unitCost, 2);

                // Update physical branch inventory
                $stock = InventoryStock::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'branch_id' => $branch->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => 0.000,
                        'cost_price' => $unitCost,
                    ]
                );

                $qtyBefore = (float) $stock->quantity;
                $qtyAfter = $qtyBefore - $qty;
                $stock->update(['quantity' => $qtyAfter]);

                $itemsData[] = [
                    'tenant_id' => $tenant->id,
                    'product_id' => $product->id,
                    'unit_id' => $item['unit_id'] ?? $product->stock_unit_id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'unit_cost' => $unitCost,
                    'discount_amount' => 0.00,
                    'subtotal' => $lineSubtotal,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                ];
            }

            // 2. Adjust with Delivery Charges & Discounts
            $deliveryCharges = (float) ($validated['delivery_charges'] ?? 0.00);
            $discountAmount = (float) ($validated['discount_amount'] ?? 0.00);
            $grandTotal = round(max(0, $subtotal + $deliveryCharges - $discountAmount), 2);

            $paidAmount = min($grandTotal, (float) ($validated['paid_amount'] ?? 0.00));
            $dueAmount = max(0, $grandTotal - $paidAmount);

            // 3. Check Customer Credit Limit
            if ($customer->credit_limit > 0 && ($customer->current_balance + $dueAmount) > $customer->credit_limit) {
                throw ValidationException::withMessages([
                    'customer_id' => [__('Credit limit exceeded. Customer limit is :curr :limit, current balance is :curr :bal, this order requires :curr :due.', [
                        'curr' => $tenant->currency,
                        'limit' => number_format($customer->credit_limit, 2),
                        'bal' => number_format($customer->current_balance, 2),
                        'due' => number_format($dueAmount, 2),
                    ])],
                ]);
            }

            $paymentStatus = 'unpaid';
            if ($paidAmount >= $grandTotal && $grandTotal > 0) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            }

            $invoiceNo = SalesInvoice::generateInvoiceNumber($tenant);

            // 4. Create Sales Invoice Header
            $invoice = SalesInvoice::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'pos_session_id' => null, // Standalone credit entry, not bound to till session
                'customer_id' => $customer->id,
                'invoice_no' => $invoiceNo,
                'date' => $validated['date'],
                'status' => 'completed',
                'payment_status' => $paymentStatus,
                'payment_method' => 'credit',
                'subtotal' => $subtotal,
                'tax_amount' => 0.00,
                'discount_type' => 'flat',
                'discount_rate' => 0.00,
                'discount_amount' => $discountAmount,
                'delivery_charges' => $deliveryCharges,
                'rounding_amount' => 0.00,
                'total_amount' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => 0.00,
                'cost_amount' => $totalCost,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // 5. Insert Items & Stock Movements
            foreach ($itemsData as $itemRow) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'tenant_id' => $tenant->id,
                    'product_id' => $itemRow['product_id'],
                    'unit_id' => $itemRow['unit_id'],
                    'quantity' => $itemRow['quantity'],
                    'unit_price' => $itemRow['unit_price'],
                    'unit_cost' => $itemRow['unit_cost'],
                    'discount_amount' => $itemRow['discount_amount'],
                    'subtotal' => $itemRow['subtotal'],
                ]);

                StockMovement::create([
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branch->id,
                    'product_id' => $itemRow['product_id'],
                    'type' => 'sale_out',
                    'reference_type' => 'sales_invoice',
                    'reference_id' => $invoice->id,
                    'reference_no' => $invoice->invoice_no,
                    'quantity_change' => -$itemRow['quantity'],
                    'quantity_before' => $itemRow['qty_before'],
                    'quantity_after' => $itemRow['qty_after'],
                    'unit_cost' => $itemRow['unit_cost'],
                    'notes' => __('Credit Customer Sale: ').$invoice->invoice_no.' ('.$customer->name.')',
                    'created_by' => auth()->id(),
                ]);
            }

            // 6. Update Customer Credit Ledger Balance
            $newCustomerBalance = $customer->current_balance + $dueAmount;
            $customer->update(['current_balance' => $newCustomerBalance]);

            // Record Ledger Debit (Receivable)
            PartyLedgerEntry::create([
                'tenant_id' => $tenant->id,
                'party_type' => 'customer',
                'party_id' => $customer->id,
                'date' => $invoice->date,
                'reference_type' => 'sale',
                'reference_id' => $invoice->id,
                'reference_no' => $invoice->invoice_no,
                'description' => __('Credit Produce Sale: ').$invoice->invoice_no,
                'debit' => $dueAmount,
                'credit' => 0.00,
                'balance' => $newCustomerBalance,
                'created_by' => auth()->id(),
            ]);

            // 7. Handle On-The-Spot Partial Cash Payment (if any)
            if ($paidAmount > 0 && ! empty($validated['financial_account_id'])) {
                $account = FinancialAccount::where('tenant_id', $tenant->id)->findOrFail($validated['financial_account_id']);
                $account->increment('current_balance', $paidAmount);

                AuditLog::record(
                    'credit_sale.partial_payment',
                    [
                        'invoice_no' => $invoice->invoice_no,
                        'paid_amount' => $paidAmount,
                        'account' => $account->name,
                    ],
                    auth()->user(),
                    $tenant->id
                );
            }

            // 8. Audit Trail
            AuditLog::record(
                'credit_sale.created',
                [
                    'invoice_no' => $invoice->invoice_no,
                    'customer' => $customer->name,
                    'total_amount' => $grandTotal,
                    'paid_amount' => $paidAmount,
                    'due_amount' => $dueAmount,
                ],
                auth()->user(),
                $tenant->id
            );

            return redirect()->route('tenant.credit-sales.show', $invoice)
                ->with('success', __('Credit sale recorded successfully. Bill #: :no', ['no' => $invoice->invoice_no]));
        });
    }

    /**
     * Show credit sale invoice details with print & share options.
     */
    public function show(SalesInvoice $salesInvoice): View
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        abort_if((int) $salesInvoice->tenant_id !== (int) $tenant->id, 404);

        $salesInvoice->load([
            'items.product.stockUnit',
            'items.unit',
            'customer',
            'branch',
            'creator',
        ]);

        $customer = $salesInvoice->customer;
        $whatsAppMessage = $this->buildWhatsAppMessage($salesInvoice, $customer, $tenant);
        $whatsAppUrl = $this->buildWhatsAppUrl($customer?->phone ?? $customer?->mobile, $whatsAppMessage);

        return view('tenant.credit-sales.show', compact('salesInvoice', 'tenant', 'customer', 'whatsAppMessage', 'whatsAppUrl'));
    }

    /**
     * Download or stream PDF for credit sale invoice.
     */
    public function downloadPdf(SalesInvoice $salesInvoice): Response
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        abort_if((int) $salesInvoice->tenant_id !== (int) $tenant->id, 404);

        $salesInvoice->load([
            'items.product.stockUnit',
            'items.unit',
            'customer',
            'branch',
            'creator',
        ]);

        $customer = $salesInvoice->customer;
        $amountInWords = $this->printService->numberToWords((float) $salesInvoice->total_amount);

        $pdf = Pdf::loadView('tenant.credit-sales.pdf', compact('salesInvoice', 'tenant', 'customer', 'amountInWords'))
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        return $pdf->download("credit_sale_{$salesInvoice->invoice_no}.pdf");
    }

    /**
     * Redirect to WhatsApp share link.
     */
    public function whatsappShare(SalesInvoice $salesInvoice): RedirectResponse
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        abort_if((int) $salesInvoice->tenant_id !== (int) $tenant->id, 404);

        $salesInvoice->load(['items.product', 'items.unit', 'customer']);
        $customer = $salesInvoice->customer;

        $message = $this->buildWhatsAppMessage($salesInvoice, $customer, $tenant);
        $url = $this->buildWhatsAppUrl($customer?->phone ?? $customer?->mobile, $message);

        return redirect()->away($url);
    }

    /**
     * Export all credit sales to CSV format.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();

        $query = SalesInvoice::with(['customer', 'branch', 'creator'])
            ->where('tenant_id', $tenant->id)
            ->where('payment_method', 'credit');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('id', 'desc')->get();

        $filename = 'credit_sales_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($invoices, $tenant) {
            $output = fopen('php://output', 'w');

            // Write UTF-8 BOM for Microsoft Excel compatibility with Urdu text
            fwrite($output, "\xEF\xBB\xBF");

            // Header row
            fputcsv($output, [
                'Invoice No',
                'Date',
                'Customer Code',
                'Customer Name',
                'Phone',
                'Branch',
                'Subtotal ('.$tenant->currency.')',
                'Discount ('.$tenant->currency.')',
                'Delivery Charges ('.$tenant->currency.')',
                'Total Amount ('.$tenant->currency.')',
                'Paid Amount ('.$tenant->currency.')',
                'Due Amount ('.$tenant->currency.')',
                'Payment Status',
                'Notes',
                'Created By',
            ]);

            foreach ($invoices as $inv) {
                fputcsv($output, [
                    $inv->invoice_no,
                    $inv->date ? $inv->date->format('Y-m-d') : '',
                    $inv->customer?->code ?? '',
                    $inv->customer?->name ?? 'Walk-in',
                    $inv->customer?->phone ?? '',
                    $inv->branch?->name ?? '',
                    number_format($inv->subtotal, 2, '.', ''),
                    number_format($inv->discount_amount, 2, '.', ''),
                    number_format($inv->delivery_charges, 2, '.', ''),
                    number_format($inv->total_amount, 2, '.', ''),
                    number_format($inv->paid_amount, 2, '.', ''),
                    number_format($inv->due_amount, 2, '.', ''),
                    ucfirst($inv->payment_status),
                    $inv->notes ?? '',
                    $inv->creator?->name ?? '',
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export itemized lines of a single credit sale to CSV.
     */
    public function exportSingleCsv(SalesInvoice $salesInvoice): StreamedResponse
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        abort_if((int) $salesInvoice->tenant_id !== (int) $tenant->id, 404);

        $salesInvoice->load(['items.product.stockUnit', 'items.unit', 'customer']);

        $filename = "credit_sale_{$salesInvoice->invoice_no}_items.csv";

        return response()->streamDownload(function () use ($salesInvoice, $tenant) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Invoice No',
                'Date',
                'Customer',
                'Item Code',
                'Product Name (EN)',
                'Product Name (UR)',
                'Unit',
                'Quantity',
                'Rate ('.$tenant->currency.')',
                'Line Amount ('.$tenant->currency.')',
            ]);

            foreach ($salesInvoice->items as $item) {
                fputcsv($output, [
                    $salesInvoice->invoice_no,
                    $salesInvoice->date ? $salesInvoice->date->format('Y-m-d') : '',
                    $salesInvoice->customer?->name ?? '',
                    $item->product?->code ?? '',
                    $item->product?->name ?? '',
                    $item->product?->name_ur ?? '',
                    $item->unit?->code ?? $item->product?->stockUnit?->code ?? 'kg',
                    number_format($item->quantity, 3, '.', ''),
                    number_format($item->unit_price, 2, '.', ''),
                    number_format($item->subtotal, 2, '.', ''),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Build pre-formatted WhatsApp text message for credit sale.
     */
    protected function buildWhatsAppMessage(SalesInvoice $invoice, ?Customer $customer, Tenant $tenant): string
    {
        $lines = [];
        $lines[] = '🌿 *'.$tenant->name.'*';
        $lines[] = '*Credit Sale Invoice / بل ادھار فروخت*';
        $lines[] = '--------------------------------';
        $lines[] = "📄 *Invoice #:* {$invoice->invoice_no}";
        $lines[] = '📅 *Date:* '.($invoice->date ? $invoice->date->format('d-M-Y') : now()->format('d-M-Y'));

        if ($customer) {
            $custName = $customer->name.($customer->name_ur ? " ({$customer->name_ur})" : '');
            $lines[] = "👤 *Customer:* {$custName}";
            if ($customer->phone) {
                $lines[] = "📞 *Phone:* {$customer->phone}";
            }
        }

        $lines[] = '--------------------------------';
        $lines[] = '*ITEMS / تفصیل اشیاء:*';

        foreach ($invoice->items as $idx => $item) {
            $num = $idx + 1;
            $pName = $item->product?->name ?? 'Vegetable Item';
            if ($item->product?->name_ur) {
                $pName .= ' / '.$item->product->name_ur;
            }
            $unit = $item->unit?->code ?? $item->product?->stockUnit?->code ?? 'kg';
            $qty = number_format($item->quantity, 2);
            $price = number_format($item->unit_price, 2);
            $lineTotal = number_format($item->subtotal, 2);

            $lines[] = "{$num}. *{$pName}*";
            $lines[] = "   {$qty} {$unit} x {$tenant->currency} {$price} = {$tenant->currency} {$lineTotal}";
        }

        $lines[] = '--------------------------------';
        $lines[] = "💵 *Subtotal:* {$tenant->currency} ".number_format($invoice->subtotal, 2);

        if ($invoice->discount_amount > 0) {
            $lines[] = "🏷️ *Discount:* -{$tenant->currency} ".number_format($invoice->discount_amount, 2);
        }

        if ($invoice->delivery_charges > 0) {
            $lines[] = "🚚 *Delivery/Freight:* +{$tenant->currency} ".number_format($invoice->delivery_charges, 2);
        }

        $lines[] = "💰 *Total Bill:* {$tenant->currency} ".number_format($invoice->total_amount, 2);

        if ($invoice->paid_amount > 0) {
            $lines[] = "✅ *Paid on Spot:* {$tenant->currency} ".number_format($invoice->paid_amount, 2);
        }

        $due = max(0, $invoice->total_amount - $invoice->paid_amount);
        $lines[] = "⚠️ *Current Bill Balance:* {$tenant->currency} ".number_format($due, 2);

        if ($customer) {
            $lines[] = '--------------------------------';
            $lines[] = "📋 *Total Khata Balance:* {$tenant->currency} ".number_format($customer->current_balance, 2);
        }

        $lines[] = '--------------------------------';
        $lines[] = 'شکریہ! برائے رابطہ: '.($tenant->phone ?? '');

        return implode("\n", $lines);
    }

    /**
     * Build WhatsApp URL with phone and message.
     */
    protected function buildWhatsAppUrl(?string $phone, string $message): string
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone ?? '');

        // Convert Pakistan local number 0300... to international 92300...
        if (str_starts_with($cleanPhone, '03')) {
            $cleanPhone = '92'.substr($cleanPhone, 1);
        }

        $encodedMsg = urlencode($message);

        return ! empty($cleanPhone)
            ? "https://api.whatsapp.com/send?phone={$cleanPhone}&text={$encodedMsg}"
            : "https://api.whatsapp.com/send?text={$encodedMsg}";
    }
}
