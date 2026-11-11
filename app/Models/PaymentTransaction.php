<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $primaryKey = 'payment_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'billing_id',
        'provider',
        'reference_number',
        'checkout_session_id',
        'provider_payment_id',
        'checkout_url',
        'amount_centavos',
        'currency',
        'status',
        'is_live',
        'paid_at',
        'payment_method',
        'recorded_by_user_id',
        'external_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_centavos' => 'integer',
            'is_live' => 'boolean',
            'paid_at' => 'datetime',
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

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(
            PaymentWebhookEvent::class,
            'payment_id',
            'payment_id'
        );
    }
}
