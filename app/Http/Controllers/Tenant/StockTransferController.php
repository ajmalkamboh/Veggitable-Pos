<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function __construct(
        protected TenantContext $context,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display list of stock transfers.
     */
    public function index(Request $request): View
    {
        Gate::authorize('inventory.view');

        $tenant = $this->context->getTenant();

        $query = StockTransfer::with(['fromBranch', 'toBranch', 'creator'])
            ->where('tenant_id', $tenant->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('transfer_no', 'like', "%{$search}%");
        }

        $transfers = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('tenant.inventory.transfers.index', compact('transfers', 'tenant'));
    }

    /**
     * Show form to create a new stock transfer.
     */
    public function create(): View
    {
        Gate::authorize('inventory.manage');

        $tenant = $this->context->getTenant();
        $branches = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        $products = Product::where('tenant_id', $tenant->id)->where('is_active', true)->with('stockUnit')->get();

        return view('tenant.inventory.transfers.create', compact('branches', 'products', 'tenant'));
    }

    /**
     * Store and dispatch stock transfer.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('inventory.manage');

        $tenant = $this->context->getTenant();

        $validated = $request->validate([
            'from_branch_id' => ['required', 'exists:branches,id'],
            'to_branch_id' => ['required', 'exists:branches,id', 'different:from_branch_id'],
            'transfer_date' => ['required', 'date'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'driver_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        return DB::transaction(function () use ($tenant, $validated) {
            $transferNo = StockTransfer::generateTransferNumber($tenant);

            $transfer = StockTransfer::create([
                'tenant_id' => $tenant->id,
                'transfer_no' => $transferNo,
                'from_branch_id' => $validated['from_branch_id'],
                'to_branch_id' => $validated['to_branch_id'],
                'transfer_date' => $validated['transfer_date'],
                'vehicle_number' => $validated['vehicle_number'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'driver_phone' => $validated['driver_phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::where('tenant_id', $tenant->id)->findOrFail($item['product_id']);
                $stock = InventoryStock::where('tenant_id', $tenant->id)
                    ->where('branch_id', $validated['from_branch_id'])
                    ->where('product_id', $product->id)
                    ->first();

                $unitCost = $stock ? (float) $stock->cost_price : (float) $product->cost_price;

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'tenant_id' => $tenant->id,
                    'product_id' => $product->id,
                    'quantity_dispatched' => $item['quantity'],
                    'unit_cost' => $unitCost,
                ]);
            }

            // Dispatch immediately: deduct from source branch and set status to 'in_transit'
            $this->inventoryService->dispatchTransfer($transfer);

            AuditLog::log(
                event: 'inventory.transfer_dispatched',
                action: "Dispatched stock transfer '{$transfer->transfer_no}' from branch #{$transfer->from_branch_id} to branch #{$transfer->to_branch_id}",
                auditable: $transfer
            );

            return redirect()->route('tenant.inventory.transfers.show', $transfer)
                ->with('success', __('Stock transfer dispatched successfully and marked in transit.'));
        });
    }

    /**
     * Show transfer details.
     */
    public function show(StockTransfer $stockTransfer): View
    {
        Gate::authorize('inventory.view');

        $tenant = $this->context->getTenant();
        abort_if((int) $stockTransfer->tenant_id !== (int) $tenant->id, 404);

        $stockTransfer->load(['fromBranch', 'toBranch', 'items.product.stockUnit', 'creator', 'dispatcher', 'receiver']);

        return view('tenant.inventory.transfers.show', compact('stockTransfer', 'tenant'));
    }

    /**
     * Receive dispatched transfer at destination branch.
     */
    public function receive(Request $request, StockTransfer $stockTransfer): RedirectResponse
    {
        Gate::authorize('inventory.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $stockTransfer->tenant_id !== (int) $tenant->id, 404);

        if ($stockTransfer->status !== 'in_transit') {
            return back()->with('error', __('Transfer must be in transit to receive.'));
        }

        $validated = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($stockTransfer, $validated) {
            $this->inventoryService->receiveTransfer($stockTransfer, $validated['items'] ?? []);

            AuditLog::log(
                event: 'inventory.transfer_received',
                action: "Received stock transfer '{$stockTransfer->transfer_no}' at destination branch",
                auditable: $stockTransfer
            );

            return redirect()->route('tenant.inventory.transfers.show', $stockTransfer)
                ->with('success', __('Stock transfer received and destination branch inventory replenished.'));
        });
    }
}
