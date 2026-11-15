<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\HeldSalesTicket;
use App\Models\InventoryBatch;
use App\Models\InventoryStock;
use App\Models\PartyLedgerEntry;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosService
{
    /**
     * Process POS sale checkout.
     */
    public function checkout(Tenant $tenant, Branch $branch, array $validated, ?User $user): SalesInvoice
    {
        return DB::transaction(function () use ($tenant, $branch, $validated, $user) {
            $customer = null;
            if (! empty($validated['customer_id'])) {
                $customer = Customer::where('tenant_id', $tenant->id)->findOrFail($validated['customer_id']);
            }

            // 1. Calculate Produce Subtotal, Line Items, and COGS from FIFO Batches
            $subtotal = 0.00;
            $totalCost = 0.00;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $product = Product::where('tenant_id', $tenant->id)->findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $itemDiscount = (float) ($item['discount_amount'] ?? 0.00);
                $lineSubtotal = round(max(0, ($qty * $unitPrice) - $itemDiscount), 2);
                $subtotal += $lineSubtotal;

                // FIFO Batch Cost Allocation
                $unitCost = $this->deductFifoBatchAndGetCost($tenant->id, $branch->id, $product->id, $qty);
                $lineCost = round($qty * $unitCost, 2);
                $totalCost += $lineCost;

                // Deduct from aggregate branch stock
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

                $qtyBefore = (float) $stock->quantity;
                $qtyAfter = $qtyBefore - $qty;
                $stock->update(['quantity' => $qtyAfter]);

                $itemsData[] = [
                    'tenant_id' => $tenant->id,
                    'product_id' => $product->id,
                    'unit_id' => $item['unit_id'] ?? $product->stock_unit_id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                    'discount_amount' => $itemDiscount,
                    'subtotal' => $lineSubtotal,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                ];
            }

            // 2. Discounts, Rounding & Grand Total
            $discountAmount = (float) ($validated['discount_amount'] ?? 0.00);
            $taxAmount = (float) ($validated['tax_amount'] ?? 0.00);
            $roundingAmount = (float) ($validated['rounding_amount'] ?? 0.00);

            $grandTotal = round(max(0, $subtotal - $discountAmount + $taxAmount + $roundingAmount), 2);

            $paidAmount = (float) ($validated['paid_amount'] ?? $grandTotal);
            $changeAmount = max(0, $paidAmount - $grandTotal);
            $actualPaid = min($grandTotal, $paidAmount);
            $dueAmount = max(0, $grandTotal - $actualPaid);

            // 3. Payment Method & Credit Verification
            $paymentMethod = $validated['payment_method'] ?? 'cash';

            if ($paymentMethod === 'credit' || $dueAmount > 0) {
                if (! $customer) {
                    throw ValidationException::withMessages([
                        'customer_id' => [__('A registered customer account is required for credit sales.')],
                    ]);
                }

                // Check credit limit
                if ($customer->credit_limit > 0 && ($customer->current_balance + $dueAmount) > $customer->credit_limit) {
                    throw ValidationException::withMessages([
                        'customer_id' => [__('Credit limit exceeded. Customer credit limit is :currency :limit, current balance is :currency :current.', [
                            'currency' => $tenant->currency,
                            'limit' => number_format($customer->credit_limit, 2),
                            'current' => number_format($customer->current_balance, 2),
                        ])],
                    ]);
                }
            }

            $paymentStatus = 'paid';
            if ($dueAmount > 0 && $actualPaid > 0) {
                $paymentStatus = 'partial';
            } elseif ($dueAmount >= $grandTotal && $grandTotal > 0) {
                $paymentStatus = 'unpaid';
            }

            // Find active POS session
            $activeSession = PosSession::where('tenant_id', $tenant->id)
                ->where('branch_id', $branch->id)
                ->where('status', 'open')
                ->latest()
                ->first();

            $invoiceNo = SalesInvoice::generateInvoiceNumber($tenant);

            // 4. Create Sales Invoice Header
            $invoice = SalesInvoice::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'pos_session_id' => $activeSession?->id,
                'customer_id' => $customer?->id,
                'invoice_no' => $invoiceNo,
                'date' => $validated['date'] ?? now()->toDateString(),
                'status' => 'completed',
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_type' => $validated['discount_type'] ?? 'flat',
                'discount_rate' => (float) ($validated['discount_rate'] ?? 0.00),
                'discount_amount' => $discountAmount,
                'rounding_amount' => $roundingAmount,
                'total_amount' => $grandTotal,
                'paid_amount' => $actualPaid,
                'change_amount' => $changeAmount,
                'cost_amount' => $totalCost,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user?->id ?? auth()->id(),
            ]);

            // 5. Create Line Items & Log Stock Movements
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
                    'notes' => __('POS Sale Checkout: ').$invoice->invoice_no,
                    'created_by' => $user?->id ?? auth()->id(),
                ]);
            }

            // 6. Handle Customer Credit Ledger Entry
            if ($customer && $dueAmount > 0) {
                $newCustomerBalance = $customer->current_balance + $dueAmount;
                $customer->update(['current_balance' => $newCustomerBalance]);

                PartyLedgerEntry::create([
                    'tenant_id' => $tenant->id,
                    'party_type' => 'customer',
                    'party_id' => $customer->id,
                    'date' => $invoice->date,
                    'reference_type' => 'sale',
                    'reference_id' => $invoice->id,
                    'reference_no' => $invoice->invoice_no,
                    'description' => __('POS Credit Sale: ').$invoice->invoice_no,
                    'debit' => $dueAmount,
                    'credit' => 0.00,
                    'balance' => $newCustomerBalance,
                    'created_by' => $user?->id ?? auth()->id(),
                ]);
            }

            // 7. If paid into financial account (cash or bank)
            if ($actualPaid > 0 && ! empty($validated['financial_account_id'])) {
                $account = FinancialAccount::where('tenant_id', $tenant->id)->find($validated['financial_account_id']);
                if ($account) {
                    $account->increment('current_balance', $actualPaid);
                }
            }

            // 8. If held ticket was resumed, delete it
            if (! empty($validated['held_ticket_id'])) {
                HeldSalesTicket::where('tenant_id', $tenant->id)
                    ->where('id', $validated['held_ticket_id'])
                    ->delete();
            }

            AuditLog::log(
                event: 'pos.sale_completed',
                action: "Completed sale '{$invoice->invoice_no}' for {$tenant->currency} {$grandTotal} ({$paymentMethod})",
                auditable: $invoice
            );

            return $invoice;
        });
    }

    /**
     * Park / Hold cart ticket for later resumption.
     */
    public function holdCart(Tenant $tenant, Branch $branch, array $validated, User $user): HeldSalesTicket
    {
        $ticketCount = HeldSalesTicket::where('tenant_id', $tenant->id)
            ->where('branch_id', $branch->id)
            ->count() + 1;

        $ticketNo = 'HOLD-'.sprintf('%02d', $ticketCount);

        return HeldSalesTicket::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'ticket_number' => $ticketNo,
            'customer_id' => $validated['customer_id'] ?? null,
            'cart_data' => $validated['cart_data'],
            'total_amount' => (float) ($validated['total_amount'] ?? 0.00),
        ]);
    }

    /**
     * Process sales return & refund.
     */
    public function returnSale(Tenant $tenant, SalesInvoice $invoice, array $validated, User $user): SalesReturn
    {
        return DB::transaction(function () use ($tenant, $invoice, $validated, $user) {
            $branch = $invoice->branch;
            $returnNo = SalesReturn::generateReturnNumber($tenant);
            $totalRefund = 0.00;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $product = Product::where('tenant_id', $tenant->id)->findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $subtotal = round($qty * $price, 2);
                $totalRefund += $subtotal;

                // Restock back into aggregate inventory
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

                $qtyBefore = (float) $stock->quantity;
                $qtyAfter = $qtyBefore + $qty;
                $stock->update(['quantity' => $qtyAfter]);

                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                ];
            }

            $salesReturn = SalesReturn::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'sales_invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'return_no' => $returnNo,
                'date' => $validated['date'] ?? now()->toDateString(),
                'total_amount' => $totalRefund,
                'refund_method' => $validated['refund_method'] ?? 'cash',
                'reason' => $validated['reason'],
                'created_by' => $user->id,
            ]);

            foreach ($itemsData as $itemRow) {
                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'tenant_id' => $tenant->id,
                    'product_id' => $itemRow['product_id'],
                    'quantity' => $itemRow['quantity'],
                    'unit_price' => $itemRow['unit_price'],
                    'subtotal' => $itemRow['subtotal'],
                ]);

                StockMovement::create([
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branch->id,
                    'product_id' => $itemRow['product_id'],
                    'type' => 'adjustment_in',
                    'reference_type' => 'sales_return',
                    'reference_id' => $salesReturn->id,
                    'reference_no' => $salesReturn->return_no,
                    'quantity_change' => $itemRow['quantity'],
                    'quantity_before' => $itemRow['qty_before'],
                    'quantity_after' => $itemRow['qty_after'],
                    'unit_cost' => $itemRow['unit_price'],
                    'notes' => __('Sales Return Refund: ').$salesReturn->return_no,
                    'created_by' => $user->id,
                ]);
            }

            // Customer credit adjustment or cash refund
            if ($invoice->customer && $salesReturn->refund_method === 'credit_adjustment') {
                $newCustomerBalance = max(0, $invoice->customer->current_balance - $totalRefund);
                $invoice->customer->update(['current_balance' => $newCustomerBalance]);

                PartyLedgerEntry::create([
                    'tenant_id' => $tenant->id,
                    'party_type' => 'customer',
                    'party_id' => $invoice->customer->id,
                    'date' => $salesReturn->date,
                    'reference_type' => 'receipt',
                    'reference_id' => $salesReturn->id,
                    'reference_no' => $salesReturn->return_no,
                    'description' => __('Sales Return Credit Adjustment: ').$salesReturn->return_no,
                    'debit' => 0.00,
                    'credit' => $totalRefund,
                    'balance' => $newCustomerBalance,
                    'created_by' => $user->id,
                ]);
            } elseif ($salesReturn->refund_method === 'cash' && ! empty($validated['financial_account_id'])) {
                $account = FinancialAccount::where('tenant_id', $tenant->id)->find($validated['financial_account_id']);
                if ($account) {
                    $account->decrement('current_balance', $totalRefund);
                }
            }

            AuditLog::log(
                event: 'pos.sale_returned',
                action: "Processed sales return '{$salesReturn->return_no}' for invoice '{$invoice->invoice_no}' amounting to {$tenant->currency} {$totalRefund}",
                auditable: $salesReturn
            );

            return $salesReturn;
        });
    }

    /**
     * Deduct from active produce lots following FIFO and return weighted unit cost.
     */
    protected function deductFifoBatchAndGetCost(int $tenantId, int $branchId, int $productId, float $quantity): float
    {
        $remaining = $quantity;
        $totalCost = 0.00;
        $totalDeducted = 0.00;

        $batches = InventoryBatch::where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->orderBy('received_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $currentBatchQty = (float) $batch->current_quantity;
            $deductQty = min($currentBatchQty, $remaining);

            $totalCost += ($deductQty * (float) $batch->unit_cost);
            $totalDeducted += $deductQty;

            if ($currentBatchQty <= $deductQty) {
                $batch->update([
                    'current_quantity' => 0.000,
                    'status' => 'depleted',
                ]);
            } else {
                $batch->update([
                    'current_quantity' => $currentBatchQty - $deductQty,
                ]);
            }

            $remaining -= $deductQty;
        }

        // If no batches exist or ran out, fallback to product cost_price
        if ($totalDeducted < $quantity) {
            $product = Product::find($productId);
            $missingQty = $quantity - $totalDeducted;
            $fallbackCost = (float) ($product->cost_price ?? 0.00);
            $totalCost += ($missingQty * $fallbackCost);
        }

        return $quantity > 0 ? round($totalCost / $quantity, 2) : 0.00;
    }
}
