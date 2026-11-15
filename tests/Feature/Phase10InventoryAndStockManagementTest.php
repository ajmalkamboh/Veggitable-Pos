<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\InventoryBatch;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Role;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase10InventoryAndStockManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Branch $branchA1;

    protected Branch $branchA2;

    protected Branch $branchB;

    protected User $ownerA;

    protected User $ownerB;

    protected Product $productTomato;

    protected Product $productPotato;

    protected Unit $unitKg;

    protected Supplier $supplierA;

    protected FinancialAccount $accountA;

    protected TenantContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->context = app(TenantContext::class);
        $this->context->reset();

        $plan = SubscriptionPlan::create([
            'name' => 'Professional Plan',
            'slug' => 'pro',
            'price_monthly' => 5000.00,
            'max_branches' => 10,
            'max_users' => 20,
            'features' => ['inventory_management', 'purchases'],
            'is_active' => true,
        ]);

        // Tenant A
        $this->tenantA = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'onboarding_completed' => true,
            'currency' => 'PKR',
        ]);
        $this->branchA1 = $this->tenantA->branches()->create([
            'name' => 'Badami Bagh Warehouse',
            'code' => 'BR-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);
        $this->branchA2 = $this->tenantA->branches()->create([
            'name' => 'Gulberg Retail Shop',
            'code' => 'BR-02',
            'is_headquarters' => false,
            'is_active' => true,
        ]);
        $this->ownerA = User::create([
            'tenant_id' => $this->tenantA->id,
            'current_branch_id' => $this->branchA1->id,
            'name' => 'Tariq Mehmood',
            'email' => 'tariq@veggitable.test',
            'password' => Hash::make('password123'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        $this->ownerA->branches()->attach([$this->branchA1->id, $this->branchA2->id]);
        $this->ownerA->roles()->attach(Role::where('slug', 'owner')->first()->id);

        $this->accountA = FinancialAccount::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Main Cash Till',
            'account_type' => 'cash',
            'account_number' => 'CASH-001',
            'current_balance' => 50000.00,
            'is_active' => true,
        ]);

        $this->supplierA = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'code' => 'SUP-0001',
            'name' => 'Haji Bashir Commission Shop',
            'phone' => '03001234567',
            'commission_rate' => 5.00,
            'payment_terms_days' => 7,
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'is_active' => true,
        ]);

        $cat = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Vegetables',
            'name_ur' => 'سبزیاں',
            'slug' => 'vegetables',
            'is_active' => true,
        ]);

        $this->unitKg = Unit::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Kilogram',
            'name_ur' => 'کلوگرام',
            'code' => 'kg',
            'short_name' => 'kg',
            'base_unit' => true,
            'operator' => '*',
            'operation_value' => 1.0000,
            'is_active' => true,
        ]);

        $this->productTomato = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'stock_unit_id' => $this->unitKg->id,
            'name' => 'Farm Tomatoes',
            'name_ur' => 'ٹماٹر',
            'code' => 'PRD-TOM-01',
            'barcode' => '2000000001',
            'purchase_price' => 80.00,
            'cost_price' => 80.00,
            'selling_price' => 120.00,
            'reorder_level' => 20.00,
            'is_perishable' => true,
            'shelf_life_days' => 5,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $this->productPotato = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'stock_unit_id' => $this->unitKg->id,
            'name' => 'Red Potatoes',
            'name_ur' => 'لال آلو',
            'code' => 'PRD-POT-01',
            'barcode' => '2000000002',
            'purchase_price' => 50.00,
            'cost_price' => 50.00,
            'selling_price' => 80.00,
            'reorder_level' => 30.00,
            'is_perishable' => false,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        // Tenant B
        $this->tenantB = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'onboarding_completed' => true,
        ]);
        $this->branchB = $this->tenantB->branches()->create([
            'name' => 'Branch B',
            'code' => 'BR-B1',
            'is_headquarters' => true,
            'is_active' => true,
        ]);
        $this->ownerB = User::create([
            'tenant_id' => $this->tenantB->id,
            'current_branch_id' => $this->branchB->id,
            'name' => 'Owner Beta',
            'email' => 'beta@veggitable.test',
            'password' => Hash::make('password123'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        $this->ownerB->branches()->attach($this->branchB->id);
        $this->ownerB->roles()->attach(Role::where('slug', 'owner')->first()->id);
    }

    public function test_purchase_receiving_creates_inventory_stock_batch_and_movement(): void
    {
        $this->context->setTenant($this->tenantA);

        // Receive 100kg gross, 5kg tare = 95kg net tomatoes @ 80.00
        $postData = [
            'supplier_id' => $this->supplierA->id,
            'branch_id' => $this->branchA1->id,
            'date' => now()->toDateString(),
            'commission_rate' => 0.00,
            'mandi_fee' => 0.00,
            'freight_charges' => 0.00,
            'paid_amount' => 0.00,
            'items' => [
                [
                    'product_id' => $this->productTomato->id,
                    'unit_id' => $this->unitKg->id,
                    'quantity' => 10,
                    'gross_weight' => 100.00,
                    'tare_weight' => 5.00,
                    'unit_price' => 80.00,
                    'lot_number' => 'LOT-TOM-001',
                ],
            ],
        ];

        $response = $this->actingAs($this->ownerA)->post(route('tenant.purchases.store'), $postData);
        $response->assertRedirect();

        // 1. Verify aggregate InventoryStock was updated to 95 kg
        $stock = InventoryStock::where('tenant_id', $this->tenantA->id)
            ->where('branch_id', $this->branchA1->id)
            ->where('product_id', $this->productTomato->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(95.00, (float) $stock->quantity);
        $this->assertEquals(80.00, (float) $stock->cost_price);

        // 2. Verify InventoryBatch was created with expiry date (5 days shelf life)
        $batch = InventoryBatch::where('tenant_id', $this->tenantA->id)
            ->where('branch_id', $this->branchA1->id)
            ->where('product_id', $this->productTomato->id)
            ->first();

        $this->assertNotNull($batch);
        $this->assertEquals('LOT-TOM-001', $batch->lot_number);
        $this->assertEquals(95.00, (float) $batch->initial_quantity);
        $this->assertEquals(95.00, (float) $batch->current_quantity);
        $this->assertEquals(80.00, (float) $batch->unit_cost);
        $this->assertEquals('active', $batch->status);
        $this->assertEquals(now()->addDays(5)->toDateString(), $batch->expiry_date->toDateString());

        // 3. Verify StockMovement was logged
        $movement = StockMovement::where('tenant_id', $this->tenantA->id)
            ->where('branch_id', $this->branchA1->id)
            ->where('product_id', $this->productTomato->id)
            ->where('type', 'purchase_in')
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(95.00, (float) $movement->quantity_change);
        $this->assertEquals(0.00, (float) $movement->quantity_before);
        $this->assertEquals(95.00, (float) $movement->quantity_after);
    }

    public function test_cancelling_purchase_reverses_inventory_stock_and_logs_movement(): void
    {
        $this->context->setTenant($this->tenantA);

        $invoice = PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'supplier_id' => $this->supplierA->id,
            'invoice_no' => 'PUR-REV-01',
            'date' => now()->toDateString(),
            'status' => 'received',
            'payment_status' => 'unpaid',
            'subtotal' => 4000.00,
            'total_amount' => 4000.00,
            'paid_amount' => 0.00,
            'created_by' => $this->ownerA->id,
        ]);

        $item = $invoice->items()->create([
            'tenant_id' => $this->tenantA->id,
            'product_id' => $this->productTomato->id,
            'quantity' => 50,
            'unit_price' => 80.00,
            'subtotal' => 4000.00,
            'lot_number' => 'LOT-REV-01',
        ]);

        // Establish initial stock
        app(InventoryService::class)->recordPurchaseIn($invoice);

        $stock = InventoryStock::where('product_id', $this->productTomato->id)->first();
        $this->assertEquals(50.00, (float) $stock->quantity);

        // Cancel invoice
        $response = $this->actingAs($this->ownerA)->delete(route('tenant.purchases.destroy', $invoice));
        $response->assertRedirect(route('tenant.purchases.index'));

        // Stock reversed back to 0
        $stock->refresh();
        $this->assertEquals(0.00, (float) $stock->quantity);

        // Movement logged as adjustment_out
        $reversalMovement = StockMovement::where('product_id', $this->productTomato->id)
            ->where('type', 'adjustment_out')
            ->first();

        $this->assertNotNull($reversalMovement);
        $this->assertEquals(-50.00, (float) $reversalMovement->quantity_change);
    }

    public function test_tenant_can_view_inventory_overview_with_valuation_and_low_stock_alerts(): void
    {
        $this->context->setTenant($this->tenantA);

        // Stock 1: 15kg tomatoes (below reorder level of 20kg -> Low stock!)
        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productTomato->id,
            'quantity' => 15.000,
            'cost_price' => 80.00,
            'reorder_level' => 20.000,
        ]);

        // Stock 2: 100kg potatoes @ 50.00 = 5000.00
        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productPotato->id,
            'quantity' => 100.000,
            'cost_price' => 50.00,
            'reorder_level' => 30.000,
        ]);

        $response = $this->actingAs($this->ownerA)->get(route('tenant.inventory.index'));

        $response->assertStatus(200);
        $response->assertSee('Current Stock Levels &amp; Inventory Valuation', false);
        $response->assertSee('Low Stock Alert!');
        $response->assertSee('Farm Tomatoes');
        $response->assertSee('Red Potatoes');
        // Total valuation: (15 * 80) + (100 * 50) = 1200 + 5000 = 6,200.00
        $response->assertSee('6,200.00');
    }

    public function test_branch_stock_transfer_lifecycle(): void
    {
        $this->context->setTenant($this->tenantA);

        // 1. Initial stock at Warehouse Branch A1 = 100kg
        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productPotato->id,
            'quantity' => 100.000,
            'cost_price' => 50.00,
            'reorder_level' => 30.000,
        ]);

        InventoryBatch::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productPotato->id,
            'lot_number' => 'LOT-POT-TRF-01',
            'received_date' => now()->toDateString(),
            'initial_quantity' => 100.000,
            'current_quantity' => 100.000,
            'unit_cost' => 50.00,
            'status' => 'active',
        ]);

        // 2. Dispatch Transfer of 40kg from Branch A1 to Branch A2
        $transferData = [
            'from_branch_id' => $this->branchA1->id,
            'to_branch_id' => $this->branchA2->id,
            'transfer_date' => now()->toDateString(),
            'vehicle_number' => 'LEA-4589',
            'driver_name' => 'Kashif',
            'driver_phone' => '03009999999',
            'notes' => 'Morning replenishment to Gulberg shop',
            'items' => [
                [
                    'product_id' => $this->productPotato->id,
                    'quantity' => 40.000,
                ],
            ],
        ];

        $responseDispatch = $this->actingAs($this->ownerA)
            ->post(route('tenant.inventory.transfers.store'), $transferData);

        $responseDispatch->assertRedirect();

        $transfer = StockTransfer::where('tenant_id', $this->tenantA->id)->first();
        $this->assertNotNull($transfer);
        $this->assertEquals('in_transit', $transfer->status);

        // Source branch stock decreased to 60 kg
        $sourceStock = InventoryStock::where('branch_id', $this->branchA1->id)
            ->where('product_id', $this->productPotato->id)
            ->first();
        $this->assertEquals(60.00, (float) $sourceStock->quantity);

        // Movement transfer_out logged
        $outMovement = StockMovement::where('branch_id', $this->branchA1->id)
            ->where('type', 'transfer_out')
            ->first();
        $this->assertNotNull($outMovement);
        $this->assertEquals(-40.00, (float) $outMovement->quantity_change);

        // 3. Receive Transfer at Destination Branch A2
        $responseReceive = $this->actingAs($this->ownerA)
            ->post(route('tenant.inventory.transfers.receive', $transfer), [
                'items' => [
                    $transfer->items->first()->id => 40.000,
                ],
            ]);

        $responseReceive->assertRedirect(route('tenant.inventory.transfers.show', $transfer));

        $transfer->refresh();
        $this->assertEquals('received', $transfer->status);

        // Destination branch stock increased to 40 kg
        $destStock = InventoryStock::where('branch_id', $this->branchA2->id)
            ->where('product_id', $this->productPotato->id)
            ->first();
        $this->assertNotNull($destStock);
        $this->assertEquals(40.00, (float) $destStock->quantity);

        // Destination batch created
        $destBatch = InventoryBatch::where('branch_id', $this->branchA2->id)
            ->where('product_id', $this->productPotato->id)
            ->first();
        $this->assertNotNull($destBatch);
        $this->assertEquals(40.00, (float) $destBatch->current_quantity);

        // Movement transfer_in logged
        $inMovement = StockMovement::where('branch_id', $this->branchA2->id)
            ->where('type', 'transfer_in')
            ->first();
        $this->assertNotNull($inMovement);
        $this->assertEquals(40.00, (float) $inMovement->quantity_change);
    }

    public function test_physical_stock_audit_adjustment_updates_stock_and_logs_movements(): void
    {
        $this->context->setTenant($this->tenantA);

        // Current system stock = 50kg
        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productTomato->id,
            'quantity' => 50.000,
            'cost_price' => 80.00,
            'reorder_level' => 20.000,
        ]);

        // Physical count audit: counted = 44.5kg (evaporation / drying shrinkage of 5.5kg)
        $auditData = [
            'branch_id' => $this->branchA1->id,
            'date' => now()->toDateString(),
            'reason' => 'drying_weight_loss',
            'notes' => 'Evaporation during hot afternoon',
            'items' => [
                [
                    'product_id' => $this->productTomato->id,
                    'counted_quantity' => 44.500,
                ],
            ],
        ];

        $response = $this->actingAs($this->ownerA)
            ->post(route('tenant.inventory.adjustments.store'), $auditData);

        $response->assertRedirect();

        $adjustment = StockAdjustment::where('tenant_id', $this->tenantA->id)->first();
        $this->assertNotNull($adjustment);
        $this->assertEquals('drying_weight_loss', $adjustment->reason);

        // Stock quantity updated to 44.5 kg
        $stock = InventoryStock::where('product_id', $this->productTomato->id)->first();
        $this->assertEquals(44.500, (float) $stock->quantity);

        // Audit adjustment movement logged
        $movement = StockMovement::where('type', 'adjustment_out')
            ->where('reference_type', 'stock_adjustment')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(-5.500, (float) $movement->quantity_change);
        $this->assertEquals(50.000, (float) $movement->quantity_before);
        $this->assertEquals(44.500, (float) $movement->quantity_after);
    }

    public function test_fifo_batch_deduction_order(): void
    {
        $this->context->setTenant($this->tenantA);

        // Batch 1: Received Day 1 (30kg)
        $batch1 = InventoryBatch::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productTomato->id,
            'lot_number' => 'LOT-DAY-1',
            'received_date' => now()->subDays(2)->toDateString(),
            'initial_quantity' => 30.000,
            'current_quantity' => 30.000,
            'unit_cost' => 80.00,
            'status' => 'active',
        ]);

        // Batch 2: Received Day 2 (50kg)
        $batch2 = InventoryBatch::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productTomato->id,
            'lot_number' => 'LOT-DAY-2',
            'received_date' => now()->subDay()->toDateString(),
            'initial_quantity' => 50.000,
            'current_quantity' => 50.000,
            'unit_cost' => 85.00,
            'status' => 'active',
        ]);

        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productTomato->id,
            'quantity' => 80.000,
            'cost_price' => 80.00,
        ]);

        // Dispatch transfer of 40kg -> Should consume all 30kg of Batch 1 and 10kg of Batch 2
        $transfer = StockTransfer::create([
            'tenant_id' => $this->tenantA->id,
            'transfer_no' => 'TRF-FIFO-01',
            'from_branch_id' => $this->branchA1->id,
            'to_branch_id' => $this->branchA2->id,
            'transfer_date' => now()->toDateString(),
            'status' => 'pending',
            'created_by' => $this->ownerA->id,
        ]);

        $transfer->items()->create([
            'tenant_id' => $this->tenantA->id,
            'product_id' => $this->productTomato->id,
            'quantity_dispatched' => 40.000,
            'unit_cost' => 80.00,
        ]);

        app(InventoryService::class)->dispatchTransfer($transfer);

        $batch1->refresh();
        $this->assertEquals(0.000, (float) $batch1->current_quantity);
        $this->assertEquals('depleted', $batch1->status);

        $batch2->refresh();
        $this->assertEquals(40.000, (float) $batch2->current_quantity);
        $this->assertEquals('active', $batch2->status);
    }

    public function test_tenant_isolation_protects_inventory_and_transfers(): void
    {
        $this->context->setTenant($this->tenantA);

        $transferA = StockTransfer::create([
            'tenant_id' => $this->tenantA->id,
            'transfer_no' => 'TRF-ISO-01',
            'from_branch_id' => $this->branchA1->id,
            'to_branch_id' => $this->branchA2->id,
            'transfer_date' => now()->toDateString(),
            'status' => 'in_transit',
            'created_by' => $this->ownerA->id,
        ]);

        $adjustmentA = StockAdjustment::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'adjustment_no' => 'ADJ-ISO-01',
            'date' => now()->toDateString(),
            'reason' => 'physical_count_discrepancy',
            'created_by' => $this->ownerA->id,
        ]);

        // Attempt by Owner B to access Tenant A's records
        $this->context->setTenant($this->tenantB);

        $responseTransfer = $this->actingAs($this->ownerB)->get(route('tenant.inventory.transfers.show', $transferA));
        $this->assertContains($responseTransfer->status(), [403, 404]);

        $responseAdjustment = $this->actingAs($this->ownerB)->get(route('tenant.inventory.adjustments.show', $adjustmentA));
        $this->assertContains($responseAdjustment->status(), [403, 404]);
    }
}
