<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'code',
        'name',
        'symbol',
        'symbol_position',
        'symbol_space',
        'decimals',
        'decimal_separator',
        'thousand_separator',
        'locale',
        'is_active',
    ];

    protected $casts = [
        'symbol_space' => 'boolean',
        'is_active' => 'boolean',
        'decimals' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Monedas habilitadas para mostrarse/formatear dinero.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Indica si es la moneda por defecto configurada para la plataforma.
     */
    public function isDefault(): bool
    {
        return strcasecmp(
            (string) $this->code,
            (string) config('currency.default', 'ARS')
        ) === 0;
    }
}
