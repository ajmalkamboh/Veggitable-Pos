<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Enums\TenantStatus;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase04AuthenticationRolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Branch $branchA1;

    protected Branch $branchA2;

    protected User $ownerA;

    protected User $cashierA;

    protected TenantContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->context = app(TenantContext::class);
        $this->context->reset();

        // 1. Create Subscription Plan
        $plan = SubscriptionPlan::create([
            'name' => 'Standard Plan',
            'slug' => 'standard',
            'price_monthly' => 3500.00,
            'max_branches' => 5,
            'max_users' => 10,
            'features' => ['pos_counter', 'inventory_management', 'accounting_ledger'],
            'is_active' => true,
        ]);

        // 2. Setup Tenant A
        $this->tenantA = Tenant::factory()->create([
            'name' => 'Al-Madina Fresh Store',
            'slug' => 'al-madina',
            'status' => TenantStatus::Active,
            'plan_id' => $plan->id,
        ]);

        $this->branchA1 = Branch::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Main Outlet',
            'code' => 'HQ-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->branchA2 = Branch::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Mandi Branch',
            'code' => 'BR-02',
            'is_headquarters' => false,
            'is_active' => true,
        ]);

        $this->ownerA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Owner Haji',
            'email' => 'owner@almadina.test',
            'password' => Hash::make('password123'),
            'current_branch_id' => $this->branchA1->id,
            'is_tenant_owner' => true,
            'is_super_admin' => false,
            'status' => 'active',
        ]);
        $this->ownerA->branches()->sync([$this->branchA1->id, $this->branchA2->id]);
        $this->ownerA->assignRole('owner');

        $this->cashierA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Bilal Cashier',
            'email' => 'bilal@almadina.test',
            'password' => Hash::make('password123'),
            'current_branch_id' => $this->branchA1->id,
            'is_tenant_owner' => false,
            'is_super_admin' => false,
            'status' => 'active',
        ]);
        $this->cashierA->branches()->sync([$this->branchA1->id]); // Cashier only has access to branch A1
        $this->cashierA->assignRole('cashier');

        // 3. Setup Tenant B
        $this->tenantB = Tenant::factory()->create([
            'name' => 'Bismillah Shop',
            'slug' => 'bismillah',
            'status' => TenantStatus::Active,
            'plan_id' => $plan->id,
        ]);
    }

    /**
     * Test guest can view login page.
     */
    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('Sign In');
        $response->assertSee('Keep me signed in on this device');
    }

    /**
     * Test user can authenticate with valid credentials and sets active tenant context.
     */
    public function test_user_can_authenticate_with_valid_credentials(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'owner@almadina.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->ownerA);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->ownerA->id,
            'action' => 'auth.login',
        ]);
    }

    /**
     * Test login fails with invalid password.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'owner@almadina.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test suspended user cannot log in.
     */
    public function test_suspended_user_cannot_log_in(): void
    {
        $this->cashierA->update(['status' => 'suspended']);

        $response = $this->post(route('login.store'), [
            'email' => 'bilal@almadina.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    /**
     * Test user belonging to a suspended business cannot log in.
     */
    public function test_user_from_suspended_tenant_cannot_log_in(): void
    {
        $this->tenantA->update(['status' => TenantStatus::Suspended]);

        $response = $this->post(route('login.store'), [
            'email' => 'owner@almadina.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    /**
     * Test authenticated user can log out.
     */
    public function test_authenticated_user_can_log_out(): void
    {
        $this->actingAs($this->ownerA);

        $response = $this->post(route('logout'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->ownerA->id,
            'action' => 'auth.logout',
        ]);
    }

    /**
     * Test password reset request and reset flow.
     */
    public function test_password_reset_flow(): void
    {
        // 1. Request reset link
        $forgotResponse = $this->post(route('password.email'), [
            'email' => 'bilal@almadina.test',
        ]);
        $forgotResponse->assertSessionHas('status');

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', 'bilal@almadina.test')
            ->first();
        $this->assertNotNull($resetRecord);

        // 2. Perform password update
        $token = 'random-plain-token';
        DB::table('password_reset_tokens')->update([
            'token' => Hash::make($token),
        ]);

        $updateResponse = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'bilal@almadina.test',
            'password' => 'newPassword99!',
            'password_confirmation' => 'newPassword99!',
        ]);

        $updateResponse->assertRedirect(route('login'));
        $this->cashierA->refresh();
        $this->assertTrue(Hash::check('newPassword99!', $this->cashierA->password));
    }

    /**
     * Test tenant owner can create staff employee with branch and role assignments.
     */
    public function test_tenant_owner_can_create_staff_employee(): void
    {
        $this->context->setTenant($this->tenantA);

        $managerRole = Role::where('slug', 'manager')->firstOrFail();

        $response = $this->actingAs($this->ownerA)->post(route('tenant.users.store'), [
            'name' => 'Kareem Manager',
            'email' => 'kareem@almadina.test',
            'password' => 'managerPass123',
            'phone' => '+92 301 5556677',
            'branch_ids' => [$this->branchA1->id, $this->branchA2->id],
            'role_ids' => [$managerRole->id],
        ]);

        $response->assertRedirect(route('tenant.users.index'));

        $this->assertDatabaseHas('users', [
            'tenant_id' => $this->tenantA->id,
            'email' => 'kareem@almadina.test',
            'status' => 'active',
        ]);

        $kareem = User::where('email', 'kareem@almadina.test')->firstOrFail();
        $this->assertTrue($kareem->hasRole('manager'));
        $this->assertTrue($kareem->canAccessBranch($this->branchA1));
        $this->assertTrue($kareem->canAccessBranch($this->branchA2));
    }

    /**
     * Test branch-level access restriction.
     */
    public function test_branch_level_access_restriction(): void
    {
        // Cashier A is assigned ONLY to Branch A1
        $this->assertTrue($this->cashierA->canAccessBranch($this->branchA1));
        $this->assertFalse($this->cashierA->canAccessBranch($this->branchA2));

        // Owner A has access to all branches within their tenant
        $this->assertTrue($this->ownerA->canAccessBranch($this->branchA1));
        $this->assertTrue($this->ownerA->canAccessBranch($this->branchA2));

        // Neither can access a branch of Tenant B
        $branchB = Branch::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Karachi Outlet',
            'code' => 'HQ-01',
        ]);

        $this->assertFalse($this->cashierA->canAccessBranch($branchB));
        $this->assertFalse($this->ownerA->canAccessBranch($branchB));
    }

    /**
     * Test role-based permissions matrix.
     */
    public function test_role_based_permissions_matrix(): void
    {
        // Cashier has 'pos.counter' permission
        $this->assertTrue($this->cashierA->hasPermission('pos.counter'));

        // Cashier DOES NOT have 'accounting.manage' or 'inventory.manage'
        $this->assertFalse($this->cashierA->hasPermission('accounting.manage'));
        $this->assertFalse($this->cashierA->hasPermission('inventory.manage'));

        // Business owner has all permissions
        $this->assertTrue($this->ownerA->hasPermission('pos.counter'));
        $this->assertTrue($this->ownerA->hasPermission('accounting.manage'));
        $this->assertTrue($this->ownerA->hasPermission('inventory.manage'));
    }

    /**
     * Test tenant can create custom role with specific permissions.
     */
    public function test_tenant_can_create_custom_role(): void
    {
        $this->context->setTenant($this->tenantA);

        $posPermission = Permission::where('slug', 'pos.counter')->firstOrFail();
        $invViewPermission = Permission::where('slug', 'inventory.view')->firstOrFail();

        $response = $this->actingAs($this->ownerA)->post(route('tenant.roles.store'), [
            'name' => 'Floor Assistant',
            'slug' => 'floor_assistant',
            'description' => 'Assists at cash register and shelf checks.',
            'permission_ids' => [$posPermission->id, $invViewPermission->id],
        ]);

        $response->assertRedirect(route('tenant.roles.index'));

        $this->assertDatabaseHas('roles', [
            'tenant_id' => $this->tenantA->id,
            'slug' => 'floor-assistant',
            'is_system' => false,
        ]);

        $role = Role::where('slug', 'floor-assistant')->where('tenant_id', $this->tenantA->id)->firstOrFail();
        $this->assertTrue($role->hasPermission('pos.counter'));
        $this->assertTrue($role->hasPermission('inventory.view'));
        $this->assertFalse($role->hasPermission('accounting.manage'));
    }

    /**
     * Test tenant cannot edit system roles.
     */
    public function test_system_roles_cannot_be_modified_by_tenant(): void
    {
        $this->context->setTenant($this->tenantA);

        $systemCashierRole = Role::where('slug', 'cashier')->whereNull('tenant_id')->firstOrFail();

        $response = $this->actingAs($this->ownerA)->put(route('tenant.roles.update', $systemCashierRole->id), [
            'name' => 'Hacked Cashier',
            'permission_ids' => [1],
        ]);

        $response->assertRedirect(route('tenant.roles.index'));
        $response->assertSessionHas('error');
        $this->assertEquals('Cashier', $systemCashierRole->fresh()->name);
    }

    /**
     * Test tenant isolation on staff accounts (Tenant A cannot see or edit Tenant B staff).
     */
    public function test_tenant_cannot_view_or_modify_other_tenants_staff(): void
    {
        $userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Tenant B Staff',
            'email' => 'staff@bismillah.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->context->setTenant($this->tenantA);

        // Edit attempt on Tenant B staff by Tenant A owner should be rejected with 403
        $response = $this->actingAs($this->ownerA)->get(route('tenant.users.edit', $userB->id));
        $response->assertStatus(403);
    }

    /**
     * Test audit trail logs are visible to tenant.
     */
    public function test_tenant_audit_trail_is_viewable(): void
    {
        $this->context->setTenant($this->tenantA);

        AuditLog::record('test.action', ['item' => 'val'], $this->ownerA, $this->tenantA->id);

        $response = $this->actingAs($this->ownerA)->get(route('tenant.audit.index'));
        $response->assertStatus(200);
        $response->assertSee('test.action');
    }
}
