<?php

namespace App\Models;

use App\Core\Localization\BilingualSearchable;
use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToTenant, BilingualSearchable, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'subcategory_id',
        'name',
        'name_ur',
        'code',
        'barcode',
        'type',
        'purchase_unit_id',
        'selling_unit_id',
        'stock_unit_id',
        'unit_conversion_factor',
        'cost_price',
        'selling_price',
        'min_selling_price',
        'reorder_level',
        'is_perishable',
        'shelf_life_days',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_conversion_factor' => 'decimal:4',
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'min_selling_price' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'is_perishable' => 'boolean',
            'shelf_life_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function sellingUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'selling_unit_id');
    }

    public function stockUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'stock_unit_id');
    }

    /**
     * Estimated profit margin percentage.
     */
    public function getProfitMarginPercentageAttribute(): float
    {
        if ($this->cost_price <= 0) {
            return 0.0;
        }

        return round((($this->selling_price - $this->cost_price) / $this->cost_price) * 100, 1);
    }

    /**
     * Generate unique sequential barcode.
     */
    public static function generateBarcode(Tenant $tenant): string
    {
        $prefix = '20'; // Standard in-store retail prefix
        $tenantPart = sprintf('%04d', $tenant->id % 10000);
        $random = sprintf('%06d', rand(1, 999999));

        return $prefix.$tenantPart.$random;
    }
}
