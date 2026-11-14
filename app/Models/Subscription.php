<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'billing_cycle',
        'status',
        'trial_starts_at',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancels_at',
        'grace_ends_at',
        'price_paid',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'billing_cycle' => BillingCycle::class,
            'trial_starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancels_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'price_paid' => 'decimal:2',
        ];
    }

    /**
     * Tenant associated with this subscription.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Subscription plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Invoices generated for this subscription.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    /**
     * Check if subscription is in trial.
     */
    public function isTrialing(): bool
    {
        return $this->status === SubscriptionStatus::Trialing &&
            $this->trial_ends_at &&
            $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription is actively paid.
     */
    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active &&
            (! $this->ends_at || $this->ends_at->isFuture());
    }

    /**
     * Check if subscription is in grace period.
     */
    public function isGracePeriod(): bool
    {
        return $this->status === SubscriptionStatus::GracePeriod &&
            $this->grace_ends_at &&
            $this->grace_ends_at->isFuture();
    }

    /**
     * Check if subscription has expired.
     */
    public function isExpired(): bool
    {
        return $this->status === SubscriptionStatus::Expired ||
            ($this->ends_at && $this->ends_at->isPast() && (! $this->grace_ends_at || $this->grace_ends_at->isPast()));
    }

    /**
     * Calculate days remaining.
     */
    public function daysRemaining(): int
    {
        $targetDate = $this->isTrialing() ? $this->trial_ends_at : $this->ends_at;

        if (! $targetDate) {
            return 999;
        }

        return max(0, (int) now()->diffInDays($targetDate, false));
    }
}

