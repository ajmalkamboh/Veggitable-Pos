<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AccountingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase14FinancialAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected User $userA;

    protected User $userB;

    protected TenantContext $context;

    protected AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->context = app(TenantContext::class);
        $this->context->reset();
        $this->accountingService = app(AccountingService::class);

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Pro',
            'slug' => 'enterprise-pro',
            'price_monthly' => 5000.00,
            'max_branches' => 10,
            'max_users' => 20,
            'features' => ['accounting_manage'],
            'is_active' => true,
        ]);

        $this->tenantA = Tenant::factory()->create([
            'name' => 'Khyber Vegetable Mart',
            'slug' => 'khyber-veg',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchA = $this->tenantA->branches()->create([
            'name' => 'Peshawar Main Branch',
            'code' => 'PSH-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $ownerRole = Role::where('slug', 'owner')->first();

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'current_branch_id' => $this->branchA->id,
            'name' => 'Accountant Tariq',
            'email' => 'tariq@khyberveg.pk',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);
        $this->userA->roles()->attach($ownerRole);

        // Tenant B
        $this->tenantB = Tenant::factory()->create([
            'name' => 'Quetta Dry Fruit & Veg',
            'slug' => 'quetta-veg',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchB = $this->tenantB->branches()->create([
            'name' => 'Liaquat Bazaar Branch',
            'code' => 'QTA-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'current_branch_id' => $this->branchB->id,
            'name' => 'Quetta Accountant',
            'email' => 'accountant@quettaveg.pk',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);
        $this->userB->roles()->attach($ownerRole);

        $this->context->setTenant($this->tenantA);
        $this->accountingService->seedDefaultChartOfAccounts($this->tenantA);
    }

    public function test_default_chart_of_accounts_is_seeded_automatically(): void
    {
        $this->assertDatabaseHas('chart_of_accounts', [
            'tenant_id' => $this->tenantA->id,
            'account_code' => '1010',
            'name' => 'Cash in Hand / Drawer Till',
            'type' => 'asset',
        ]);

        $this->assertDatabaseHas('chart_of_accounts', [
            'tenant_id' => $this->tenantA->id,
            'account_code' => '2010',
            'name' => 'Accounts Payable - Mandi Aarthi & Suppliers',
            'type' => 'liability',
        ]);

        $this->assertDatabaseHas('chart_of_accounts', [
            'tenant_id' => $this->tenantA->id,
            'account_code' => '4010',
            'name' => 'Produce Sales Revenue',
            'type' => 'revenue',
        ]);

        $this->assertDatabaseHas('chart_of_accounts', [
            'tenant_id' => $this->tenantA->id,
            'account_code' => '5010',
            'name' => 'Cost of Goods Sold (Procurement)',
            'type' => 'expense',
        ]);
    }

    public function test_user_can_create_custom_chart_of_account(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $payload = [
            'account_code' => '1060',
            'name' => 'Shop Delivery Rickshaw',
            'name_ur' => 'دکان رکشہ ڈلیوری',
            'type' => 'asset',
            'subtype' => 'non_current_asset',
            'opening_balance' => 120000.00,
            'description' => 'Vehicle for local hotel produce deliveries',
        ];

        $response = $this->post(route('tenant.accounting.coa.store'), $payload);
        $response->assertRedirect(route('tenant.accounting.coa.index'));

        $this->assertDatabaseHas('chart_of_accounts', [
            'tenant_id' => $this->tenantA->id,
            'account_code' => '1060',
            'name' => 'Shop Delivery Rickshaw',
            'current_balance' => 120000.00,
        ]);
    }

    public function test_balanced_journal_entry_posts_and_updates_account_balances(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $cashAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('account_code', '1010')->first();
        $capitalAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('account_code', '3010')->first();

        $payload = [
            'branch_id' => $this->branchA->id,
            'date' => now()->toDateString(),
            'narration' => 'Initial capital cash injection by owner',
            'reference_type' => 'manual',
            'reference_no' => 'CAP-001',
            'status' => 'posted',
            'lines' => [
                [
                    'account_id' => $cashAcc->id,
                    'description' => 'Cash deposited into drawer',
                    'debit' => 50000.00,
                    'credit' => 0.00,
                ],
                [
                    'account_id' => $capitalAcc->id,
                    'description' => 'Owner capital credit',
                    'debit' => 0.00,
                    'credit' => 50000.00,
                ],
            ],
        ];

        $response = $this->post(route('tenant.accounting.journal.store'), $payload);
        $response->assertRedirect();

        $entry = JournalEntry::where('tenant_id', $this->tenantA->id)->first();
        $this->assertEquals(50000.00, (float) $entry->total_debit);
        $this->assertEquals(50000.00, (float) $entry->total_credit);
        $this->assertEquals('posted', $entry->status);

        // Verify account balance updates
        $cashAcc->refresh();
        $capitalAcc->refresh();

        $this->assertEquals(50000.00, (float) $cashAcc->current_balance);
        $this->assertEquals(50000.00, (float) $capitalAcc->current_balance);
    }

    public function test_unbalanced_journal_entry_is_rejected(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $cashAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('account_code', '1010')->first();
        $capitalAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('account_code', '3010')->first();

        $this->expectException(\DomainException::class);

        $this->accountingService->createJournalEntry($this->tenantA, $this->branchA, $this->userA, [
            'date' => now()->toDateString(),
            'narration' => 'Unbalanced entry test',
            'lines' => [
                ['account_id' => $cashAcc->id, 'debit' => 50000.00, 'credit' => 0.00],
                ['account_id' => $capitalAcc->id, 'debit' => 0.00, 'credit' => 45000.00], // 5,000 difference!
            ],
        ]);
    }

    public function test_reversing_journal_entry_creates_offsetting_voucher(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $cashAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('account_code', '1010')->first();
        $capitalAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('account_code', '3010')->first();

        // 1. Post original JV of 20,000
        $entry = $this->accountingService->createJournalEntry($this->tenantA, $this->branchA, $this->userA, [
            'date' => now()->toDateString(),
            'narration' => 'Original posting',
            'status' => 'posted',
            'lines' => [
                ['account_id' => $cashAcc->id, 'debit' => 20000.00, 'credit' => 0.00],
                ['account_id' => $capitalAcc->id, 'debit' => 0.00, 'credit' => 20000.00],
            ],
        ]);

        $cashAcc->refresh();
        $this->assertEquals(20000.00, (float) $cashAcc->current_balance);

        // 2. Reverse entry
        $reverseResponse = $this->post(route('tenant.accounting.journal.reverse', $entry->id), [
            'reason' => 'Wrong transaction entered',
        ]);
        $reverseResponse->assertRedirect();

        $entry->refresh();
        $this->assertEquals('reversed', $entry->status);

        // Balance restored back to 0
        $cashAcc->refresh();
        $capitalAcc->refresh();
        $this->assertEquals(0.00, (float) $cashAcc->current_balance);
        $this->assertEquals(0.00, (float) $capitalAcc->current_balance);
    }

    public function test_trial_balance_and_profit_and_loss_calculation(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $cashAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('account_code', '1010')->first();
        $salesAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('account_code', '4010')->first();
        $rentAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('account_code', '5040')->first();

        // 1. Sale: Debit Cash 30,000, Credit Sales 30,000
        $this->accountingService->createJournalEntry($this->tenantA, $this->branchA, $this->userA, [
            'date' => now()->toDateString(),
            'narration' => 'Cash vegetable sales',
            'status' => 'posted',
            'lines' => [
                ['account_id' => $cashAcc->id, 'debit' => 30000.00, 'credit' => 0.00],
                ['account_id' => $salesAcc->id, 'debit' => 0.00, 'credit' => 30000.00],
            ],
        ]);

        // 2. Rent Expense: Debit Rent 8,000, Credit Cash 8,000
        $this->accountingService->createJournalEntry($this->tenantA, $this->branchA, $this->userA, [
            'date' => now()->toDateString(),
            'narration' => 'Shop rent payment',
            'status' => 'posted',
            'lines' => [
                ['account_id' => $rentAcc->id, 'debit' => 8000.00, 'credit' => 0.00],
                ['account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 8000.00],
            ],
        ]);

        // Trial Balance Check
        $tb = $this->accountingService->getTrialBalance($this->tenantA);
        $this->assertTrue($tb['is_balanced']);
        $this->assertEquals(30000.00, (float) $tb['total_debits']);
        $this->assertEquals(30000.00, (float) $tb['total_credits']);

        // Profit & Loss Check: Revenue 30,000, Expense 8,000, Net Profit = 22,000
        $pnl = $this->accountingService->getProfitAndLoss($this->tenantA);
        $this->assertEquals(30000.00, (float) $pnl['total_revenue']);
        $this->assertEquals(8000.00, (float) $pnl['total_operating_expenses']);
        $this->assertEquals(22000.00, (float) $pnl['net_profit']);
    }

    public function test_tenant_isolation_in_financial_accounting(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $cashAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('account_code', '1010')->first();
        $capitalAcc = ChartOfAccount::where('tenant_id', $this->tenantA->id)->where('account_code', '3010')->first();

        $entryA = $this->accountingService->createJournalEntry($this->tenantA, $this->branchA, $this->userA, [
            'date' => now()->toDateString(),
            'narration' => 'Tenant A confidential entry',
            'status' => 'posted',
            'lines' => [
                ['account_id' => $cashAcc->id, 'debit' => 15000.00, 'credit' => 0.00],
                ['account_id' => $capitalAcc->id, 'debit' => 0.00, 'credit' => 15000.00],
            ],
        ]);

        // Tenant B logs in
        $this->actingAs($this->userB);
        $this->context->setTenant($this->tenantB);

        // Attempt to view Tenant A's voucher
        $viewResponse = $this->get(route('tenant.accounting.journal.show', $entryA->id));
        $viewResponse->assertNotFound();

        // Attempt to reverse Tenant A's voucher
        $reverseResponse = $this->post(route('tenant.accounting.journal.reverse', $entryA->id), [
            'reason' => 'Unauthorized attempt',
        ]);
        $reverseResponse->assertNotFound();
    }
}
