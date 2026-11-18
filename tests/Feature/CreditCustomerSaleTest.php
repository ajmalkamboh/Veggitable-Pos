<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\InventoryStock;
use App\Models\PartyLedgerEntry;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreditCustomerSaleTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected User $userA;

    protected User $userB;

    protected Customer $customerA;

    protected Product $tomato;

    protected Product $potato;

    protected FinancialAccount $cashAccount;

    protected Unit $kg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'name' => 'Madina Vegetable Store',
            'slug' => 'madina-veg',
            'currency' => 'PKR',
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Bismillah Greengrocers',
            'slug' => 'bismillah-veg',
            'currency' => 'PKR',
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchA = Branch::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Main Outlet',
            'code' => 'HQ-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Branch B',
            'code' => 'BR-02',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->userA = User::create([
            'name' => 'Tariq Shopkeeper',
            'email' => 'tariq@madina.test',
            'password' => Hash::make('password'),
            'tenant_id' => $this->tenantA->id,
            'current_branch_id' => $this->branchA->id,
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);

        $this->userB = User::create([
            'name' => 'Akram Shopkeeper',
            'email' => 'akram@bismillah.test',
            'password' => Hash::make('password'),
            'tenant_id' => $this->tenantB->id,
            'current_branch_id' => $this->branchB->id,
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);

        $this->userA->branches()->attach($this->branchA->id, ['tenant_id' => $this->tenantA->id, 'is_default' => true]);
        $this->userB->branches()->attach($this->branchB->id, ['tenant_id' => $this->tenantB->id, 'is_default' => true]);

        $ownerRole = Role::firstOrCreate(
            ['slug' => 'owner', 'tenant_id' => null],
            ['name' => 'Owner', 'module' => 'platform', 'is_system' => true]
        );
        $this->userA->roles()->attach($ownerRole->id, ['tenant_id' => $this->tenantA->id]);
        $this->userB->roles()->attach($ownerRole->id, ['tenant_id' => $this->tenantB->id]);

        $this->kg = Unit::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Kilogram',
            'name_ur' => 'کلوگرام',
            'code' => 'kg',
            'is_fractional' => true,
        ]);

        $this->tomato = Product::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Fresh Tomatoes',
            'name_ur' => 'تازہ ٹماٹر',
            'code' => 'PROD-TOM-01',
            'stock_unit_id' => $this->kg->id,
            'cost_price' => 50.00,
            'selling_price' => 80.00,
            'is_active' => true,
        ]);

        $this->potato = Product::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'White Potatoes',
            'name_ur' => 'سفید آلو',
            'code' => 'PROD-POT-01',
            'stock_unit_id' => $this->kg->id,
            'cost_price' => 35.00,
            'selling_price' => 60.00,
            'is_active' => true,
        ]);

        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->tomato->id,
            'quantity' => 100.000,
            'cost_price' => 50.00,
        ]);

        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->potato->id,
            'quantity' => 200.000,
            'cost_price' => 35.00,
        ]);

        $this->customerA = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Al-Khan Hotel & Restaurant',
            'name_ur' => 'الخان ہوٹل',
            'code' => 'CUST-0001',
            'phone' => '03001234567',
            'credit_limit' => 50000.00,
            'opening_balance' => 0.00,
            'current_balance' => 5000.00,
            'is_active' => true,
        ]);

        $this->cashAccount = FinancialAccount::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Main Counter Cash',
            'account_type' => 'cash',
            'account_number' => 'CASH-001',
            'current_balance' => 10000.00,
            'is_active' => true,
        ]);
    }

    public function test_user_can_access_credit_sale_create_page(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('tenant.credit-sales.create'));

        $response->assertOk();
        $response->assertSee('Credit Customer Sale Entry');
        $response->assertSee('Al-Khan Hotel & Restaurant');
        $response->assertSee('Fresh Tomatoes');
        $response->assertSee('White Potatoes');
    }

    public function test_user_can_record_credit_sale_with_inventory_and_khata_impact(): void
    {
        $postData = [
            'customer_id' => $this->customerA->id,
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'delivery_charges' => 150.00,
            'discount_amount' => 50.00,
            'paid_amount' => 0.00,
            'notes' => 'Supplied for dinner banquet',
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'unit_id' => $this->kg->id,
                    'quantity' => 20.00,
                    'unit_price' => 85.00, // 20 * 85 = 1,700
                ],
                [
                    'product_id' => $this->potato->id,
                    'unit_id' => $this->kg->id,
                    'quantity' => 40.00,
                    'unit_price' => 60.00, // 40 * 60 = 2,400
                ],
            ],
        ];

        // Subtotal = 1,700 + 2,400 = 4,100
        // Grand Total = 4,100 + 150 (delivery) - 50 (discount) = 4,200
        // Net Due = 4,200 (Paid = 0)

        $response = $this->actingAs($this->userA)
            ->post(route('tenant.credit-sales.store'), $postData);

        $response->assertRedirect();

        // 1. Check Invoice Header
        $invoice = SalesInvoice::where('tenant_id', $this->tenantA->id)
            ->where('payment_method', 'credit')
            ->latest('id')
            ->first();

        $this->assertNotNull($invoice);
        $this->assertEquals(4100.00, (float) $invoice->subtotal);
        $this->assertEquals(150.00, (float) $invoice->delivery_charges);
        $this->assertEquals(50.00, (float) $invoice->discount_amount);
        $this->assertEquals(4200.00, (float) $invoice->total_amount);
        $this->assertEquals(0.00, (float) $invoice->paid_amount);
        $this->assertEquals(4200.00, (float) $invoice->due_amount);
        $this->assertEquals('unpaid', $invoice->payment_status);

        // 2. Check Line Items
        $this->assertCount(2, $invoice->items);

        // 3. Check Inventory Deductions
        $tomatoStock = InventoryStock::where('tenant_id', $this->tenantA->id)
            ->where('branch_id', $this->branchA->id)
            ->where('product_id', $this->tomato->id)
            ->first();
        $this->assertEquals(80.000, (float) $tomatoStock->quantity); // 100 - 20 = 80

        $potatoStock = InventoryStock::where('tenant_id', $this->tenantA->id)
            ->where('branch_id', $this->branchA->id)
            ->where('product_id', $this->potato->id)
            ->first();
        $this->assertEquals(160.000, (float) $potatoStock->quantity); // 200 - 40 = 160

        // 4. Check Stock Movements
        $movements = StockMovement::where('tenant_id', $this->tenantA->id)
            ->where('reference_id', $invoice->id)
            ->get();
        $this->assertCount(2, $movements);

        // 5. Check Customer Khata Balance (Initial 5,000 + 4,200 due = 9,200)
        $this->customerA->refresh();
        $this->assertEquals(9200.00, (float) $this->customerA->current_balance);

        // 6. Check Party Ledger Entry
        $ledgerEntry = PartyLedgerEntry::where('tenant_id', $this->tenantA->id)
            ->where('party_id', $this->customerA->id)
            ->where('reference_id', $invoice->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($ledgerEntry);
        $this->assertEquals(4200.00, (float) $ledgerEntry->debit);
        $this->assertEquals(0.00, (float) $ledgerEntry->credit);
        $this->assertEquals(9200.00, (float) $ledgerEntry->balance);
    }

    public function test_partial_payment_on_credit_sale(): void
    {
        $postData = [
            'customer_id' => $this->customerA->id,
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'delivery_charges' => 0.00,
            'discount_amount' => 0.00,
            'paid_amount' => 1000.00, // Rs 1,000 paid immediately in cash
            'financial_account_id' => $this->cashAccount->id,
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'unit_id' => $this->kg->id,
                    'quantity' => 30.00,
                    'unit_price' => 100.00, // 30 * 100 = 3,000
                ],
            ],
        ];

        // Total = 3,000. Paid = 1,000. Net Due = 2,000.
        // Customer balance was 5,000. New balance = 5,000 + 2,000 = 7,000.

        $response = $this->actingAs($this->userA)
            ->post(route('tenant.credit-sales.store'), $postData);

        $response->assertRedirect();

        $invoice = SalesInvoice::where('tenant_id', $this->tenantA->id)
            ->where('payment_method', 'credit')
            ->latest('id')
            ->first();

        $this->assertEquals('partial', $invoice->payment_status);
        $this->assertEquals(1000.00, (float) $invoice->paid_amount);
        $this->assertEquals(2000.00, (float) $invoice->due_amount);

        $this->customerA->refresh();
        $this->assertEquals(7000.00, (float) $this->customerA->current_balance);

        $this->cashAccount->refresh();
        $this->assertEquals(11000.00, (float) $this->cashAccount->current_balance); // 10,000 + 1,000
    }

    public function test_credit_limit_is_strictly_enforced(): void
    {
        // Set low credit limit of 6,000 (current balance is 5,000)
        $this->customerA->update(['credit_limit' => 6000.00]);

        $postData = [
            'customer_id' => $this->customerA->id,
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'paid_amount' => 0.00,
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'unit_id' => $this->kg->id,
                    'quantity' => 25.00,
                    'unit_price' => 100.00, // 2,500 due -> 5,000 + 2,500 = 7,500 > 6,000 limit
                ],
            ],
        ];

        $response = $this->actingAs($this->userA)
            ->post(route('tenant.credit-sales.store'), $postData);

        $response->assertSessionHasErrors('customer_id');

        // Customer balance should remain untouched
        $this->customerA->refresh();
        $this->assertEquals(5000.00, (float) $this->customerA->current_balance);
    }

    public function test_credit_sales_csv_export(): void
    {
        // Create a credit sale first
        $this->actingAs($this->userA)->post(route('tenant.credit-sales.store'), [
            'customer_id' => $this->customerA->id,
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'unit_id' => $this->kg->id,
                    'quantity' => 10.00,
                    'unit_price' => 80.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->userA)
            ->get(route('tenant.credit-sales.export-csv'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_single_invoice_csv_items_export(): void
    {
        $this->actingAs($this->userA)->post(route('tenant.credit-sales.store'), [
            'customer_id' => $this->customerA->id,
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'unit_id' => $this->kg->id,
                    'quantity' => 10.00,
                    'unit_price' => 80.00,
                ],
            ],
        ]);

        $invoice = SalesInvoice::where('tenant_id', $this->tenantA->id)->latest('id')->first();

        $response = $this->actingAs($this->userA)
            ->get(route('tenant.credit-sales.export-single-csv', $invoice));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_pdf_invoice_download(): void
    {
        $this->actingAs($this->userA)->post(route('tenant.credit-sales.store'), [
            'customer_id' => $this->customerA->id,
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'unit_id' => $this->kg->id,
                    'quantity' => 10.00,
                    'unit_price' => 80.00,
                ],
            ],
        ]);

        $invoice = SalesInvoice::where('tenant_id', $this->tenantA->id)->latest('id')->first();

        $response = $this->actingAs($this->userA)
            ->get(route('tenant.credit-sales.pdf', $invoice));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_whatsapp_share_redirect_contains_invoice_and_balance(): void
    {
        $this->actingAs($this->userA)->post(route('tenant.credit-sales.store'), [
            'customer_id' => $this->customerA->id,
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'unit_id' => $this->kg->id,
                    'quantity' => 10.00,
                    'unit_price' => 80.00,
                ],
            ],
        ]);

        $invoice = SalesInvoice::where('tenant_id', $this->tenantA->id)->latest('id')->first();

        $response = $this->actingAs($this->userA)
            ->get(route('tenant.credit-sales.whatsapp', $invoice));

        $response->assertRedirect();
        $targetUrl = $response->getTargetUrl();

        $this->assertStringContainsString('api.whatsapp.com/send', $targetUrl);
        $this->assertStringContainsString('923001234567', $targetUrl); // Converted 0300 to international 92300
    }

    public function test_cross_tenant_isolation_prevents_viewing_other_tenants_credit_sale(): void
    {
        $this->actingAs($this->userA)->post(route('tenant.credit-sales.store'), [
            'customer_id' => $this->customerA->id,
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $this->tomato->id,
                    'unit_id' => $this->kg->id,
                    'quantity' => 5.00,
                    'unit_price' => 80.00,
                ],
            ],
        ]);

        $invoiceA = SalesInvoice::where('tenant_id', $this->tenantA->id)->latest('id')->first();

        // User B from Tenant B attempts to view or download Tenant A's credit invoice
        $response = $this->actingAs($this->userB)
            ->get(route('tenant.credit-sales.show', $invoiceA));

        $this->assertContains($response->status(), [403, 404]);

        $pdfResponse = $this->actingAs($this->userB)
            ->get(route('tenant.credit-sales.pdf', $invoiceA));

        $this->assertContains($pdfResponse->status(), [403, 404]);
    }
}
