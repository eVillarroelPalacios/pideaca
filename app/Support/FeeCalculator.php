<?php

namespace App\Support;

use App\Models\FeeRule;
use Illuminate\Support\Carbon;

/**
 * Cotiza la comisión de una transacción según las fee_rules vigentes.
 *
 * Orden de prioridad: regla más específica (módulo+país+moneda) y, ante
 * empate, la de mayor valid_from. Devuelve el desglose + la regla usada
 * (para copiarla al pago como histórico inmutable).
 */
class FeeCalculator
{
    public static function quote(string $module, float $amount, ?string $country = null, ?string $currency = null, ?Carbon $at = null): array
    {
        $at = $at ?? now();

        $rule = FeeRule::where('module', $module)
            ->where('active', true)
            ->where(function ($q) use ($country) {
                $q->whereNull('country')->orWhere('country', $country);
            })
            ->where(function ($q) use ($currency) {
                $q->whereNull('currency')->orWhere('currency', $currency);
            })
            ->where(function ($q) use ($at) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $at);
            })
            ->where(function ($q) use ($at) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $at);
            })
            ->orderByRaw('CASE WHEN country IS NULL THEN 0 ELSE 1 END DESC, CASE WHEN currency IS NULL THEN 0 ELSE 1 END DESC, valid_from DESC NULLS LAST, id DESC')
            ->first();

        $percent = $rule ? (float) $rule->percent : 0.0;
        $fixed = $rule ? (float) $rule->fixed_fee : 0.0;
        $min = $rule ? (float) $rule->min_fee : 0.0;

        $fee = round($amount * $percent / 100 + $fixed, 2);
        $fee = max($fee, $min);
        $fee = min($fee, $amount);

        return [
            'rule_id' => $rule?->id,
            'percent' => $percent,
            'fixed_fee' => $fixed,
            'min_fee' => $min,
            'fee' => $fee,
            'net' => round($amount - $fee, 2),
        ];
    }
}
