<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryStock extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'product_id',
        'quantity',
        'cost_price',
        'reorder_level',
        'last_restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'cost_price' => 'decimal:2',
            'reorder_level' => 'decimal:3',
            'last_restocked_at' => 'datetime',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'product_id', 'product_id')
            ->where('branch_id', $this->branch_id)
            ->where('status', 'active');
    }

    /**
     * Check if stock level is at or below reorder threshold.
     */
    public function isLowStock(): bool
    {
        $threshold = $this->reorder_level > 0 ? $this->reorder_level : ($this->product->reorder_level ?? 0);

        return (float) $this->quantity <= (float) $threshold;
    }
}
