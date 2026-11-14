<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\Exceptions\CrossTenantAccessException;
use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\TenantRecord;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase02MultiTenantArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected TenantContext $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = app(TenantContext::class);
        $this->context->reset();
    }

    /**
     * Test tenant registration creates tenant, headquarters branch, and owner atomically.
     */
    public function test_tenant_registration_creates_tenant_headquarters_branch_and_owner_atomically(): void
    {
        $response = $this->post('/register-business', [
            'business_name' => 'Punjab Sabzi Mart',
            'slug' => 'punjab-sabzi',
            'owner_name' => 'Chaudhry Akram',
            'owner_email' => 'akram@punjabsabzi.test',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
            'phone' => '+92 300 9876543',
            'city' => 'Faisalabad',
            'currency' => 'PKR',
            'default_locale' => 'ur',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('tenants', [
            'slug' => 'punjab-sabzi',
            'name' => 'Punjab Sabzi Mart',
            'currency' => 'PKR',
        ]);

        $tenant = Tenant::where('slug', 'punjab-sabzi')->firstOrFail();

        $this->assertDatabaseHas('branches', [
            'tenant_id' => $tenant->id,
            'code' => 'HQ-01',
            'is_headquarters' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'akram@punjabsabzi.test',
            'tenant_id' => $tenant->id,
            'is_tenant_owner' => true,
        ]);

        $this->assertAuthenticated();
    }

    /**
     * Test that tenant global scope isolates records between Tenant A and Tenant B.
     */
    public function test_tenant_global_scope_isolates_records_between_tenants(): void
    {
        $tenantA = Tenant::factory()->create(['name' => 'Tenant A Store']);
        $tenantB = Tenant::factory()->create(['name' => 'Tenant B Store']);

        // Create Record in Tenant A context
        $this->context->setTenant($tenantA);
        $recordA = TenantRecord::create([
            'name' => 'Tomatoes Lot A',
            'code' => 'LOT-01',
            'amount' => 1500.00,
        ]);

        // Create Record in Tenant B context
        $this->context->setTenant($tenantB);
        $recordB = TenantRecord::create([
            'name' => 'Onions Lot B',
            'code' => 'LOT-02',
            'amount' => 2500.00,
        ]);

        // When in Tenant B context, only Record B is visible
        $visibleRecordsB = TenantRecord::all();
        $this->assertTrue($visibleRecordsB->contains('id', $recordB->id));
        $this->assertFalse($visibleRecordsB->contains('id', $recordA->id));

        // When switching back to Tenant A context, only Record A is visible
        $this->context->setTenant($tenantA);
        $visibleRecordsA = TenantRecord::all();
        $this->assertTrue($visibleRecordsA->contains('id', $recordA->id));
        $this->assertFalse($visibleRecordsA->contains('id', $recordB->id));
    }

    /**
     * Test Tenant A user cannot read or show Tenant B's record via ID manipulation.
     */
    public function test_tenant_cannot_read_or_show_another_tenants_record_via_id_manipulation(): void
    {
        $tenantA = Tenant::factory()->create();
        $branchA = Branch::factory()->for($tenantA)->create(['is_headquarters' => true]);
        $ownerA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'current_branch_id' => $branchA->id,
            'is_tenant_owner' => true,
        ]);

        $tenantB = Tenant::factory()->create();
        $branchB = Branch::factory()->for($tenantB)->create(['is_headquarters' => true]);
        $this->context->setTenant($tenantB);
        $recordB = TenantRecord::create([
            'name' => 'Tenant B Secret Stock',
            'code' => 'SEC-01',
            'amount' => 50000.00,
        ]);

        // Act as Owner A attempting to view Tenant B's record
        $this->context->setTenant($tenantA);
        $response = $this->actingAs($ownerA)->get("/tenant/records/{$recordB->id}");

        // Due to TenantScope and policy authorization, Owner A receives 404 or 403
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    /**
     * Test client-supplied tenant_id is guarded and cannot spoof another tenant.
     */
    public function test_client_supplied_tenant_id_is_guarded_and_cannot_spoof_different_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->context->setTenant($tenantA);

        $this->expectException(CrossTenantAccessException::class);

        // Attempting to inject a record with Tenant B's ID while inside Tenant A context
        TenantRecord::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Spoofed Record',
            'code' => 'SPF-01',
            'amount' => 100.00,
        ]);
    }

    /**
     * Test composite unique constraints allow same code across different tenants but block duplicates within tenant.
     */
    public function test_composite_unique_constraints_allow_same_code_across_different_tenants(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        // Both tenants can create a branch with the same code HQ-01
        $branchA = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Branch A Headquarters',
            'code' => 'HQ-01',
            'is_headquarters' => true,
        ]);

        $branchB = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Branch B Headquarters',
            'code' => 'HQ-01',
            'is_headquarters' => true,
        ]);

        $this->assertNotNull($branchA);
        $this->assertNotNull($branchB);
        $this->assertEquals($branchA->code, $branchB->code);

        // Creating duplicate code within Tenant A must fail
        $this->expectException(QueryException::class);
        Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Duplicate Branch Code',
            'code' => 'HQ-01',
        ]);
    }

    /**
     * Test suspended tenant is blocked by middleware.
     */
    public function test_suspended_tenant_is_blocked_by_middleware(): void
    {
        $suspendedTenant = Tenant::factory()->suspended()->create([
            'name' => 'Suspended Shop',
        ]);
        $branch = Branch::factory()->for($suspendedTenant)->create();
        $user = User::factory()->create([
            'tenant_id' => $suspendedTenant->id,
            'current_branch_id' => $branch->id,
        ]);

        $this->context->setTenant($suspendedTenant);

        $response = $this->actingAs($user)->get('/tenant/records');

        $response->assertStatus(403);
    }

    /**
     * Test multi-branch support and branch switching.
     */
    public function test_multi_branch_support_and_branch_switching(): void
    {
        $tenant = Tenant::factory()->create();
        $branch1 = Branch::factory()->for($tenant)->create(['name' => 'Branch One', 'code' => 'BR-01']);
        $branch2 = Branch::factory()->for($tenant)->create(['name' => 'Branch Two', 'code' => 'BR-02']);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_branch_id' => $branch1->id,
        ]);

        $this->context->setTenant($tenant);

        // Switch to Branch 2
        $response = $this->actingAs($user)->post("/tenant/branch/{$branch2->id}/switch");

        $response->assertStatus(302);
        $response->assertSessionHas('branch_id', $branch2->id);

        $user->refresh();
        $this->assertEquals($branch2->id, $user->current_branch_id);

        // Attempting to switch to another tenant's branch must be forbidden (403)
        $otherTenant = Tenant::factory()->create();
        $this->context->setTenant($otherTenant);
        $otherBranch = Branch::factory()->for($otherTenant)->create(['name' => 'Other Branch', 'code' => 'BR-OT']);
        $this->context->setTenant($tenant);

        $forbiddenResponse = $this->actingAs($user)->post("/tenant/branch/{$otherBranch->id}/switch");
        $this->assertTrue(in_array($forbiddenResponse->status(), [403, 404]));
    }
}
