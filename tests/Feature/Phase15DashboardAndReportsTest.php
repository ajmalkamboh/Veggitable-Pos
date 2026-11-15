<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\WastageRecord;
use App\Services\ReportingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase15DashboardAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Branch $branchA1;

    protected Branch $branchA2;

    protected Branch $branchB;

    protected User $userA;

    protected User $userB;

    protected Customer $customerA;

    protected Supplier $supplierA;

    protected Product $productA;

    protected ReportingService $reportingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $context = app(TenantContext::class);
        $context->reset();
        $this->reportingService = app(ReportingService::class);

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Pro',
            'slug' => 'enterprise-pro',
            'price_monthly' => 5000.00,
            'max_branches' => 10,
            'max_users' => 20,
            'features' => ['all'],
            'is_active' => true,
        ]);

        // Tenant A
        $this->tenantA = Tenant::factory()->create([
            'name' => 'Green Valley Produce',
            'slug' => 'green-valley',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchA1 = $this->tenantA->branches()->create([
            'name' => 'Main Bazaar Lahore',
            'code' => 'LHR-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->branchA2 = $this->tenantA->branches()->create([
            'name' => 'Model Town Outlet',
            'code' => 'LHR-02',
            'is_headquarters' => false,
            'is_active' => true,
        ]);

        $ownerRole = Role::where('slug', 'owner')->first();

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Haji Tariq',
            'email' => 'tariq@greenvalley.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $ownerRole->id,
            'is_active' => true,
        ]);

        // Tenant B
        $this->tenantB = Tenant::factory()->create([
            'name' => 'Rawalpindi Sabzi Store',
            'slug' => 'rwp-sabzi',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchB = $this->tenantB->branches()->create([
            'name' => 'Raja Bazaar RWP',
            'code' => 'RWP-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'branch_id' => $this->branchB->id,
            'name' => 'Malik Asghar',
            'email' => 'asghar@rwpsabzi.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $ownerRole->id,
            'is_active' => true,
        ]);

        // Seed some master data for Tenant A
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

        $this->productA = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'unit_id' => $kgUnit->id,
            'name' => 'Farm Fresh Tomatoes',
            'urdu_name' => 'تازہ ٹماٹر',
            'code' => 'VEG-TOMATO',
            'cost_price' => 80.00,
            'selling_price' => 120.00,
            'is_active' => true,
        ]);

        $this->customerA = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Al-Madina Hotel',
            'code' => 'CUST-001',
            'phone' => '03001234567',
            'is_active' => true,
        ]);

        $this->supplierA = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Badami Bagh Commission Agent',
            'code' => 'SUPP-001',
            'contact_person' => 'Chaudhry Akram',
            'phone' => '03219876543',
            'is_active' => true,
        ]);

        $this->expenseCategory = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Produce Ice & Operations',
            'slug' => 'produce-ice-operations',
            'type' => 'expense',
            'is_active' => true,
        ]);
    }

    public function test_tenant_executive_dashboard_displays_real_time_metrics(): void
    {
        // Create transactions for Tenant A
        // 1. Sales Invoice
        SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'customer_id' => $this->customerA->id,
            'invoice_no' => 'INV-20261115-001',
            'date' => now()->toDateString(),
            'subtotal' => 1200.00,
            'discount_amount' => 50.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 1150.00,
            'paid_amount' => 1000.00,
            'cost_amount' => 800.00,
            'status' => 'partial',
            'payment_status' => 'partial',
        ]);

        // 2. Mandi Purchase
        PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'supplier_id' => $this->supplierA->id,
            'invoice_no' => 'PUR-20261115-001',
            'date' => now()->toDateString(),
            'subtotal' => 5000.00,
            'commission_amount' => 250.00,
            'mandi_fee' => 50.00,
            'freight_charges' => 200.00,
            'total_amount' => 5500.00,
            'paid_amount' => 4000.00,
            'status' => 'partial',
        ]);

        // 3. Spoilage Wastage
        WastageRecord::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'wastage_no' => 'WST-20261115-001',
            'date' => now()->toDateString(),
            'reason' => 'damage',
            'total_quantity' => 10.00,
            'total_loss_cost' => 800.00,
            'status' => 'approved',
            'created_by' => $this->userA->id,
            'approved_by' => $this->userA->id,
        ]);

        // 4. Operational Expense
        Expense::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'category_id' => $this->expenseCategory->id,
            'expense_no' => 'EXP-20261115-001',
            'date' => now()->toDateString(),
            'amount' => 300.00,
            'title' => 'Crushed ice for greens',
            'category_name' => 'Produce Ice & Preservation',
            'payment_method' => 'cash',
            'status' => 'approved',
            'created_by' => $this->userA->id,
        ]);

        // 5. Financial Account / Drawer Balance
        FinancialAccount::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Main POS Cash Till',
            'account_number' => 'ACC-CASH-01',
            'account_type' => 'cash',
            'current_balance' => 25000.00,
            'is_active' => true,
        ]);

        // Fetch metrics via service
        $metrics = $this->reportingService->getDashboardMetrics($this->tenantA);

        $this->assertEquals(1150.00, $metrics['todaySales']);
        $this->assertEquals(1, $metrics['todayTransactionsCount']);
        $this->assertEquals(1000.00, $metrics['todayCashCollected']);
        $this->assertEquals(150.00, $metrics['todayCreditDue']);
        $this->assertEquals(350.00, $metrics['monthGrossProfit']);
        $this->assertEquals(5500.00, $metrics['todayPurchases']);
        $this->assertEquals(800.00, $metrics['todayWastageLoss']);
        $this->assertEquals(300.00, $metrics['todayExpenses']);
        $this->assertEquals(25000.00, $metrics['cashAndBankReserves']);

        // Test HTTP Dashboard View
        $response = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Green Valley Produce');
        $response->assertSee('1,150.00');
        $response->assertSee('5,500.00');
        $response->assertSee('800.00');
        $response->assertSee('300.00');
    }

    public function test_executive_dashboard_supports_branch_filtering(): void
    {
        // Branch 1 sale
        SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'invoice_no' => 'INV-BR1-001',
            'date' => now()->toDateString(),
            'subtotal' => 1000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 1000.00,
            'paid_amount' => 1000.00,
            'cost_amount' => 700.00,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);

        // Branch 2 sale
        SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA2->id,
            'invoice_no' => 'INV-BR2-001',
            'date' => now()->toDateString(),
            'subtotal' => 2000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 2000.00,
            'paid_amount' => 2000.00,
            'cost_amount' => 1400.00,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);

        // Filter by Branch 1
        $metricsB1 = $this->reportingService->getDashboardMetrics($this->tenantA, $this->branchA1->id);
        $this->assertEquals(1000.00, $metricsB1['todaySales']);

        // Filter by Branch 2
        $metricsB2 = $this->reportingService->getDashboardMetrics($this->tenantA, $this->branchA2->id);
        $this->assertEquals(2000.00, $metricsB2['todaySales']);

        // All branches consolidated
        $metricsAll = $this->reportingService->getDashboardMetrics($this->tenantA, null);
        $this->assertEquals(3000.00, $metricsAll['todaySales']);

        // Check via Controller request
        $response = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.dashboard', ['branch_id' => $this->branchA2->id]));

        $response->assertStatus(200);
        $response->assertSee('2,000.00');
    }

    public function test_reports_hub_index_page_is_accessible(): void
    {
        $response = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Reports & Business Intelligence Hub');
        $response->assertSee('Sales Analytics');
        $response->assertSee('Mandi Procurement');
        $response->assertSee('Day-End Register (Z-Report)');
        $response->assertSee('Profit & Loss (Income)');
    }

    public function test_sales_analytics_report_compiles_data_and_filters_correctly(): void
    {
        SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'customer_id' => $this->customerA->id,
            'invoice_no' => 'INV-SALE-001',
            'date' => '2026-11-10',
            'subtotal' => 2000.00,
            'discount_amount' => 100.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 1900.00,
            'paid_amount' => 1500.00,
            'cost_amount' => 1200.00,
            'status' => 'partial',
            'payment_status' => 'partial',
        ]);

        SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA2->id,
            'invoice_no' => 'INV-SALE-002',
            'date' => '2026-11-14',
            'subtotal' => 3000.00,
            'discount_amount' => 200.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 2800.00,
            'paid_amount' => 2800.00,
            'cost_amount' => 1800.00,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);

        $reportAll = $this->reportingService->getSalesReport($this->tenantA);
        $this->assertEquals(5000.00, $reportAll['gross_sales']);
        $this->assertEquals(300.00, $reportAll['discounts']);
        $this->assertEquals(4700.00, $reportAll['net_sales']);
        $this->assertEquals(1700.00, $reportAll['gross_profit']);
        $this->assertEquals(36.2, $reportAll['margin_pct']); // 1700 / 4700 * 100 = 36.17%

        // Filter by branch A1
        $reportFiltered = $this->reportingService->getSalesReport($this->tenantA, ['branch_id' => $this->branchA1->id]);
        $this->assertCount(1, $reportFiltered['invoices']);
        $this->assertEquals(1900.00, $reportFiltered['net_sales']);

        // Check via Controller
        $response = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.reports.sales', ['customer_id' => $this->customerA->id]));

        $response->assertStatus(200);
        $response->assertSee('INV-SALE-001');
        $response->assertDontSee('INV-SALE-002');
    }

    public function test_mandi_procurement_report_compiles_data_and_charges_correctly(): void
    {
        PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'supplier_id' => $this->supplierA->id,
            'invoice_no' => 'MANDI-LOT-101',
            'date' => '2026-11-12',
            'subtotal' => 10000.00,
            'commission_amount' => 500.00,
            'mandi_fee' => 100.00,
            'freight_charges' => 400.00,
            'total_amount' => 11000.00,
            'paid_amount' => 7000.00,
            'status' => 'partial',
        ]);

        $report = $this->reportingService->getPurchaseReport($this->tenantA);

        $this->assertEquals(10000.00, $report['subtotal']);
        $this->assertEquals(500.00, $report['commission']);
        $this->assertEquals(100.00, $report['mandi_fee']);
        $this->assertEquals(400.00, $report['freight']);
        $this->assertEquals(11000.00, $report['total_amount']);
        $this->assertEquals(7000.00, $report['paid']);
        $this->assertEquals(4000.00, $report['due']);

        $response = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.reports.purchases'));

        $response->assertStatus(200);
        $response->assertSee('MANDI-LOT-101');
        $response->assertSee('Badami Bagh Commission Agent');
        $response->assertSee('11,000.00');
    }

    public function test_day_end_register_z_report_computes_daily_cash_flow_accurately(): void
    {
        $testDate = '2026-11-15';

        // 1. Counter cash collected
        SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'invoice_no' => 'INV-Z-001',
            'date' => $testDate,
            'subtotal' => 5000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 5000.00,
            'paid_amount' => 4500.00, // Cash in
            'cost_amount' => 3000.00,
            'status' => 'partial',
            'payment_status' => 'partial',
        ]);

        // 2. Mandi cash payout
        PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'supplier_id' => $this->supplierA->id,
            'invoice_no' => 'PUR-Z-001',
            'date' => $testDate,
            'subtotal' => 2000.00,
            'commission_amount' => 0.00,
            'mandi_fee' => 0.00,
            'freight_charges' => 0.00,
            'total_amount' => 2000.00,
            'paid_amount' => 1500.00, // Cash out
            'status' => 'partial',
        ]);

        // 3. Petty cash expense
        Expense::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA1->id,
            'category_id' => $this->expenseCategory->id,
            'expense_no' => 'EXP-Z-001',
            'date' => $testDate,
            'amount' => 500.00, // Cash out
            'title' => 'Shop broom and cleaning supplies',
            'category_name' => 'General Operations',
            'payment_method' => 'cash',
            'status' => 'approved',
            'created_by' => $this->userA->id,
        ]);

        // Net Cash Flow = 4500 - (1500 + 500) = 2500
        $zReport = $this->reportingService->getDayEndRegisterReport($this->tenantA, null, $testDate);

        $this->assertEquals(4500.00, $zReport['sales_cash_in']);
        $this->assertEquals(1500.00, $zReport['purchase_cash_out']);
        $this->assertEquals(500.00, $zReport['expense_cash_out']);
        $this->assertEquals(2500.00, $zReport['net_cash_flow']);

        $response = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.reports.day-end', ['date' => $testDate]));

        $response->assertStatus(200);
        $response->assertSee('+4,500.00');
        $response->assertSee('-1,500.00');
        $response->assertSee('-500.00');
        $response->assertSee('2,500.00');
    }

    public function test_reporting_and_dashboard_strictly_enforce_tenant_isolation(): void
    {
        // Tenant B creates a high value sale
        SalesInvoice::create([
            'tenant_id' => $this->tenantB->id,
            'branch_id' => $this->branchB->id,
            'invoice_no' => 'INV-TENANT-B-999',
            'date' => now()->toDateString(),
            'subtotal' => 99999.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 99999.00,
            'paid_amount' => 99999.00,
            'cost_amount' => 50000.00,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);

        // Tenant A views reports & dashboard
        $responseSales = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.reports.sales'));

        $responseSales->assertStatus(200);
        $responseSales->assertDontSee('INV-TENANT-B-999');
        $responseSales->assertDontSee('99,999.00');

        $metricsA = $this->reportingService->getDashboardMetrics($this->tenantA);
        $this->assertEquals(0.00, $metricsA['todaySales']);

        app(TenantContext::class)->setTenant($this->tenantB);
        $metricsB = $this->reportingService->getDashboardMetrics($this->tenantB);
        $this->assertEquals(99999.00, $metricsB['todaySales']);
    }
}
