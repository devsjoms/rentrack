<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingItem extends Model
{
    protected $table = 'billing_items';

    protected $primaryKey = 'billing_item_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'billing_id',
        'item_type',
        'description',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
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
