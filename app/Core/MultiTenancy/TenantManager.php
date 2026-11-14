<?php

namespace App\Core\MultiTenancy;

use App\Core\MultiTenancy\Exceptions\CrossTenantAccessException;
use App\Core\MultiTenancy\Exceptions\TenantExpiredException;
use App\Core\MultiTenancy\Exceptions\TenantSuspendedException;
use App\Enums\TenantStatus;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TenantManager
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Resolve and activate the tenant based on the incoming HTTP request.
     */
    public function resolveFromRequest(Request $request): ?Tenant
    {
        $tenant = null;
        $user = $request->user();

        // 1. If user is authenticated and attached to a tenant
        if ($user instanceof User && $user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }

        // 2. Route parameter (e.g. /t/{tenant}/... or ?tenant=...)
        if (! $tenant && $request->route('tenant')) {
            $routeTenant = $request->route('tenant');
            $tenant = is_numeric($routeTenant)
                ? Tenant::find($routeTenant)
                : Tenant::where('slug', $routeTenant)->first();
        }

        // 3. Header identification (e.g. API requests or background synchronization)
        if (! $tenant && $request->header('X-Tenant-ID')) {
            $tenant = Tenant::find($request->header('X-Tenant-ID'));
        } elseif (! $tenant && $request->header('X-Tenant-Slug')) {
            $tenant = Tenant::where('slug', $request->header('X-Tenant-Slug'))->first();
        }

        // 4. Subdomain identification (e.g. {subdomain}.veggitable.test)
        if (! $tenant) {
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0] ?? null;
            if ($subdomain && ! in_array($subdomain, ['www', 'admin', 'localhost', '127', 'veggitable'])) {
                $tenant = Tenant::where('subdomain', $subdomain)->orWhere('domain', $host)->first();
            }
        }

        // 5. Session fallback
        if (! $tenant && Session::has('tenant_id')) {
            $tenant = Tenant::find(Session::get('tenant_id'));
        }

        // Validate cross-tenant access if user is authenticated
        if ($user instanceof User && $user->tenant_id && $tenant) {
            if ($user->tenant_id !== $tenant->id && ! $user->is_super_admin) {
                throw new CrossTenantAccessException('User does not have permission to access this business.');
            }
        }

        if ($tenant) {
            $this->activateTenant($tenant);

            // Also resolve active branch
            $this->resolveBranch($request, $tenant, $user);
        }

        return $tenant;
    }

    /**
     * Activate a tenant in the context and validate status.
     */
    public function activateTenant(Tenant $tenant, bool $validateStatus = true): void
    {
        if ($validateStatus) {
            $this->validateTenantStatus($tenant);
        }

        $this->context->setTenant($tenant);
        Session::put('tenant_id', $tenant->id);
    }

    /**
     * Resolve the active branch for the tenant and user.
     */
    public function resolveBranch(Request $request, Tenant $tenant, ?User $user = null): ?Branch
    {
        $branch = null;

        // 1. Branch ID from request header or parameter
        $branchId = $request->header('X-Branch-ID') ?? $request->input('branch_id');
        if ($branchId) {
            $branch = Branch::where('tenant_id', $tenant->id)->find($branchId);
        }

        // 2. User assigned current_branch_id
        if (! $branch && $user?->current_branch_id) {
            $branch = Branch::where('tenant_id', $tenant->id)->find($user->current_branch_id);
        }

        // 3. Session branch
        if (! $branch && Session::has('branch_id')) {
            $branch = Branch::where('tenant_id', $tenant->id)->find(Session::get('branch_id'));
        }

        // 4. Headquarters or first active branch fallback
        if (! $branch) {
            $branch = Branch::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderByDesc('is_headquarters')
                ->first();
        }

        if ($branch) {
            $this->context->setBranch($branch);
            Session::put('branch_id', $branch->id);
        }

        return $branch;
    }

    /**
     * Validate that tenant is active and not suspended or expired.
     */
    public function validateTenantStatus(Tenant $tenant): void
    {
        if ($tenant->status === TenantStatus::Suspended) {
            throw new TenantSuspendedException;
        }

        if ($tenant->status === TenantStatus::Expired) {
            throw new TenantExpiredException;
        }

        if ($tenant->subscription_ends_at && $tenant->subscription_ends_at->isPast()) {
            throw new TenantExpiredException;
        }
    }

    /**
     * Get the tenant context instance.
     */
    public function getContext(): TenantContext
    {
        return $this->context;
    }
}
