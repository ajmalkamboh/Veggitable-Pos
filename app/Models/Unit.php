<?php

namespace App\Models;

use App\Core\Localization\BilingualSearchable;
use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use BelongsToTenant, BilingualSearchable, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'name_ur',
        'code',
        'is_fractional',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_fractional' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'from_unit_id');
    }

    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'to_unit_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'stock_unit_id');
    }
}
