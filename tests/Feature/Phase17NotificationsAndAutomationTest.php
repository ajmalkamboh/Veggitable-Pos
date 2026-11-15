<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryStock;
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
use App\Notifications\CustomerBalanceAlertNotification;
use App\Notifications\DailySalesSummaryNotification;
use App\Notifications\DailyWastageSummaryNotification;
use App\Notifications\LowStockAlertNotification;
use App\Notifications\SubscriptionExpiryNotification;
use App\Notifications\SupplierPaymentReminderNotification;
use App\Services\AutomationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Phase17NotificationsAndAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    protected Branch $branchA;

    protected Branch $branchB;

    protected User $userA;

    protected User $userB;

    protected Product $productTomatoes;

    protected Customer $customerA;

    protected Supplier $supplierA;

    protected AutomationService $automationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $context = app(TenantContext::class);
        $context->reset();

        $this->automationService = app(AutomationService::class);

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
            'name' => 'Islamabad Fresh Organic Market',
            'slug' => 'isb-fresh',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
            'subscription_ends_at' => now()->addDays(7)->toDateString(),
        ]);

        $this->branchA = $this->tenantA->branches()->create([
            'name' => 'F-6 Super Market Branch',
            'code' => 'ISB-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $ownerRole = Role::where('slug', 'owner')->first();

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'current_branch_id' => $this->branchA->id,
            'name' => 'Malik Jahangir',
            'email' => 'jahangir@isbfresh.pk',
            'password' => Hash::make('Secret123!'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        if ($ownerRole) {
            $this->userA->roles()->attach($ownerRole->id, ['tenant_id' => $this->tenantA->id]);
        }

        // Tenant B
        $this->tenantB = Tenant::factory()->create([
            'name' => 'Peshawar Sabzi Mandi Store',
            'slug' => 'psh-mandi',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchB = $this->tenantB->branches()->create([
            'name' => 'Karkhano Branch',
            'code' => 'PSH-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'current_branch_id' => $this->branchB->id,
            'name' => 'Khan Zaman',
            'email' => 'zaman@pshmandi.pk',
            'password' => Hash::make('Secret123!'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        if ($ownerRole) {
            $this->userB->roles()->attach($ownerRole->id, ['tenant_id' => $this->tenantB->id]);
        }

        // Master Data
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

        $this->productTomatoes = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'unit_id' => $kgUnit->id,
            'name' => 'Swat Fresh Tomatoes',
            'name_ur' => 'سوات کے تازہ ٹماٹر',
            'code' => 'TOM-SWAT-01',
            'cost_price' => 70.00,
            'selling_price' => 110.00,
            'reorder_level' => 25.00,
            'is_active' => true,
        ]);

        $this->customerA = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Serena Hotel Islamabad',
            'code' => 'CUST-SERENA',
            'phone' => '051-2874000',
            'current_balance' => 8500.00,
            'credit_limit' => 5000.00,
            'is_active' => true,
        ]);

        $this->supplierA = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'I-11 Islamabad Sabzi Mandi Commission Agent',
            'code' => 'SUPP-I11',
            'contact_person' => 'Haji Sheraz',
            'phone' => '03335557778',
            'current_balance' => 18000.00,
            'is_active' => true,
        ]);
    }

    public function test_low_stock_automation_detects_threshold_and_dispatches_notification(): void
    {
        Notification::fake();

        // Quantity is 10 kg, which is <= reorder_level 25 kg
        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productTomatoes->id,
            'quantity' => 10.00,
            'reorder_level' => 25.00,
            'cost_price' => 70.00,
        ]);

        $dispatched = $this->automationService->checkLowStock($this->tenantA);

        $this->assertGreaterThanOrEqual(1, $dispatched);

        Notification::assertSentTo(
            $this->userA,
            LowStockAlertNotification::class,
            function ($notification) {
                return $notification->product->id === $this->productTomatoes->id
                    && $notification->currentQuantity == 10.00
                    && $notification->reorderLevel == 25.00;
            }
        );
    }

    public function test_daily_sales_summary_automation_compiles_turnover_and_profit(): void
    {
        Notification::fake();

        $today = now()->toDateString();

        SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'invoice_no' => 'INV-SUMM-01',
            'date' => $today,
            'subtotal' => 5000.00,
            'discount_amount' => 200.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 4800.00,
            'paid_amount' => 4000.00,
            'cost_amount' => 3000.00,
            'status' => 'partial',
            'payment_status' => 'partial',
        ]);

        $notif = $this->automationService->sendDailySalesSummary($this->tenantA, $today);

        $this->assertNotNull($notif);
        $this->assertEquals(4800.00, $notif->netSales);
        $this->assertEquals(4000.00, $notif->cashCollected);
        $this->assertEquals(800.00, $notif->creditDue);
        $this->assertEquals(1800.00, $notif->grossProfit); // 4800 - 3000

        Notification::assertSentTo($this->userA, DailySalesSummaryNotification::class);
    }

    public function test_daily_wastage_summary_automation_compiles_cull_loss(): void
    {
        Notification::fake();

        $today = now()->toDateString();

        WastageRecord::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'wastage_no' => 'WST-NOTIF-01',
            'date' => $today,
            'reason' => 'spoilage',
            'total_quantity' => 12.00,
            'total_loss_cost' => 840.00,
            'status' => 'approved',
            'created_by' => $this->userA->id,
        ]);

        $notif = $this->automationService->sendDailyWastageSummary($this->tenantA, $today);

        $this->assertNotNull($notif);
        $this->assertEquals(840.00, $notif->totalLossCost);
        $this->assertEquals(12.00, $notif->totalQuantity);
        $this->assertEquals(1, $notif->vouchersCount);

        Notification::assertSentTo($this->userA, DailyWastageSummaryNotification::class);
    }

    public function test_customer_balance_and_supplier_payable_alerts(): void
    {
        Notification::fake();

        // 1. Customer Overdue Balance Alert (Customer has 8500 balance > threshold 0)
        $custAlerts = $this->automationService->checkCustomerBalances($this->tenantA, 5000.00);
        $this->assertGreaterThanOrEqual(1, $custAlerts);

        Notification::assertSentTo(
            $this->userA,
            CustomerBalanceAlertNotification::class,
            function ($n) {
                return $n->customer->id === $this->customerA->id
                    && $n->currentBalance == 8500.00;
            }
        );

        // 2. Supplier Payment Reminder Alert
        PurchaseInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'supplier_id' => $this->supplierA->id,
            'invoice_no' => 'PUR-NOTIF-01',
            'date' => now()->toDateString(),
            'subtotal' => 18000.00,
            'total_amount' => 18000.00,
            'paid_amount' => 0.00,
            'status' => 'due',
        ]);

        $suppAlerts = $this->automationService->checkSupplierPayables($this->tenantA, 10000.00);
        $this->assertGreaterThanOrEqual(1, $suppAlerts);

        Notification::assertSentTo(
            $this->userA,
            SupplierPaymentReminderNotification::class,
            function ($n) {
                return $n->supplier->id === $this->supplierA->id
                    && $n->currentPayable == 18000.00;
            }
        );
    }

    public function test_subscription_expiry_automation_scans_upcoming_expirations(): void
    {
        Notification::fake();

        // Tenant A subscription expires in exactly 7 days
        $notified = $this->automationService->checkSubscriptionExpiries();

        $this->assertGreaterThanOrEqual(1, $notified);

        Notification::assertSentTo(
            $this->userA,
            SubscriptionExpiryNotification::class,
            function ($n) {
                return $n->tenant->id === $this->tenantA->id
                    && $n->daysRemaining === 7;
            }
        );
    }

    public function test_artisan_console_commands_run_successfully(): void
    {
        $this->artisan('veggitable:daily-summaries', [
            '--tenant' => $this->tenantA->id,
            '--date' => now()->toDateString(),
        ])
            ->assertExitCode(0);

        $this->artisan('veggitable:check-subscriptions')
            ->assertExitCode(0);
    }

    public function test_notifications_center_mark_as_read_and_settings_persistence(): void
    {
        // 1. Dispatch real database notification
        $this->userA->notify(new LowStockAlertNotification(
            $this->tenantA,
            $this->productTomatoes,
            5.0,
            25.0,
            'F-6 Super Market Branch'
        ));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->userA->id,
            'notifiable_type' => User::class,
        ]);

        $notif = $this->userA->unreadNotifications()->first();
        $this->assertNotNull($notif);

        // 2. View in Notifications Center
        $responseIndex = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.notifications.index'));

        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Low Stock Alert: Swat Fresh Tomatoes');
        $responseIndex->assertSee('F-6 Super Market Branch');

        // 3. Mark single notification as read
        $responseRead = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->post(route('tenant.notifications.read', $notif->id));

        $responseRead->assertRedirect();
        $this->assertNull($this->userA->fresh()->unreadNotifications()->find($notif->id));

        // 4. Update notification channel settings
        $responseSettings = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->post(route('tenant.notifications.settings.update'), [
                'notify_low_stock' => '1',
                'notify_daily_summary' => '1',
                'notify_customer_overdue' => '1',
                'notify_supplier_due' => '1',
                'email_channel' => '1',
                'sms_channel' => '1',
                'whatsapp_channel' => '1',
                'sms_provider' => 'Jazz_Bulk_SMS',
                'sms_sender_id' => 'ISBFRESH',
                'sms_api_url' => 'https://api.sms-gateway.pk/send',
                'whatsapp_webhook_url' => 'https://graph.facebook.com/v18.0/123456/messages',
            ]);

        $responseSettings->assertRedirect(route('tenant.notifications.settings'));

        $this->tenantA->refresh();
        $notifSettings = $this->tenantA->settings['notifications'];
        $this->assertEquals('Jazz_Bulk_SMS', $notifSettings['sms_provider']);
        $this->assertEquals('ISBFRESH', $notifSettings['sms_sender_id']);
        $this->assertTrue($notifSettings['sms_channel']);
        $this->assertTrue($notifSettings['whatsapp_channel']);

        // 5. Test Interactive Trigger API
        $responseTrigger = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->postJson(route('tenant.notifications.trigger'), [
                'routine' => 'daily_sales',
            ]);

        $responseTrigger->assertStatus(200);
        $responseTrigger->assertJson([
            'success' => true,
            'routine' => 'daily_sales',
        ]);
    }

    public function test_notifications_strictly_enforce_tenant_boundaries(): void
    {
        // Tenant B user receives an alert
        $this->userB->notify(new LowStockAlertNotification(
            $this->tenantB,
            $this->productTomatoes,
            2.0,
            10.0,
            'Karkhano Branch'
        ));

        // Tenant A views notification center
        $responseA = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->get(route('tenant.notifications.index'));

        $responseA->assertStatus(200);
        $responseA->assertDontSee('Karkhano Branch');

        // Tenant A cannot mark Tenant B's notification as read
        $notifB = $this->userB->notifications()->first();

        $responseCross = $this->actingAs($this->userA)
            ->withSession(['tenant_id' => $this->tenantA->id])
            ->post(route('tenant.notifications.read', $notifB->id));

        $responseCross->assertStatus(404);
    }
}
