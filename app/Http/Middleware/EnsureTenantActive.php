<?php

namespace App\Http\Middleware;

use App\Core\MultiTenancy\TenantContext;
use App\Enums\TenantStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
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
        if ($this->context->hasTenant()) {
            $tenant = $this->context->getTenant();

            if ($tenant->status === TenantStatus::Suspended) {
                abort(403, 'This business account is currently suspended. Please contact platform administration.');
            }

            if ($tenant->status === TenantStatus::Expired) {
                abort(403, 'Your subscription or trial period has expired. Please renew your subscription to continue.');
            }

            if ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isPast()) {
                abort(403, 'Your subscription period has ended. Please renew to regain access.');
            }
        }

        return $next($request);
    }
}
