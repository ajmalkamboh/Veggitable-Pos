<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'sales_return_id',
        'tenant_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function return(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
