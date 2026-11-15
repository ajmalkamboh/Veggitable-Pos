<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryBatch;
use App\Models\InventoryStock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Display real-time aggregate inventory levels.
     */
    public function index(Request $request): View
    {
        Gate::authorize('inventory.view');

        $tenant = $this->context->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $categories = Category::where('tenant_id', $tenant->id)->where('is_active', true)->get();

        $selectedBranchId = $request->filled('branch_id')
            ? (int) $request->branch_id
            : ($branches->first()->id ?? null);

        $query = InventoryStock::with(['product.category', 'product.stockUnit', 'product.sellingUnit', 'branch'])
            ->where('tenant_id', $tenant->id);

        if ($selectedBranchId) {
            $query->where('branch_id', $selectedBranchId);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->boolean('low_stock_only')) {
            $query->where(function ($q) {
                $q->whereRaw('quantity <= reorder_level')
                    ->orWhereHas('product', function ($pq) {
                        $pq->whereRaw('inventory_stocks.quantity <= products.reorder_level');
                    });
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_ur', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $stocks = $query->orderBy('quantity', 'asc')->paginate(20)->withQueryString();

        // Calculate summary metrics for the active branch
        $summaryQuery = InventoryStock::where('tenant_id', $tenant->id);
        if ($selectedBranchId) {
            $summaryQuery->where('branch_id', $selectedBranchId);
        }

        $totalItems = (clone $summaryQuery)->count();
        $totalValuation = (clone $summaryQuery)->selectRaw('SUM(quantity * cost_price) as total')->value('total') ?? 0.00;
        $lowStockCount = (clone $summaryQuery)->where(function ($q) {
            $q->whereRaw('quantity <= reorder_level')
                ->orWhereHas('product', function ($pq) {
                    $pq->whereRaw('inventory_stocks.quantity <= products.reorder_level');
                });
        })->count();
        $outOfStockCount = (clone $summaryQuery)->where('quantity', '<=', 0)->count();

        return view('tenant.inventory.index', compact(
            'stocks',
            'branches',
            'categories',
            'selectedBranchId',
            'totalItems',
            'totalValuation',
            'lowStockCount',
            'outOfStockCount',
            'tenant'
        ));
    }

    /**
     * Display produce batches / lots for traceability and shelf-life monitoring.
     */
    public function batches(Request $request): View
    {
        Gate::authorize('inventory.view');

        $tenant = $this->context->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)->get();

        $query = InventoryBatch::with(['product.stockUnit', 'branch', 'purchaseInvoice.supplier'])
            ->where('tenant_id', $tenant->id);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'active');
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('name_ur', 'like', "%{$search}%");
                    });
            });
        }

        $batches = $query->orderBy('received_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.inventory.batches', compact('batches', 'branches', 'tenant'));
    }

    /**
     * Display immutable stock ledger / movement history.
     */
    public function movements(Request $request): View
    {
        Gate::authorize('inventory.view');

        $tenant = $this->context->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)->get();

        $query = StockMovement::with(['product.stockUnit', 'branch', 'batch', 'creator'])
            ->where('tenant_id', $tenant->id);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->orderBy('id', 'desc')->paginate(25)->withQueryString();

        return view('tenant.inventory.movements', compact('movements', 'branches', 'tenant'));
    }
}
