<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'current_branch_id',
        'is_super_admin',
        'is_tenant_owner',
        'status',
        'phone',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_tenant_owner' => 'boolean',
            'preferences' => 'array',
        ];
    }

    /**
     * Tenant to which this user belongs.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Currently active branch for this user.
     */
    public function currentBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'current_branch_id');
    }

    /**
     * Branches to which this user is assigned.
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * Roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    /**
     * Check if user is a platform super administrator.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Check if user is the business owner.
     */
    public function isTenantOwner(): bool
    {
        return (bool) $this->is_tenant_owner;
    }

    /**
     * Check if user account is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if user account is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verify whether user belongs to a specific tenant.
     */
    public function belongsToTenant(?int $tenantId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $tenantId !== null && (int) $this->tenant_id === (int) $tenantId;
    }

    /**
     * Check if user has a specific role or any role from a list.
     */
    public function hasRole(string|array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $rolesList = is_array($roles) ? $roles : [$roles];

        if ($this->isTenantOwner() && in_array('owner', $rolesList, true)) {
            return true;
        }

        return $this->roles()
            ->where(function ($q) {
                $q->where('role_user.tenant_id', $this->tenant_id)
                    ->orWhereNull('role_user.tenant_id');
            })
            ->whereIn('slug', $rolesList)
            ->exists();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->isSuperAdmin() || $this->isTenantOwner()) {
            return true;
        }

        return $this->roles()
            ->where(function ($q) {
                $q->where('role_user.tenant_id', $this->tenant_id)
                    ->orWhereNull('role_user.tenant_id');
            })
            ->whereHas('permissions', function ($pq) use ($permissionSlug) {
                $pq->where('slug', $permissionSlug);
            })
            ->exists();
    }

    /**
     * Verify if user is authorized to access a given branch.
     */
    public function canAccessBranch(int|Branch $branch): bool
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isTenantOwner()) {
            $targetBranch = $branch instanceof Branch ? $branch : Branch::find($branchId);

            return $targetBranch && (int) $targetBranch->tenant_id === (int) $this->tenant_id;
        }

        return $this->branches()->where('branches.id', $branchId)->exists();
    }

    /**
     * Assign a role to this user within their tenant context.
     */
    public function assignRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)
                ->where(function ($q) {
                    $q->where('tenant_id', $this->tenant_id)
                        ->orWhereNull('tenant_id');
                })
                ->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching([
            $role->id => ['tenant_id' => $this->tenant_id],
        ]);
    }

    /**
     * Remove a role from this user.
     */
    public function removeRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->first();
            if (! $role) {
                return;
            }
        }

        $this->roles()->detach($role->id);
    }
}
