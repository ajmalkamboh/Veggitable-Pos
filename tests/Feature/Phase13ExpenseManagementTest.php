<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase13ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected User $userA;

    protected User $userB;

    protected Category $categoryTea;

    protected FinancialAccount $pettyCashAccount;

    protected TenantContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->context = app(TenantContext::class);
        $this->context->reset();

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Pro',
            'slug' => 'enterprise-pro',
            'price_monthly' => 5000.00,
            'max_branches' => 10,
            'max_users' => 20,
            'features' => ['expenses_manage'],
            'is_active' => true,
        ]);

        $this->tenantA = Tenant::factory()->create([
            'name' => 'Al-Madina Vegetables',
            'slug' => 'al-madina',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchA = $this->tenantA->branches()->create([
            'name' => 'Main Outlet',
            'code' => 'OUT-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $ownerRole = Role::where('slug', 'owner')->first();

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'current_branch_id' => $this->branchA->id,
            'name' => 'Shop Manager',
            'email' => 'manager@almadina.pk',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);
        $this->userA->roles()->attach($ownerRole);

        // Tenant B
        $this->tenantB = Tenant::factory()->create([
            'name' => 'Multan Fruits',
            'slug' => 'multan-fruits',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchB = $this->tenantB->branches()->create([
            'name' => 'Cantonment Branch',
            'code' => 'CNT-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'current_branch_id' => $this->branchB->id,
            'name' => 'Multan Manager',
            'email' => 'manager@multanfruits.pk',
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);
        $this->userB->roles()->attach($ownerRole);

        $this->context->setTenant($this->tenantA);

        $this->categoryTea = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Daily Tea & Meals',
            'name_ur' => 'روزانہ چائے کھانا',
            'slug' => 'daily-tea-meals',
            'type' => 'expense',
        ]);

        $this->pettyCashAccount = FinancialAccount::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Petty Cash Till',
            'type' => 'cash',
            'opening_balance' => 10000.00,
            'current_balance' => 10000.00,
            'is_active' => true,
        ]);
    }

    public function test_user_can_record_approved_expense_and_account_balance_is_deducted(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $payload = [
            'branch_id' => $this->branchA->id,
            'category_id' => $this->categoryTea->id,
            'financial_account_id' => $this->pettyCashAccount->id,
            'date' => now()->toDateString(),
            'amount' => 1500.00,
            'payment_method' => 'cash',
            'payee_name' => 'Aslam Tea Stall',
            'reference_no' => 'BILL-4421',
            'description' => 'Morning and afternoon counter tea for staff',
            'status' => 'approved',
        ];

        $response = $this->post(route('tenant.expenses.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'category_id' => $this->categoryTea->id,
            'financial_account_id' => $this->pettyCashAccount->id,
            'amount' => 1500.00,
            'payee_name' => 'Aslam Tea Stall',
            'status' => 'approved',
            'approved_by' => $this->userA->id,
        ]);

        // Account balance deducted from 10,000 to 8,500
        $this->pettyCashAccount->refresh();
        $this->assertEquals(8500.00, (float) $this->pettyCashAccount->current_balance);
    }

    public function test_pending_expense_does_not_deduct_account_balance_until_approved(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $payload = [
            'branch_id' => $this->branchA->id,
            'category_id' => $this->categoryTea->id,
            'financial_account_id' => $this->pettyCashAccount->id,
            'date' => now()->toDateString(),
            'amount' => 2500.00,
            'payment_method' => 'cash',
            'status' => 'pending_approval',
        ];

        $response = $this->post(route('tenant.expenses.store'), $payload);
        $response->assertRedirect();

        $expense = Expense::where('tenant_id', $this->tenantA->id)->first();
        $this->assertEquals('pending_approval', $expense->status);

        // Account balance should remain untouched at 10,000
        $this->pettyCashAccount->refresh();
        $this->assertEquals(10000.00, (float) $this->pettyCashAccount->current_balance);

        // Manager approves voucher
        $approveResponse = $this->post(route('tenant.expenses.approve', $expense->id));
        $approveResponse->assertRedirect();

        $expense->refresh();
        $this->assertEquals('approved', $expense->status);
        $this->assertEquals($this->userA->id, $expense->approved_by);

        // Account balance now deducted to 7,500
        $this->pettyCashAccount->refresh();
        $this->assertEquals(7500.00, (float) $this->pettyCashAccount->current_balance);
    }

    public function test_manager_can_reject_pending_expense_voucher(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $payload = [
            'branch_id' => $this->branchA->id,
            'category_id' => $this->categoryTea->id,
            'financial_account_id' => $this->pettyCashAccount->id,
            'date' => now()->toDateString(),
            'amount' => 5000.00,
            'payment_method' => 'cash',
            'status' => 'pending_approval',
        ];

        $this->post(route('tenant.expenses.store'), $payload);
        $expense = Expense::where('tenant_id', $this->tenantA->id)->first();

        // Reject
        $rejectResponse = $this->post(route('tenant.expenses.reject', $expense->id), [
            'rejection_reason' => 'Exceeds daily petty cash threshold; original receipt missing',
        ]);
        $rejectResponse->assertRedirect();

        $expense->refresh();
        $this->assertEquals('rejected', $expense->status);
        $this->assertEquals('Exceeds daily petty cash threshold; original receipt missing', $expense->rejection_reason);

        // Account balance remains 10,000
        $this->pettyCashAccount->refresh();
        $this->assertEquals(10000.00, (float) $this->pettyCashAccount->current_balance);
    }

    public function test_cancelling_expense_refunds_account_balance(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $payload = [
            'branch_id' => $this->branchA->id,
            'category_id' => $this->categoryTea->id,
            'financial_account_id' => $this->pettyCashAccount->id,
            'date' => now()->toDateString(),
            'amount' => 2000.00,
            'payment_method' => 'cash',
            'status' => 'approved',
        ];

        $this->post(route('tenant.expenses.store'), $payload);
        $this->pettyCashAccount->refresh();
        $this->assertEquals(8000.00, (float) $this->pettyCashAccount->current_balance);

        $expense = Expense::where('tenant_id', $this->tenantA->id)->first();

        // Cancel / delete
        $deleteResponse = $this->delete(route('tenant.expenses.destroy', $expense->id));
        $deleteResponse->assertRedirect();

        // Account balance refunded back to 10,000
        $this->pettyCashAccount->refresh();
        $this->assertEquals(10000.00, (float) $this->pettyCashAccount->current_balance);

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_expense_with_receipt_attachment(): void
    {
        Storage::fake('public');

        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $file = UploadedFile::fake()->create('bill_receipt.pdf', 500, 'application/pdf');

        $payload = [
            'branch_id' => $this->branchA->id,
            'category_id' => $this->categoryTea->id,
            'financial_account_id' => $this->pettyCashAccount->id,
            'date' => now()->toDateString(),
            'amount' => 800.00,
            'payment_method' => 'cash',
            'status' => 'approved',
            'attachment' => $file,
        ];

        $response = $this->post(route('tenant.expenses.store'), $payload);
        $response->assertRedirect();

        $expense = Expense::where('tenant_id', $this->tenantA->id)->first();
        $this->assertNotNull($expense->attachment_path);
        Storage::disk('public')->assertExists($expense->attachment_path);
    }

    public function test_tenant_isolation_in_expenses(): void
    {
        $this->actingAs($this->userA);
        $this->context->setTenant($this->tenantA);

        $expenseA = Expense::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'category_id' => $this->categoryTea->id,
            'expense_no' => 'EXP-2601-99999',
            'date' => now()->toDateString(),
            'amount' => 3000.00,
            'payment_method' => 'cash',
            'status' => 'pending_approval',
            'created_by' => $this->userA->id,
        ]);

        // Tenant B logs in
        $this->actingAs($this->userB);
        $this->context->setTenant($this->tenantB);

        // Attempt to view Tenant A's expense
        $viewResponse = $this->get(route('tenant.expenses.show', $expenseA->id));
        $viewResponse->assertNotFound();

        // Attempt to approve Tenant A's expense
        $approveResponse = $this->post(route('tenant.expenses.approve', $expenseA->id));
        $approveResponse->assertNotFound();

        // Attempt to delete Tenant A's expense
        $deleteResponse = $this->delete(route('tenant.expenses.destroy', $expenseA->id));
        $deleteResponse->assertNotFound();
    }

    public function test_bilingual_labels_and_status_badges(): void
    {
        $expense = new Expense(['payment_method' => 'cash', 'status' => 'approved']);
        $this->assertStringContainsString('Cash Drawer', $expense->payment_method_label);
        $this->assertStringContainsString('کیش', $expense->payment_method_label);
        $this->assertEquals('bg-success', $expense->status_badge_class);

        $bankExpense = new Expense(['payment_method' => 'bank', 'status' => 'pending_approval']);
        $this->assertStringContainsString('Bank Transfer', $bankExpense->payment_method_label);
        $this->assertEquals('bg-warning text-dark', $bankExpense->status_badge_class);
    }
}
