<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case GracePeriod = 'grace_period';
    case Expired = 'expired';
    case Canceled = 'canceled';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'Trial Period',
            self::Active => 'Active Subscription',
            self::GracePeriod => 'Grace Period',
            self::Expired => 'Expired',
            self::Canceled => 'Canceled',
        };
    }

    /**
     * Bootstrap badge class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Trialing => 'bg-info text-dark',
            self::Active => 'bg-success',
            self::GracePeriod => 'bg-warning text-dark',
            self::Expired => 'bg-danger',
            self::Canceled => 'bg-secondary',
        };
    }

    /**
     * Check if subscription status permits operational access.
     */
    public function isValid(): bool
    {
        return in_array($this, [self::Trialing, self::Active, self::GracePeriod]);
    }
}
