<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
        };
    }

    /**
     * Get Bootstrap badge CSS class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-success',
            self::Suspended => 'bg-danger',
            self::Expired => 'bg-warning text-dark',
        };
    }

    /**
     * Check if tenant status allows operational activities.
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
