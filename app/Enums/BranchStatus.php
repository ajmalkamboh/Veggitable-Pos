<?php

namespace App\Enums;

enum BranchStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    /**
     * Get Bootstrap badge CSS class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-success',
            self::Inactive => 'bg-secondary',
        };
    }

    /**
     * Check if branch is active.
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
