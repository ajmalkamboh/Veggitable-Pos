<?php

namespace Tests\Feature;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Category;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\TenantOnboardingService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase06LocalizationAndRtlTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantUrdu;

    protected Tenant $tenantEnglish;

    protected User $userUrdu;

    protected TenantContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->context = app(TenantContext::class);
        $this->context->reset();

        $plan = SubscriptionPlan::create([
            'name' => 'Standard Plan',
            'slug' => 'standard',
            'price_monthly' => 3500.00,
            'max_branches' => 5,
            'max_users' => 10,
            'features' => ['pos_counter', 'inventory_management'],
            'is_active' => true,
        ]);

        // Tenant with default Urdu locale
        $this->tenantUrdu = Tenant::factory()->create([
            'name' => 'المدینہ سبزی و فروٹ شاپ',
            'slug' => 'al-madina-ur',
            'default_locale' => 'ur',
            'plan_id' => $plan->id,
            'onboarding_completed' => true,
        ]);

        $this->userUrdu = User::create([
            'tenant_id' => $this->tenantUrdu->id,
            'name' => 'حاجی محمد',
            'email' => 'haji@madina.test',
            'password' => Hash::make('password123'),
            'is_tenant_owner' => true,
            'status' => 'active',
            'preferences' => ['locale' => 'ur'],
        ]);

        // Seed bilingual defaults
        $onboardingService = app(TenantOnboardingService::class);
        $onboardingService->seedDefaultUnits($this->tenantUrdu);
        $onboardingService->seedDefaultCategories($this->tenantUrdu);

        // Tenant with default English locale
        $this->tenantEnglish = Tenant::factory()->create([
            'name' => 'Bismillah Veg Mart',
            'slug' => 'bismillah-en',
            'default_locale' => 'en',
            'plan_id' => $plan->id,
            'onboarding_completed' => true,
        ]);
    }

    /**
     * Test switching language route updates session and redirects back.
     */
    public function test_locale_switch_route_updates_session(): void
    {
        // Switch to Urdu
        $responseUr = $this->get(route('language.switch', 'ur'));
        $responseUr->assertRedirect();
        $responseUr->assertSessionHas('locale', 'ur');

        // Switch to English
        $responseEn = $this->get(route('language.switch', 'en'));
        $responseEn->assertRedirect();
        $responseEn->assertSessionHas('locale', 'en');
    }

    /**
     * Test switching language persists user preference when authenticated.
     */
    public function test_locale_switch_persists_user_preference(): void
    {
        $this->actingAs($this->userUrdu);

        $this->get(route('language.switch', 'en'));

        $this->userUrdu->refresh();
        $this->assertEquals('en', $this->userUrdu->preferences['locale'] ?? null);
    }

    /**
     * Test middleware resolves tenant default locale when session is empty.
     */
    public function test_middleware_resolves_tenant_default_locale(): void
    {
        $this->context->setTenant($this->tenantUrdu);

        $response = $this->withSession([])->get(route('dashboard'));
        $response->assertStatus(200);
        $this->assertEquals('ur', App::getLocale());
    }

    /**
     * Test RTL layout attributes and CSS applied when Urdu locale is active.
     */
    public function test_rtl_layout_attributes_applied_in_urdu(): void
    {
        $response = $this->withSession(['locale' => 'ur'])->get(route('login'));
        $response->assertStatus(200);

        // Verify dir="rtl" and lang="ur"
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ur"', false);

        // Verify bootstrap RTL and custom-rtl.css are included
        $response->assertSee('bootstrap.rtl.min.css');
        $response->assertSee('custom-rtl.css');
        $response->assertDontSee('bootstrap.min.css');
    }

    /**
     * Test LTR layout attributes and CSS applied when English locale is active.
     */
    public function test_ltr_layout_attributes_applied_in_english(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get(route('login'));
        $response->assertStatus(200);

        // Verify dir="ltr" and lang="en"
        $response->assertSee('dir="ltr"', false);
        $response->assertSee('lang="en"', false);

        // Verify standard bootstrap is included without custom-rtl.css
        $response->assertSee('bootstrap.min.css');
        $response->assertDontSee('bootstrap.rtl.min.css');
        $response->assertDontSee('custom-rtl.css');
    }

    /**
     * Test translation dictionaries resolve authentic Urdu strings.
     */
    public function test_urdu_translations_resolve_correctly(): void
    {
        App::setLocale('ur');

        $this->assertEquals('ڈیش بورڈ', __('common.dashboard'));
        $this->assertEquals('ترتیبات', __('common.settings'));
        $this->assertEquals('تازہ سبزیاں', __('shop.vegetables'));
        $this->assertEquals('سیل کاؤنٹر / پی او ایس', __('shop.pos_counter'));
        $this->assertEquals('فروخت کی پرچی / رسید', __('shop.thermal_receipt'));
        $this->assertEquals('کل رقم', __('shop.total_amount'));
    }

    /**
     * Test bilingual search finds records by both English and Urdu keywords.
     */
    public function test_bilingual_search_finds_records_in_both_languages(): void
    {
        $this->context->setTenant($this->tenantUrdu);

        // 1. Search category in Urdu
        $urduFound = Category::searchBilingual('پتوں والی')->first();
        $this->assertNotNull($urduFound);
        $this->assertEquals('Fresh Leafy Vegetables', $urduFound->name);

        // 2. Search same category in English
        $englishFound = Category::searchBilingual('Leafy')->first();
        $this->assertNotNull($englishFound);
        $this->assertEquals($urduFound->id, $englishFound->id);

        // 3. Search unit in Urdu
        $unitUrdu = Unit::searchBilingual('کلوگرام')->first();
        $this->assertNotNull($unitUrdu);
        $this->assertEquals('kg', $unitUrdu->code);

        // 4. Search unit in English
        $unitEn = Unit::searchBilingual('Kilogram')->first();
        $this->assertNotNull($unitEn);
        $this->assertEquals($unitUrdu->id, $unitEn->id);
    }
}
