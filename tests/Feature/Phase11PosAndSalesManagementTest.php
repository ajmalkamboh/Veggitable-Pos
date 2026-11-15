<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\FinancialAccount;
use App\Models\HeldSalesTicket;
use App\Models\InventoryBatch;
use App\Models\InventoryStock;
use App\Models\PartyLedgerEntry;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\StockMovement;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase11PosAndSalesManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected User $cashierA;

    protected User $cashierB;

    protected Customer $customerCredit;

    protected Product $productTomato;

    protected Unit $unitKg;

    protected FinancialAccount $accountA;

    protected TenantContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->context = app(TenantContext::class);
        $this->context->reset();

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise',
            'price_monthly' => 10000.00,
            'max_branches' => 20,
            'max_users' => 50,
            'features' => ['pos_counter', 'inventory_management'],
            'is_active' => true,
        ]);

        // Tenant A
        $this->tenantA = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'onboarding_completed' => true,
            'currency' => 'PKR',
        ]);
        $this->branchA = $this->tenantA->branches()->create([
            'name' => 'Gulberg Branch Counter',
            'code' => 'BR-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);
        $this->cashierA = User::create([
            'tenant_id' => $this->tenantA->id,
            'current_branch_id' => $this->branchA->id,
            'name' => 'Cashier Zeeshan',
            'email' => 'zeeshan@veggitable.test',
            'password' => Hash::make('password123'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        $this->cashierA->branches()->attach($this->branchA->id);
        $this->cashierA->roles()->attach(Role::where('slug', 'owner')->first()->id);

        $this->accountA = FinancialAccount::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Main POS Cash Drawer',
            'account_type' => 'cash',
            'account_number' => 'CASH-001',
            'current_balance' => 10000.00,
            'is_active' => true,
        ]);

        $this->customerCredit = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'code' => 'CUST-0001',
            'name' => 'Pearl Continental Hotel Kitchen',
            'phone' => '03211234567',
            'type' => 'hotel_restaurant',
            'credit_limit' => 5000.00,
            'credit_days' => 15,
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'is_active' => true,
        ]);

        $cat = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Fresh Vegetables',
            'name_ur' => 'تازہ سبزیاں',
            'slug' => 'fresh-vegetables',
            'type' => 'product',
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
            'name' => 'Farm Fresh Tomatoes',
            'name_ur' => 'ٹماٹر',
            'code' => 'PRD-TOMATO-01',
            'barcode' => '2000000001',
            'purchase_price' => 70.00,
            'cost_price' => 70.00,
            'selling_price' => 120.00,
            'reorder_level' => 10.00,
            'is_active' => true,
        ]);

        // Tenant B
        $this->tenantB = Tenant::factory()->create([
            'plan_id' => $plan->id,
            'onboarding_completed' => true,
        ]);
        $this->branchB = $this->tenantB->branches()->create([
            'name' => 'Branch B Counter',
            'code' => 'BR-B1',
            'is_headquarters' => true,
            'is_active' => true,
        ]);
        $this->cashierB = User::create([
            'tenant_id' => $this->tenantB->id,
            'current_branch_id' => $this->branchB->id,
            'name' => 'Cashier Beta',
            'email' => 'beta@veggitable.test',
            'password' => Hash::make('password123'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        $this->cashierB->branches()->attach($this->branchB->id);
        $this->cashierB->roles()->attach(Role::where('slug', 'owner')->first()->id);
    }

    public function test_cashier_can_open_and_close_till_session(): void
    {
        $this->context->setTenant($this->tenantA);

        // 1. Open Till
        $responseOpen = $this->actingAs($this->cashierA)->post(route('tenant.pos.session.open'), [
            'branch_id' => $this->branchA->id,
            'opening_cash' => 5000.00,
            'notes' => 'Morning counter open',
        ]);

        $responseOpen->assertRedirect(route('tenant.pos.counter'));

        $session = PosSession::where('tenant_id', $this->tenantA->id)
            ->where('branch_id', $this->branchA->id)
            ->where('status', 'open')
            ->first();

        $this->assertNotNull($session);
        $this->assertEquals(5000.00, (float) $session->opening_cash);

        // 2. Close Till with physical count 5500 (Expected 5000, Variance +500)
        $responseClose = $this->actingAs($this->cashierA)->post(route('tenant.pos.session.close', $session), [
            'closing_cash' => 5500.00,
            'notes' => 'Evening till close',
        ]);

        $responseClose->assertRedirect(route('tenant.sales.index'));

        $session->refresh();
        $this->assertEquals('closed', $session->status);
        $this->assertEquals(5500.00, (float) $session->closing_cash);
        $this->assertEquals(500.00, (float) $session->cash_difference);
    }

    public function test_pos_cash_sale_deducts_inventory_and_records_cogs_and_movement(): void
    {
        $this->context->setTenant($this->tenantA);

        // Setup stock: 50kg tomatoes @ cost 70.00
        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productTomato->id,
            'quantity' => 50.000,
            'cost_price' => 70.00,
        ]);

        InventoryBatch::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productTomato->id,
            'lot_number' => 'LOT-TOM-SALE-01',
            'received_date' => now()->toDateString(),
            'initial_quantity' => 50.000,
            'current_quantity' => 50.000,
            'unit_cost' => 70.00,
            'status' => 'active',
        ]);

        // Sell 10kg tomatoes @ 120.00 = 1200.00 (Cost = 10 * 70 = 700.00, Profit = 500.00)
        $checkoutData = [
            'branch_id' => $this->branchA->id,
            'payment_method' => 'cash',
            'discount_amount' => 0.00,
            'paid_amount' => 1200.00,
            'financial_account_id' => $this->accountA->id,
            'items' => [
                [
                    'product_id' => $this->productTomato->id,
                    'quantity' => 10.000,
                    'unit_price' => 120.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA)->post(route('tenant.pos.checkout'), $checkoutData);
        $response->assertRedirect();

        $invoice = SalesInvoice::where('tenant_id', $this->tenantA->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(1200.00, (float) $invoice->total_amount);
        $this->assertEquals(1200.00, (float) $invoice->paid_amount);
        $this->assertEquals(700.00, (float) $invoice->cost_amount);
        $this->assertEquals(500.00, (float) $invoice->profit_amount);
        $this->assertEquals('paid', $invoice->payment_status);

        // 1. Stock decreased to 40 kg
        $stock = InventoryStock::where('product_id', $this->productTomato->id)->first();
        $this->assertEquals(40.000, (float) $stock->quantity);

        // 2. Batch decreased to 40 kg
        $batch = InventoryBatch::where('product_id', $this->productTomato->id)->first();
        $this->assertEquals(40.000, (float) $batch->current_quantity);

        // 3. Movement logged as sale_out
        $movement = StockMovement::where('product_id', $this->productTomato->id)
            ->where('type', 'sale_out')
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(-10.000, (float) $movement->quantity_change);
        $this->assertEquals(50.000, (float) $movement->quantity_before);
        $this->assertEquals(40.000, (float) $movement->quantity_after);

        // 4. Financial Account incremented
        $this->accountA->refresh();
        $this->assertEquals(11200.00, (float) $this->accountA->current_balance);
    }

    public function test_pos_credit_sale_enforces_credit_limit_and_updates_customer_balance_and_ledger(): void
    {
        $this->context->setTenant($this->tenantA);

        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productTomato->id,
            'quantity' => 100.000,
            'cost_price' => 70.00,
        ]);

        // Customer limit is 5000.00. First credit sale = 3000.00 (allowed)
        $saleData = [
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customerCredit->id,
            'payment_method' => 'credit',
            'discount_amount' => 0.00,
            'paid_amount' => 0.00,
            'items' => [
                [
                    'product_id' => $this->productTomato->id,
                    'quantity' => 25.000,
                    'unit_price' => 120.00, // 25 * 120 = 3000.00
                ],
            ],
        ];

        $response = $this->actingAs($this->cashierA)->post(route('tenant.pos.checkout'), $saleData);
        $response->assertRedirect();

        $invoice = SalesInvoice::where('tenant_id', $this->tenantA->id)->first();
        $this->assertEquals(3000.00, (float) $invoice->total_amount);
        $this->assertEquals(0.00, (float) $invoice->paid_amount);
        $this->assertEquals(3000.00, (float) $invoice->due_amount);
        $this->assertEquals('unpaid', $invoice->payment_status);

        // Customer balance increased to 3000.00
        $this->customerCredit->refresh();
        $this->assertEquals(3000.00, (float) $this->customerCredit->current_balance);

        // Party ledger entry created
        $ledger = PartyLedgerEntry::where('party_type', 'customer')
            ->where('party_id', $this->customerCredit->id)
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(3000.00, (float) $ledger->debit);
        $this->assertEquals(3000.00, (float) $ledger->balance);

        // Second credit sale: 2500.00 (3000 + 2500 = 5500 > 5000 limit -> Must be rejected!)
        $excessData = [
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customerCredit->id,
            'payment_method' => 'credit',
            'discount_amount' => 0.00,
            'paid_amount' => 0.00,
            'items' => [
                [
                    'product_id' => $this->productTomato->id,
                    'quantity' => 25.000,
                    'unit_price' => 100.00, // 2500.00
                ],
            ],
        ];

        $responseExcess = $this->actingAs($this->cashierA)->post(route('tenant.pos.checkout'), $excessData);
        $responseExcess->assertSessionHasErrors(['customer_id']);
    }

    public function test_cart_hold_and_resume_lifecycle(): void
    {
        $this->context->setTenant($this->tenantA);

        // 1. Hold / Park Cart
        $holdData = [
            'cart_data' => [
                [
                    'product_id' => $this->productTomato->id,
                    'name' => 'Farm Fresh Tomatoes',
                    'quantity' => 5.000,
                    'unit_price' => 120.00,
                    'unit' => 'kg',
                    'discount_amount' => 0.00,
                ],
            ],
            'total_amount' => 600.00,
        ];

        $responseHold = $this->actingAs($this->cashierA)->post(route('tenant.pos.hold'), $holdData);
        $responseHold->assertStatus(200);

        $heldTicket = HeldSalesTicket::where('tenant_id', $this->tenantA->id)->first();
        $this->assertNotNull($heldTicket);
        $this->assertEquals('HOLD-01', $heldTicket->ticket_number);
        $this->assertEquals(600.00, (float) $heldTicket->total_amount);

        // 2. Resume and checkout held cart
        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productTomato->id,
            'quantity' => 50.000,
            'cost_price' => 70.00,
        ]);

        $checkoutData = [
            'branch_id' => $this->branchA->id,
            'payment_method' => 'cash',
            'discount_amount' => 0.00,
            'paid_amount' => 600.00,
            'held_ticket_id' => $heldTicket->id,
            'items' => [
                [
                    'product_id' => $this->productTomato->id,
                    'quantity' => 5.000,
                    'unit_price' => 120.00,
                ],
            ],
        ];

        $responseCheckout = $this->actingAs($this->cashierA)->post(route('tenant.pos.checkout'), $checkoutData);
        $responseCheckout->assertRedirect();

        // Held ticket deleted after completion
        $this->assertNull(HeldSalesTicket::find($heldTicket->id));
    }

    public function test_sales_return_refunds_and_restocks_produce(): void
    {
        $this->context->setTenant($this->tenantA);

        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productTomato->id,
            'quantity' => 40.000,
            'cost_price' => 70.00,
        ]);

        $invoice = SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customerCredit->id,
            'invoice_no' => 'INV-2026-RET-01',
            'date' => now()->toDateString(),
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 1200.00,
            'total_amount' => 1200.00,
            'paid_amount' => 1200.00,
            'cost_amount' => 700.00,
            'created_by' => $this->cashierA->id,
        ]);

        $invoice->items()->create([
            'tenant_id' => $this->tenantA->id,
            'product_id' => $this->productTomato->id,
            'quantity' => 10.000,
            'unit_price' => 120.00,
            'unit_cost' => 70.00,
            'subtotal' => 1200.00,
        ]);

        // Process return of 2kg tomatoes @ 120 = 240.00 cash refund
        $returnData = [
            'date' => now()->toDateString(),
            'reason' => 'Customer brought back damaged tomatoes',
            'refund_method' => 'cash',
            'financial_account_id' => $this->accountA->id,
            'items' => [
                [
                    'product_id' => $this->productTomato->id,
                    'quantity' => 2.000,
                    'unit_price' => 120.00,
                ],
            ],
        ];

        $responseReturn = $this->actingAs($this->cashierA)
            ->post(route('tenant.sales.returns.store', $invoice), $returnData);

        $responseReturn->assertRedirect(route('tenant.sales.show', $invoice));

        $return = SalesReturn::where('tenant_id', $this->tenantA->id)->first();
        $this->assertNotNull($return);
        $this->assertEquals(240.00, (float) $return->total_amount);

        // Stock replenished from 40 to 42 kg
        $stock = InventoryStock::where('product_id', $this->productTomato->id)->first();
        $this->assertEquals(42.000, (float) $stock->quantity);

        // Movement logged as adjustment_in
        $movement = StockMovement::where('type', 'adjustment_in')
            ->where('reference_type', 'sales_return')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(2.000, (float) $movement->quantity_change);

        // Financial account decremented by 240 (10000 -> 9760)
        $this->accountA->refresh();
        $this->assertEquals(9760.00, (float) $this->accountA->current_balance);
    }

    public function test_thermal_receipt_renders_correctly(): void
    {
        $this->context->setTenant($this->tenantA);

        $invoice = SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'invoice_no' => 'INV-2026-PRINT-01',
            'date' => now()->toDateString(),
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 600.00,
            'total_amount' => 600.00,
            'paid_amount' => 1000.00,
            'change_amount' => 400.00,
            'cost_amount' => 350.00,
            'created_by' => $this->cashierA->id,
        ]);

        $invoice->items()->create([
            'tenant_id' => $this->tenantA->id,
            'product_id' => $this->productTomato->id,
            'unit_id' => $this->unitKg->id,
            'quantity' => 5.000,
            'unit_price' => 120.00,
            'subtotal' => 600.00,
        ]);

        $response = $this->actingAs($this->cashierA)->get(route('tenant.pos.receipt', $invoice));

        $response->assertStatus(200);
        $response->assertSee('INV-2026-PRINT-01');
        $response->assertSee('Farm Fresh Tomatoes');
        $response->assertSee('شکریہ! دوبارہ تشریف لائیں');
    }

    public function test_tenant_isolation_protects_sales_invoices(): void
    {
        $this->context->setTenant($this->tenantA);

        $invoiceA = SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'invoice_no' => 'INV-ISO-01',
            'date' => now()->toDateString(),
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 500.00,
            'total_amount' => 500.00,
            'paid_amount' => 500.00,
            'cost_amount' => 300.00,
            'created_by' => $this->cashierA->id,
        ]);

        // Attempt by Cashier B to inspect Tenant A's sales
        $this->context->setTenant($this->tenantB);

        $responseShow = $this->actingAs($this->cashierB)->get(route('tenant.sales.show', $invoiceA));
        $this->assertContains($responseShow->status(), [403, 404]);

        $responseReceipt = $this->actingAs($this->cashierB)->get(route('tenant.pos.receipt', $invoiceA));
        $this->assertContains($responseReceipt->status(), [403, 404]);
    }
}
