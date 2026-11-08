<?php

namespace App\Support;

use App\Models\Currency;

class Money
{
    /**
     * Formatea un monto con la moneda indicada (o la del usuario actual).
     * El formato sale de la tabla currencies, no del locale del navegador.
     */
    public static function format($value, ?Currency $currency = null, ?int $decimals = null): string
    {
        $currency = $currency ?: CurrencyResolver::current();
        $decimals = $decimals ?? (int) ($currency->decimals ?? 2);

        $number = (float) $value;
        $negative = $number < 0;
        $number = abs($number);

        $formatted = number_format(
            $number,
            $decimals,
            (string) ($currency->decimal_separator ?? ','),
            (string) ($currency->thousand_separator ?? '.')
        );

        $symbol = (string) ($currency->symbol ?? '$');
        $space = $currency->symbol_space === false ? '' : ' ';

        $text = $currency->symbol_position === 'after'
            ? $formatted . $space . $symbol
            : $symbol . $space . $formatted;

        return $negative ? '-' . $text : $text;
    }

    /**
     * Formatea un monto usando la moneda del cliente de la orden.
     */
    public static function forOrder($value, $order, ?int $decimals = null): string
    {
        return static::format($value, CurrencyResolver::forOrder($order), $decimals);
    }
}
