<?php

namespace App\Providers;

use App\Core\MultiTenancy\TenantContext;
use App\Core\MultiTenancy\TenantManager;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
    }
}
