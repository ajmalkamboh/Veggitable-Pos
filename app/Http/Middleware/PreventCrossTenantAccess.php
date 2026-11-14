<?php

namespace App\Http\Middleware;

use App\Core\MultiTenancy\TenantContext;
use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventCrossTenantAccess
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
        $user = $request->user();

        if ($user instanceof User) {
            // Super Admin has platform-wide authorization
            if ($user->isSuperAdmin()) {
                return $next($request);
            }

            // If a route parameter specifies tenant, ensure it matches user's tenant
            $routeTenant = $request->route('tenant');
            if ($routeTenant) {
                $targetTenantId = $routeTenant instanceof Tenant
                    ? $routeTenant->id
                    : (is_numeric($routeTenant) ? (int) $routeTenant : Tenant::where('slug', $routeTenant)->value('id'));

                if ($targetTenantId && (int) $user->tenant_id !== (int) $targetTenantId) {
                    abort(403, 'Cross-tenant access forbidden: you do not have permission to access this business.');
                }
            }

            // Ensure active context matches user's tenant
            if ($this->context->hasTenant() && (int) $this->context->getTenantId() !== (int) $user->tenant_id) {
                abort(403, 'Cross-tenant context mismatch detected.');
            }
        }

        return $next($request);
    }
}
