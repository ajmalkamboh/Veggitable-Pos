<?php

namespace App\Core\MultiTenancy;

use App\Core\MultiTenancy\Exceptions\CrossTenantAccessException;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait for an Eloquent model.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            $context = app(TenantContext::class);

            if ($context->hasTenant()) {
                $activeTenantId = $context->getTenantId();

                // If tenant_id is already set, ensure it strictly matches the current context
                if (! empty($model->tenant_id) && (int) $model->tenant_id !== $activeTenantId) {
                    throw new CrossTenantAccessException('Cannot assign record to a different tenant ID.');
                }

                // Automatically enforce the active tenant ID
                $model->tenant_id = $activeTenantId;
            }
        });
    }

    /**
     * Relationship to the owning Tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if this model belongs to the currently active tenant context.
     */
    public function belongsToCurrentTenant(): bool
    {
        $context = app(TenantContext::class);

        return $context->hasTenant() && (int) $this->tenant_id === $context->getTenantId();
    }
}
