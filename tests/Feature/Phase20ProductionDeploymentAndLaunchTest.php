<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Branch;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LaunchReadinessService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase20ProductionDeploymentAndLaunchTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected Tenant $tenantA;

    protected Branch $branchA;

    protected User $tenantUser;

    protected LaunchReadinessService $readinessService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $context = app(TenantContext::class);
        $context->reset();

        $this->readinessService = app(LaunchReadinessService::class);

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
            'name' => 'Islamabad Fresh Produce',
            'slug' => 'isb-fresh',
            'plan_id' => $plan->id,
            'status' => 'active',
            'onboarding_completed' => true,
        ]);

        $this->branchA = $this->tenantA->branches()->create([
            'name' => 'F-6 Super Market Branch',
            'code' => 'ISB-01',
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $ownerRole = Role::where('slug', 'owner')->first();

        $this->tenantUser = User::create([
            'tenant_id' => $this->tenantA->id,
            'current_branch_id' => $this->branchA->id,
            'name' => 'Malik Jahangir',
            'email' => 'jahangir@isbfresh.pk',
            'password' => Hash::make('Secret123!'),
            'is_tenant_owner' => true,
            'status' => 'active',
        ]);
        if ($ownerRole) {
            $this->tenantUser->roles()->attach($ownerRole->id, ['tenant_id' => $this->tenantA->id]);
        }
    }

    public function test_launch_readiness_service_returns_compliant_status_and_specs(): void
    {
        $diagnostics = $this->readinessService->runDiagnostics();

        $this->assertIsArray($diagnostics);
        $this->assertContains($diagnostics['status'], ['pass', 'warn']);
        $this->assertEquals(0, $diagnostics['summary']['failures']);
        $this->assertGreaterThanOrEqual(8, $diagnostics['summary']['passed']);

        $specs = $diagnostics['server_specs'];
        $this->assertNotEmpty($specs['php_version']);
        $this->assertNotEmpty($specs['laravel_version']);
        $this->assertNotEmpty($specs['memory_limit']);

        $checks = $diagnostics['checks'];
        $this->assertEquals('pass', $checks['php_version']['status']);
        $this->assertEquals('pass', $checks['php_extensions']['status']);
        $this->assertEquals('pass', $checks['database_connection']['status']);
        $this->assertEquals('pass', $checks['writable_paths']['status']);
        $this->assertEquals('pass', $checks['multi_tenancy']['status']);
    }

    public function test_artisan_launch_readiness_command_executes_with_success_code(): void
    {
        $this->artisan('veggitable:launch-readiness')
            ->expectsOutputToContain('PRODUCTION LAUNCH READINESS AUDIT')
            ->expectsOutputToContain('Audit Summary')
            ->assertExitCode(0);
    }

    public function test_superadmin_can_access_system_health_dashboard_and_metrics(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.health.index'));

        $response->assertStatus(200);
        $response->assertSee('System Health & Production Readiness');
        $response->assertSee('Readiness Score');
        $response->assertSee('Commercial SaaS Launch Checklist');
        $response->assertSee('Cron Scheduler Daemon');
        $response->assertSee('Queue Worker Daemon');
    }

    public function test_tenant_user_is_forbidden_from_accessing_system_health(): void
    {
        $response = $this->actingAs($this->tenantUser)->get(route('superadmin.health.index'));

        $response->assertStatus(403);
    }

    public function test_superadmin_can_trigger_cache_optimization_and_purge(): void
    {
        $optResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.health.optimize'));
        $optResponse->assertRedirect(route('superadmin.health.index'));
        $optResponse->assertSessionHas('success');

        $clearResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.health.clear'));
        $clearResponse->assertRedirect(route('superadmin.health.index'));
        $clearResponse->assertSessionHas('success');
    }

    public function test_laravel_application_up_health_endpoint_responds_ok(): void
    {
        $response = $this->get('/up');
        $response->assertStatus(200);
    }

    public function test_production_deployment_guide_documentation_exists(): void
    {
        $guidePath = base_path('docs/PRODUCTION_DEPLOYMENT_GUIDE.md');

        $this->assertFileExists($guidePath);
        $content = File::get($guidePath);

        $this->assertStringContainsString('Nginx Virtual Host Configuration', $content);
        $this->assertStringContainsString('PHP 8.3 OPCache & FPM Tuning', $content);
        $this->assertStringContainsString('Cron Scheduler & Supervisor Queue Workers', $content);
        $this->assertStringContainsString('Automated Backups & Disaster Recovery', $content);
        $this->assertStringContainsString('Launch Verification Checklist', $content);
    }
}
