<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookEvent extends Model
{
    protected $table = 'payment_webhook_events';

    protected $primaryKey = 'webhook_event_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'provider_event_id',
        'payment_id',
        'event_type',
        'checkout_session_id',
        'payload_sha256',
        'is_live',
        'processing_status',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_live' => 'boolean',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(
            PaymentTransaction::class,
            'payment_id',
            'payment_id'
        );
    }
}
