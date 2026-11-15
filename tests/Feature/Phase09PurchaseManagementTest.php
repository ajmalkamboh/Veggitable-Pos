<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\PartyLedgerEntry;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase09PurchaseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected User $ownerA;

    protected User $ownerB;

    protected FinancialAccount $accountA;

    protected Supplier $supplierA;

    protected Product $productA;

    protected Unit $unitKg;

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
            'features' => ['purchases', 'inventory_management'],
            'is_active' => true,
        ]);

        // Tenant A
        $this->tenantA = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'onboarding_completed' => true,
            'currency' => 'PKR',
        ]);
        $this->branchA = $this->tenantA->branches()->create([
            'name' => 'Badami Bagh Branch',
            'code' => 'BR-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);
        $this->ownerA = User::create([
            'tenant_id' => $this->tenantA->id,
            'current_branch_id' => $this->branchA->id,
            'name' => 'Malik Aslam',
            'email' => 'malik@veggitable.test',
            'password' => Hash::make('password123'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        $this->ownerA->branches()->attach($this->branchA->id);
        $this->ownerA->roles()->attach(Role::where('slug', 'owner')->first()->id);

        $this->accountA = FinancialAccount::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Main Cash Drawer',
            'account_type' => 'cash',
            'account_number' => 'CASH-001',
            'current_balance' => 50000.00,
            'is_active' => true,
        ]);
        $this->supplierA = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'code' => 'SUP-0001',
            'name' => 'Haji Bashir Commission Shop',
            'contact_person' => 'Haji Bashir',
            'phone' => '03001234567',
            'mandi_name' => 'Badami Bagh Subzi Mandi',
            'stall_number' => 'Stall 42',
            'commission_agent_name' => 'Chaudhry Akram Arhti',
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
        $this->productA = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'unit_id' => $this->unitKg->id,
            'name' => 'Farm Fresh Tomatoes',
            'name_ur' => 'تازہ ٹماٹر',
            'code' => 'PRD-0001',
            'barcode' => '2000000001',
            'purchase_price' => 80.00,
            'cost_price' => 80.00,
            'selling_price' => 120.00,
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
            'email' => 'ownerb@veggitable.test',
            'password' => Hash::make('password123'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        $this->ownerB->branches()->attach($this->branchB->id);
        $this->ownerB->roles()->attach(Role::where('slug', 'owner')->first()->id);
    }

    public function test_tenant_owner_can_view_purchases_index(): void
    {
        $this->context->setTenant($this->tenantA);

        $response = $this->actingAs($this->ownerA)->get(route('tenant.purchases.index'));

        $response->assertStatus(200);
        $response->assertSee('Mandi Purchases &amp; Invoices', false);
        $response->assertSee('New Mandi Purchase');
    }

    public function test_purchase_invoice_creation_with_gross_tare_net_weight_calculation(): void
    {
        $this->context->setTenant($this->tenantA);

        // Produce: 100kg gross scale, 5kg tare (crates), net = 95kg. Rate = 80 => Subtotal = 95 * 80 = 7600
        // Commission (5%) = 380
        // Mandi Fee (Chungi) = 100
        // Freight (Mazdoori) = 250
        // Total = 7600 + 380 + 100 + 250 = 8330
        $postData = [
            'supplier_id' => $this->supplierA->id,
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'supplier_invoice_no' => 'PARCHI-9874',
            'commission_rate' => 5.00,
            'mandi_fee' => 100.00,
            'freight_charges' => 250.00,
            'other_charges' => 0.00,
            'discount' => 0.00,
            'paid_amount' => 0.00,
            'notes' => 'Morning auction produce delivery',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'unit_id' => $this->unitKg->id,
                    'quantity' => 10, // 10 crates
                    'gross_weight' => 100.00,
                    'tare_weight' => 5.00,
                    'unit_price' => 80.00,
                    'lot_number' => 'LOT-TOMATO-01',
                ],
            ],
        ];

        $response = $this->actingAs($this->ownerA)->post(route('tenant.purchases.store'), $postData);

        $response->assertRedirect();

        $invoice = PurchaseInvoice::where('tenant_id', $this->tenantA->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(7600.00, (float) $invoice->subtotal);
        $this->assertEquals(380.00, (float) $invoice->commission_amount);
        $this->assertEquals(100.00, (float) $invoice->mandi_fee);
        $this->assertEquals(250.00, (float) $invoice->freight_charges);
        $this->assertEquals(8330.00, (float) $invoice->total_amount);
        $this->assertEquals(0.00, (float) $invoice->paid_amount);
        $this->assertEquals(8330.00, (float) $invoice->due_amount);
        $this->assertEquals('unpaid', $invoice->payment_status);

        // Check line item net weight calculation
        $item = $invoice->items()->first();
        $this->assertNotNull($item);
        $this->assertEquals(100.00, (float) $item->gross_weight);
        $this->assertEquals(5.00, (float) $item->tare_weight);
        $this->assertEquals(95.00, (float) $item->net_weight);
        $this->assertEquals(7600.00, (float) $item->subtotal);

        // Check supplier balance updated
        $this->supplierA->refresh();
        $this->assertEquals(8330.00, (float) $this->supplierA->current_balance);

        // Check party ledger entry created
        $ledger = PartyLedgerEntry::where('tenant_id', $this->tenantA->id)
            ->where('party_type', 'supplier')
            ->where('party_id', $this->supplierA->id)
            ->where('reference_type', 'purchase_invoice')
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(8330.00, (float) $ledger->credit);
        $this->assertEquals(0.00, (float) $ledger->debit);
        $this->assertEquals(8330.00, (float) $ledger->balance);
    }

    public function test_purchase_invoice_creation_with_immediate_cash_payment(): void
    {
        $this->context->setTenant($this->tenantA);

        // Produce: 50kg net at 100/kg = 5000. Commission = 250. Freight = 150. Total = 5400.
        // Full payment of 5400 from cash drawer.
        $postData = [
            'supplier_id' => $this->supplierA->id,
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'commission_rate' => 5.00,
            'mandi_fee' => 0.00,
            'freight_charges' => 150.00,
            'other_charges' => 0.00,
            'discount' => 0.00,
            'paid_amount' => 5400.00,
            'financial_account_id' => $this->accountA->id,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'unit_id' => $this->unitKg->id,
                    'quantity' => 50,
                    'gross_weight' => 52.00,
                    'tare_weight' => 2.00,
                    'unit_price' => 100.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->ownerA)->post(route('tenant.purchases.store'), $postData);

        $response->assertRedirect();

        $invoice = PurchaseInvoice::where('tenant_id', $this->tenantA->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(5400.00, (float) $invoice->total_amount);
        $this->assertEquals(5400.00, (float) $invoice->paid_amount);
        $this->assertEquals(0.00, (float) $invoice->due_amount);
        $this->assertEquals('paid', $invoice->payment_status);

        // Account balance decreased from 50000 to 44600
        $this->accountA->refresh();
        $this->assertEquals(44600.00, (float) $this->accountA->current_balance);

        // Supplier balance remains 0 because full payment was settled
        $this->supplierA->refresh();
        $this->assertEquals(0.00, (float) $this->supplierA->current_balance);

        // Two ledger entries: Invoice credit and Payment debit
        $entries = PartyLedgerEntry::where('party_id', $this->supplierA->id)->get();
        $this->assertCount(2, $entries);
    }

    public function test_purchase_return_debit_note_creation_and_balance_adjustment(): void
    {
        $this->context->setTenant($this->tenantA);

        // Create base invoice of 5000 unpaid
        $invoice = PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'supplier_id' => $this->supplierA->id,
            'invoice_no' => 'PI-2601-0001',
            'date' => now()->toDateString(),
            'status' => 'received',
            'payment_status' => 'unpaid',
            'subtotal' => 5000.00,
            'commission_rate' => 0.00,
            'commission_amount' => 0.00,
            'mandi_fee' => 0.00,
            'freight_charges' => 0.00,
            'other_charges' => 0.00,
            'discount' => 0.00,
            'total_amount' => 5000.00,
            'paid_amount' => 0.00,
            'created_by' => $this->ownerA->id,
        ]);

        $invoice->items()->create([
            'tenant_id' => $this->tenantA->id,
            'product_id' => $this->productA->id,
            'unit_id' => $this->unitKg->id,
            'quantity' => 50,
            'unit_price' => 100.00,
            'subtotal' => 5000.00,
            'lot_number' => 'LOT-001',
        ]);

        $this->supplierA->update(['current_balance' => 5000.00]);

        // Post return / debit note for 10kg @ 100 = 1000
        $returnData = [
            'date' => now()->toDateString(),
            'reason' => 'Spoiled rotten produce on bottom layer',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 10,
                    'unit_price' => 100.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->ownerA)
            ->post(route('tenant.purchases.returns.store', $invoice), $returnData);

        $response->assertRedirect(route('tenant.purchases.show', $invoice));

        $return = PurchaseReturn::where('tenant_id', $this->tenantA->id)->first();
        $this->assertNotNull($return);
        $this->assertEquals(1000.00, (float) $return->total_amount);
        $this->assertEquals('Spoiled rotten produce on bottom layer', $return->reason);

        // Supplier balance reduced to 4000
        $this->supplierA->refresh();
        $this->assertEquals(4000.00, (float) $this->supplierA->current_balance);

        // Debit entry in party ledger
        $ledger = PartyLedgerEntry::where('party_id', $this->supplierA->id)
            ->where('reference_type', 'purchase_return')
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(1000.00, (float) $ledger->debit);
        $this->assertEquals(4000.00, (float) $ledger->balance);
    }

    public function test_purchase_invoice_cancellation_reverses_ledger_and_balances(): void
    {
        $this->context->setTenant($this->tenantA);

        $invoice = PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'supplier_id' => $this->supplierA->id,
            'invoice_no' => 'PI-2601-0002',
            'date' => now()->toDateString(),
            'status' => 'received',
            'payment_status' => 'paid',
            'subtotal' => 2000.00,
            'commission_rate' => 0.00,
            'commission_amount' => 0.00,
            'mandi_fee' => 0.00,
            'freight_charges' => 0.00,
            'other_charges' => 0.00,
            'discount' => 0.00,
            'total_amount' => 2000.00,
            'paid_amount' => 2000.00,
            'financial_account_id' => $this->accountA->id,
            'created_by' => $this->ownerA->id,
        ]);

        $this->accountA->update(['current_balance' => 48000.00]);
        $this->supplierA->update(['current_balance' => 0.00]);

        $response = $this->actingAs($this->ownerA)
            ->delete(route('tenant.purchases.destroy', $invoice));

        $response->assertRedirect(route('tenant.purchases.index'));

        $invoice->refresh();
        $this->assertEquals('cancelled', $invoice->status);

        // Financial account refunded 2000 -> 50000
        $this->accountA->refresh();
        $this->assertEquals(50000.00, (float) $this->accountA->current_balance);

        // Reversal ledger entry logged
        $reversalLedger = PartyLedgerEntry::where('party_id', $this->supplierA->id)
            ->where('reference_type', 'adjustment')
            ->first();

        $this->assertNotNull($reversalLedger);
        $this->assertEquals(2000.00, (float) $reversalLedger->debit);
    }

    public function test_tenant_isolation_prevents_viewing_or_modifying_other_tenant_purchases(): void
    {
        $this->context->setTenant($this->tenantA);

        $invoiceA = PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'supplier_id' => $this->supplierA->id,
            'invoice_no' => 'PI-2601-ISO',
            'date' => now()->toDateString(),
            'status' => 'received',
            'payment_status' => 'unpaid',
            'subtotal' => 3000.00,
            'commission_rate' => 0.00,
            'commission_amount' => 0.00,
            'mandi_fee' => 0.00,
            'freight_charges' => 0.00,
            'other_charges' => 0.00,
            'discount' => 0.00,
            'total_amount' => 3000.00,
            'paid_amount' => 0.00,
            'created_by' => $this->ownerA->id,
        ]);

        // Attempt by Owner B to access Tenant A's invoice
        $this->context->setTenant($this->tenantB);

        $responseShow = $this->actingAs($this->ownerB)->get(route('tenant.purchases.show', $invoiceA));
        $this->assertContains($responseShow->status(), [403, 404]);

        $responseDelete = $this->actingAs($this->ownerB)->delete(route('tenant.purchases.destroy', $invoiceA));
        $this->assertContains($responseDelete->status(), [403, 404]);
    }

    public function test_print_view_renders_correctly(): void
    {
        $this->context->setTenant($this->tenantA);

        $invoice = PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'supplier_id' => $this->supplierA->id,
            'invoice_no' => 'PI-PRINT-TEST',
            'date' => now()->toDateString(),
            'status' => 'received',
            'payment_status' => 'unpaid',
            'subtotal' => 1500.00,
            'commission_rate' => 5.00,
            'commission_amount' => 75.00,
            'mandi_fee' => 25.00,
            'freight_charges' => 50.00,
            'other_charges' => 0.00,
            'discount' => 0.00,
            'total_amount' => 1650.00,
            'paid_amount' => 0.00,
            'created_by' => $this->ownerA->id,
        ]);

        $invoice->items()->create([
            'tenant_id' => $this->tenantA->id,
            'product_id' => $this->productA->id,
            'unit_id' => $this->unitKg->id,
            'quantity' => 20,
            'unit_price' => 75.00,
            'subtotal' => 1500.00,
            'lot_number' => 'LOT-PRN-01',
        ]);

        $response = $this->actingAs($this->ownerA)->get(route('tenant.purchases.print', $invoice));

        $response->assertStatus(200);
        $response->assertSee('MANDI PURCHASE VOUCHER');
        $response->assertSee('PI-PRINT-TEST');
        $response->assertSee('Farm Fresh Tomatoes');
    }
}
