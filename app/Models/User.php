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
     * Verify whether user belongs to a specific tenant.
     */
    public function belongsToTenant(?int $tenantId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $tenantId !== null && (int) $this->tenant_id === (int) $tenantId;
    }
}
