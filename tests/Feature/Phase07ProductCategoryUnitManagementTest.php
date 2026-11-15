<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\User;
use App\Services\TenantOnboardingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase07ProductCategoryUnitManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected User $ownerA;

    protected User $ownerB;

    protected TenantContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->context = app(TenantContext::class);
        $this->context->reset();

        $plan = SubscriptionPlan::create([
            'name' => 'Standard Plan',
            'slug' => 'standard',
            'price_monthly' => 3500.00,
            'max_branches' => 5,
            'max_users' => 10,
            'features' => ['pos_counter', 'inventory_management'],
            'is_active' => true,
        ]);

        // Tenant A
        $this->tenantA = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'onboarding_completed' => true,
        ]);
        $branchA = $this->tenantA->branches()->create([
            'name' => 'Branch A Main',
            'code' => 'BR-A1',
            'is_headquarters' => true,
            'is_active' => true,
        ]);
        $this->ownerA = User::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $branchA->id,
            'name' => 'Owner Alpha',
            'email' => 'ownerA@veggitable.test',
            'password' => Hash::make('password123'),
            'is_tenant_owner' => true,
            'is_active' => true,
        ]);
        $this->ownerA->branches()->attach($branchA->id);
        $this->ownerA->roles()->attach(Role::where('slug', 'owner')->first()->id);

        // Tenant B
        $this->tenantB = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'onboarding_completed' => true,
        ]);
        $branchB = $this->tenantB->branches()->create([
            'name' => 'Branch B Main',
            'code' => 'BR-B1',
            'is_headquarters' => true,
            'is_active' => true,
        ]);
        $this->ownerB = User::create([
            'tenant_id' => $this->tenantB->id,
            'branch_id' => $branchB->id,
            'name' => 'Owner Beta',
            'email' => 'ownerB@veggitable.test',
            'password' => Hash::make('password123'),
            'is_tenant_owner' => true,
            'is_active' => true,
        ]);
        $this->ownerB->branches()->attach($branchB->id);
        $this->ownerB->roles()->attach(Role::where('slug', 'owner')->first()->id);

        // Seed default units and categories
        $service = app(TenantOnboardingService::class);
        $service->seedDefaultUnits($this->tenantA);
        $service->seedDefaultCategories($this->tenantA);
        $service->seedDefaultUnits($this->tenantB);
        $service->seedDefaultCategories($this->tenantB);
    }

    public function test_user_can_create_category_and_subcategory(): void
    {
        $response = $this->actingAs($this->ownerA)->post(route('tenant.categories.store'), [
            'name' => 'Leafy Greens',
            'name_ur' => 'پتوں والی سبزیاں',
            'type' => 'product',
            'description' => 'Spinach, Mint, Coriander, Fenugreek',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('tenant.categories.index', ['type' => 'product']));

        $parent = Category::where('tenant_id', $this->tenantA->id)->where('slug', 'leafy-greens')->first();
        $this->assertNotNull($parent);
        $this->assertEquals('Leafy Greens', $parent->name);
        $this->assertEquals('پتوں والی سبزیاں', $parent->name_ur);
        $this->assertNull($parent->parent_id);

        // Subcategory under Leafy Greens
        $subResponse = $this->actingAs($this->ownerA)->post(route('tenant.categories.store'), [
            'name' => 'Fresh Herbs',
            'name_ur' => 'تازہ جڑی بوٹیاں',
            'type' => 'product',
            'parent_id' => $parent->id,
            'is_active' => 1,
        ]);

        $subResponse->assertRedirect();
        $sub = Category::where('tenant_id', $this->tenantA->id)->where('slug', 'fresh-herbs')->first();
        $this->assertNotNull($sub);
        $this->assertEquals($parent->id, $sub->parent_id);
        $this->assertEquals(1, $parent->subcategories()->count());
    }

    public function test_cannot_delete_category_with_subcategories(): void
    {
        $parent = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Root Vegetables',
            'slug' => 'root-veg',
            'type' => 'product',
            'is_active' => true,
        ]);

        Category::create([
            'tenant_id' => $this->tenantA->id,
            'parent_id' => $parent->id,
            'name' => 'Potatoes',
            'slug' => 'potatoes',
            'type' => 'product',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->ownerA)->delete(route('tenant.categories.destroy', $parent));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }

    public function test_unit_crud_and_conversion_multiplier_calculation(): void
    {
        // 1. Create custom bulk unit (e.g. Mann)
        $response = $this->actingAs($this->ownerA)->post(route('tenant.units.store'), [
            'name' => 'Mann (40 Kg)',
            'name_ur' => 'من',
            'code' => 'mann',
            'is_fractional' => 1,
        ]);

        $response->assertRedirect(route('tenant.units.index'));
        $mann = Unit::where('tenant_id', $this->tenantA->id)->where('code', 'mann')->first();
        $this->assertNotNull($mann);

        $kg = Unit::where('tenant_id', $this->tenantA->id)->where('code', 'kg')->first();
        $this->assertNotNull($kg);

        // 2. Create conversion rule: 1 Mann = 40 Kg
        $convResponse = $this->actingAs($this->ownerA)->post(route('tenant.units.conversions.store'), [
            'from_unit_id' => $mann->id,
            'to_unit_id' => $kg->id,
            'multiplier' => 40.0,
            'description' => 'Mandi wholesale lot standard',
        ]);

        $convResponse->assertRedirect(route('tenant.units.index'));

        $conversion = UnitConversion::where('tenant_id', $this->tenantA->id)
            ->where('from_unit_id', $mann->id)
            ->where('to_unit_id', $kg->id)
            ->first();

        $this->assertNotNull($conversion);
        $this->assertEquals(40.0, (float) $conversion->multiplier);

        // Test conversion logic: 5 Mann => 200 Kg
        $this->assertEquals(200.0, $conversion->convert(5));
    }

    public function test_system_default_units_cannot_be_deleted(): void
    {
        $systemUnit = Unit::where('tenant_id', $this->tenantA->id)->where('is_system', true)->first();
        $this->assertNotNull($systemUnit);

        $response = $this->actingAs($this->ownerA)->delete(route('tenant.units.destroy', $systemUnit));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('units', ['id' => $systemUnit->id]);
    }

    public function test_user_can_create_product_with_auto_generated_barcode_and_margin_calculation(): void
    {
        $category = Category::where('tenant_id', $this->tenantA->id)->where('type', 'product')->first();
        $kgUnit = Unit::where('tenant_id', $this->tenantA->id)->where('code', 'kg')->first();

        $response = $this->actingAs($this->ownerA)->post(route('tenant.products.store'), [
            'name' => 'Red Tomato Farm Fresh',
            'name_ur' => 'لال ٹماٹر تازہ',
            'type' => 'vegetable',
            'category_id' => $category->id,
            'selling_unit_id' => $kgUnit->id,
            'stock_unit_id' => $kgUnit->id,
            'unit_conversion_factor' => 1.0,
            'cost_price' => 80.00,
            'selling_price' => 120.00,
            'min_selling_price' => 100.00,
            'reorder_level' => 15.00,
            'is_perishable' => 1,
            'shelf_life_days' => 4,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('tenant.products.index'));

        $product = Product::where('tenant_id', $this->tenantA->id)->where('name', 'Red Tomato Farm Fresh')->first();
        $this->assertNotNull($product);
        $this->assertNotEmpty($product->code);
        $this->assertNotEmpty($product->barcode);
        $this->assertEquals('لال ٹماٹر تازہ', $product->name_ur);

        // Verify profit margin: ((120 - 80) / 80) * 100 = 50.0%
        $this->assertEquals(50.0, $product->profit_margin_percentage);
    }

    public function test_bilingual_product_search_works_in_english_and_urdu(): void
    {
        $kgUnit = Unit::where('tenant_id', $this->tenantA->id)->where('code', 'kg')->first();

        Product::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Golden Delicious Apple',
            'name_ur' => 'سیب گولڈن کشمیری',
            'code' => 'APL-GLD-01',
            'barcode' => '200100987654',
            'type' => 'fruit',
            'stock_unit_id' => $kgUnit->id,
            'unit_conversion_factor' => 1.0,
            'cost_price' => 150.00,
            'selling_price' => 220.00,
            'reorder_level' => 10.00,
            'is_perishable' => true,
            'shelf_life_days' => 7,
            'is_active' => true,
        ]);

        // Search English
        $searchEn = Product::where('tenant_id', $this->tenantA->id)->searchBilingual('Delicious')->get();
        $this->assertCount(1, $searchEn);

        // Search Urdu
        $searchUr = Product::where('tenant_id', $this->tenantA->id)->searchBilingual('کشمیری')->get();
        $this->assertCount(1, $searchUr);
    }

    public function test_strict_tenant_isolation_for_products_categories_and_units(): void
    {
        $kgUnitA = Unit::where('tenant_id', $this->tenantA->id)->where('code', 'kg')->first();

        // Tenant A creates product
        $productA = Product::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Tenant A Secret Carrot',
            'code' => 'CARROT-A',
            'barcode' => '200100111111',
            'type' => 'vegetable',
            'stock_unit_id' => $kgUnitA->id,
            'unit_conversion_factor' => 1.0,
            'cost_price' => 40.00,
            'selling_price' => 60.00,
            'reorder_level' => 5.00,
            'is_active' => true,
        ]);

        // Tenant B logs in and tries to access Tenant A product
        $response = $this->actingAs($this->ownerB)->get(route('tenant.products.show', $productA));
        $this->assertTrue(in_array($response->status(), [403, 404]));

        // Tenant B tries to edit Tenant A product
        $editResponse = $this->actingAs($this->ownerB)->get(route('tenant.products.edit', $productA));
        $this->assertTrue(in_array($editResponse->status(), [403, 404]));

        // Tenant B tries to delete Tenant A product
        $deleteResponse = $this->actingAs($this->ownerB)->delete(route('tenant.products.destroy', $productA));
        $this->assertTrue(in_array($deleteResponse->status(), [403, 404]));

        $this->assertDatabaseHas('products', ['id' => $productA->id]);
    }

    public function test_barcode_sticker_sheet_is_accessible(): void
    {
        $kgUnitA = Unit::where('tenant_id', $this->tenantA->id)->where('code', 'kg')->first();

        $product = Product::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Green Chilli Spicy',
            'name_ur' => 'ہری مرچ تیز',
            'code' => 'CHL-001',
            'barcode' => '200100555555',
            'type' => 'vegetable',
            'stock_unit_id' => $kgUnitA->id,
            'unit_conversion_factor' => 1.0,
            'cost_price' => 90.00,
            'selling_price' => 140.00,
            'reorder_level' => 5.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->ownerA)->get(route('tenant.products.barcode', $product));

        $response->assertOk();
        $response->assertSee('200100555555');
        $response->assertSee('Green Chilli Spicy');
    }

    public function test_permissions_protect_product_management_routes(): void
    {
        // Create an employee with Viewer role (only products.view, cannot manage)
        $viewerRole = Role::where('slug', 'viewer')->first();
        $viewerUser = User::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->ownerA->branch_id,
            'name' => 'Auditor Viewer',
            'email' => 'auditor@veggitable.test',
            'password' => Hash::make('password123'),
            'is_tenant_owner' => false,
            'is_active' => true,
        ]);
        $viewerUser->roles()->attach($viewerRole->id);

        // Viewer CAN view product list
        $viewResponse = $this->actingAs($viewerUser)->get(route('tenant.products.index'));
        $viewResponse->assertOk();

        // Viewer CANNOT access product creation form (403 Forbidden)
        $createResponse = $this->actingAs($viewerUser)->get(route('tenant.products.create'));
        $createResponse->assertStatus(403);

        // Viewer CANNOT create product
        $storeResponse = $this->actingAs($viewerUser)->post(route('tenant.products.store'), [
            'name' => 'Unauthorized Item',
            'type' => 'vegetable',
            'cost_price' => 10,
            'selling_price' => 20,
            'reorder_level' => 5,
            'unit_conversion_factor' => 1,
        ]);
        $storeResponse->assertStatus(403);
    }
}
