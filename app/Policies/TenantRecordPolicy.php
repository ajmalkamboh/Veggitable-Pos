<?php

namespace App\Policies;

use App\Models\TenantRecord;
use App\Models\User;

class TenantRecordPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TenantRecord $record): bool
    {
        return (int) $user->tenant_id === (int) $record->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TenantRecord $record): bool
    {
        return (int) $user->tenant_id === (int) $record->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TenantRecord $record): bool
    {
        return (int) $user->tenant_id === (int) $record->tenant_id;
    }
}
