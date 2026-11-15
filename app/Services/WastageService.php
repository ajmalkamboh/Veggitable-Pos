<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InventoryBatch;
use App\Models\InventoryStock;
use App\Models\ProduceRegrading;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WastageRecord;
use App\Models\WastageRecordItem;
use Illuminate\Support\Facades\DB;

class WastageService
{
    /**
     * Create and optionally approve a wastage & damage record.
     */
    public function createWastage(Tenant $tenant, Branch $branch, User $user, array $data): WastageRecord
    {
        return DB::transaction(function () use ($tenant, $branch, $user, $data) {
            $prefix = 'WST-'.date('ym');
            $lastRecord = WastageRecord::where('tenant_id', $tenant->id)
                ->where('wastage_no', 'like', "{$prefix}-%")
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = 1;
            if ($lastRecord && preg_match('/-(\d+)$/', $lastRecord->wastage_no, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            }
            $wastageNo = sprintf('%s-%05d', $prefix, $nextNumber);

            $status = $data['status'] ?? 'approved';
            $isApproved = ($status === 'approved');

            $record = WastageRecord::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'wastage_no' => $wastageNo,
                'date' => $data['date'] ?? now()->toDateString(),
                'reason' => $data['reason'] ?? 'spoilage',
                'status' => $status,
                'total_items_count' => count($data['items']),
                'total_quantity' => 0.000,
                'total_loss_cost' => 0.00,
                'approved_by' => $isApproved ? $user->id : null,
                'approved_at' => $isApproved ? now() : null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $totalQty = 0.0;
            $totalCost = 0.0;

            foreach ($data['items'] as $itemData) {
                $product = Product::where('tenant_id', $tenant->id)->findOrFail($itemData['product_id']);
                $qty = (float) $itemData['quantity'];
                if ($qty <= 0) {
                    continue;
                }

                $batch = null;
                if (! empty($itemData['batch_id'])) {
                    $batch = InventoryBatch::where('tenant_id', $tenant->id)->find($itemData['batch_id']);
                }

                $unitCost = (float) ($itemData['unit_cost'] ?? ($batch ? $batch->unit_cost : $product->cost_price));
                $lineTotal = round($qty * $unitCost, 2);

                $totalQty += $qty;
                $totalCost += $lineTotal;

                WastageRecordItem::create([
                    'tenant_id' => $tenant->id,
                    'wastage_record_id' => $record->id,
                    'product_id' => $product->id,
                    'batch_id' => $batch?->id,
                    'quantity' => $qty,
                    'unit_code' => $product->unit?->code ?? 'kg',
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                    'disposal_method' => $itemData['disposal_method'] ?? 'discarded',
                    'notes' => $itemData['notes'] ?? null,
                ]);

                if ($isApproved) {
                    $this->applyWastageStockDeduction($tenant, $branch, $record, $product, $batch, $qty, $unitCost, $user);
                }
            }

            $record->update([
                'total_quantity' => $totalQty,
                'total_loss_cost' => $totalCost,
            ]);

            return $record->load(['items.product', 'branch', 'creator']);
        });
    }

    /**
     * Approve a pending wastage record and deduct stock.
     */
    public function approve(WastageRecord $record, User $approver): WastageRecord
    {
        return DB::transaction(function () use ($record, $approver) {
            if ($record->status !== 'pending_approval') {
                throw new \DomainException(__('Only pending wastage records can be approved.'));
            }

            $record->load(['items.product', 'tenant', 'branch']);
            $tenant = $record->tenant;
            $branch = $record->branch;

            foreach ($record->items as $item) {
                $product = $item->product;
                $batch = $item->batch;
                $this->applyWastageStockDeduction(
                    $tenant,
                    $branch,
                    $record,
                    $product,
                    $batch,
                    (float) $item->quantity,
                    (float) $item->unit_cost,
                    $approver
                );
            }

            $record->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            return $record;
        });
    }

    /**
     * Reject a pending wastage record.
     */
    public function reject(WastageRecord $record, User $rejecter, string $reason): WastageRecord
    {
        if ($record->status !== 'pending_approval') {
            throw new \DomainException(__('Only pending wastage records can be rejected.'));
        }

        $record->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => $rejecter->id,
            'approved_at' => now(),
        ]);

