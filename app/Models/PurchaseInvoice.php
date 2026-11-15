<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'supplier_id',
        'invoice_no',
        'supplier_invoice_no',
        'date',
        'due_date',
        'status',
        'payment_status',
        'subtotal',
        'commission_rate',
        'commission_amount',
        'mandi_fee',
        'freight_charges',
        'other_charges',
        'discount',
        'total_amount',
        'paid_amount',
        'financial_account_id',
        'notes',
        'attachment_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'mandi_fee' => 'decimal:2',
            'freight_charges' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'discount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Outstanding due balance on this invoice.
     */
    public function getDueBalanceAttribute(): float
    {
        return (float) ($this->total_amount - $this->paid_amount);
    }

    /**
     * Outstanding due amount accessor.
     */
    public function getDueAmountAttribute(): float
    {
        return $this->due_balance;
    }

    /**
     * Generate unique sequential purchase invoice number.
     */
    public static function generateInvoiceNumber(Tenant $tenant): string
    {
        $year = date('Y');
        $count = self::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereYear('created_at', $year)
            ->withTrashed()
            ->count() + 1;

        return sprintf('PUR-%s-%04d', $year, $count);
    }
}
