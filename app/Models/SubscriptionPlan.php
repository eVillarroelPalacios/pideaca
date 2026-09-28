<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    public const FREQUENCY_DAILY = 'DAILY';

    public const FREQUENCY_WEEKLY = 'WEEKLY';

    public const FREQUENCY_BIWEEKLY = 'BIWEEKLY';

    public const FREQUENCY_MONTHLY = 'MONTHLY';

    public const FREQUENCIES = [
        self::FREQUENCY_DAILY,
        self::FREQUENCY_WEEKLY,
        self::FREQUENCY_BIWEEKLY,
        self::FREQUENCY_MONTHLY,
    ];

    protected $fillable = [
        'provider_id',
        'title',
        'description',
        'frequency',
        'price',
        'discount_percentage',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionPlanItem::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CustomerSubscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Descuento en dinero que el plan aplica sobre su propio precio.
     */
    public function discountAmount(): float
    {
        return round((float) $this->price * (float) $this->discount_percentage / 100, 2);
    }

    /**
     * Precio que se cobra por ciclo, ya con el descuento del plan aplicado.
     */
    public function chargeAmount(): float
    {
        return round((float) $this->price - $this->discountAmount(), 2);
    }

    /**
     * Cuantos ciclos del plan caen en un mes, para proyectar la caja fija.
     * Es una estimacion de calendario, no un promedio historico.
     */
    public function cyclesPerMonth(): float
    {
        return match ($this->frequency) {
            self::FREQUENCY_DAILY => 30.0,
            self::FREQUENCY_WEEKLY => 30 / 7,
            self::FREQUENCY_BIWEEKLY => 15.0,
            self::FREQUENCY_MONTHLY => 1.0,
            default => 1.0,
        };
    }

    /**
     * Que ocupa un suscriptor: los cupos cuentan las suscripciones activas y
     * pausadas, porque un paused sigue reservando el lugar en la cocina.
     */
    public function activeSubscribers(): int
    {
        return $this->subscriptions()
            ->whereIn('status', [
                CustomerSubscription::STATUS_ACTIVE,
                CustomerSubscription::STATUS_PAUSED,
            ])
            ->count();
    }

    public function hasCapacity(): bool
    {
        if ($this->capacity === null) {
            return true;
        }

        return $this->activeSubscribers() < $this->capacity;
    }

    public function availableSlots(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->activeSubscribers());
    }

    /**
     * Suma de una linea del plan sobre el precio actual del producto.
     */
    public function lineTotal(SubscriptionPlanItem $item): float
    {
        return round((float) $item->product->price * (int) $item->quantity, 2);
    }

    /**
     * Proxima fecha de entrega a partir de la actual segun la frecuencia.
     *
     * Si la fecha arrastra retraso (por ejemplo, un ciclo que no pudo
     * generarse por falta de stock) se avanza hasta pasar la fecha de
     * referencia en lugar de acumular ciclos pendientes.
     */
    public function nextDeliveryDateAfter(Carbon $current, ?Carbon $notBefore = null): Carbon
    {
        $next = $current->copy();

        do {
            $next = match ($this->frequency) {
                self::FREQUENCY_DAILY => $next->copy()->addDay(),
                self::FREQUENCY_WEEKLY => $next->copy()->addWeek(),
                self::FREQUENCY_BIWEEKLY => $next->copy()->addWeeks(2),
                self::FREQUENCY_MONTHLY => $next->copy()->addMonthNoOverflow(),
                default => $next->copy()->addDay(),
            };
        } while ($notBefore !== null && $next->lessThanOrEqualTo($notBefore));

        return $next;
    }
}
