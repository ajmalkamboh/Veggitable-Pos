<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Enums\TenantStatus;
use App\Models\Branch;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantOnboardingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase05BusinessOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $owner;

    protected TenantContext $context;

    protected TenantOnboardingService $onboardingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->context = app(TenantContext::class);
        $this->context->reset();
        $this->onboardingService = app(TenantOnboardingService::class);

        $plan = SubscriptionPlan::create([
            'name' => 'Basic Plan',
            'slug' => 'basic',
            'price_monthly' => 1500.00,
            'max_branches' => 2,
            'max_users' => 3,
            'features' => ['pos_counter', 'inventory_management'],
            'is_active' => true,
        ]);

        // Create newly registered tenant that has NOT completed onboarding
        $this->tenant = Tenant::factory()->create([
            'name' => 'Sargodha Orange & Veg Store',
            'slug' => 'sargodha-store',
            'status' => TenantStatus::Active,
            'plan_id' => $plan->id,
            'onboarding_completed' => false,
            'onboarding_step' => 1,
            'currency' => 'PKR',
        ]);

        $branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Initial Counter',
            'code' => 'HQ-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Malik Aslam',
            'email' => 'aslam@sargodha.test',
            'password' => Hash::make('password123'),
            'current_branch_id' => $branch->id,
            'is_tenant_owner' => true,
            'is_super_admin' => false,
            'status' => 'active',
        ]);
        $this->owner->branches()->sync([$branch->id]);
        $this->owner->assignRole('owner');
    }

    /**
     * Test owner can view onboarding checklist overview.
     */
    public function test_owner_can_view_onboarding_checklist_overview(): void
    {
        $this->context->setTenant($this->tenant);

        $response = $this->actingAs($this->owner)->get(route('tenant.onboarding.index'));
        $response->assertStatus(200);
        $response->assertSee('Setup Checklist');
        $response->assertSee('Business Profile');
        $response->assertSee('Primary Shop Outlet');
        $response->assertSee('Cash Drawer');
        $response->assertSee('Master Units');
    }

    /**
     * Test uncompleted onboarding tenant is redirected away from operational routes.
     */
    public function test_uncompleted_onboarding_blocks_operational_routes(): void
    {
        $this->context->setTenant($this->tenant);
        $this->assertFalse($this->tenant->isOnboarded());

        // Attempting to access operational inventory/records route
        $response = $this->actingAs($this->owner)->get(route('tenant.records.index'));
        $response->assertRedirect(route('tenant.onboarding.index'));
        $response->assertSessionHas('warning');
    }

    /**
     * Test step 1 updates business profile, logo, currency, and fiscal year.
     */
    public function test_step1_updates_business_profile_details(): void
    {
        $this->context->setTenant($this->tenant);
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('store_logo.png', 200, 200);

        $response = $this->actingAs($this->owner)->post(route('tenant.onboarding.step1.update'), [
            'name' => 'Sargodha Citrus & Fresh Vegetables',
            'email' => 'sales@sargodhafresh.test',
            'phone' => '+92 300 9988776',
            'address' => 'Grain & Veg Market, Mandi Road',
            'city' => 'Sargodha',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'default_locale' => 'ur',
            'financial_year_start' => '07-01',
            'business_opening_date' => '2026-01-15',
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('tenant.onboarding.step2'));

        $this->tenant->refresh();
        $this->assertEquals('Sargodha Citrus & Fresh Vegetables', $this->tenant->name);
        $this->assertEquals('+92 300 9988776', $this->tenant->phone);
        $this->assertEquals('Sargodha', $this->tenant->city);
        $this->assertEquals('07-01', $this->tenant->financial_year_start);
        $this->assertNotNull($this->tenant->logo_path);
        Storage::disk('public')->assertExists($this->tenant->logo_path);
    }

    /**
     * Test step 2 saves primary branch details.
     */
    public function test_step2_saves_branch_details(): void
    {
        $this->context->setTenant($this->tenant);

        $response = $this->actingAs($this->owner)->post(route('tenant.onboarding.step2.save'), [
            'branch_name' => 'Main Mandi Retail Shed',
            'branch_code' => 'MND-01',
            'city' => 'Sargodha',
            'address' => 'Shed # 14, Mandi Bazaar',
            'phone' => '+92 321 4455667',
        ]);

        $response->assertRedirect(route('tenant.onboarding.step3'));

        $this->assertDatabaseHas('branches', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Main Mandi Retail Shed',
            'code' => 'MND-01',
            'is_headquarters' => true,
        ]);
    }

    /**
     * Test step 3 saves cash drawer and bank accounts.
     */
    public function test_step3_configures_cash_and_bank_accounts(): void
    {
        $this->context->setTenant($this->tenant);

        $response = $this->actingAs($this->owner)->post(route('tenant.onboarding.step3.save'), [
            'cash_drawer_name' => 'Counter Cash Till #1',
            'cash_opening_balance' => 15000.00,
            'bank_name' => 'Meezan Bank Ltd',
            'bank_account_name' => 'Sargodha Veg Store Business Account',
            'account_number' => 'PK99MEZN00001122334455',
            'bank_opening_balance' => 85000.00,
        ]);

        $response->assertRedirect(route('tenant.onboarding.step4'));

        $this->assertDatabaseHas('financial_accounts', [
            'tenant_id' => $this->tenant->id,
            'type' => 'cash',
            'name' => 'Counter Cash Till #1',
            'opening_balance' => 15000.00,
        ]);

        $this->assertDatabaseHas('financial_accounts', [
            'tenant_id' => $this->tenant->id,
            'type' => 'bank',
            'bank_name' => 'Meezan Bank Ltd',
            'opening_balance' => 85000.00,
        ]);
    }

    /**
     * Test step 4 seeds default units and categories.
     */
    public function test_step4_renders_and_seeds_defaults(): void
    {
        $this->context->setTenant($this->tenant);

        $response = $this->actingAs($this->owner)->get(route('tenant.onboarding.step4'));
        $response->assertStatus(200);

        // Verify default units seeded
        $this->assertDatabaseHas('units', ['tenant_id' => $this->tenant->id, 'code' => 'kg']);
        $this->assertDatabaseHas('units', ['tenant_id' => $this->tenant->id, 'code' => 'bori']);
        $this->assertDatabaseHas('units', ['tenant_id' => $this->tenant->id, 'code' => 'crate']);

        // Verify default categories seeded
        $this->assertDatabaseHas('categories', ['tenant_id' => $this->tenant->id, 'slug' => 'leafy-vegetables', 'type' => 'product']);
        $this->assertDatabaseHas('categories', ['tenant_id' => $this->tenant->id, 'slug' => 'shop-rent', 'type' => 'expense']);
    }

    /**
     * Test completing onboarding unlocks operational routes.
     */
    public function test_completing_onboarding_unlocks_operational_routes(): void
    {
        $this->context->setTenant($this->tenant);

        $finishResponse = $this->actingAs($this->owner)->post(route('tenant.onboarding.finish'));
        $finishResponse->assertRedirect(route('dashboard'));

        $this->tenant->refresh();
        $this->assertTrue($this->tenant->isOnboarded());
        $this->assertEquals(100, $this->tenant->onboardingProgressPercentage());

        // Now operational route should be accessible (HTTP 200, no redirect)
        $operationalResponse = $this->actingAs($this->owner)->get(route('tenant.records.index'));
        $operationalResponse->assertStatus(200);
    }
}
