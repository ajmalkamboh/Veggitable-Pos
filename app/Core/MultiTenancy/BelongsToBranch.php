<?php

namespace App\Core\MultiTenancy;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBranch
{
    /**
     * Boot the BelongsToBranch trait.
     */
    public static function bootBelongsToBranch(): void
    {
        static::creating(function ($model): void {
            $context = app(TenantContext::class);

            if ($context->hasBranch() && empty($model->branch_id)) {
                $model->branch_id = $context->getBranchId();
            }
        });
    }

    /**
     * Relationship to the branch.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope query to a specific branch.
     */
    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where($this->getTable().'.branch_id', $branchId);
    }
}
