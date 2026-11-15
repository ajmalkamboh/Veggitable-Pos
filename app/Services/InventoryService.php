<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Inward stock from received purchase invoice.
     */
    public function recordPurchaseIn(PurchaseInvoice $invoice): void
    {
        $invoice->load(['items.product', 'tenant']);
        $tenant = $invoice->tenant;
        $branchId = $invoice->branch_id;

        foreach ($invoice->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            // Quantity to stock: net_weight if scale weighed, else quantity
            $stockQty = (float) ($item->net_weight !== null && $item->net_weight > 0 ? $item->net_weight : $item->quantity);
            $unitCost = (float) $item->unit_price;

            // Calculate expiry date if product has perishable shelf life
            $expiryDate = null;
            if ($product->is_perishable && $product->shelf_life_days > 0) {
                $expiryDate = $invoice->date->copy()->addDays($product->shelf_life_days);
            }

            // 1. Create produce lot batch
            $batch = InventoryBatch::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'purchase_invoice_id' => $invoice->id,
                'purchase_invoice_item_id' => $item->id,
                'lot_number' => $item->lot_number ?? 'LOT-'.now()->format('ymd').'-'.rand(100, 999),
                'received_date' => $invoice->date,
                'expiry_date' => $expiryDate,
                'initial_quantity' => $stockQty,
                'current_quantity' => $stockQty,
                'unit_cost' => $unitCost,
                'status' => 'active',
            ]);

            // 2. Update aggregate branch inventory stock
            $stock = InventoryStock::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => 0.000,
                    'cost_price' => $unitCost,
                    'reorder_level' => $product->reorder_level ?? 0.000,
                ]
            );

            $qtyBefore = (float) $stock->quantity;
            $qtyAfter = $qtyBefore + $stockQty;

            $stock->update([
                'quantity' => $qtyAfter,
                'cost_price' => $unitCost,
                'last_restocked_at' => now(),
            ]);

            // 3. Log immutable stock movement
            StockMovement::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'type' => 'purchase_in',
                'reference_type' => 'purchase_invoice',
                'reference_id' => $invoice->id,
                'reference_no' => $invoice->invoice_no,
                'quantity_change' => $stockQty,
                'quantity_before' => $qtyBefore,
                'quantity_after' => $qtyAfter,
                'unit_cost' => $unitCost,
                'notes' => __('Mandi Purchase Received: ').$invoice->invoice_no,
                'created_by' => $invoice->created_by,
            ]);
        }
    }

    /**
     * Reverse stock entries on invoice cancellation.
     */
    public function reversePurchaseIn(PurchaseInvoice $invoice): void
    {
        $invoice->load(['items.product', 'tenant']);
        $tenant = $invoice->tenant;
        $branchId = $invoice->branch_id;

        foreach ($invoice->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $stockQty = (float) ($item->net_weight !== null && $item->net_weight > 0 ? $item->net_weight : $item->quantity);

            $stock = InventoryStock::where('tenant_id', $tenant->id)
                ->where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->first();

            if ($stock) {
                $qtyBefore = (float) $stock->quantity;
                $qtyAfter = max(0, $qtyBefore - $stockQty);
                $stock->update(['quantity' => $qtyAfter]);

                // Soft-delete or mark batch depleted
                InventoryBatch::where('tenant_id', $tenant->id)
                    ->where('purchase_invoice_item_id', $item->id)
                    ->update(['status' => 'cancelled']);

                StockMovement::create([
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'type' => 'adjustment_out',
                    'reference_type' => 'purchase_invoice',
                    'reference_id' => $invoice->id,
                    'reference_no' => 'CNCL-'.$invoice->invoice_no,
                    'quantity_change' => -$stockQty,
                    'quantity_before' => $qtyBefore,
                    'quantity_after' => $qtyAfter,
                    'unit_cost' => (float) $item->unit_price,
                    'notes' => __('Cancelled purchase invoice: ').$invoice->invoice_no,
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Outward stock for debit notes / purchase returns.
     */
    public function recordPurchaseReturn(PurchaseReturn $return): void
    {
        $return->load(['items.product', 'purchaseInvoice', 'tenant']);
        $tenant = $return->tenant;
        $invoice = $return->purchaseInvoice;
        $branchId = $invoice->branch_id;

        foreach ($return->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $qty = (float) $item->quantity;

            $stock = InventoryStock::where('tenant_id', $tenant->id)
                ->where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->first();

            if ($stock) {
                $qtyBefore = (float) $stock->quantity;
                $qtyAfter = max(0, $qtyBefore - $qty);
                $stock->update(['quantity' => $qtyAfter]);

                StockMovement::create([
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'type' => 'adjustment_out',
                    'reference_type' => 'purchase_return',
                    'reference_id' => $return->id,
                    'reference_no' => $return->return_no,
                    'quantity_change' => -$qty,
                    'quantity_before' => $qtyBefore,
                    'quantity_after' => $qtyAfter,
                    'unit_cost' => (float) $item->unit_price,
                    'notes' => __('Debit note return: ').$return->return_no.' - '.$return->reason,
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Dispatch inter-branch stock transfer.
     */
    public function dispatchTransfer(StockTransfer $transfer): void
    {
        $tenant = $transfer->tenant;
        $fromBranchId = $transfer->from_branch_id;

        foreach ($transfer->items as $item) {
            $product = $item->product;
            $qty = (float) $item->quantity_dispatched;

            $stock = InventoryStock::where('tenant_id', $tenant->id)
                ->where('branch_id', $fromBranchId)
                ->where('product_id', $product->id)
                ->first();

            $qtyBefore = $stock ? (float) $stock->quantity : 0.000;
            $qtyAfter = max(0, $qtyBefore - $qty);

            if ($stock) {
                $stock->update(['quantity' => $qtyAfter]);
            }

            // Deduct from batches using FIFO
            $this->deductFifoBatches($tenant->id, $fromBranchId, $product->id, $qty);

            StockMovement::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $fromBranchId,
                'product_id' => $product->id,
                'type' => 'transfer_out',
                'reference_type' => 'stock_transfer',
                'reference_id' => $transfer->id,
                'reference_no' => $transfer->transfer_no,
                'quantity_change' => -$qty,
                'quantity_before' => $qtyBefore,
                'quantity_after' => $qtyAfter,
                'unit_cost' => (float) $item->unit_cost,
                'notes' => __('Dispatched transfer to branch #').$transfer->to_branch_id,
                'created_by' => auth()->id(),
            ]);
        }

        $transfer->update([
            'status' => 'in_transit',
            'dispatched_at' => now(),
            'dispatched_by' => auth()->id(),
        ]);
    }

    /**
     * Receive inter-branch stock transfer at destination branch.
     */
    public function receiveTransfer(StockTransfer $transfer, array $receivedQuantities = []): void
    {
        $tenant = $transfer->tenant;
        $toBranchId = $transfer->to_branch_id;

        foreach ($transfer->items as $item) {
            $product = $item->product;
            $receivedQty = isset($receivedQuantities[$item->id])
                ? (float) $receivedQuantities[$item->id]
                : (float) $item->quantity_dispatched;

            $item->update(['quantity_received' => $receivedQty]);

            // 1. Add destination branch stock
            $stock = InventoryStock::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'branch_id' => $toBranchId,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => 0.000,
                    'cost_price' => (float) $item->unit_cost,
                    'reorder_level' => $product->reorder_level ?? 0.000,
                ]
            );

            $qtyBefore = (float) $stock->quantity;
            $qtyAfter = $qtyBefore + $receivedQty;

            $stock->update([
                'quantity' => $qtyAfter,
                'cost_price' => (float) $item->unit_cost,
                'last_restocked_at' => now(),
            ]);

            // 2. Create destination lot batch
            $batch = InventoryBatch::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $toBranchId,
                'product_id' => $product->id,
                'lot_number' => 'LOT-TRF-'.now()->format('ymd').'-'.rand(100, 999),
                'received_date' => now()->toDateString(),
                'initial_quantity' => $receivedQty,
                'current_quantity' => $receivedQty,
                'unit_cost' => (float) $item->unit_cost,
                'status' => 'active',
            ]);

            // 3. Log movement
            StockMovement::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $toBranchId,
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'type' => 'transfer_in',
                'reference_type' => 'stock_transfer',
                'reference_id' => $transfer->id,
                'reference_no' => $transfer->transfer_no,
                'quantity_change' => $receivedQty,
                'quantity_before' => $qtyBefore,
                'quantity_after' => $qtyAfter,
                'unit_cost' => (float) $item->unit_cost,
                'notes' => __('Received transfer from branch #').$transfer->from_branch_id,
                'created_by' => auth()->id(),
            ]);
        }

        $transfer->update([
            'status' => 'received',
            'received_at' => now(),
            'received_by' => auth()->id(),
        ]);
    }

    /**
     * Process physical inventory reconciliation adjustment.
     */
    public function recordAdjustment(Tenant $tenant, Branch $branch, array $validated): StockAdjustment
    {
        return DB::transaction(function () use ($tenant, $branch, $validated) {
            $adjustmentNo = StockAdjustment::generateAdjustmentNumber($tenant);

            $adjustment = StockAdjustment::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'adjustment_no' => $adjustmentNo,
                'date' => $validated['date'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                $product = Product::where('tenant_id', $tenant->id)->findOrFail($itemData['product_id']);

                $stock = InventoryStock::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'branch_id' => $branch->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => 0.000,
                        'cost_price' => (float) $product->cost_price,
                        'reorder_level' => (float) ($product->reorder_level ?? 0),
                    ]
                );

                $currentQty = (float) $stock->quantity;
                $countedQty = (float) $itemData['counted_quantity'];
                $diffQty = round($countedQty - $currentQty, 3);
                $unitCost = (float) ($stock->cost_price > 0 ? $stock->cost_price : $product->cost_price);

                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'tenant_id' => $tenant->id,
                    'product_id' => $product->id,
                    'current_quantity' => $currentQty,
                    'counted_quantity' => $countedQty,
                    'difference_quantity' => $diffQty,
                    'unit_cost' => $unitCost,
                ]);

                // Update real-time aggregate stock
                $stock->update(['quantity' => $countedQty]);

                if ($diffQty !== 0.0) {
                    $type = $diffQty > 0 ? 'adjustment_in' : 'adjustment_out';

                    StockMovement::create([
                        'tenant_id' => $tenant->id,
                        'branch_id' => $branch->id,
                        'product_id' => $product->id,
                        'type' => $type,
                        'reference_type' => 'stock_adjustment',
                        'reference_id' => $adjustment->id,
                        'reference_no' => $adjustment->adjustment_no,
                        'quantity_change' => $diffQty,
                        'quantity_before' => $currentQty,
                        'quantity_after' => $countedQty,
                        'unit_cost' => $unitCost,
                        'notes' => __('Physical count adjustment: ').$adjustment->adjustment_no.' ('.$validated['reason'].')',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            return $adjustment;
        });
    }

    /**
     * Deduct quantity from produce lots following First-In, First-Out (FIFO).
     */
    protected function deductFifoBatches(int $tenantId, int $branchId, int $productId, float $quantity): void
    {
        $remainingToDeduct = $quantity;

        $batches = InventoryBatch::where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->orderBy('received_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($batches as $batch) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $currentBatchQty = (float) $batch->current_quantity;

            if ($currentBatchQty <= $remainingToDeduct) {
                $batch->update([
                    'current_quantity' => 0.000,
                    'status' => 'depleted',
                ]);
                $remainingToDeduct -= $currentBatchQty;
            } else {
                $batch->update([
                    'current_quantity' => $currentBatchQty - $remainingToDeduct,
                ]);
                $remainingToDeduct = 0.0;
            }
        }
    }
}
