<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'chart_of_accounts';

    protected $guarded = ['id'];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    /**
     * Assets and Expenses increase on Debit.
     */
    public function isDebitNormal(): bool
    {
        return in_array($this->type, ['asset', 'expense']);
    }

    /**
     * Liabilities, Equity, and Revenue increase on Credit.
     */
    public function isCreditNormal(): bool
    {
        return in_array($this->type, ['liability', 'equity', 'revenue']);
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            'asset' => 'bg-info text-dark',
            'liability' => 'bg-warning text-dark',
            'equity' => 'bg-primary',
            'revenue' => 'bg-success',
            'expense' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
