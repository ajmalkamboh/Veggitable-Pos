<?php

namespace App\Http\Middleware;

use App\Core\MultiTenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantOnboarded
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->context->getTenant();
        $user = $request->user();

        // Platform Super Admin bypasses tenant onboarding requirement
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($tenant && ! $tenant->isOnboarded()) {
            if (! $request->routeIs('tenant.onboarding.*') && ! $request->routeIs('logout') && ! $request->routeIs('language.*')) {
                return redirect()->route('tenant.onboarding.index')
                    ->with('warning', 'Please complete your initial business configuration to unlock shop operations.');
            }
        }

        return $next($request);
    }
}
