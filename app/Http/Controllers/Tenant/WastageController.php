<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\InventoryStock;
use App\Models\ProduceRegrading;
use App\Models\Product;
use App\Models\WastageRecord;
use App\Services\WastageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WastageController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected WastageService $wastageService
    ) {}

    /**
     * Display a listing of wastage & damage records.
     */
    public function index(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $query = WastageRecord::where('tenant_id', $tenant->id)
            ->with(['branch', 'creator', 'approver', 'items.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('wastage_no', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        $records = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(15);

        // Metrics for summary cards
        $metricsQuery = WastageRecord::where('tenant_id', $tenant->id)
            ->where('status', 'approved');

        $totalLossThisMonth = (clone $metricsQuery)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('total_loss_cost');

        $totalQtyThisMonth = (clone $metricsQuery)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('total_quantity');

        $pendingApprovalsCount = WastageRecord::where('tenant_id', $tenant->id)
            ->where('status', 'pending_approval')
            ->count();

        $branches = Branch::where('tenant_id', $tenant->id)->get();

        return view('tenant.inventory.wastage.index', compact(
            'records',
            'totalLossThisMonth',
            'totalQtyThisMonth',
            'pendingApprovalsCount',
            'branches'
        ));
    }

    /**
     * Show form to create a new wastage voucher.
     */
    public function create(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $user = auth()->user();
        $activeBranchId = $request->get('branch_id', $user->current_branch_id ?? Branch::where('tenant_id', $tenant->id)->value('id'));

        $branches = Branch::where('tenant_id', $tenant->id)->get();
        $products = Product::where('tenant_id', $tenant->id)
            ->with(['unit'])
            ->orderBy('name')
            ->get();

        // Load current branch stock
        $stocks = InventoryStock::where('tenant_id', $tenant->id)
            ->where('branch_id', $activeBranchId)
            ->pluck('quantity', 'product_id');

        // Load active batches
        $batches = InventoryBatch::where('tenant_id', $tenant->id)
            ->where('branch_id', $activeBranchId)
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->get()
            ->groupBy('product_id');

        return view('tenant.inventory.wastage.create', compact('branches', 'products', 'stocks', 'batches', 'activeBranchId'));
    }

    /**
     * Store newly submitted wastage voucher.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        $user = auth()->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date',
            'reason' => 'required|in:spoilage,drying_shrinkage,handling_damage,pest_damage,trimming_cleaning,expired,other',
            'status' => 'nullable|in:approved,pending_approval',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_id' => 'nullable|exists:inventory_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.disposal_method' => 'nullable|in:discarded,donated,composted,salvaged',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        $branch = Branch::where('tenant_id', $tenant->id)->findOrFail($validated['branch_id']);

        $record = $this->wastageService->createWastage($tenant, $branch, $user, $validated);

        return redirect()->route('tenant.inventory.wastage.show', $record->id)
            ->with('success', __('Wastage record :no recorded successfully.', ['no' => $record->wastage_no]));
    }

    /**
     * Show detailed wastage voucher.
     */
    public function show(int $id): View
    {
        $tenant = $this->context->getTenant();
        $record = WastageRecord::where('tenant_id', $tenant->id)
            ->with(['branch', 'creator', 'approver', 'items.product.unit', 'items.batch'])
            ->findOrFail($id);

        return view('tenant.inventory.wastage.show', compact('record'));
    }

    /**
     * Approve pending wastage record.
     */
    public function approve(int $id): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        $record = WastageRecord::where('tenant_id', $tenant->id)->findOrFail($id);

        $this->wastageService->approve($record, auth()->user());

        return redirect()->back()->with('success', __('Wastage record :no approved and stock deducted.', ['no' => $record->wastage_no]));
    }

    /**
     * Reject pending wastage record.
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:255']);
        $tenant = $this->context->getTenant();
        $record = WastageRecord::where('tenant_id', $tenant->id)->findOrFail($id);

        $this->wastageService->reject($record, auth()->user(), $request->rejection_reason);

        return redirect()->back()->with('success', __('Wastage record :no rejected.', ['no' => $record->wastage_no]));
    }

    /**
     * Produce regrading listing.
     */
    public function regradingsIndex(Request $request): View
    {
        $tenant = $this->context->getTenant();
        $regradings = ProduceRegrading::where('tenant_id', $tenant->id)
            ->with(['branch', 'sourceProduct', 'targetProduct', 'creator'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('tenant.inventory.wastage.regradings', compact('regradings'));
    }

    /**
     * Form to record produce sorting & regrading.
     */
    public function regradingCreate(): View
    {
        $tenant = $this->context->getTenant();
        $user = auth()->user();
        $branchId = $user->current_branch_id ?? Branch::where('tenant_id', $tenant->id)->value('id');

        $branches = Branch::where('tenant_id', $tenant->id)->get();
        $products = Product::where('tenant_id', $tenant->id)->with('unit')->orderBy('name')->get();

        return view('tenant.inventory.wastage.regrade_create', compact('branches', 'products', 'branchId'));
    }

    /**
     * Store produce regrading.
     */
    public function regradingStore(Request $request): RedirectResponse
    {
        $tenant = $this->context->getTenant();
        $user = auth()->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date',
            'source_product_id' => 'required|exists:products,id|different:target_product_id',
            'source_quantity' => 'required|numeric|min:0.001',
            'target_product_id' => 'required|exists:products,id',
            'target_quantity' => 'required|numeric|min:0.001|lte:source_quantity',
            'source_unit_cost' => 'nullable|numeric|min:0',
            'target_unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $branch = Branch::where('tenant_id', $tenant->id)->findOrFail($validated['branch_id']);

        $regrading = $this->wastageService->regradeProduce($tenant, $branch, $user, $validated);

        return redirect()->route('tenant.inventory.regradings.index')
            ->with('success', __('Produce regrading :no recorded successfully.', ['no' => $regrading->regrading_no]));
    }
}
