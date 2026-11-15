<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConversion extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'from_unit_id',
        'to_unit_id',
        'multiplier',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'multiplier' => 'decimal:4',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }

    /**
     * Convert an input quantity using the multiplier.
     */
    public function convert(float $quantity): float
    {
        return $quantity * (float) $this->multiplier;
    }
}
