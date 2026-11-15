<?php

namespace App\Providers;

use App\Core\MultiTenancy\TenantContext;
use App\Core\MultiTenancy\TenantManager;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->singleton(TenantManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Central Gate interceptor for role-based permissions
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin() || $user->isTenantOwner()) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null; // Fall through to specific policies
        });

        // Register Security & Enterprise Rate Limiters
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiters for authentication, POS operations, and critical endpoints.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by($request->ip().'|'.$email)->response(function () {
                return back()->withErrors([
                    'email' => __('Too many login attempts. Please try again in 60 seconds.'),
                ])->withInput();
            });
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('pos-transactions', function (Request $request) {
            $key = $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();

            return Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('backup-actions', function (Request $request) {
            $key = $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();

            return Limit::perMinute(10)->by($key);
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
