<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoicePayment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'sales_invoice_id',
        'tenant_id',
        'method',
        'amount',
        'financial_account_id',
        'reference_no',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function getPaymentMethodAttribute(): string
    {
        return $this->method ?? 'cash';
    }

    public function getTransactionReferenceAttribute(): ?string
    {
        return $this->reference_no;
    }
}
