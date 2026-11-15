<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display list of physical stock adjustments.
     */
    public function index(Request $request): View
    {
        Gate::authorize('inventory.view');

        $tenant = $this->context->getTenant();

        $query = StockAdjustment::with(['branch', 'creator', 'items.product'])
            ->where('tenant_id', $tenant->id);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        $adjustments = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $branches = Branch::where('tenant_id', $tenant->id)->get();

        return view('tenant.inventory.adjustments.index', compact('adjustments', 'branches', 'tenant'));
    }

    /**
     * Show form to perform physical stock audit & adjustment.
     */
    public function create(Request $request): View
    {
        Gate::authorize('inventory.manage');

        $tenant = $this->context->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get();

        $selectedBranchId = $request->filled('branch_id')
            ? (int) $request->branch_id
            : ($branches->first()->id ?? null);

        // Fetch products with their current stock at the selected branch
        $products = Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->with(['stockUnit'])
            ->get();

        $stocks = InventoryStock::where('tenant_id', $tenant->id)
            ->where('branch_id', $selectedBranchId)
            ->pluck('quantity', 'product_id');

        return view('tenant.inventory.adjustments.create', compact('branches', 'products', 'stocks', 'selectedBranchId', 'tenant'));
    }

    /**
     * Save physical audit reconciliation.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('inventory.manage');

        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.counted_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $branch = Branch::where('tenant_id', $tenant->id)->findOrFail($validated['branch_id']);

        $adjustment = $this->inventoryService->recordAdjustment($tenant, $branch, $validated);

        AuditLog::log(
            event: 'inventory.reconciled',
            action: "Recorded physical stock reconciliation '{$adjustment->adjustment_no}' for branch '{$branch->name}'",
            auditable: $adjustment
        );

        return redirect()->route('tenant.inventory.adjustments.show', $adjustment)
            ->with('success', __('Physical stock audit reconciliation recorded and inventory adjusted successfully.'));
    }

    /**
     * Display adjustment details and reconciliation variance report.
     */
    public function show(StockAdjustment $stockAdjustment): View
    {
        Gate::authorize('inventory.view');

        $tenant = $this->context->getTenant();
        abort_if((int) $stockAdjustment->tenant_id !== (int) $tenant->id, 404);

        $stockAdjustment->load(['branch', 'creator', 'items.product.stockUnit']);

        return view('tenant.inventory.adjustments.show', compact('stockAdjustment', 'tenant'));
    }
}
