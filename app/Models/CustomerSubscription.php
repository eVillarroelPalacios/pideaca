<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class CustomerSubscription extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_PAUSED = 'PAUSED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_PAYMENT_FAILED = 'PAYMENT_FAILED';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_CANCELLED,
        self::STATUS_PAYMENT_FAILED,
    ];

    /**
     * Estados que el cliente puede fijar desde la API: pausar, reanudar y cancelar.
     * CANCELLED es terminal.
     */
    public const CLIENT_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_CANCELLED,
        self::STATUS_PAYMENT_FAILED,
    ];

    public const CLIENT_TRANSITIONS = [
        self::STATUS_ACTIVE => [self::STATUS_PAUSED, self::STATUS_CANCELLED],
        self::STATUS_PAUSED => [self::STATUS_ACTIVE, self::STATUS_CANCELLED],
        // El cliente regulariza la deuda y vuelve, o se da de baja.
        self::STATUS_PAYMENT_FAILED => [self::STATUS_ACTIVE, self::STATUS_CANCELLED],
    ];

    protected $fillable = [
        'user_id',
        'provider_id',
        'subscription_plan_id',
        'status',
        'next_delivery_date',
        'preferred_delivery_day',
        'preferred_delivery_time',
        'delivery_address_id',
        'payment_method',
    ];

    protected $casts = [
        'next_delivery_date' => 'datetime',
        'preferred_delivery_day' => 'integer',
        'preferred_delivery_time' => 'datetime:H:i',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerSubscriptionItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Deuda viva: hay al menos un ciclo sin cobrar. El cliente no puede volver
     * a ACTIVE hasta que el comercio confirme el pago.
     */
    public function hasUnpaidCycles(): bool
    {
        return $this->payments()
            ->whereIn('status', [SubscriptionPayment::STATUS_PENDING, SubscriptionPayment::STATUS_FAILED])
            ->exists();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Suscripciones cuya proxima entrega ya vencio: hoy o con atraso.
     */
    public function scopeDue(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('next_delivery_date', '<=', $date->toDateString());
    }

    /**
     * Proxima fecha de entrega respetando la frecuencia del plan y el dia
     * preferido del cliente, que siempre gana: si pidio los sabados, cae
     * siempre en sabado. La fecha nunca retrocede, se avanza hasta el
     * proximo dia preferido (1 a 7 dias).
     */
    public function nextDeliveryDate(Carbon $reference): Carbon
    {
        $next = $this->plan->nextDeliveryDateAfter($this->next_delivery_date, $reference);

        if ($this->preferred_delivery_day === null) {
            return $next;
        }

        $dias = ($this->preferred_delivery_day - $next->dayOfWeekIso + 7) % 7;

        return $dias === 0 ? $next : $next->copy()->addDays($dias);
    }

    /**
     * Productos que van en el pedido: los sabores que eligio el cliente y, si
     * no eligio ninguno, los que trae el plan.
     *
     * @return Collection<int, array{product: Product, quantity: int}>
     */
    public function itemsForOrder()
    {
        if ($this->items->isNotEmpty()) {
            return $this->items->map(fn (CustomerSubscriptionItem $item) => [
                'product' => $item->product,
                'quantity' => (int) $item->quantity,
            ]);
        }

        return $this->plan->items->map(fn (SubscriptionPlanItem $item) => [
            'product' => $item->product,
            'quantity' => (int) $item->quantity,
        ]);
    }
}