        return $record;
    }

    /**
     * Perform produce regrading (e.g. sorting Grade A into Grade B with moisture / cull shrinkage).
     */
    public function regradeProduce(Tenant $tenant, Branch $branch, User $user, array $data): ProduceRegrading
    {
        return DB::transaction(function () use ($tenant, $branch, $user, $data) {
            $prefix = 'RGD-'.date('ym');
            $lastRegrade = ProduceRegrading::where('tenant_id', $tenant->id)
                ->where('regrading_no', 'like', "{$prefix}-%")
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = 1;
            if ($lastRegrade && preg_match('/-(\d+)$/', $lastRegrade->regrading_no, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            }
            $regradingNo = sprintf('%s-%05d', $prefix, $nextNumber);

            $sourceProduct = Product::where('tenant_id', $tenant->id)->findOrFail($data['source_product_id']);
            $targetProduct = Product::where('tenant_id', $tenant->id)->findOrFail($data['target_product_id']);

            $sourceQty = (float) $data['source_quantity'];
            $targetQty = (float) $data['target_quantity'];
            $wastageQty = max(0.0, round($sourceQty - $targetQty, 3));

            $sourceUnitCost = (float) ($data['source_unit_cost'] ?? $sourceProduct->cost_price);
            $targetUnitCost = (float) ($data['target_unit_cost'] ?? $targetProduct->cost_price);
            $lossCost = round($wastageQty * $sourceUnitCost, 2);

            $regrading = ProduceRegrading::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'regrading_no' => $regradingNo,
                'date' => $data['date'] ?? now()->toDateString(),
                'source_product_id' => $sourceProduct->id,
                'source_quantity' => $sourceQty,
                'target_product_id' => $targetProduct->id,
                'target_quantity' => $targetQty,
                'wastage_quantity' => $wastageQty,
                'source_unit_cost' => $sourceUnitCost,
                'target_unit_cost' => $targetUnitCost,
                'loss_cost' => $lossCost,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // 1. Deduct source product stock
            $sourceStock = InventoryStock::firstOrCreate(
                ['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'product_id' => $sourceProduct->id],
                ['quantity' => 0.000, 'cost_price' => $sourceUnitCost]
            );
            $sourceBefore = (float) $sourceStock->quantity;
            $sourceAfter = max(0.0, $sourceBefore - $sourceQty);
            $sourceStock->update(['quantity' => $sourceAfter]);

            $this->deductFifoBatches($tenant->id, $branch->id, $sourceProduct->id, $sourceQty);

            StockMovement::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'product_id' => $sourceProduct->id,
                'type' => 'adjustment_out',
                'reference_type' => 'produce_regrading',
                'reference_id' => $regrading->id,
                'reference_no' => $regrading->regrading_no,
                'quantity_change' => -$sourceQty,
                'quantity_before' => $sourceBefore,
                'quantity_after' => $sourceAfter,
                'unit_cost' => $sourceUnitCost,
                'notes' => __('Produce Regrading Out to: ').$targetProduct->name,
                'created_by' => $user->id,
            ]);

            // 2. Add target product stock
            $targetStock = InventoryStock::firstOrCreate(
                ['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'product_id' => $targetProduct->id],
                ['quantity' => 0.000, 'cost_price' => $targetUnitCost]
            );
            $targetBefore = (float) $targetStock->quantity;
            $targetAfter = $targetBefore + $targetQty;
            $targetStock->update(['quantity' => $targetAfter, 'cost_price' => $targetUnitCost]);

            // Create new batch for target product
            $targetBatch = InventoryBatch::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'product_id' => $targetProduct->id,
                'lot_number' => 'LOT-RGD-'.strtoupper(substr($targetProduct->code, 0, 4)).'-'.date('dmy-His'),
                'received_date' => now()->toDateString(),
                'expiry_date' => $targetProduct->shelf_life_days ? now()->addDays($targetProduct->shelf_life_days)->toDateString() : null,
                'initial_quantity' => $targetQty,
                'current_quantity' => $targetQty,
                'unit_cost' => $targetUnitCost,
                'status' => 'active',
            ]);

            StockMovement::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'product_id' => $targetProduct->id,
                'batch_id' => $targetBatch->id,
                'type' => 'adjustment_in',
                'reference_type' => 'produce_regrading',
                'reference_id' => $regrading->id,
                'reference_no' => $regrading->regrading_no,
                'quantity_change' => $targetQty,
                'quantity_before' => $targetBefore,
                'quantity_after' => $targetAfter,
                'unit_cost' => $targetUnitCost,
                'notes' => __('Produce Regraded In from: ').$sourceProduct->name,
                'created_by' => $user->id,
            ]);

            return $regrading;
        });
    }

    /**
     * Deduct stock and log movements for approved wastage.
     */
    protected function applyWastageStockDeduction(
        Tenant $tenant,
        Branch $branch,
        WastageRecord $record,
        Product $product,
        ?InventoryBatch $batch,
        float $quantity,
        float $unitCost,
        User $user
    ): void {
        $stock = InventoryStock::firstOrCreate(
            ['tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'product_id' => $product->id],
            ['quantity' => 0.000, 'cost_price' => $unitCost]
        );

        $qtyBefore = (float) $stock->quantity;
        $qtyAfter = max(0.0, $qtyBefore - $quantity);
        $stock->update(['quantity' => $qtyAfter]);

        // If specific batch was assigned, deplete it; otherwise FIFO
        if ($batch) {
            $batchQty = (float) $batch->current_quantity;
            $newBatchQty = max(0.0, $batchQty - $quantity);
            $batch->update([
                'current_quantity' => $newBatchQty,
                'status' => $newBatchQty <= 0 ? 'depleted' : 'active',
            ]);
        } else {
            $this->deductFifoBatches($tenant->id, $branch->id, $product->id, $quantity);
        }

        StockMovement::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'batch_id' => $batch?->id,
            'type' => 'wastage_out',
            'reference_type' => 'wastage_record',
            'reference_id' => $record->id,
            'reference_no' => $record->wastage_no,
            'quantity_change' => -$quantity,
            'quantity_before' => $qtyBefore,
            'quantity_after' => $qtyAfter,
            'unit_cost' => $unitCost,
            'notes' => __('Wastage/Spoilage loss: ').$record->wastage_no.' ('.$record->reason_label.')',
            'created_by' => $user->id,
        ]);
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
