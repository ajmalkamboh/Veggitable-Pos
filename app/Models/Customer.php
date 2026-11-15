<?php

namespace App\Models;

use App\Core\Localization\BilingualSearchable;
use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToTenant, BilingualSearchable, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'name_ur',
        'code',
        'type',
        'contact_person',
        'phone',
        'mobile',
        'address',
        'city',
        'tax_id',
        'credit_limit',
        'credit_days',
        'opening_balance',
        'current_balance',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'credit_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(PartyLedgerEntry::class, 'party_id')
            ->where('party_type', 'customer')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');
    }

    /**
     * Generate next sequential customer code.
     */
    public static function generateCode(Tenant $tenant): string
    {
        $count = self::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->withTrashed()
            ->count() + 1;

        return sprintf('CUST-%04d', $count);
    }
}
