<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'user_id',
        'session_no',
        'opened_at',
        'opening_cash',
        'closed_at',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public static function generateSessionNumber(Tenant $tenant): string
    {
        $year = date('Y');
        $count = self::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('REG-%s-%04d', $year, $count);
    }
}
