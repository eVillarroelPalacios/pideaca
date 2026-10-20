<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    use HasFactory;

    public const TYPE_EMERGENCY = 'EMERGENCY';

    public const TYPE_SCHEDULED = 'SCHEDULED';

    public const TYPE_QUOTE = 'QUOTE';

    public const ORDER_TYPES = [
        self::TYPE_EMERGENCY,
        self::TYPE_SCHEDULED,
        self::TYPE_QUOTE,
    ];

    public const STATUS_REQUESTED = 'REQUESTED';

    public const STATUS_QUOTED = 'QUOTED';

    public const STATUS_ACCEPTED = 'ACCEPTED';

    public const STATUS_PROVIDER_DISPATCHED = 'PROVIDER_DISPATCHED';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_QUOTED,
        self::STATUS_ACCEPTED,
        self::STATUS_PROVIDER_DISPATCHED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Flujo operativo: de cada estado solo se aceptan los siguientes.
     * Las urgencias despachan directo (REQUESTED -> PROVIDER_DISPATCHED) y el
     * costo in-sitio pasa por QUOTED hasta que el cliente lo aprueba.
     * COMPLETED y CANCELLED son estados finales.
     */
    public const TRANSITIONS = [
        self::STATUS_REQUESTED => [self::STATUS_QUOTED, self::STATUS_ACCEPTED, self::STATUS_PROVIDER_DISPATCHED, self::STATUS_CANCELLED],
        self::STATUS_QUOTED => [self::STATUS_ACCEPTED, self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
        self::STATUS_ACCEPTED => [self::STATUS_QUOTED, self::STATUS_PROVIDER_DISPATCHED, self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED, self::STATUS_COMPLETED],
        self::STATUS_PROVIDER_DISPATCHED => [self::STATUS_QUOTED, self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED, self::STATUS_COMPLETED],
        self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [],
    ];

    /**
     * Estados simplificados: solo se muestran/filtran estos cinco. El backend
     * sigue guardando los valores legacy en la BD (TRANSITIONS de arriba) y
     * STATUS_DISPLAY_MAP los agrupa para la vista.
     */
    public const STATUS_PENDING = 'PENDIENTE';

    public const STATUS_PRESUPUESTADO = 'PRESUPUESTADO';

    public const STATUS_EN_PROCESO = 'EN_PROCESO';

    public const STATUS_COMPLETADO = 'COMPLETADO';

    public const STATUS_CANCELADO = 'CANCELADO';

    public const DISPLAY_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PRESUPUESTADO,
        self::STATUS_EN_PROCESO,
        self::STATUS_COMPLETADO,
        self::STATUS_CANCELADO,
    ];

    public const STATUS_DISPLAY_MAP = [
        self::STATUS_REQUESTED => self::STATUS_PENDING,
        self::STATUS_QUOTED => self::STATUS_PRESUPUESTADO,
        self::STATUS_ACCEPTED => self::STATUS_PRESUPUESTADO,
        self::STATUS_PROVIDER_DISPATCHED => self::STATUS_EN_PROCESO,
        self::STATUS_IN_PROGRESS => self::STATUS_EN_PROCESO,
        self::STATUS_COMPLETED => self::STATUS_COMPLETADO,
        self::STATUS_CANCELLED => self::STATUS_CANCELADO,
        self::STATUS_PENDING => self::STATUS_PENDING,
        self::STATUS_PRESUPUESTADO => self::STATUS_PRESUPUESTADO,
        self::STATUS_EN_PROCESO => self::STATUS_EN_PROCESO,
        self::STATUS_COMPLETADO => self::STATUS_COMPLETADO,
        self::STATUS_CANCELADO => self::STATUS_CANCELADO,
    ];

    public const DISPLAY_STATUS_LABELS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_PRESUPUESTADO => 'Presupuestado',
        self::STATUS_EN_PROCESO => 'En proceso',
        self::STATUS_COMPLETADO => 'Completado',
        self::STATUS_CANCELADO => 'Cancelado',
    ];

    public const ESCROW_HOLDING = 'HOLDING';

    public const ESCROW_RELEASED = 'RELEASED';

    public const ESCROW_REFUNDED = 'REFUNDED';

    public const ESCROW_STATUSES = [
        self::ESCROW_HOLDING,
        self::ESCROW_RELEASED,
        self::ESCROW_REFUNDED,
    ];

    protected $fillable = [
        'customer_id',
        'sub_group_id',
        'merchant_id',
        'order_type',
        'status',
        'latitude',
        'longitude',
        'scheduled_at',
        'fulfillment',
        'payment_method',
        'payment_mode',
        'deposit_amount',
        'visit_fee',
        'additional_cost',
        'estimate_details',
        'total_amount',
        'escrow_status',
        'completed_at',
        'commission_amount',
        'description',
        'address',
        'quote_context',
        'address_street',
        'address_unit',
        'address_neighborhood',
        'reminder_channel',
        'photo_path',
        'audio_path',
        'completion_photo_path',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'sub_group_id' => 'integer',
        'merchant_id' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'visit_fee' => 'decimal:2',
        'additional_cost' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'quote_context' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function subGroup(): BelongsTo
    {
        return $this->belongsTo(SubGroup::class, 'sub_group_id');
    }

    /**
     * Comercio asignado: nulo mientras la orden sigue en el pool de su
     * subgrupo, y se completa cuando un prestador la acepta.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'merchant_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(ServiceQuote::class, 'service_order_id')->latest();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ServiceRating::class, 'service_order_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('order_type', $type);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Ordenes que ve un prestador en "Ordenes de Servicio": las suyas (ya
     * aceptadas) y las del pool de sus subgrupos todavia sin comercio.
     */
    public function scopeForProvider($query, Provider $provider)
    {
        return $query->where(function ($q) use ($provider) {
            $q->where('merchant_id', $provider->id)
                ->orWhere(function ($pool) use ($provider) {
                    $pool->whereNull('merchant_id')
                        ->whereIn('sub_group_id', $provider->subgroups()->select('sub_groups.id'));
                });
        });
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [
            self::STATUS_REQUESTED,
            self::STATUS_QUOTED,
            self::STATUS_ACCEPTED,
            self::STATUS_PROVIDER_DISPATCHED,
        ], true);
    }

    /**
     * Estados a los que esta orden puede pasar desde su estado actual.
     */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isEscrowHolding(): bool
    {
        return $this->escrow_status === self::ESCROW_HOLDING;
    }

    public function statusLabel(): string
    {
        return $this->displayStatusLabel();
    }

    public function getDisplayStatusAttribute(): string
    {
        return $this->displayStatus();
    }

    /**
     * Estado simplificado para vista y filtros (PENDIENTE/PRESUPUESTADO/...).
     */
    public function displayStatus(): string
    {
        return self::toDisplayStatus($this->status);
    }

    public function displayStatusLabel(): string
    {
        return self::DISPLAY_STATUS_LABELS[$this->displayStatus()] ?? $this->status;
    }

    public static function toDisplayStatus(string $status): string
    {
        return self::STATUS_DISPLAY_MAP[$status] ?? $status;
    }

    public static function displayStatuses(): array
    {
        return self::DISPLAY_STATUSES;
    }

    /**
     * Traduce un estado simplificado del filtro a los valores legacy que lo
     * componen en la BD; si llega un valor legacy se devuelve tal cual.
     */
    public static function legacyStatusesFor(string $status): array
    {
        return match ($status) {
            self::STATUS_PENDING => [self::STATUS_REQUESTED],
            self::STATUS_PRESUPUESTADO => [self::STATUS_QUOTED, self::STATUS_ACCEPTED],
            self::STATUS_EN_PROCESO => [self::STATUS_PROVIDER_DISPATCHED, self::STATUS_IN_PROGRESS],
            self::STATUS_COMPLETADO => [self::STATUS_COMPLETED],
            self::STATUS_CANCELADO => [self::STATUS_CANCELLED],
            default => [$status],
        };
    }
}
