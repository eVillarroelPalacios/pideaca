<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaignRule extends Model
{
    public const TYPE_INACTIVE_CUSTOMER = 'INACTIVE_CUSTOMER';

    public const TYPE_RECURRING_DAY_REMINDER = 'RECURRING_DAY_REMINDER';

    public const TYPE_WELCOME_BACK = 'WELCOME_BACK';

    public const TYPES = [
        self::TYPE_INACTIVE_CUSTOMER,
        self::TYPE_RECURRING_DAY_REMINDER,
        self::TYPE_WELCOME_BACK,
    ];

    /**
     * Tokens que el mensaje no puede perder: sin ellos el circuito de la
     * campana pierde personalizacion y el cliente no recibe el cupon.
     */
    public const REQUIRED_TOKENS = [
        '{nombre}',
        '{comercio}',
        '{cupon}',
    ];

    /**
     * Texto inicial que el comercio puede personalizar; los tokens son los que
     * el motor reemplaza al renderizar.
     */
    public const DEFAULT_TEMPLATES = [
        self::TYPE_INACTIVE_CUSTOMER => 'Hola {nombre}! Hace {dias} dias no pides en {comercio}. Usa el cupon {cupon} para un 15% OFF',
        self::TYPE_RECURRING_DAY_REMINDER => 'Hola {nombre}! Hoy es el dia de tu entrega semanal en {comercio}. Tu cupon: {cupon}',
        self::TYPE_WELCOME_BACK => 'Hola {nombre}! Te extranamos en {comercio}. Vuelve con el cupon {cupon} y recupera tu cliente habitual',
    ];

    protected $fillable = [
        'provider_id',
        'rule_type',
        'days_inactive',
        'day_of_week',
        'message_template',
        'discount_code',
        'is_enabled',
    ];

    protected $casts = [
        'days_inactive' => 'integer',
        'day_of_week' => 'integer',
        'is_enabled' => 'boolean',
    ];

    /**
     * Tokens que no puede perder un mensaje de este tipo: los de la plantilla
     * por defecto ({dias} en INACTIVE_CUSTOMER) mas los tres obligatorios.
     *
     * @return array<int, string>
     */
    public static function requiredTokensFor(string $type): array
    {
        $tokens = [];

        $fuente = self::DEFAULT_TEMPLATES[$type] ?? '';

        foreach (preg_match_all('/\{[a-z_]+\}/', $fuente, $coincidencias) ? $coincidencias[0] : [] as $token) {
            $tokens[$token] = true;
        }

        foreach (self::REQUIRED_TOKENS as $token) {
            $tokens[$token] = true;
        }

        return array_keys($tokens);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MarketingLog::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Reemplaza los tokens de la plantilla con los datos reales del envio.
     *
     * @param  array<string, string|int|null>  $variables
     */
    public function renderMessage(array $variables): string
    {
        $reemplazos = [];

        foreach ($variables as $token => $valor) {
            $reemplazos['{'.$token.'}'] = $valor === null || $valor === '' ? '' : (string) $valor;
        }

        return trim(strtr($this->message_template, $reemplazos));
    }
}
