<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'status',
        'email',
        'phone',
        'address',
        'city',
        'currency',
        'timezone',
        'default_locale',
        'settings',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    /**
     * Branches belonging to this tenant.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Headquarters branch for this tenant.
     */
    public function headquarters(): HasOne
    {
        return $this->hasOne(Branch::class)->where('is_headquarters', true);
    }

    /**
     * Users belonging to this tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Owner user for this tenant.
     */
    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('is_tenant_owner', true);
    }

    /**
     * Tenant records.
     */
    public function records(): HasMany
    {
        return $this->hasMany(TenantRecord::class);
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active;
    }

    /**
     * Get a setting value with fallback default.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];

        return data_get($settings, $key, $default);
    }
}
