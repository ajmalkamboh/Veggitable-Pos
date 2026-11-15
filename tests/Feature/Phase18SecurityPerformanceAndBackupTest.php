<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\BackupService;
use App\Services\SecurityAuditService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class Phase18SecurityPerformanceAndBackupTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;

    protected Branch $branchA;

    protected User $userA;

    protected User $superAdmin;

    protected Tenant $tenantB;

    protected Branch $branchB;

    protected User $userB;

    protected BackupService $backupService;

    protected SecurityAuditService $securityAuditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $context = app(TenantContext::class);
        $context->reset();

        $this->backupService = app(BackupService::class);
        $this->securityAuditService = app(SecurityAuditService::class);

        $plan = SubscriptionPlan::create([
            'name' => 'Enterprise Pro',
            'slug' => 'enterprise-pro',
            'price_monthly' => 5000.00,
            'max_branches' => 10,
            'max_users' => 20,
            'features' => ['all'],
            'is_active' => true,
        ]);

        // Platform Super Admin
        $this->superAdmin = User::create([
            'name' => 'Platform Architect',
            'email' => 'superadmin@veggitable.pk',
            'password' => Hash::make('AdminSecret123!'),
            'is_super_admin' => true,
            'is_tenant_owner' => false,
            'status' => 'active',
        ]);

        // Tenant A
        $this->tenantA = Tenant::factory()->create([
            'name' => 'Islamabad Premium Vegetables',
            'slug' => 'isb-premium',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchA = $this->tenantA->branches()->create([
            'name' => 'F-7 Jinnah Super Branch',
            'code' => 'F7-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $ownerRole = Role::where('slug', 'owner')->first();

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'current_branch_id' => $this->branchA->id,
            'name' => 'Raja Tariq',
            'email' => 'tariq@isbveg.pk',
            'password' => Hash::make('Secret123!'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        if ($ownerRole) {
            $this->userA->roles()->attach($ownerRole->id, ['tenant_id' => $this->tenantA->id]);
        }

        // Tenant B
        $this->tenantB = Tenant::factory()->create([
            'name' => 'Rawalpindi Sabzi Mandi Outlet',
            'slug' => 'rwp-mandi',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchB = $this->tenantB->branches()->create([
            'name' => 'Pirwadhai Mandi Counter',
            'code' => 'RWP-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'current_branch_id' => $this->branchB->id,
            'name' => 'Malik Zubair',
            'email' => 'zubair@rwpmandi.pk',
            'password' => Hash::make('Secret123!'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        if ($ownerRole) {
            $this->userB->roles()->attach($ownerRole->id, ['tenant_id' => $this->tenantB->id]);
        }

        // Master records for Tenant A
        $cat = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Fresh Root Crops',
            'slug' => 'root-crops',
            'is_active' => true,
        ]);

        $unit = Unit::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Kilogram',
            'short_name' => 'kg',
            'code' => 'kg',
            'is_active' => true,
        ]);

        $prod = Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'name' => 'Swat Fresh Potatoes',
            'name_ur' => 'سوات کے تازہ آلو',
            'code' => 'POT-SWAT-01',
            'cost_price' => 50.00,
            'selling_price' => 80.00,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Islamabad Marriott Hotel',
            'code' => 'CUST-MARRIOTT',
            'current_balance' => 15000.00,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Haji Rehman Commission Agent',
            'code' => 'SUPP-REHMAN',
            'current_balance' => 25000.00,
            'is_active' => true,
        ]);

        InventoryStock::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $prod->id,
            'quantity' => 150.00,
            'reorder_level' => 30.00,
            'cost_price' => 50.00,
        ]);

        SalesInvoice::create([
            'tenant_id' => $this->tenantA->id,
            'branch_id' => $this->branchA->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-SEC-01',
            'date' => now()->toDateString(),
            'subtotal' => 2400.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'rounding_amount' => 0.00,
            'total_amount' => 2400.00,
            'paid_amount' => 2400.00,
            'cost_amount' => 1500.00,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);
    }

    public function test_rate_limiting_enforces_throttling_on_login_attempts(): void
    {
        RateLimiter::clear('invalid@isbveg.pk|127.0.0.1');

        // Execute 5 invalid attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'invalid@isbveg.pk',
                'password' => 'WrongPassword',
            ]);
        }

        // The 6th attempt must be throttled
        $response = $this->post('/login', [
            'email' => 'invalid@isbveg.pk',
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    public function test_security_audit_service_detects_tenant_isolation_status(): void
    {
        $audit = $this->securityAuditService->auditTenantIsolation();

        $this->assertIsArray($audit);
        $this->assertArrayHasKey('sales_invoices', $audit);
        $this->assertArrayHasKey('inventory_stocks', $audit);
        $this->assertArrayHasKey('products', $audit);

        // Every checked table must have tenant_id present and zero nulls
        foreach ($audit as $tableName => $result) {
            $this->assertTrue($result['has_tenant_id'], "Table {$tableName} must have tenant_id column");
            $this->assertEquals(0, $result['null_tenant_records'], "Table {$tableName} must not have null tenant records");
            $this->assertEquals(0, $result['orphaned_records'], "Table {$tableName} must not have orphaned tenant records");
            $this->assertTrue($result['is_secure'], "Table {$tableName} must pass isolation audit");
        }

        $metrics = $this->securityAuditService->getSecurityMetrics();
        $this->assertTrue($metrics['csrf_protection']);
        $this->assertEquals(100, $metrics['tenant_isolation']['isolation_integrity_percent']);
    }

    public function test_security_audit_service_sanitizes_xss_inputs_while_preserving_nastaliq_urdu(): void
    {
        $malicious = "<script>alert('xss')</script>Swat Fresh Tomatoes سوات کے تازہ ٹماٹر";
        $cleaned = $this->securityAuditService->sanitizeInput($malicious);

        $this->assertStringNotContainsString('<script>', $cleaned);
        $this->assertStringNotContainsString('</script>', $cleaned);
        $this->assertStringContainsString('Swat Fresh Tomatoes', $cleaned);
        $this->assertStringContainsString('سوات کے تازہ ٹماٹر', $cleaned);
    }

    public function test_backup_service_creates_full_compressed_database_backup_with_checksum(): void
    {
        $backup = $this->backupService->createBackup(null, true);

        $this->assertNotNull($backup);
        $this->assertFileExists($backup['path']);
        $this->assertStringEndsWith('.sql.gz', $backup['filename']);
        $this->assertGreaterThan(0, $backup['size_bytes']);
        $this->assertEquals(64, strlen($backup['sha256']));
        $this->assertEquals('full', $backup['type']);
        $this->assertTrue($backup['compressed']);

        // Verify SHA-256 match
        $fileHash = hash_file('sha256', $backup['path']);
        $this->assertEquals($backup['sha256'], $fileHash);

        // Cleanup
        $this->backupService->deleteBackup($backup['filename']);
        $this->assertFileDoesNotExist($backup['path']);
    }

    public function test_backup_service_exports_isolated_tenant_data_package(): void
    {
        $export = $this->backupService->exportTenantData($this->tenantA, false);

        $this->assertNotNull($export);
        $this->assertFileExists($export['path']);
        $this->assertStringEndsWith('.json', $export['filename']);
        $this->assertEquals($this->tenantA->id, $export['tenant_id']);
        $this->assertEquals($this->tenantA->slug, $export['tenant_slug']);

        $content = File::get($export['path']);
        $json = json_decode($content, true);

        $this->assertIsArray($json);
        $this->assertEquals('1.0', $json['version']);
        $this->assertEquals($this->tenantA->id, $json['tenant']['id']);
        $this->assertEquals('Islamabad Premium Vegetables', $json['tenant']['name']);

        // Verify counts
        $this->assertGreaterThanOrEqual(1, $json['summary_counts']['products']);
        $this->assertGreaterThanOrEqual(1, $json['summary_counts']['sales_invoices']);
        $this->assertGreaterThanOrEqual(1, $json['summary_counts']['customers']);

        // Verify Tenant B's data is NOT present in Tenant A's export
        $this->assertStringNotContainsString('Rawalpindi Sabzi Mandi Outlet', $content);
        $this->assertStringNotContainsString('Pirwadhai Mandi Counter', $content);

        // Cleanup
        $this->backupService->deleteBackup($export['filename']);
    }

    public function test_backup_service_retention_cleans_outdated_backups(): void
    {
        $backup = $this->backupService->createBackup(null, false);
        $filePath = $backup['path'];

        $this->assertFileExists($filePath);

        // Artificially change file modified time to 40 days ago
        touch($filePath, now()->subDays(40)->timestamp);

        $purged = $this->backupService->cleanOldBackups(30);

        $this->assertGreaterThanOrEqual(1, $purged);
        $this->assertFileDoesNotExist($filePath);
    }

    public function test_artisan_backup_and_clean_commands_execute_successfully(): void
    {
        $this->artisan('veggitable:backup', ['--no-compress' => true])
            ->assertExitCode(0);

        $this->artisan('veggitable:backup', ['--tenant' => $this->tenantA->slug])
            ->assertExitCode(0);

        $this->artisan('veggitable:clean-backups', ['--days' => 90])
            ->assertExitCode(0);
    }

    public function test_superadmin_can_access_backups_dashboard_and_download_backup(): void
    {
        // 1. Tenant User must be rejected with 403 Forbidden
        $response = $this->actingAs($this->userA)->get(route('superadmin.backups.index'));
        $response->assertStatus(403);

        // 2. Super Admin can access dashboard
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.backups.index'));
        $response->assertStatus(200);
        $response->assertSee('Platform Disaster Recovery');
        $response->assertSee('Multi-Tenant Data Partitioning');

        // 3. Super Admin can trigger instant backup
        $postResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.backups.generate'), [
            'compress' => 1,
        ]);
        $postResponse->assertRedirect(route('superadmin.backups.index'));
        $postResponse->assertSessionHas('success');

        // 4. Download latest backup
        $backups = $this->backupService->listBackups();
        $this->assertNotEmpty($backups);
        $latest = $backups[0];

        $downloadResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.backups.download', $latest['filename']));
        $downloadResponse->assertStatus(200);

        // 5. Delete backup
        $deleteResponse = $this->actingAs($this->superAdmin)->delete(route('superadmin.backups.destroy', $latest['filename']));
        $deleteResponse->assertRedirect(route('superadmin.backups.index'));
        $deleteResponse->assertSessionHas('success');
    }

    public function test_tenant_can_access_data_export_and_download_own_package(): void
    {
        app(TenantContext::class)->setTenant($this->tenantA);

        $response = $this->actingAs($this->userA)->get(route('tenant.backup.index'));
        $response->assertStatus(200);
        $response->assertSee('Business Data Export & Portable Backup');
        $response->assertSee('Islamabad Premium Vegetables');

        // Trigger export and direct download
        $exportResponse = $this->actingAs($this->userA)->post(route('tenant.backup.export'), [
            'compress' => 0,
            'download' => 1,
        ]);

        $exportResponse->assertStatus(200);
        $this->assertEquals('application/json', $exportResponse->headers->get('content-type'));
    }
}
