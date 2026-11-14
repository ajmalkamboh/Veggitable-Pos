<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'trial_days',
        'grace_days',
        'max_branches',
        'max_users',
        'max_products',
        'features',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'trial_days' => 'integer',
            'grace_days' => 'integer',
            'max_branches' => 'integer',
            'max_users' => 'integer',
            'max_products' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Tenants on this plan.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'plan_id');
    }

    /**
     * Subscriptions under this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /**
     * Check if this plan includes a given feature key.
     */
    public function hasFeature(string $featureKey): bool
    {
        $features = $this->features ?? [];

        return in_array($featureKey, $features, true) || in_array('*', $features, true);
    }

    /**
     * Check if adding branch is allowed.
     */
    public function allowsBranches(int $currentCount): bool
    {
        if ($this->max_branches === -1) {
            return true; // Unlimited
        }

        return $currentCount < $this->max_branches;
    }

    /**
     * Check if adding user is allowed.
     */
    public function allowsUsers(int $currentCount): bool
    {
        if ($this->max_users === -1) {
            return true;
        }

        return $currentCount < $this->max_users;
    }

    /**
     * Full list of all standard platform feature keys.
     *
     * @return array<string, string>
     */
    public static function availableFeatures(): array
    {
        return [
            'pos' => 'Vegetable POS & Cashier Counter',
            'purchases' => 'Purchases & Supplier Management',
            'inventory' => 'FIFO Inventory & Perishable Batches',
            'wastage' => 'Daily Wastage & Spoilage Tracking',
            'expenses' => 'Daily Expense Tracking',
            'accounting' => 'Double-Entry Accounting & Ledgers',
            'multi_branch' => 'Multiple Branch Outlets',
            'thermal_printing' => '80mm Thermal Receipt Printing',
            'reports_excel' => 'Excel & PDF Export Reporting',
            'api_access' => 'REST API Access',
        ];
    }
}
