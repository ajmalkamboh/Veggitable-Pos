<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
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
        'onboarding_completed',
        'onboarding_step',
        'logo_path',
        'financial_year_start',
        'business_opening_date',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'settings' => 'array',
            'onboarding_completed' => 'boolean',
            'onboarding_step' => 'integer',
            'business_opening_date' => 'date',
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
     * All subscription contracts for this tenant.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Invoices generated for this tenant.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    /**
     * Branches/outlets belonging to this tenant.
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
     * The primary owner account of this business.
     */
    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('is_tenant_owner', true);
    }

    /**
     * Tenant isolated records.
     */
    public function records(): HasMany
    {
        return $this->hasMany(TenantRecord::class);
    }

    /**
     * Financial accounts (Cash & Bank).
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(FinancialAccount::class);
    }

    /**
     * Master measurement units.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Master categories.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Master products catalog.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Commission agents, farmers and suppliers.
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Retail and commercial customer accounts.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Ledger entries for suppliers and customers.
     */
    public function partyLedgerEntries(): HasMany
    {
        return $this->hasMany(PartyLedgerEntry::class);
    }

    /**
     * Unit conversion multipliers.
     */
    public function unitConversions(): HasMany
    {
        return $this->hasMany(UnitConversion::class);
    }

    /**
     * Check if tenant is fully onboarded.
     */
    public function isOnboarded(): bool
    {
        return (bool) $this->onboarding_completed;
    }

    /**
     * Calculate onboarding checklist completion percentage (0 - 100%).
     */
    public function onboardingProgressPercentage(): int
    {
        if ($this->onboarding_completed) {
            return 100;
        }

        $points = 0;
        if (! empty($this->address) && ! empty($this->phone)) {
            $points += 25;
        }
        if ($this->branches()->exists()) {
            $points += 25;
        }
        if ($this->accounts()->exists()) {
            $points += 25;
        }
        if ($this->units()->exists() && $this->categories()->exists()) {
            $points += 25;
        }

        return $points;
    }

    /**
     * Check if tenant is currently entitled to a specific feature.
     */
    public function hasFeature(string $featureKey): bool
    {
        if (! $this->plan) {
            return false;
        }

        return $this->plan->hasFeature($featureKey);
    }

    /**
     * Verify if tenant can create another branch under their plan limit.
     */
    public function canAddBranch(): bool
    {
        if (! $this->plan) {
            return false;
        }

        if ($this->plan->max_branches === -1) {
            return true;
        }

        return $this->branches()->count() < $this->plan->max_branches;
    }

    /**
     * Verify if tenant can create another user under their plan limit.
     */
    public function canAddUser(): bool
    {
        if (! $this->plan) {
            return false;
        }

        if ($this->plan->max_users === -1) {
            return true;
        }

        return $this->users()->count() < $this->plan->max_users;
    }

    /**
     * Check if tenant subscription or trial has expired.
     */
    public function isExpired(): bool
    {
        if ($this->subscription_ends_at && $this->subscription_ends_at->isPast()) {
            return true;
        }

        if (! $this->subscription_ends_at && $this->trial_ends_at && $this->trial_ends_at->isPast()) {
            return true;
        }

        return false;
    }
}
