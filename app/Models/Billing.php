<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Billing extends Model
{
    use HasFactory;

    protected $table = 'billings';

    protected $primaryKey = 'billing_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'room_id',
        'billing_period',
        'billing_date',
        'due_date',
        'amount',
        'status',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'billing_period' => 'date',
            'billing_date' => 'date',
            'due_date' => 'date',
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class,
            'tenant_id',
            'tenant_id'
        );
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(
            Room::class,
            'room_id',
            'room_id'
        );
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(
            PaymentTransaction::class,
            'billing_id',
            'billing_id'
        );
    }
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            BillingItem::class,
            'billing_id',
            'billing_id'
        );
    }

    public function advanceAllocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            AdvancePaymentAllocation::class,
            'billing_id',
            'billing_id'
        );
    }
}
