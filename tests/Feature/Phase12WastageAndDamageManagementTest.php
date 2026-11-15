<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryBatch;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\WastageRecord;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase12WastageAndDamageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected User $userA;

    protected User $userB;

    protected Product $productTomatoA;

    protected Product $productTomatoB;

    protected Unit $unitKg;

    protected TenantContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->context = app(TenantContext::class);
        $this->context->reset();

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Pro',
            'slug' => 'enterprise-pro',
            'price_monthly' => 5000.00,
            'max_branches' => 10,
            'max_users' => 20,
            'features' => ['inventory_management'],
            'is_active' => true,
        ]);

        $this->tenantA = Tenant::factory()->create([
            'name' => 'Fresh Greens Lahore',
            'slug' => 'fresh-greens',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchA = $this->tenantA->branches()->create([
            'name' => 'Main Outlet',
            'code' => 'OUT-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $ownerRole = Role::where('slug', 'owner')->first();

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'current_branch_id' => $this->branchA->id,
            'name' => 'Store Manager',
            'email' => 'manager@freshgreens.pk',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);
        $this->userA->roles()->attach($ownerRole);

        // Setup Tenant B for isolation testing
        $this->tenantB = Tenant::factory()->create([
            'name' => 'Rawalpindi Sabzi',
            'slug' => 'rawalpindi-sabzi',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchB = $this->tenantB->branches()->create([
            'name' => 'Pindi Outlet',
            'code' => 'PND-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'current_branch_id' => $this->branchB->id,
            'name' => 'Pindi Manager',
            'email' => 'manager@pindisabzi.pk',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);
        $this->userB->roles()->attach($ownerRole);

        $this->context = app(TenantContext::class);
        $this->context->setTenant($this->tenantA);

        $category = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Fresh Vegetables',
            'slug' => 'fresh-vegetables',
            'code' => 'VEG',
            'type' => 'vegetables',
        ]);

        $this->unitKg = Unit::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Kilogram',
            'code' => 'kg',
            'symbol' => 'kg',
            'is_base' => true,
        ]);

        // Products for Tenant A
        $this->productTomatoA = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $category->id,
            'unit_id' => $this->unitKg->id,
            'name' => 'Tomato Grade A',
            'code' => 'TOM-A',
            'cost_price' => 80.00,
            'selling_price' => 120.00,
            'shelf_life_days' => 5,
        ]);

        $this->productTomatoB = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $category->id,
            'unit_id' => $this->unitKg->id,
            'name' => 'Tomato Grade B',
            'code' => 'TOM-B',
            'cost_price' => 50.00,
            'selling_price' => 70.00,
            'shelf_life_days' => 3,
        ]);

        // Stock initial 100 kg Tomato Grade A
        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productTomatoA->id,
            'quantity' => 100.000,
            'cost_price' => 80.00,
        ]);

        InventoryBatch::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productTomatoA->id,
            'lot_number' => 'LOT-TOM-A-01',
            'received_date' => now()->subDay()->toDateString(),
            'initial_quantity' => 100.000,
            'current_quantity' => 100.000,
            'unit_cost' => 80.00,
            'status' => 'active',
        ]);
    }

    public function test_user_can_record_approved_wastage_and_stock_is_deducted(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $payload = [
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'reason' => 'spoilage',
            'status' => 'approved',
            'notes' => 'Culled 15kg rotten tomatoes during morning sorting',
            'items' => [
                [
                    'product_id' => $this->productTomatoA->id,
                    'quantity' => 15.000,
                    'unit_cost' => 80.00,
                    'disposal_method' => 'discarded',
                    'notes' => 'Rotten / moldy',
                ],
            ],
        ];

        $response = $this->post(route('tenant.inventory.wastage.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('wastage_records', [
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'reason' => 'spoilage',
            'status' => 'approved',
            'total_quantity' => 15.000,
            'total_loss_cost' => 1200.00, // 15 * 80
            'approved_by' => $this->userA->id,
        ]);

        // Verify stock deducted: 100 - 15 = 85 kg
        $stock = InventoryStock::where('tenant_id', $this->tenantA->id)
            ->where('branch_id', $this->branchA->id)
            ->where('product_id', $this->productTomatoA->id)
            ->first();
        $this->assertEquals(85.000, (float) $stock->quantity);

        // Verify batch deducted: 100 - 15 = 85 kg
        $batch = InventoryBatch::where('tenant_id', $this->tenantA->id)
            ->where('lot_number', 'LOT-TOM-A-01')
            ->first();
        $this->assertEquals(85.000, (float) $batch->current_quantity);

        // Verify stock movement logged
        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productTomatoA->id,
            'type' => 'wastage_out',
            'quantity_change' => -15.000,
            'quantity_before' => 100.000,
            'quantity_after' => 85.000,
        ]);
    }

    public function test_user_can_submit_pending_approval_wastage_and_stock_is_not_deducted_until_approved(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $payload = [
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'reason' => 'drying_shrinkage',
            'status' => 'pending_approval',
            'notes' => 'Evening moisture shrinkage count',
            'items' => [
                [
                    'product_id' => $this->productTomatoA->id,
                    'quantity' => 10.000,
                    'unit_cost' => 80.00,
                    'disposal_method' => 'discarded',
                ],
            ],
        ];

        $response = $this->post(route('tenant.inventory.wastage.store'), $payload);
        $response->assertRedirect();

        $record = WastageRecord::where('tenant_id', $this->tenantA->id)->first();
        $this->assertEquals('pending_approval', $record->status);
        $this->assertNull($record->approved_by);

        // Stock should still be 100 kg
        $stock = InventoryStock::where('tenant_id', $this->tenantA->id)
            ->where('branch_id', $this->branchA->id)
            ->where('product_id', $this->productTomatoA->id)
            ->first();
        $this->assertEquals(100.000, (float) $stock->quantity);

        // Now manager approves
        $approveResponse = $this->post(route('tenant.inventory.wastage.approve', $record->id));
        $approveResponse->assertRedirect();

        $record->refresh();
        $this->assertEquals('approved', $record->status);
        $this->assertEquals($this->userA->id, $record->approved_by);

        // Stock now deducted to 90 kg
        $stock->refresh();
        $this->assertEquals(90.000, (float) $stock->quantity);
    }

    public function test_manager_can_reject_pending_wastage_voucher(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $payload = [
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'reason' => 'handling_damage',
            'status' => 'pending_approval',
            'items' => [
                [
                    'product_id' => $this->productTomatoA->id,
                    'quantity' => 25.000,
                    'unit_cost' => 80.00,
                    'disposal_method' => 'discarded',
                ],
            ],
        ];

        $this->post(route('tenant.inventory.wastage.store'), $payload);
        $record = WastageRecord::where('tenant_id', $this->tenantA->id)->first();

        // Reject
        $rejectResponse = $this->post(route('tenant.inventory.wastage.reject', $record->id), [
            'rejection_reason' => 'Loss quantity is excessively high; recount required',
        ]);
        $rejectResponse->assertRedirect();

        $record->refresh();
        $this->assertEquals('rejected', $record->status);
        $this->assertEquals('Loss quantity is excessively high; recount required', $record->rejection_reason);

        // Stock remains untouched at 100 kg
        $stock = InventoryStock::where('tenant_id', $this->tenantA->id)
            ->where('branch_id', $this->branchA->id)
            ->where('product_id', $this->productTomatoA->id)
            ->first();
        $this->assertEquals(100.000, (float) $stock->quantity);
    }

    public function test_produce_sorting_and_regrading_deducts_source_adds_target_and_logs_loss(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        // 50 kg Grade A sorted into 45 kg Grade B with 5 kg culled/lost
        $payload = [
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'source_product_id' => $this->productTomatoA->id,
            'source_quantity' => 50.000,
            'source_unit_cost' => 80.00,
            'target_product_id' => $this->productTomatoB->id,
            'target_quantity' => 45.000,
            'target_unit_cost' => 50.00,
            'notes' => 'Morning grading: 5kg culled as soft/damaged',
        ];

        $response = $this->post(route('tenant.inventory.regradings.store'), $payload);
        $response->assertRedirect(route('tenant.inventory.regradings.index'));

        $this->assertDatabaseHas('produce_regradings', [
            'tenant_id' => $this->tenantA->id,
            'source_product_id' => $this->productTomatoA->id,
            'target_product_id' => $this->productTomatoB->id,
            'source_quantity' => 50.000,
            'target_quantity' => 45.000,
            'wastage_quantity' => 5.000,
            'loss_cost' => 400.00, // 5 * 80
        ]);

        // Source product Grade A stock: 100 - 50 = 50 kg
        $stockA = InventoryStock::where('tenant_id', $this->tenantA->id)
            ->where('branch_id', $this->branchA->id)
            ->where('product_id', $this->productTomatoA->id)
            ->first();
        $this->assertEquals(50.000, (float) $stockA->quantity);

        // Target product Grade B stock: 0 + 45 = 45 kg
        $stockB = InventoryStock::where('tenant_id', $this->tenantA->id)
            ->where('branch_id', $this->branchA->id)
            ->where('product_id', $this->productTomatoB->id)
            ->first();
        $this->assertEquals(45.000, (float) $stockB->quantity);

        // Verify batch created for Target product
        $this->assertDatabaseHas('inventory_batches', [
            'tenant_id' => $this->tenantA->id,
            'product_id' => $this->productTomatoB->id,
            'initial_quantity' => 45.000,
            'current_quantity' => 45.000,
            'status' => 'active',
        ]);
    }

    public function test_tenant_isolation_in_wastage_and_damage(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $recordA = WastageRecord::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'wastage_no' => 'WST-2601-99999',
            'date' => now()->toDateString(),
            'reason' => 'spoilage',
            'status' => 'pending_approval',
            'total_items_count' => 1,
            'total_quantity' => 10.000,
            'total_loss_cost' => 800.00,
            'created_by' => $this->userA->id,
        ]);

        // Tenant B logs in
        $this->actingAs($this->userB);
        $this->context->setTenant($this->tenantB);

        // Attempt to view Tenant A's wastage voucher
        $viewResponse = $this->get(route('tenant.inventory.wastage.show', $recordA->id));
        $viewResponse->assertNotFound();

        // Attempt to approve Tenant A's wastage voucher
        $approveResponse = $this->post(route('tenant.inventory.wastage.approve', $recordA->id));
        $approveResponse->assertNotFound();
    }

    public function test_wastage_with_specific_batch_depletes_that_batch(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $batch = InventoryBatch::where('tenant_id', $this->tenantA->id)
            ->where('lot_number', 'LOT-TOM-A-01')
            ->first();

        $payload = [
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'reason' => 'pest_damage',
            'status' => 'approved',
            'items' => [
                [
                    'product_id' => $this->productTomatoA->id,
                    'batch_id' => $batch->id,
                    'quantity' => 20.000,
                    'unit_cost' => 80.00,
                    'disposal_method' => 'composted',
                ],
            ],
        ];

        $response = $this->post(route('tenant.inventory.wastage.store'), $payload);
        $response->assertRedirect();

        $batch->refresh();
        $this->assertEquals(80.000, (float) $batch->current_quantity);
    }

    public function test_bilingual_reason_labels_and_status_badges(): void
    {
        $record = new WastageRecord(['reason' => 'spoilage', 'status' => 'approved']);
        $this->assertStringContainsString('Rotten / Spoilage', $record->reason_label);
        $this->assertStringContainsString('خراب', $record->reason_label);
        $this->assertEquals('bg-success', $record->status_badge_class);

        $shrinkage = new WastageRecord(['reason' => 'drying_shrinkage', 'status' => 'pending_approval']);
        $this->assertStringContainsString('Drying Shrinkage', $shrinkage->reason_label);
        $this->assertEquals('bg-warning text-dark', $shrinkage->status_badge_class);
    }
}
