<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProduceRegrading extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'source_quantity' => 'decimal:3',
        'target_quantity' => 'decimal:3',
        'wastage_quantity' => 'decimal:3',
        'source_unit_cost' => 'decimal:2',
        'target_unit_cost' => 'decimal:2',
        'loss_cost' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'source_product_id');
    }

    public function targetProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'target_product_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
