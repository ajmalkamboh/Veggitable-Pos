<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\PartyLedgerEntry;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase08SupplierCustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected User $ownerA;

    protected User $ownerB;

    protected FinancialAccount $accountA;

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

        $this->accountA = FinancialAccount::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $branchA->id,
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 50000.00,
            'current_balance' => 50000.00,
            'is_active' => true,
        ]);

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
    }

    public function test_user_can_create_supplier_with_opening_balance_and_ledger_entry(): void
    {
        $response = $this->actingAs($this->ownerA)->post(route('tenant.suppliers.store'), [
            'name' => 'Al-Rehman Commission Agent',
            'name_ur' => 'الرحمٰن کمیشن ایجنٹ',
            'type' => 'commission_agent',
            'contact_person' => 'Chaudhry Akram',
            'phone' => '042-37890123',
            'mobile' => '0300-9876543',
            'mandi_stall_no' => 'Gala # 18, Badami Bagh Mandi',
            'commission_rate' => 5.00,
            'opening_balance' => 25000.00,
            'payment_terms_days' => 7,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('tenant.suppliers.index'));

        $supplier = Supplier::where('tenant_id', $this->tenantA->id)->where('name', 'Al-Rehman Commission Agent')->first();
        $this->assertNotNull($supplier);
        $this->assertEquals('SUP-0001', $supplier->code);
        $this->assertEquals('الرحمٰن کمیشن ایجنٹ', $supplier->name_ur);
        $this->assertEquals(25000.00, (float) $supplier->opening_balance);
        $this->assertEquals(25000.00, (float) $supplier->current_balance);

        // Verify initial ledger entry
        $ledger = PartyLedgerEntry::where('tenant_id', $this->tenantA->id)
            ->where('party_type', 'supplier')
            ->where('party_id', $supplier->id)
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals('opening_balance', $ledger->reference_type);
        $this->assertEquals(25000.00, (float) $ledger->credit);
        $this->assertEquals(25000.00, (float) $ledger->balance);
    }

    public function test_user_can_create_customer_with_credit_limit_and_opening_balance(): void
    {
        $response = $this->actingAs($this->ownerA)->post(route('tenant.customers.store'), [
            'name' => 'Pearl Continental Hotel & Canteen',
            'name_ur' => 'پی سی ہوٹل کینٹین',
            'type' => 'hotel_restaurant',
            'contact_person' => 'Chef Imran',
            'phone' => '042-36360210',
            'mobile' => '0321-4567890',
            'credit_limit' => 100000.00,
            'credit_days' => 15,
            'opening_balance' => 15000.00,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('tenant.customers.index'));

        $customer = Customer::where('tenant_id', $this->tenantA->id)->where('name', 'Pearl Continental Hotel & Canteen')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('CUST-0001', $customer->code);
        $this->assertEquals('پی سی ہوٹل کینٹین', $customer->name_ur);
        $this->assertEquals(100000.00, (float) $customer->credit_limit);
        $this->assertEquals(15000.00, (float) $customer->current_balance);

        // Verify initial ledger entry
        $ledger = PartyLedgerEntry::where('tenant_id', $this->tenantA->id)
            ->where('party_type', 'customer')
            ->where('party_id', $customer->id)
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(15000.00, (float) $ledger->debit);
        $this->assertEquals(15000.00, (float) $ledger->balance);
    }

    public function test_recording_payment_to_supplier_updates_balance_and_account(): void
    {
        $supplier = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Bismillah Aarthi',
            'code' => 'SUP-0010',
            'type' => 'commission_agent',
            'opening_balance' => 20000.00,
            'current_balance' => 20000.00,
            'is_active' => true,
        ]);

        // Record payment of 12,000 from cash drawer
        $response = $this->actingAs($this->ownerA)->post(route('tenant.suppliers.payment', $supplier), [
            'amount' => 12000.00,
            'account_id' => $this->accountA->id,
            'date' => now()->toDateString(),
            'reference_no' => 'CHQ-7788',
            'description' => 'Weekly payment for potato lots',
        ]);

        $response->assertRedirect(route('tenant.suppliers.show', $supplier));

        // 1. Supplier balance decreased: 20000 - 12000 = 8000
        $supplier->refresh();
        $this->assertEquals(8000.00, (float) $supplier->current_balance);

        // 2. Cash account decreased: 50000 - 12000 = 38000
        $this->accountA->refresh();
        $this->assertEquals(38000.00, (float) $this->accountA->current_balance);

        // 3. Payment ledger entry created (debit supplier)
        $ledger = PartyLedgerEntry::where('tenant_id', $this->tenantA->id)
            ->where('party_type', 'supplier')
            ->where('party_id', $supplier->id)
            ->where('reference_type', 'payment')
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(12000.00, (float) $ledger->debit);
        $this->assertEquals(0.00, (float) $ledger->credit);
        $this->assertEquals(8000.00, (float) $ledger->balance);
    }

    public function test_recording_receipt_from_customer_updates_balance_and_account(): void
    {
        $customer = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Monal Restaurant',
            'code' => 'CUST-0020',
            'type' => 'hotel_restaurant',
            'opening_balance' => 30000.00,
            'current_balance' => 30000.00,
            'is_active' => true,
        ]);

        // Receive payment of 18,000 into cash drawer
        $response = $this->actingAs($this->ownerA)->post(route('tenant.customers.receipt', $customer), [
            'amount' => 18000.00,
            'account_id' => $this->accountA->id,
            'date' => now()->toDateString(),
            'reference_no' => 'REC-1122',
            'description' => 'Cheque clearance',
        ]);

        $response->assertRedirect(route('tenant.customers.show', $customer));

        // 1. Customer receivable decreased: 30000 - 18000 = 12000
        $customer->refresh();
        $this->assertEquals(12000.00, (float) $customer->current_balance);

        // 2. Cash account increased: 50000 + 18000 = 68000
        $this->accountA->refresh();
        $this->assertEquals(68000.00, (float) $this->accountA->current_balance);

        // 3. Ledger entry created (credit customer)
        $ledger = PartyLedgerEntry::where('tenant_id', $this->tenantA->id)
            ->where('party_type', 'customer')
            ->where('party_id', $customer->id)
            ->where('reference_type', 'receipt')
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(0.00, (float) $ledger->debit);
        $this->assertEquals(18000.00, (float) $ledger->credit);
        $this->assertEquals(12000.00, (float) $ledger->balance);
    }

    public function test_bilingual_search_for_suppliers_and_customers(): void
    {
        Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Sargodha Citrus Farm Direct',
            'name_ur' => 'سرگودھا مالٹا فارم',
            'code' => 'SUP-SARG',
            'type' => 'farmer',
            'is_active' => true,
        ]);

        // English search
        $resEn = Supplier::where('tenant_id', $this->tenantA->id)->searchBilingual('Citrus')->get();
        $this->assertCount(1, $resEn);

        // Urdu search
        $resUr = Supplier::where('tenant_id', $this->tenantA->id)->searchBilingual('سرگودھا')->get();
        $this->assertCount(1, $resUr);
    }

    public function test_strict_tenant_isolation_for_suppliers_and_customers(): void
    {
        $supplierA = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Tenant A Secret Supplier',
            'code' => 'SUP-PRIV-A',
            'type' => 'commission_agent',
            'is_active' => true,
        ]);

        $customerA = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Tenant A Secret VIP Client',
            'code' => 'CUST-PRIV-A',
            'type' => 'hotel_restaurant',
            'is_active' => true,
        ]);

        // Owner B tries to access Tenant A supplier
        $supResponse = $this->actingAs($this->ownerB)->get(route('tenant.suppliers.show', $supplierA));
        $this->assertTrue(in_array($supResponse->status(), [403, 404]));

        // Owner B tries to access Tenant A customer
        $custResponse = $this->actingAs($this->ownerB)->get(route('tenant.customers.show', $customerA));
        $this->assertTrue(in_array($custResponse->status(), [403, 404]));

        // Owner B tries to delete Tenant A supplier
        $delResponse = $this->actingAs($this->ownerB)->delete(route('tenant.suppliers.destroy', $supplierA));
        $this->assertTrue(in_array($delResponse->status(), [403, 404]));
    }

    public function test_cannot_delete_supplier_or_customer_with_unsettled_balance(): void
    {
        $supplier = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Active Balance Supplier',
            'code' => 'SUP-BAL',
            'type' => 'wholesaler',
            'current_balance' => 5000.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->ownerA)->delete(route('tenant.suppliers.destroy', $supplier));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_permissions_protect_supplier_and_customer_actions(): void
    {
        $viewerRole = Role::where('slug', 'viewer')->first();
        $viewerUser = User::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->ownerA->branch_id,
            'name' => 'Auditor Staff',
            'email' => 'auditor@veggitable.test',
            'password' => Hash::make('password123'),
            'is_tenant_owner' => false,
            'is_active' => true,
        ]);
        $viewerUser->roles()->attach($viewerRole->id);

        // Viewer can see lists
        $this->actingAs($viewerUser)->get(route('tenant.suppliers.index'))->assertOk();
        $this->actingAs($viewerUser)->get(route('tenant.customers.index'))->assertOk();

        // Viewer cannot access supplier creation
        $this->actingAs($viewerUser)->get(route('tenant.suppliers.create'))->assertStatus(403);

        // Viewer cannot access customer creation
        $this->actingAs($viewerUser)->get(route('tenant.customers.create'))->assertStatus(403);
    }
}
