<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'purchase_invoice_id',
        'supplier_id',
        'return_no',
        'date',
        'reason',
        'total_amount',
        'refund_status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->invoice();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate unique sequential purchase return number.
     */
    public static function generateReturnNumber(Tenant $tenant): string
    {
        $year = date('Y');
        $count = self::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('PRTN-%s-%04d', $year, $count);
    }
}
