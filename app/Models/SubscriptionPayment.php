<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cobro de un ciclo de suscripcion. Es el ledger que despues sostiene el
 * reporte de ingresos recurrentes del comercio.
 */
class SubscriptionPayment extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_PAID = 'PAID';

    public const STATUS_FAILED = 'FAILED';

    public const STATUS_REFUNDED = 'REFUNDED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_FAILED,
        self::STATUS_REFUNDED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'customer_subscription_id',
        'order_id',
        'provider_id',
        'user_id',
        'gateway',
        'gateway_payment_id',
        'status',
        'attempt',
        'subtotal',
        'discount',
        'delivery_fee',
        'total',
        'currency',
        'period_start',
        'period_end',
        'paid_at',
        'failure_reason',
    ];

    protected $casts = [
        'attempt' => 'integer',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CustomerSubscription::class, 'customer_subscription_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
