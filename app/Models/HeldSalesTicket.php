<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeldSalesTicket extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'user_id',
        'ticket_number',
        'customer_id',
        'cart_data',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'cart_data' => 'array',
            'total_amount' => 'decimal:2',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
