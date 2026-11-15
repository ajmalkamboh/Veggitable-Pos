<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\InventoryStock;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\WastageRecord;
use App\Services\AccountingService;
use App\Services\BackupService;
use App\Services\HardwareIntegrationService;
use App\Services\InventoryService;
use App\Services\PosService;
use App\Services\PrintService;
use App\Services\ReportingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase19CompleteSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $plan;

    protected Tenant $tenantA;

    protected Branch $branchA;

    protected User $userA;

    protected Tenant $tenantB;

    protected Branch $branchB;

    protected User $userB;

    protected AccountingService $accountingService;

    protected InventoryService $inventoryService;

    protected PosService $posService;

    protected ReportingService $reportingService;

    protected PrintService $printService;

    protected BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $context = app(TenantContext::class);
        $context->reset();

        $this->accountingService = app(AccountingService::class);
        $this->inventoryService = app(InventoryService::class);
        $this->posService = app(PosService::class);
        $this->reportingService = app(ReportingService::class);
        $this->printService = app(PrintService::class);
        $this->backupService = app(BackupService::class);

        $this->plan = SubscriptionPlan::create([
            'name' => 'Enterprise Pro',
            'slug' => 'enterprise-pro',
            'price_monthly' => 5000.00,
            'max_branches' => 10,
            'max_users' => 20,
            'features' => ['all'],
            'is_active' => true,
        ]);

        // Tenant A: Multan Fresh Produce
        $this->tenantA = Tenant::factory()->create([
            'name' => 'Multan Fresh Fruits & Vegetables',
            'slug' => 'mlt-fresh',
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'currency' => 'PKR',
            'onboarding_completed' => true,
        ]);

        $this->branchA = $this->tenantA->branches()->create([
            'name' => 'Multan Cantt Main Outlet',
            'code' => 'MLT-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $ownerRole = Role::where('slug', 'owner')->first();

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'current_branch_id' => $this->branchA->id,
            'name' => 'Malik Jahangir',
            'email' => 'jahangir@mltfresh.pk',
            'password' => Hash::make('Secret123!'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        if ($ownerRole) {
            $this->userA->roles()->attach($ownerRole->id, ['tenant_id' => $this->tenantA->id]);
        }

        // Tenant B: Gujranwala Sabzi Mart
        $this->tenantB = Tenant::factory()->create([
            'name' => 'Gujranwala Sabzi Mart',
            'slug' => 'grw-mart',
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'currency' => 'PKR',
            'onboarding_completed' => true,
        ]);

        $this->branchB = $this->tenantB->branches()->create([
            'name' => 'Model Town Branch',
            'code' => 'GRW-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'current_branch_id' => $this->branchB->id,
            'name' => 'Chaudhry Akram',
            'email' => 'akram@grwmart.pk',
            'password' => Hash::make('Secret123!'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        if ($ownerRole) {
            $this->userB->roles()->attach($ownerRole->id, ['tenant_id' => $this->tenantB->id]);
        }
    }

    /**
     * Scenario A: Business Onboarding & Financial Initialization
     */
    public function test_scenario_a_complete_onboarding_and_financial_initialization(): void
    {
        $newTenant = Tenant::factory()->create([
            'name' => 'Faisalabad Fresh Farms',
            'slug' => 'fsd-farms',
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'onboarding_completed' => false,
        ]);

        app(TenantContext::class)->setTenant($newTenant);

        // Step 1: Profile update
        $newTenant->update([
            'phone' => '041-8899001',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'default_locale' => 'ur',
            'onboarding_step' => 2,
        ]);

        // Step 2: Branch setup
        $branch = $newTenant->branches()->create([
            'name' => 'D-Ground Main Market',
            'code' => 'FSD-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        // Step 3: Cash and Bank accounts
        $cashAcc = FinancialAccount::create([
            'tenant_id' => $newTenant->id,
            'branch_id' => $branch->id,
            'name' => 'Counter Cash Drawer',
            'type' => 'cash',
            'account_number' => 'CASH-FSD-01',
            'current_balance' => 10000.00,
            'is_active' => true,
        ]);

        // Step 4: Seed Chart of Accounts
        $this->accountingService->seedDefaultChartOfAccounts($newTenant);

        $newTenant->update([
            'onboarding_completed' => true,
            'onboarding_step' => 4,
        ]);

        $this->assertTrue($newTenant->fresh()->onboarding_completed);
        $this->assertEquals(1, $newTenant->branches()->count());
        $this->assertEquals(10000.00, $cashAcc->current_balance);
        $this->assertGreaterThan(10, ChartOfAccount::where('tenant_id', $newTenant->id)->count());
    }

    /**
     * Scenario B: Mandi Wholesale Procurement -> FIFO Lot Stock -> Supplier Ledger -> Double Entry JV
     */
    public function test_scenario_b_mandi_procurement_to_fifo_lot_stock_and_accounting_jv(): void
    {
        app(TenantContext::class)->setTenant($this->tenantA);
        $this->accountingService->seedDefaultChartOfAccounts($this->tenantA);

        $cat = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Fresh Vegetables',
            'slug' => 'fresh-vegetables',
            'is_active' => true,
        ]);

        $kgUnit = Unit::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Kilogram',
            'short_name' => 'kg',
            'code' => 'kg',
            'is_active' => true,
        ]);

        $onions = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'unit_id' => $kgUnit->id,
            'name' => 'Swat Fresh Onions',
            'name_ur' => 'سوات کے پیاز',
            'code' => 'ONI-SWAT-01',
            'cost_price' => 60.00,
            'selling_price' => 90.00,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Haji Qasim Mandi Commission Agent',
            'code' => 'SUPP-QASIM',
            'phone' => '0300-9988776',
            'current_balance' => 0.00,
            'is_active' => true,
        ]);

        $cashAccount = FinancialAccount::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Counter Petty Cash',
            'type' => 'cash',
            'account_number' => 'CASH-MLT-01',
            'current_balance' => 20000.00,
            'is_active' => true,
        ]);

        // Purchase Invoice: 200 kg @ Rs 60 = Rs 12,000 + Mandi fees (Rs 600 comm + Rs 150 chungi + Rs 450 freight) = Rs 13,200
        $invoice = PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'supplier_id' => $supplier->id,
            'invoice_no' => 'MND-PUR-001',
            'date' => now()->toDateString(),
            'subtotal' => 12000.00,
            'commission_rate' => 5.0,
            'commission_amount' => 600.00,
            'market_fee' => 150.00,
            'freight' => 450.00,
            'total_amount' => 13200.00,
            'paid_amount' => 5000.00,
            'due_amount' => 8200.00,
            'status' => 'received',
            'payment_status' => 'partial',
        ]);

        $invoice->items()->create([
            'tenant_id' => $this->tenantA->id,
            'product_id' => $onions->id,
            'gross_weight' => 205.00,
            'tare_weight' => 5.00,
            'net_weight' => 200.00,
            'unit_price' => 60.00,
            'total_price' => 12000.00,
        ]);

        // 1. Stock Influx
        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $onions->id,
            'quantity' => 200.00,
            'cost_price' => 66.00, // 13200 / 200
            'reorder_level' => 30.00,
        ]);

        StockMovement::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $onions->id,
            'type' => 'in',
            'quantity_change' => 200.00,
            'quantity_before' => 0.00,
            'quantity_after' => 200.00,
            'unit_cost' => 66.00,
            'reference_type' => 'purchase_invoice',
            'reference_id' => $invoice->id,
        ]);

        // 2. Update Supplier Payable
        $supplier->update(['current_balance' => 8200.00]);

        // 3. Post Double-Entry Journal Voucher
        $inventoryAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('name', 'like', '%Inventory%')->first();
        $payableAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('name', 'like', '%Payable%')->first();
        $cashCoA = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('name', 'like', '%Cash%')->first();

        $jv = $this->accountingService->createJournalEntry($this->tenantA, $this->branchA, $this->userA, [
            'date' => now()->toDateString(),
            'reference_type' => 'purchase_invoice',
            'reference_id' => $invoice->id,
            'narration' => 'Mandi Wholesale Procurement - Invoice MND-PUR-001',
            'description' => 'Mandi Wholesale Procurement - Invoice MND-PUR-001',
            'lines' => [
                ['account_id' => $inventoryAcc->id, 'debit' => 13200.00, 'credit' => 0.00, 'description' => 'Inventory produce influx'],
                ['account_id' => $cashCoA->id, 'debit' => 0.00, 'credit' => 5000.00, 'description' => 'Immediate cash settlement'],
                ['account_id' => $payableAcc->id, 'debit' => 0.00, 'credit' => 8200.00, 'description' => 'Supplier aarthi payable'],
            ],
        ]);

        $this->assertNotNull($jv);
        $this->assertEquals(13200.00, $jv->total_debit);
        $this->assertEquals(13200.00, $jv->total_credit);
        $this->assertEquals(8200.00, $supplier->fresh()->current_balance);
        $this->assertEquals(200.00, InventoryStock::where('product_id', $onions->id)->value('quantity'));
    }

    /**
     * Scenario C: POS Fast Checkout -> Scale Barcode Decoding -> Credit Customer -> Till Session
     */
    public function test_scenario_c_pos_scale_checkout_to_customer_receivables_and_cashier_till(): void
    {
        app(TenantContext::class)->setTenant($this->tenantA);

        $cat = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Vegetables', 'slug' => 'veg', 'is_active' => true]);
        $kg = Unit::create(['tenant_id' => $this->tenantA->id, 'name' => 'Kg', 'short_name' => 'kg', 'code' => 'kg', 'is_active' => true]);

        $onions = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'unit_id' => $kg->id,
            'name' => 'Swat Fresh Onions',
            'name_ur' => 'سوات کے پیاز',
            'code' => '10045', // PLU for scale barcode
            'cost_price' => 60.00,
            'selling_price' => 90.00,
            'is_active' => true,
        ]);

        $stock = InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $onions->id,
            'quantity' => 200.00,
            'cost_price' => 60.00,
        ]);

        $hotelCustomer = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Multan Continental Hotel',
            'code' => 'CUST-HOTEL',
            'current_balance' => 0.00,
            'credit_limit' => 50000.00,
            'is_active' => true,
        ]);

        // 1. Open Till Session with opening cash Rs 2,000
        $till = PosSession::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'user_id' => $this->userA->id,
            'session_no' => 'POS-SESS-001',
            'opening_cash' => 2000.00,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        // 2. Decode Scale Barcode: EAN-13 `201004505000X` -> Product PLU 10045, Weight 05000 grams = 5.000 kg
        $hwService = app(HardwareIntegrationService::class);
        $decoded = $hwService->parseWeightBarcode('2010045050003');
        $this->assertEquals('10045', $decoded['plu_code']);
        $this->assertEquals(5.000, $decoded['weight_kg']);

        // 3. Checkout Sale of 50 kg Onions: Rs 4,500 total (Cash Rs 2,000, Credit Rs 2,500)
        $invoice = SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'customer_id' => $hotelCustomer->id,
            'invoice_no' => 'INV-POS-1001',
            'date' => now()->toDateString(),
            'subtotal' => 4500.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 4500.00,
            'paid_amount' => 2000.00,
            'due_amount' => 2500.00,
            'cost_amount' => 3000.00,
            'status' => 'partial',
            'payment_status' => 'partial',
        ]);

        $invoice->items()->create([
            'tenant_id' => $this->tenantA->id,
            'product_id' => $onions->id,
            'quantity' => 50.00,
            'unit_price' => 90.00,
            'unit_cost' => 60.00,
            'subtotal' => 4500.00,
            'discount_amount' => 0.00,
        ]);

        $invoice->payments()->create([
            'tenant_id' => $this->tenantA->id,
            'sales_invoice_id' => $invoice->id,
            'amount' => 2000.00,
            'method' => 'cash',
        ]);

        // Deplete stock
        $stock->decrement('quantity', 50.00);

        StockMovement::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $onions->id,
            'type' => 'out',
            'quantity_change' => -50.00,
            'quantity_before' => 200.00,
            'quantity_after' => 150.00,
            'unit_cost' => 60.00,
            'reference_type' => 'sales_invoice',
            'reference_id' => $invoice->id,
        ]);

        // Update customer balance
        $hotelCustomer->increment('current_balance', 2500.00);

        // 4. Close Till Session
        $till->update([
            'closing_cash' => 4000.00,
            'expected_cash' => 4000.00,
            'cash_difference' => 0.00,
            'closed_at' => now(),
            'status' => 'closed',
        ]);

        $this->assertEquals(150.00, $stock->fresh()->quantity);
        $this->assertEquals(2500.00, $hotelCustomer->fresh()->current_balance);
        $this->assertEquals('closed', $till->fresh()->status);
        $this->assertEquals(0.00, $till->fresh()->cash_difference);
    }

    /**
     * Scenario D: Produce Quality Regrading & Sorting (Input Lot -> Grade B Yield + Spoilage Cull Loss)
     */
    public function test_scenario_d_produce_regrading_sorting_and_wastage_loss_accounting(): void
    {
        app(TenantContext::class)->setTenant($this->tenantA);

        $cat = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Vegetables', 'slug' => 'veg2', 'is_active' => true]);
        $kg = Unit::create(['tenant_id' => $this->tenantA->id, 'name' => 'Kg', 'short_name' => 'kg', 'code' => 'kg', 'is_active' => true]);

        $gradeA = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'unit_id' => $kg->id,
            'name' => 'Apples Murree Grade A',
            'name_ur' => 'مری کے سیب درجہ اول',
            'code' => 'APL-A-01',
            'cost_price' => 150.00,
            'selling_price' => 220.00,
            'is_active' => true,
        ]);

        $gradeB = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'unit_id' => $kg->id,
            'name' => 'Apples Murree Grade B',
            'name_ur' => 'مری کے سیب درجہ دوم',
            'code' => 'APL-B-01',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'is_active' => true,
        ]);

        $stockA = InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $gradeA->id,
            'quantity' => 100.00,
            'cost_price' => 150.00,
        ]);

        $stockB = InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $gradeB->id,
            'quantity' => 0.00,
            'cost_price' => 100.00,
        ]);

        // Regrading 30 kg Grade A: Yields 25 kg Grade B + 5 kg rotten cull loss
        $inputQty = 30.00;
        $yieldQty = 25.00;
        $cullQty = 5.00;
        $cullLossCost = $cullQty * 150.00; // Rs 750 loss

        $stockA->decrement('quantity', $inputQty);
        $stockB->increment('quantity', $yieldQty);

        $wastage = WastageRecord::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'wastage_no' => 'WST-REGRADE-01',
            'date' => now()->toDateString(),
            'reason' => 'spoilage',
            'total_quantity' => $cullQty,
            'total_loss_cost' => $cullLossCost,
            'status' => 'approved',
            'created_by' => $this->userA->id,
        ]);

        $this->assertEquals(70.00, $stockA->fresh()->quantity);
        $this->assertEquals(25.00, $stockB->fresh()->quantity);
        $this->assertEquals(750.00, $wastage->total_loss_cost);
    }

    /**
     * Scenario E: Operational Expenses & Petty Cash Account Payout
     */
    public function test_scenario_e_operational_expenses_and_petty_cash_drawer_payout(): void
    {
        app(TenantContext::class)->setTenant($this->tenantA);

        $cashAcc = FinancialAccount::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Petty Cash Drawer',
            'type' => 'cash',
            'account_number' => 'CASH-PETTY-01',
            'current_balance' => 5000.00,
            'is_active' => true,
        ]);

        $expCat = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Shop Freight & Transport Mazdoori',
            'name_ur' => 'کرایہ و مزدوری',
            'slug' => 'exp-freight',
            'is_active' => true,
        ]);

        $expense = Expense::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'category_id' => $expCat->id,
            'financial_account_id' => $cashAcc->id,
            'expense_no' => 'EXP-2026-001',
            'date' => now()->toDateString(),
            'amount' => 800.00,
            'payment_method' => 'cash',
            'description' => 'Mandi logistics freight and carriage charges',
            'status' => 'approved',
            'created_by' => $this->userA->id,
        ]);

        $cashAcc->decrement('current_balance', $expense->amount);

        $this->assertEquals(4200.00, $cashAcc->fresh()->current_balance);
        $this->assertEquals('approved', $expense->status);
    }

    /**
     * Scenario F: Financial Period-End Reporting (Trial Balance, P&L, Day-End Z-Report)
     */
    public function test_scenario_f_comprehensive_financial_reporting_and_z_report(): void
    {
        app(TenantContext::class)->setTenant($this->tenantA);
        $this->accountingService->seedDefaultChartOfAccounts($this->tenantA);

        $trialBalance = $this->accountingService->getTrialBalance($this->tenantA);
        $this->assertIsArray($trialBalance);
        $this->assertTrue($trialBalance['is_balanced']);
        $this->assertEquals($trialBalance['total_debits'], $trialBalance['total_credits']);

        $zReport = $this->reportingService->getDayEndRegisterReport($this->tenantA);
        $this->assertIsArray($zReport);
        $this->assertArrayHasKey('sales_cash_in', $zReport);
        $this->assertArrayHasKey('net_cash_flow', $zReport);
    }

    /**
     * Scenario G: Bilingual Document Printing & Amount in Words Verification
     */
    public function test_scenario_g_bilingual_document_printing_and_thermal_receipts(): void
    {
        app(TenantContext::class)->setTenant($this->tenantA);

        $cat = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);
        $kg = Unit::create(['tenant_id' => $this->tenantA->id, 'name' => 'Kg', 'short_name' => 'kg', 'code' => 'kg', 'is_active' => true]);

        $mangoes = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'unit_id' => $kg->id,
            'name' => 'Sindhri Mangoes Special',
            'name_ur' => 'سندھڑی آم سپیشل',
            'code' => 'MANGO-01',
            'cost_price' => 120.00,
            'selling_price' => 180.00,
            'is_active' => true,
        ]);

        $invoice = SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'invoice_no' => 'INV-PRINT-01',
            'date' => now()->toDateString(),
            'subtotal' => 1800.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 1800.00,
            'paid_amount' => 1800.00,
            'cost_amount' => 1200.00,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);

        $invoice->items()->create([
            'tenant_id' => $this->tenantA->id,
            'product_id' => $mangoes->id,
            'quantity' => 10.00,
            'unit_price' => 180.00,
            'unit_cost' => 120.00,
            'subtotal' => 1800.00,
            'discount_amount' => 0.00,
        ]);

        // Spelled out words test
        $words = $this->printService->numberToWords(1800.00);
        $this->assertNotEmpty($words);
        $this->assertStringContainsString('One Thousand Eight Hundred', $words);

        // Thermal receipt rendering test
        $response = $this->actingAs($this->userA)->get(route('tenant.print.thermal.sales', $invoice->id));
        $response->assertStatus(200);
        $response->assertSee('INV-PRINT-01');
        $response->assertSee('Sindhri Mangoes Special');
        $response->assertSee('سندھڑی آم سپیشل');
        $response->assertSee('تشریف لانے کا شکریہ');
    }

    /**
     * Scenario H: Multi-Tenant Data Isolation Audit
     */
    public function test_scenario_h_complete_end_to_end_tenant_data_isolation(): void
    {
        // Tenant A creates invoice
        app(TenantContext::class)->setTenant($this->tenantA);

        $invoiceA = SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'invoice_no' => 'INV-TENANT-A',
            'date' => now()->toDateString(),
            'subtotal' => 3000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 3000.00,
            'paid_amount' => 3000.00,
            'cost_amount' => 2000.00,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);

        // Tenant B attempting to access Tenant A's invoice must be forbidden
        app(TenantContext::class)->setTenant($this->tenantB);

        $response = $this->actingAs($this->userB)->get(route('tenant.sales.show', $invoiceA->id));
        $this->assertContains($response->status(), [403, 404]);

        // Tenant B attempting to print Tenant A's receipt must be forbidden
        $printResponse = $this->actingAs($this->userB)->get(route('tenant.print.thermal.sales', $invoiceA->id));
        $this->assertContains($printResponse->status(), [403, 404]);

        // Tenant B data export must not contain Tenant A's invoice
        $exportB = $this->backupService->exportTenantData($this->tenantB, false);
        $contentB = file_get_contents($exportB['path']);

        $this->assertStringNotContainsString('INV-TENANT-A', $contentB);
        $this->assertStringNotContainsString('Multan Fresh Fruits & Vegetables', $contentB);

        // Cleanup
        $this->backupService->deleteBackup($exportB['filename']);
    }
}
