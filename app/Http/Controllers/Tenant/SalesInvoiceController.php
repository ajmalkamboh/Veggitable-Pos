<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\SalesInvoice;
use App\Services\PosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SalesInvoiceController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected PosService $posService
    ) {}

    /**
     * Display sales invoice history.
     */
    public function index(Request $request): View
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)->get();
        $customers = Customer::where('tenant_id', $tenant->id)->get();

        $query = SalesInvoice::with(['customer', 'branch', 'creator'])
            ->where('tenant_id', $tenant->id);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
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
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        // Summary Metrics
        $metricQuery = SalesInvoice::where('tenant_id', $tenant->id);
        if ($request->filled('branch_id')) {
            $metricQuery->where('branch_id', $request->branch_id);
        }

        $totalSales = (clone $metricQuery)->sum('total_amount');
        $totalPaid = (clone $metricQuery)->sum('paid_amount');
        $totalReceivable = max(0, $totalSales - $totalPaid);
        $totalInvoicesCount = (clone $metricQuery)->count();

        return view('tenant.sales.index', compact(
            'invoices',
            'branches',
            'customers',
            'totalSales',
            'totalPaid',
            'totalReceivable',
            'totalInvoicesCount',
            'tenant'
        ));
    }

    /**
     * Show sales invoice details.
     */
    public function show(SalesInvoice $salesInvoice): View
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        abort_if((int) $salesInvoice->tenant_id !== (int) $tenant->id, 404);

        $salesInvoice->load([
            'items.product.stockUnit',
            'customer',
            'branch',
            'returns.items.product',
            'creator',
        ]);

        $accounts = FinancialAccount::where('tenant_id', $tenant->id)->where('is_active', true)->get();

        return view('tenant.sales.show', compact('salesInvoice', 'accounts', 'tenant'));
    }

    /**
     * Process sales return / refund.
     */
    public function returnItem(Request $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        abort_if((int) $salesInvoice->tenant_id !== (int) $tenant->id, 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'refund_method' => ['required', 'in:cash,credit_adjustment'],
            'financial_account_id' => ['nullable', 'exists:financial_accounts,id'],
            'date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->posService->returnSale($tenant, $salesInvoice, $validated, auth()->user());

        return redirect()->route('tenant.sales.show', $salesInvoice)
            ->with('success', __('Sales return and stock refund processed successfully.'));
    }
}
