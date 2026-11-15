<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\HeldSalesTicket;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected PosService $posService
    ) {}

    /**
     * Display the POS counter terminal.
     */
    public function counter(Request $request): View
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        $user = auth()->user();

        $activeBranchId = $user->current_branch_id
            ?? ($user->branches()->first()->id ?? $tenant->branches()->first()->id);

        $branch = Branch::where('tenant_id', $tenant->id)->findOrFail($activeBranchId);

        // Find or check open POS till session
        $posSession = PosSession::where('tenant_id', $tenant->id)
            ->where('branch_id', $branch->id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        $categories = Category::where('tenant_id', $tenant->id)
            ->where('type', 'product')
            ->where('is_active', true)
            ->get();

        $customers = Customer::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $accounts = FinancialAccount::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        // Products with stock at active branch
        $products = Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->with(['category', 'stockUnit'])
            ->get();

        $heldCount = HeldSalesTicket::where('tenant_id', $tenant->id)
            ->where('branch_id', $branch->id)
            ->count();

        return view('tenant.pos.counter', compact(
            'tenant',
            'branch',
            'posSession',
            'categories',
            'customers',
            'accounts',
            'products',
            'heldCount'
        ));
    }

    /**
     * Process checkout from POS counter.
     */
    public function checkout(Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        $user = auth()->user();

        $activeBranchId = $request->input('branch_id')
            ?? ($user->current_branch_id ?? $tenant->branches()->first()->id);

        $branch = Branch::where('tenant_id', $tenant->id)->findOrFail($activeBranchId);

        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'payment_method' => ['required', 'in:cash,credit,bank_card,qr_transfer,split'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'rounding_amount' => ['nullable', 'numeric'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'financial_account_id' => ['nullable', 'exists:financial_accounts,id'],
            'held_ticket_id' => ['nullable', 'exists:held_sales_tickets,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $invoice = $this->posService->checkout($tenant, $branch, $validated, $user);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Sale completed successfully!'),
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'print_url' => route('tenant.pos.receipt', $invoice),
            ]);
        }

        return redirect()->route('tenant.pos.receipt', $invoice)
            ->with('success', __('Sale completed successfully!'));
    }

    /**
     * Park / Hold current cart ticket.
     */
    public function hold(Request $request): JsonResponse
    {
        Gate::authorize('pos.hold');

        $tenant = $this->context->getTenant();
        $user = auth()->user();

        $branch = Branch::where('tenant_id', $tenant->id)
            ->findOrFail($user->current_branch_id ?? $tenant->branches()->first()->id);

        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'cart_data' => ['required', 'array', 'min:1'],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $ticket = $this->posService->holdCart($tenant, $branch, $validated, $user);

        return response()->json([
            'success' => true,
            'message' => __('Cart parked successfully as ticket :ticket', ['ticket' => $ticket->ticket_number]),
            'ticket' => $ticket,
        ]);
    }

    /**
     * Retrieve list of held cart tickets.
     */
    public function heldTickets(): JsonResponse
    {
        Gate::authorize('pos.hold');

        $tenant = $this->context->getTenant();
        $user = auth()->user();

        $tickets = HeldSalesTicket::where('tenant_id', $tenant->id)
            ->with(['customer', 'user'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'tickets' => $tickets,
        ]);
    }

    /**
     * Printable thermal receipt (80mm & 58mm).
     */
    public function receipt(SalesInvoice $salesInvoice): View
    {
        Gate::authorize('pos.counter');

        $tenant = $this->context->getTenant();
        abort_if((int) $salesInvoice->tenant_id !== (int) $tenant->id, 404);

        $salesInvoice->load(['items.product.stockUnit', 'customer', 'branch', 'creator']);

        return view('tenant.pos.receipt', compact('salesInvoice', 'tenant'));
    }
}
