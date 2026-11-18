<?php

namespace App\Support;

/**
 * Valida datos bancarios para cobro (sin dependencias externas).
 *
 * - CBU Argentina (22 dígitos, dígito verificador módulo 10 por bloque).
 * - Alias CBU (6-20 caracteres: letras, números, punto y guion).
 * - IBAN genérico (módulo 97), con largo ES=24.
 */
class BankAccountValidator
{
    public static function validCbu(?string $cbu): bool
    {
        $cbu = preg_replace('/\D/', '', (string) $cbu);

        if (strlen($cbu) !== 22) {
            return false;
        }

        return self::checkBlock(substr($cbu, 0, 7), (int) $cbu[7], [7, 1, 3, 9, 7, 1, 3])
            && self::checkBlock(substr($cbu, 0, 13 + 8 - 8 + 0) === '' ? '' : substr($cbu, 8, 13), (int) $cbu[21], [3, 9, 7, 1, 3, 9, 7, 1, 3, 9, 7, 1, 3]);
    }

    private static function checkBlock(string $digits, int $check, array $weights): bool
    {
        if (strlen($digits) !== count($weights) || ! ctype_digit($digits)) {
            return false;
        }

        $sum = 0;
        foreach ($weights as $i => $w) {
            $sum += ((int) $digits[$i]) * $w;
        }

        return (10 - ($sum % 10)) % 10 === $check;
    }

    public static function validAlias(?string $alias): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9.\-]{6,20}$/', (string) $alias);
    }

    public static function validIban(?string $iban, ?string $country = null): bool
    {
        $iban = strtoupper(preg_replace('/\s+/', '', (string) $iban));

        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }

        $lengths = ['ES' => 24, 'AR' => 22];
        $cc = substr($iban, 0, 2);

        if (isset($lengths[$cc]) && strlen($iban) !== $lengths[$cc]) {
            return false;
        }

        if ($country !== null && $cc !== strtoupper($country)) {
            return false;
        }

        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $numeric = '';

        foreach (str_split($rearranged) as $ch) {
            $numeric .= ctype_alpha($ch) ? (string) (ord($ch) - 55) : $ch;
        }

        return bcmod($numeric, '97') === '1';
    }
}
