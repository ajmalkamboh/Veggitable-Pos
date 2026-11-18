<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'pos_session_id',
        'customer_id',
        'invoice_no',
        'date',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax_amount',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'delivery_charges',
        'rounding_amount',
        'total_amount',
        'paid_amount',
        'change_amount',
        'cost_amount',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_rate' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'delivery_charges' => 'decimal:2',
            'rounding_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'cost_amount' => 'decimal:2',
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

    public function posSession(): BelongsTo
    {
        return $this->belongsTo(PosSession::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalesInvoicePayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDueAmountAttribute(): float
    {
        return (float) max(0, $this->total_amount - $this->paid_amount);
    }

    public function getProfitAmountAttribute(): float
    {
        return (float) ($this->total_amount - $this->cost_amount);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'bg-success-subtle text-success',
            'partial' => 'bg-warning-subtle text-warning',
            'due' => 'bg-danger-subtle text-danger',
            'draft' => 'bg-secondary-subtle text-secondary',
            default => 'bg-info-subtle text-info',
        };
    }

    public static function generateInvoiceNumber(Tenant $tenant): string
    {
        $year = date('Y');
        $count = self::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereYear('created_at', $year)
            ->withTrashed()
            ->count() + 1;

        return sprintf('INV-%s-%04d', $year, $count);
    }
}
