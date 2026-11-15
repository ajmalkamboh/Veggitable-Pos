<?php

namespace App\Models;

use App\Core\MultiTenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WastageRecord extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'total_quantity' => 'decimal:3',
        'total_loss_cost' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WastageRecordItem::class, 'wastage_record_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getReasonLabelAttribute(): string
    {
        return match ($this->reason) {
            'spoilage' => 'Rotten / Spoilage (خراب / گلا سڑا)',
            'drying_shrinkage' => 'Moisture / Drying Shrinkage (سوکھنا / وزن کی کمی)',
            'handling_damage' => 'Handling / In-transit Damage (ٹوٹ پھوٹ / نقصان)',
            'pest_damage' => 'Rodent / Pest Damage (کیڑا / چوہا)',
            'trimming_cleaning' => 'Trimming / Cleaning Waste (صفائی / چھانٹی)',
            'expired' => 'Expired Shelf Life (مدت ختم)',
            default => ucfirst(str_replace('_', ' ', $this->reason)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'bg-success',
            'pending_approval' => 'bg-warning text-dark',
            'rejected' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
