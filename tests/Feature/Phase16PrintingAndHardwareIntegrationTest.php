<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesInvoicePayment;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\WastageRecord;
use App\Services\HardwareIntegrationService;
use App\Services\PrintService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase16PrintingAndHardwareIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected User $userA;

    protected User $userB;

    protected Customer $customerA;

    protected Supplier $supplierA;

    protected Product $productTomatoes;

    protected Unit $unitKg;

    protected PrintService $printService;

    protected HardwareIntegrationService $hardwareService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $context = app(TenantContext::class);
        $context->reset();

        $this->printService = app(PrintService::class);
        $this->hardwareService = app(HardwareIntegrationService::class);

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
            'name' => 'Lahore Fresh Produce Mart',
            'slug' => 'lhr-fresh',
            'plan_id' => $plan->id,
            'phone' => '042-35889900',
            'address' => 'Shop 14, Main Market Gulberg, Lahore',
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchA = $this->tenantA->branches()->create([
            'name' => 'Gulberg Central Branch',
            'code' => 'GLB-01',
            'phone' => '0300-1122334',
            'address' => 'Main Gulberg Boulevard, Lahore',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $ownerRole = Role::where('slug', 'owner')->first();

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Chaudhry Niaz',
            'email' => 'niaz@lahorefresh.pk',
            'password' => Hash::make('Secret123!'),
            'role_id' => $ownerRole->id,
            'is_active' => true,
        ]);

        // Tenant B
        $this->tenantB = Tenant::factory()->create([
            'name' => 'Faisalabad Sabzi Mandi Mart',
            'slug' => 'fsd-mandi',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchB = $this->tenantB->branches()->create([
            'name' => 'Ghanta Ghar Branch',
            'code' => 'FSD-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'branch_id' => $this->branchB->id,
            'name' => 'Mian Babar',
            'email' => 'babar@fsdmandi.pk',
            'password' => Hash::make('Secret123!'),
            'role_id' => $ownerRole->id,
            'is_active' => true,
        ]);

        // Master Data for Tenant A
        $cat = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Daily Produce',
            'slug' => 'daily-produce',
            'is_active' => true,
        ]);

        $this->unitKg = Unit::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Kilogram',
            'short_name' => 'kg',
            'code' => 'kg',
            'is_active' => true,
        ]);

        $this->productTomatoes = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'unit_id' => $this->unitKg->id,
            'name' => 'Prime Red Tomatoes',
            'name_ur' => 'درجہ اول ٹماٹر',
            'code' => '00105',
            'cost_price' => 80.00,
            'selling_price' => 120.00,
            'is_active' => true,
        ]);

        $this->customerA = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Savour Foods Lahore',
            'code' => 'CUST-SAVOUR',
            'phone' => '03001234567',
            'current_balance' => 4500.00,
            'is_active' => true,
        ]);

        $this->supplierA = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Badami Bagh Arhti & Co',
            'code' => 'SUPP-BADAMI',
            'contact_person' => 'Malik Rafiq',
            'phone' => '03219876543',
            'current_balance' => 12500.00,
            'is_active' => true,
        ]);
    }

    public function test_80mm_thermal_sales_receipt_renders_accurately_with_bilingual_details(): void
    {
        $invoice = SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customerA->id,
            'invoice_no' => 'INV-20261115-088',
            'date' => now()->toDateString(),
            'subtotal' => 600.00,
            'discount_amount' => 50.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 550.00,
            'paid_amount' => 500.00,
            'change_amount' => 0.00,
            'cost_amount' => 400.00,
            'payment_method' => 'cash',
            'payment_status' => 'partial',
            'status' => 'partial',
            'created_by' => $this->userA->id,
        ]);

        SalesInvoiceItem::create([
            'tenant_id' => $this->tenantA->id,
            'sales_invoice_id' => $invoice->id,
            'product_id' => $this->productTomatoes->id,
            'unit_id' => $this->unitKg->id,
            'quantity' => 5.000,
            'unit_price' => 120.00,
            'unit_cost' => 80.00,
            'discount_amount' => 50.00,
            'subtotal' => 550.00,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.print.thermal.sales', $invoice));

        $response->assertStatus(200);
        $response->assertSee('INV-20261115-088');
        $response->assertSee('Prime Red Tomatoes');
        $response->assertSee('درجہ اول ٹماٹر');
        $response->assertSee('550.00');
        $response->assertSee('500.00');
        $response->assertSee('50.00'); // Due balance or discount
        $response->assertSee('Savour Foods Lahore');
        $response->assertSee('4,500.00'); // Customer remaining balance
        $response->assertSee('تشریف لانے کا شکریہ');
    }

    public function test_a4_sales_invoice_renders_with_tax_and_terms(): void
    {
        $invoice = SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customerA->id,
            'invoice_no' => 'INV-A4-001',
            'date' => now()->toDateString(),
            'subtotal' => 1200.00,
            'discount_amount' => 100.00,
            'tax_amount' => 50.00,
            'rounding_amount' => 0.00,
            'total_amount' => 1150.00,
            'paid_amount' => 1150.00,
            'cost_amount' => 800.00,
            'payment_method' => 'bank',
            'payment_status' => 'paid',
            'status' => 'paid',
            'created_by' => $this->userA->id,
        ]);

        SalesInvoiceItem::create([
            'tenant_id' => $this->tenantA->id,
            'sales_invoice_id' => $invoice->id,
            'product_id' => $this->productTomatoes->id,
            'unit_id' => $this->unitKg->id,
            'quantity' => 10.000,
            'unit_price' => 120.00,
            'unit_cost' => 80.00,
            'discount_amount' => 100.00,
            'subtotal' => 1100.00,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.print.a4.sales', $invoice));

        $response->assertStatus(200);
        $response->assertSee('INV-A4-001');
        $response->assertSee('SALES INVOICE');
        $response->assertSee('Savour Foods Lahore');
        $response->assertSee('1,150.00');
        $response->assertSee('Customer Signature');
        $response->assertSee('Authorized Signature');
    }

    public function test_a4_mandi_purchase_bill_renders_with_commissions_and_fees(): void
    {
        $purchase = PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'supplier_id' => $this->supplierA->id,
            'invoice_no' => 'MANDI-LOT-55',
            'supplier_invoice_no' => 'ARHTI-SLIP-9981',
            'date' => now()->toDateString(),
            'subtotal' => 10000.00,
            'commission_amount' => 500.00,
            'mandi_fee' => 100.00,
            'freight_charges' => 400.00,
            'total_amount' => 11000.00,
            'paid_amount' => 8000.00,
            'status' => 'partial',
            'created_by' => $this->userA->id,
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.print.a4.purchases', $purchase));

        $response->assertStatus(200);
        $response->assertSee('MANDI-LOT-55');
        $response->assertSee('ARHTI-SLIP-9981');
        $response->assertSee('Badami Bagh Arhti & Co');
        $response->assertSee('10,000.00');
        $response->assertSee('500.00'); // Commission
        $response->assertSee('100.00'); // Mandi fee
        $response->assertSee('400.00'); // Freight
        $response->assertSee('11,000.00'); // Total Bill
    }

    public function test_customer_statement_ledger_renders_chronological_transactions(): void
    {
        SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customerA->id,
            'invoice_no' => 'INV-STMT-01',
            'date' => now()->subDays(5)->toDateString(),
            'subtotal' => 3000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 3000.00,
            'paid_amount' => 2000.00,
            'cost_amount' => 2000.00,
            'status' => 'partial',
            'payment_status' => 'partial',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.print.a4.customer-statement', $this->customerA));

        $response->assertStatus(200);
        $response->assertSee('STATEMENT OF ACCOUNT');
        $response->assertSee('Savour Foods Lahore');
        $response->assertSee('INV-STMT-01');
        $response->assertSee('3,000.00');
        $response->assertSee('4,500.00'); // Balance due
    }

    public function test_supplier_statement_ledger_renders_procurement_and_payables(): void
    {
        PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'supplier_id' => $this->supplierA->id,
            'invoice_no' => 'PUR-STMT-01',
            'date' => now()->subDays(3)->toDateString(),
            'subtotal' => 20000.00,
            'commission_amount' => 1000.00,
            'mandi_fee' => 200.00,
            'freight_charges' => 500.00,
            'total_amount' => 21700.00,
            'paid_amount' => 15000.00,
            'status' => 'partial',
        ]);

        $response = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.print.a4.supplier-statement', $this->supplierA));

        $response->assertStatus(200);
        $response->assertSee('SUPPLIER STATEMENT');
        $response->assertSee('Badami Bagh Arhti & Co');
        $response->assertSee('PUR-STMT-01');
        $response->assertSee('21,700.00');
        $response->assertSee('12,500.00'); // Current payable balance
    }

    public function test_payment_and_receipt_vouchers_render_with_amount_in_words(): void
    {
        $cat = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Produce Ice & Packaging',
            'slug' => 'produce-ice-packaging',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $account = FinancialAccount::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Petty Cash Safe',
            'account_number' => 'ACC-SAFE-01',
            'account_type' => 'cash',
            'current_balance' => 10000.00,
            'is_active' => true,
        ]);

        // Payment Outflow Voucher
        $expense = Expense::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'category_id' => $cat->id,
            'financial_account_id' => $account->id,
            'expense_no' => 'EXP-VOUCHER-01',
            'date' => now()->toDateString(),
            'amount' => 750.00,
            'title' => 'Cold storage ice block purchases',
            'payee_name' => 'Ravi Ice Factory',
            'payment_method' => 'cash',
            'status' => 'approved',
            'created_by' => $this->userA->id,
        ]);

        $respPayment = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.print.a4.payment-voucher', $expense));

        $respPayment->assertStatus(200);
        $respPayment->assertSee('PAYMENT VOUCHER');
        $respPayment->assertSee('EXP-VOUCHER-01');
        $respPayment->assertSee('Ravi Ice Factory');
        $respPayment->assertSee('750.00');
        $respPayment->assertSee('Seven Hundred Fifty Rupees Only');

        // Money Inflow Receipt Voucher
        $invoice = SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customerA->id,
            'invoice_no' => 'INV-RCP-01',
            'date' => now()->toDateString(),
            'subtotal' => 1500.00,
            'total_amount' => 1500.00,
            'paid_amount' => 1500.00,
            'status' => 'paid',
            'payment_status' => 'paid',
            'created_by' => $this->userA->id,
        ]);

        $payment = SalesInvoicePayment::create([
            'tenant_id' => $this->tenantA->id,
            'sales_invoice_id' => $invoice->id,
            'amount' => 1500.00,
            'method' => 'cash',
            'reference_no' => 'Till Drawer #1',
        ]);

        $respReceipt = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.print.a4.receipt-voucher', $payment));

        $respReceipt->assertStatus(200);
        $respReceipt->assertSee('RECEIPT VOUCHER');
        $respReceipt->assertSee('Savour Foods Lahore');
        $respReceipt->assertSee('INV-RCP-01');
        $respReceipt->assertSee('1,500.00');
        $respReceipt->assertSee('One Thousand Five Hundred Rupees Only');
    }

    public function test_stock_sheet_and_wastage_report_render_valuation_and_cull_loss(): void
    {
        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productTomatoes->id,
            'quantity' => 100.000,
            'reorder_level' => 20.000,
            'cost_price' => 80.00,
        ]);

        WastageRecord::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'wastage_no' => 'WST-AUDIT-01',
            'date' => now()->toDateString(),
            'reason' => 'spoilage',
            'total_quantity' => 15.500,
            'total_loss_cost' => 1240.00,
            'status' => 'approved',
            'created_by' => $this->userA->id,
        ]);

        // Stock Sheet
        $respStock = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.print.a4.stock-report'));

        $respStock->assertStatus(200);
        $respStock->assertSee('INVENTORY VALUATION SHEET');
        $respStock->assertSee('Prime Red Tomatoes');
        $respStock->assertSee('100.00 kg');
        $respStock->assertSee('8,000.00'); // 100 * 80 cost valuation

        // Wastage Sheet
        $respWastage = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.print.a4.wastage-report'));

        $respWastage->assertStatus(200);
        $respWastage->assertSee('PRODUCE WASTAGE & CULL AUDIT');
        $respWastage->assertSee('WST-AUDIT-01');
        $respWastage->assertSee('15.50 kg');
        $respWastage->assertSee('1,240.00');
    }

    public function test_hardware_integration_service_decodes_weight_and_price_barcodes(): void
    {
        // 1. Weight-embedded barcode: Prefix '20', PLU '00105', Weight '01250' (1.250 kg), Check digit '8'
        $barcodeWeight = '2000105012508';
        $decodedW = $this->hardwareService->parseWeightBarcode($barcodeWeight);

        $this->assertNotNull($decodedW);
        $this->assertEquals('weight', $decodedW['type']);
        $this->assertEquals('20', $decodedW['prefix']);
        $this->assertEquals('00105', $decodedW['plu_code']);
        $this->assertEquals(1250, $decodedW['weight_grams']);
        $this->assertEquals(1.250, $decodedW['weight_kg']);

        // 2. Price-embedded barcode: Prefix '21', PLU '00105', Price '01500' (Rs. 15.00), Check digit '1'
        $barcodePrice = '2100105015001';
        $decodedP = $this->hardwareService->parsePriceBarcode($barcodePrice);

        $this->assertNotNull($decodedP);
        $this->assertEquals('price', $decodedP['type']);
        $this->assertEquals('21', $decodedP['prefix']);
        $this->assertEquals('00105', $decodedP['plu_code']);
        $this->assertEquals(15.00, $decodedP['price']);

        // 3. Invalid or non-scale standard barcode
        $stdBarcode = '8964000123456';
        $this->assertNull($this->hardwareService->parseWeightBarcode($stdBarcode));
        $this->assertNull($this->hardwareService->parsePriceBarcode($stdBarcode));

        // 4. ESC/POS commands verification
        $commands = $this->hardwareService->getEscPosCommands();
        $this->assertArrayHasKey('cut_paper_full', $commands);
        $this->assertArrayHasKey('drawer_kick_pin2', $commands);
        $this->assertArrayHasKey('initialize', $commands);
    }

    public function test_hardware_settings_persistence_and_barcode_tester_api(): void
    {
        // View settings page
        $respIndex = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.hardware.index'));

        $respIndex->assertStatus(200);
        $respIndex->assertSee('POS Hardware Configuration');
        $respIndex->assertSee('80mm');

        // Post settings update
        $respUpdate = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->post(route('tenant.hardware.settings'), [
                'printer_type' => 'thermal_80mm',
                'auto_cut_paper' => '1',
                'open_cash_drawer' => '1',
                'weighing_scale_mode' => 'barcode_embedded',
                'weight_barcode_prefix' => '20',
                'price_barcode_prefix' => '21',
                'scale_serial_baud' => 9600,
                'bilingual_receipt' => '1',
                'receipt_footer_text' => 'Thank you for shopping at Lahore Fresh Produce Mart!',
            ]);

        $respUpdate->assertRedirect(route('tenant.hardware.index'));

        $this->tenantA->refresh();
        $hw = $this->tenantA->settings['hardware'];
        $this->assertEquals('thermal_80mm', $hw['printer_type']);
        $this->assertTrue($hw['auto_cut_paper']);
        $this->assertTrue($hw['open_cash_drawer']);
        $this->assertEquals('20', $hw['weight_barcode_prefix']);

        // Test Barcode Decoder API
        $respDecode = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->postJson(route('tenant.hardware.parse-barcode'), [
                'barcode' => '2000105012508',
            ]);

        $respDecode->assertStatus(200);
        $respDecode->assertJson([
            'success' => true,
            'data' => [
                'type' => 'weight',
                'plu_code' => '00105',
                'weight_kg' => 1.250,
            ],
        ]);

        // Test Diagnostic Print Slip
        $respTestPrint = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.hardware.test-print'));

        $respTestPrint->assertStatus(200);
        $respTestPrint->assertSee('Diagnostic Slip');
        $respTestPrint->assertSee('ESC/POS Emulation');
    }

    public function test_printing_strictly_enforces_tenant_isolation(): void
    {
        // Tenant B creates an invoice
        $invoiceB = SalesInvoice::create([
            'tenant_id' => $this->tenantB->id,
            'branch_id' => $this->branchB->id,
            'invoice_no' => 'INV-TENANT-B-SEC',
            'date' => now()->toDateString(),
            'subtotal' => 999.00,
            'total_amount' => 999.00,
            'paid_amount' => 999.00,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);

        // Tenant A tries to print Tenant B's thermal receipt
        $responseThermal = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.print.thermal.sales', $invoiceB));

        $this->assertContains($responseThermal->status(), [403, 404]);

        // Tenant A tries to print Tenant B's A4 invoice
        $responseA4 = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.print.a4.sales', $invoiceB));

        $this->assertContains($responseA4->status(), [403, 404]);
    }
}
