<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class Phase01TechnicalArchitectureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the technical foundation dashboard loads successfully.
     */
    public function test_dashboard_page_loads_with_easyadmin_template(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Veggitable SaaS');
        $response->assertSee('assets/vendor/bootstrap/css/bootstrap.min.css');
        $response->assertSee('assets/css/main.css');
        $response->assertSee('assets/vendor/bootstrap/js/bootstrap.bundle.min.js');
    }

    /**
     * Test that language switcher works and redirects back.
     */
    public function test_language_switcher_switches_locale_to_urdu(): void
    {
        $response = $this->get('/language/ur');

        $response->assertStatus(302);
        $response->assertSessionHas('locale', 'ur');
    }

    /**
     * Test that Urdu locale activates RTL layout and typography.
     */
    public function test_urdu_locale_renders_rtl_direction_and_stylesheets(): void
    {
        $response = $this->withSession(['locale' => 'ur'])->get('/');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ur"', false);
        $response->assertSee('bootstrap.rtl.min.css');
        $response->assertSee('custom-rtl.css');
        $response->assertSee('ویجیٹیبل ساس');
    }

    /**
     * Test that invalid locale is ignored.
     */
    public function test_invalid_locale_is_ignored(): void
    {
        $response = $this->get('/language/invalid_lang');

        $response->assertStatus(302);
        $this->assertEquals('en', Session::get('locale', 'en'));
    }
}
