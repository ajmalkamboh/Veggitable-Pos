<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\TenantStatus;
use App\Models\Branch;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Phase03SuperAdminSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $regularTenantUser;

    protected Tenant $tenant;

    protected SubscriptionPlan $basicPlan;

    protected SubscriptionPlan $proPlan;

    protected SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriptionService = app(SubscriptionService::class);

        // 1. Create Super Admin User
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'admin@veggitable.test',
            'is_super_admin' => true,
            'is_tenant_owner' => false,
            'tenant_id' => null,
        ]);

        // 2. Create Standard Plans
        $this->basicPlan = SubscriptionPlan::create([
            'name' => 'Basic Shop Plan',
            'slug' => 'basic-shop',
            'description' => 'Entry-level vegetable shop package.',
            'price_monthly' => 1500.00,
            'price_yearly' => 15000.00,
            'trial_days' => 14,
            'grace_days' => 3,
            'max_branches' => 1,
            'max_users' => 2,
            'max_products' => 100,
            'features' => ['pos_counter', 'inventory_management'],
            'is_active' => true,
        ]);

        $this->proPlan = SubscriptionPlan::create([
            'name' => 'Professional Enterprise Plan',
            'slug' => 'pro-enterprise',
            'description' => 'Multi-branch chain package.',
            'price_monthly' => 6500.00,
            'price_yearly' => 65000.00,
            'trial_days' => 14,
            'grace_days' => 5,
            'max_branches' => 5,
            'max_users' => 10,
            'max_products' => 1000,
            'features' => ['pos_counter', 'inventory_management', 'accounting_ledger', 'custom_reports', 'api_access'],
            'is_active' => true,
        ]);

        // 3. Create Tenant with Basic Plan
        $this->tenant = Tenant::factory()->create([
            'name' => 'Khyber Vegetable Mart',
            'slug' => 'khyber-mart',
            'status' => TenantStatus::Active,
            'currency' => 'PKR',
        ]);

        $this->subscriptionService->assignPlan(
            tenant: $this->tenant,
            plan: $this->basicPlan,
            cycle: BillingCycle::Monthly,
            amountPaid: 1500.00,
            paymentMethod: 'bank_transfer',
            reference: 'TXN-KHYBER-01'
        );

        $this->tenant->refresh();

        // 4. Create Regular Tenant User
        $this->regularTenantUser = User::factory()->create([
            'name' => 'Cashier Khan',
            'email' => 'khan@khyber.test',
            'is_super_admin' => false,
            'is_tenant_owner' => false,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test super admin can access dashboard and non-superadmins are blocked with 403.
     */
    public function test_super_admin_can_access_dashboard_and_non_superadmin_is_forbidden(): void
    {
        // Guest is redirected to login
        $guestResponse = $this->get(route('superadmin.dashboard'));
        $guestResponse->assertRedirect(route('login'));

        // Regular Tenant User is forbidden
        $userResponse = $this->actingAs($this->regularTenantUser)->get(route('superadmin.dashboard'));
        $userResponse->assertStatus(403);

        // Super Admin has access
        $adminResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.dashboard'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('SaaS Platform Super Admin');
        $adminResponse->assertSee($this->tenant->name);
    }

    /**
     * Test super admin can list tenants and view tenant details.
     */
    public function test_super_admin_can_view_tenants_list_and_details(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.tenants.index'));
        $response->assertStatus(200);
        $response->assertSee($this->tenant->name);
        $response->assertSee($this->basicPlan->name);

        $showResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.tenants.show', $this->tenant->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($this->tenant->name);
        $showResponse->assertSee('Subscription Billing History');
        $showResponse->assertSee('INV-');
    }

    /**
     * Test super admin can edit tenant metadata.
     */
    public function test_super_admin_can_update_tenant_details(): void
    {
        $response = $this->actingAs($this->superAdmin)->put(route('superadmin.tenants.update', $this->tenant->id), [
            'name' => 'Khyber Organic Greens',
            'email' => 'contact@khyberorganic.test',
            'phone' => '+92 300 1122334',
            'address' => 'Shop 14, Main Sabzi Mandi',
            'city' => 'Peshawar',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'default_locale' => 'ur',
        ]);

        $response->assertRedirect(route('superadmin.tenants.show', $this->tenant->id));
        $this->tenant->refresh();

        $this->assertEquals('Khyber Organic Greens', $this->tenant->name);
        $this->assertEquals('Peshawar', $this->tenant->city);
        $this->assertEquals('ur', $this->tenant->default_locale);
    }

    /**
     * Test super admin can suspend and reactivate a tenant.
     */
    public function test_super_admin_can_toggle_tenant_status(): void
    {
        $this->assertEquals(TenantStatus::Active, $this->tenant->status);

        // Suspend
        $toggleResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.tenants.toggle-status', $this->tenant->id));
        $toggleResponse->assertRedirect();
        $this->tenant->refresh();
        $this->assertEquals(TenantStatus::Suspended, $this->tenant->status);

        // Reactivate
        $reactivateResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.tenants.toggle-status', $this->tenant->id));
        $reactivateResponse->assertRedirect();
        $this->tenant->refresh();
        $this->assertEquals(TenantStatus::Active, $this->tenant->status);
    }

    /**
     * Test super admin can create, update and deactivate subscription plans.
     */
    public function test_super_admin_can_manage_subscription_plans(): void
    {
        // 1. Create Plan
        $createResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.plans.store'), [
            'name' => 'Gold Retail Plan',
            'slug' => 'gold-retail',
            'description' => 'For medium vegetable vendors.',
            'price_monthly' => 4500.00,
            'price_yearly' => 45000.00,
            'trial_days' => 14,
            'grace_days' => 3,
            'max_branches' => 4,
            'max_users' => 8,
            'max_products' => 600,
            'features' => ['pos_counter', 'wastage_tracking', 'custom_reports'],
            'is_active' => true,
            'is_popular' => false,
            'sort_order' => 5,
        ]);

        $createResponse->assertRedirect(route('superadmin.plans.index'));
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'gold-retail', 'price_monthly' => 4500.00]);

        $plan = SubscriptionPlan::where('slug', 'gold-retail')->firstOrFail();

        // 2. Update Plan
        $updateResponse = $this->actingAs($this->superAdmin)->put(route('superadmin.plans.update', $plan->id), [
            'name' => 'Gold Retail Super Plan',
            'slug' => 'gold-retail',
            'description' => 'Updated description.',
            'price_monthly' => 5000.00,
            'price_yearly' => 50000.00,
            'trial_days' => 20,
            'grace_days' => 4,
            'max_branches' => 6,
            'max_users' => 12,
            'max_products' => 800,
            'features' => ['pos_counter', 'wastage_tracking', 'custom_reports', 'urdu_receipts'],
            'is_active' => true,
            'is_popular' => true,
            'sort_order' => 6,
        ]);

        $updateResponse->assertRedirect(route('superadmin.plans.index'));
        $plan->refresh();
        $this->assertEquals('Gold Retail Super Plan', $plan->name);
        $this->assertEquals(5000.00, (float) $plan->price_monthly);
        $this->assertTrue($plan->is_popular);

        // 3. Deactivate / Delete Plan
        $deleteResponse = $this->actingAs($this->superAdmin)->delete(route('superadmin.plans.destroy', $plan->id));
        $deleteResponse->assertRedirect(route('superadmin.plans.index'));
        $this->assertSoftDeleted('subscription_plans', ['id' => $plan->id]);
    }

    /**
     * Test super admin can change tenant plan and record manual payment.
     */
    public function test_super_admin_can_change_tenant_plan_and_record_payment(): void
    {
        // Upgrade to Pro Plan
        $changeResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.tenants.change-plan', $this->tenant->id), [
            'plan_id' => $this->proPlan->id,
            'billing_cycle' => 'yearly',
            'amount_paid' => 65000.00,
            'payment_method' => 'bank_transfer',
        ]);

        $changeResponse->assertRedirect();
        $this->tenant->refresh();

        $this->assertEquals($this->proPlan->id, $this->tenant->plan_id);
        $this->assertTrue($this->tenant->hasFeature('accounting_ledger'));
        $this->assertTrue($this->tenant->hasFeature('api_access'));

        // Check Invoice Created
        $this->assertDatabaseHas('subscription_invoices', [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->proPlan->id,
            'amount' => 65000.00,
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Test super admin can record standalone manual payment and view invoice details.
     */
    public function test_super_admin_can_record_manual_payment_and_view_invoice(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.subscriptions.store'), [
            'tenant_id' => $this->tenant->id,
            'amount' => 3500.00,
            'payment_method' => 'cash',
            'transaction_reference' => 'CASH-REC-9988',
            'notes' => 'Received cash at office counter.',
        ]);

        $response->assertRedirect(route('superadmin.subscriptions.payments'));

        $invoice = SubscriptionInvoice::where('transaction_reference', 'CASH-REC-9988')->firstOrFail();
        $this->assertEquals(3500.00, (float) $invoice->amount);
        $this->assertEquals(InvoiceStatus::Paid, $invoice->payment_status);

        // View invoice page
        $showInvoiceResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.subscriptions.show', $invoice->id));
        $showInvoiceResponse->assertStatus(200);
        $showInvoiceResponse->assertSee($invoice->invoice_number);
        $showInvoiceResponse->assertSee('CASH-REC-9988');
        $showInvoiceResponse->assertSee($this->tenant->name);
    }

    /**
     * Test subscription renewal extends expiry date.
     */
    public function test_super_admin_can_extend_subscription_duration(): void
    {
        $initialEndsAt = $this->tenant->subscription_ends_at ?? now();

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.tenants.extend', $this->tenant->id), [
            'months' => 6,
            'amount_paid' => 9000.00,
            'payment_method' => 'bank_transfer',
            'notes' => 'Semi-annual advance payment extension.',
        ]);

        $response->assertRedirect();
        $this->tenant->refresh();

        $this->assertNotNull($this->tenant->subscription_ends_at);
        $this->assertTrue($this->tenant->subscription_ends_at->isAfter($initialEndsAt));
    }

    /**
     * Test feature entitlement middleware enforces plan permissions.
     */
    public function test_feature_entitlement_middleware_enforces_plan_permissions(): void
    {
        // Define a test route requiring 'accounting_ledger' feature
        Route::get('/test-feature-protected', function () {
            return response()->json(['status' => 'granted']);
        })->middleware(['tenant.feature:accounting_ledger']);

        $context = app(TenantContext::class);
        $context->setTenant($this->tenant); // Tenant currently on basicPlan (lacks accounting_ledger)

        // Basic plan tenant should be blocked with 403
        $deniedResponse = $this->get('/test-feature-protected');
        $deniedResponse->assertStatus(403);

        // Now upgrade tenant to proPlan (has accounting_ledger)
        $this->tenant->update(['plan_id' => $this->proPlan->id]);
        $this->tenant->refresh();
        $context->setTenant($this->tenant);

        // Pro plan tenant should have access
        $grantedResponse = $this->get('/test-feature-protected');
        $grantedResponse->assertStatus(200);
        $grantedResponse->assertJson(['status' => 'granted']);
    }

    /**
     * Test branch limit check prevents exceeding allowed branches in plan.
     */
    public function test_branch_limit_enforces_plan_boundary(): void
    {
        // Basic plan allows max 1 branch
        $this->assertEquals(1, $this->basicPlan->max_branches);

        // Add 1st branch - should be allowed
        Branch::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Branch 1',
            'code' => 'B-01',
            'is_headquarters' => true,
        ]);

        $this->assertEquals(1, $this->tenant->branches()->count());
        $this->assertFalse($this->tenant->canAddBranch());

        // Attempting to assert branch creation should throw 403 HttpException
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Branch limit reached');

        $this->subscriptionService->assertCanAddBranch($this->tenant);
    }

    /**
     * Test user account limit check prevents exceeding allowed users in plan.
     */
    public function test_user_limit_enforces_plan_boundary(): void
    {
        // Basic plan allows max 2 users
        $this->assertEquals(2, $this->basicPlan->max_users);

        // Currently we have $this->regularTenantUser in setUp, create a 2nd user
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'worker2@khyber.test',
        ]);

        $this->assertEquals(2, $this->tenant->users()->count());
        $this->assertFalse($this->tenant->canAddUser());

        // Attempting to assert user creation should throw 403 HttpException
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('User account limit reached');

        $this->subscriptionService->assertCanAddUser($this->tenant);
    }

    /**
     * Test super admin can view platform reports.
     */
    public function test_super_admin_can_view_platform_reports(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Platform SaaS Reports');
        $response->assertSee('Total Platform Revenue');
        $response->assertSee('Plan Distribution');
    }
}
