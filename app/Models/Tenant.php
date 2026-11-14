<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'plan_id',
        'current_subscription_id',
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
     * Current subscription plan assigned to this tenant.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Currently active subscription record.
     */
    public function currentSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'current_subscription_id');
    }

    /**
     * All subscriptions for this tenant.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * All invoices for this tenant.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
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
     * Check if tenant has entitlement to a specific feature.
     */
    public function hasFeature(string $featureKey): bool
    {
        // If plan is assigned, check plan features
        if ($this->plan) {
            return $this->plan->hasFeature($featureKey);
        }

        // Default during trial allows standard features
        return in_array($featureKey, ['pos', 'purchases', 'inventory', 'wastage', 'expenses', 'thermal_printing']);
    }

    /**
     * Check if tenant can create an additional branch based on plan limits.
     */
    public function canAddBranch(): bool
    {
        if (! $this->plan) {
            return true; // Unrestricted trial
        }

        return $this->plan->allowsBranches($this->branches()->count());
    }

    /**
     * Check if tenant can add an additional user based on plan limits.
     */
    public function canAddUser(): bool
    {
        if (! $this->plan) {
            return true;
        }

        return $this->plan->allowsUsers($this->users()->count());
    }

    /**
     * Check if tenant has an active paid subscription.
     */
    public function isSubscribed(): bool
    {
        return $this->currentSubscription?->isActive() ?? false;
    }

    /**
     * Check if tenant is on trial.
     */
    public function isTrialing(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
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
