<?php

namespace App\Http\Middleware;

use App\Core\MultiTenancy\TenantContext;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasFeature
{
    public function __construct(
        protected TenantContext $context,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = $this->context->getTenant();

        if ($tenant) {
            $this->subscriptionService->assertHasFeature($tenant, $feature);
        }

        return $next($request);
    }
}
