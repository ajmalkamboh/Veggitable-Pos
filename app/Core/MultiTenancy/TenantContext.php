<?php

namespace App\Core\MultiTenancy;

use App\Models\Branch;
use App\Models\Tenant;

class TenantContext
{
    protected ?Tenant $tenant = null;

    protected ?Branch $branch = null;

    /**
     * Set the currently active tenant.
     */
    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the currently active tenant.
     */
    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * Check if a tenant is currently resolved.
     */
    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Get the active tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->tenant?->id;
    }

    /**
     * Set the currently active branch.
     */
    public function setBranch(?Branch $branch): void
    {
        $this->branch = $branch;
    }

    /**
     * Get the currently active branch.
     */
    public function getBranch(): ?Branch
    {
        return $this->branch;
    }

    /**
     * Check if a branch is currently set.
     */
    public function hasBranch(): bool
    {
        return $this->branch !== null;
    }

    /**
     * Get the active branch ID.
     */
    public function getBranchId(): ?int
    {
        return $this->branch?->id;
    }

    /**
     * Verify whether a given tenant ID matches the current tenant context.
     */
    public function checkAccess(?int $targetTenantId): bool
    {
        if ($targetTenantId === null) {
            return false;
        }

        return $this->hasTenant() && $this->getTenantId() === (int) $targetTenantId;
    }

    /**
     * Reset context (useful between tests or queue jobs).
     */
    public function reset(): void
    {
        $this->tenant = null;
        $this->branch = null;
    }
}
