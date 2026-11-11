<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvancePaymentAllocation extends Model
{
    protected $table = 'advance_payment_allocations';

    protected $primaryKey = 'allocation_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'advance_payment_id',
        'billing_id',
        'amount',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'applied_at' => 'datetime',
        ];
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(
            Billing::class,
            'billing_id',
            'billing_id'
        );
    }
}
