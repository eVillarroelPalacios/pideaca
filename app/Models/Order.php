<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_IN_PREPARATION = 'in_preparation';

    public const STATUS_ON_THE_WAY = 'on_the_way';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_IN_PREPARATION,
        self::STATUS_ON_THE_WAY,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Flujo de seguimiento: de cada estado solo se aceptan los siguientes.
     * El comercio recorre la cadena completa; el cliente unicamente cancela.
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_IN_PREPARATION, self::STATUS_CANCELLED],
        self::STATUS_IN_PREPARATION => [self::STATUS_ON_THE_WAY, self::STATUS_CANCELLED],
        self::STATUS_ON_THE_WAY => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED => [],
        self::STATUS_CANCELLED => [],
    ];

    protected $fillable = [
        'provider_id',
        'user_id',
        'address_id',
        'order_number',
        'status',
        'subtotal',
        'delivery_fee',
        'discount',
        'total',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_IN_PREPARATION,
        ], true);
    }

    /**
     * Estados a los que esta orden puede pasar desde su estado actual.
     *
     * @param  bool  $asClient  el cliente solo puede cancelar mientras el pedido
     *                          todavia no este en preparacion.
     * @return array<int, string>
     */
    public function allowedTransitions(bool $asClient = false): array
    {
        $transitions = self::TRANSITIONS[$this->status] ?? [];

        if (! $asClient) {
            return $transitions;
        }

        return array_values(array_filter(
            $transitions,
            fn (string $status) => $status === self::STATUS_CANCELLED && $this->isCancellable()
        ));
    }
}
