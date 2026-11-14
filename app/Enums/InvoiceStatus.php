<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';
    case Failed = 'failed';
    case Refunded = 'refunded';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
            self::Pending => 'Pending',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Bootstrap badge class.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Paid => 'bg-success',
            self::Pending => 'bg-warning text-dark',
            self::Failed => 'bg-danger',
            self::Refunded => 'bg-secondary',
        };
    }
}

