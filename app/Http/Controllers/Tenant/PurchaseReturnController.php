<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PartyLedgerEntry;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PurchaseReturnController extends Controller
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Store a purchase return (Debit Note).
     */
    public function store(Request $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        Gate::authorize('purchases.manage');

        $tenant = $this->context->getTenant();
        abort_if((int) $purchaseInvoice->tenant_id !== (int) $tenant->id, 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($tenant, $purchaseInvoice, $validated) {
            $supplier = $purchaseInvoice->supplier;
            $returnNo = PurchaseReturn::generateReturnNumber($tenant);

            $totalReturnAmount = 0.00;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $subtotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $totalReturnAmount += $subtotal;

                $itemsData[] = [
                    'tenant_id' => $tenant->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 1. Create Purchase Return
            $return = PurchaseReturn::create([
                'tenant_id' => $tenant->id,
                'purchase_invoice_id' => $purchaseInvoice->id,
                'supplier_id' => $supplier->id,
                'return_no' => $returnNo,
                'date' => $validated['date'],
                'reason' => $validated['reason'],
                'total_amount' => $totalReturnAmount,
                'refund_status' => 'adjusted',
                'created_by' => auth()->id(),
            ]);

            foreach ($itemsData as &$itemData) {
                $itemData['purchase_return_id'] = $return->id;
            }
            PurchaseReturnItem::insert($itemsData);

            // 2. Reduce supplier balance (debit supplier)
            $newSupplierBalance = $supplier->current_balance - $totalReturnAmount;
            $supplier->update(['current_balance' => $newSupplierBalance]);

            // 3. Create ledger entry
            PartyLedgerEntry::create([
                'tenant_id' => $tenant->id,
                'party_type' => 'supplier',
                'party_id' => $supplier->id,
                'date' => $validated['date'],
                'reference_type' => 'purchase_return',
                'reference_id' => $return->id,
                'reference_no' => $return->return_no,
                'description' => __('Debit Note (Return): ').$return->return_no.' - '.$validated['reason'],
                'debit' => $totalReturnAmount,
                'credit' => 0.00,
                'balance' => $newSupplierBalance,
                'created_by' => auth()->id(),
            ]);

            AuditLog::log(
                event: 'purchase.returned',
                action: "Created purchase return '{$return->return_no}' for invoice '{$purchaseInvoice->invoice_no}' amounting to {$tenant->currency} {$totalReturnAmount}",
                auditable: $return
            );

            return redirect()->route('tenant.purchases.show', $purchaseInvoice)
                ->with('success', __('Debit note recorded and supplier balance adjusted successfully.'));
        });
    }
}
