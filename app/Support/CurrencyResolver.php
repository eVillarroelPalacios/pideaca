<?php

namespace App\Support;

use App\Models\Country;
use App\Models\Currency;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CurrencyResolver
{
    /**
     * Moneda por defecto de la aplicacion (config/currency.php).
     */
    public static function defaultCurrency(): Currency
    {
        $code = strtoupper((string) config('currency.default', 'ARS'));

        $currency = Currency::query()
            ->where('is_active', true)
            ->where('code', $code)
            ->first();

        return $currency ?: self::fallback($code);
    }

    /**
     * Moneda asociada a un pais (relacion 1:1 con countries).
     */
    public static function forCountry(?Country $country): Currency
    {
        if ($country) {
            $currency = $country->relationLoaded('currency')
                ? $country->currency
                : $country->currency()->first();

            if ($currency) {
                return $currency;
            }
        }

        return self::defaultCurrency();
    }

    /**
     * Moneda del pais de la direccion primaria de un usuario.
     */
    public static function forUser(?User $user): Currency
    {
        if ($user) {
            $address = $user->relationLoaded('primaryAddress')
                ? $user->primaryAddress
                : $user->primaryAddress()->first();

            if ($address) {
                $country = $address->relationLoaded('country')
                    ? $address->country
                    : $address->country()->first();

                if ($country) {
                    return self::forCountry($country);
                }
            }
        }

        return self::defaultCurrency();
    }

    /**
     * Moneda del usuario autenticado (con cache por usuario).
     */
    public static function current(): Currency
    {
        $user = Auth::user();
        $key = $user?->getAuthIdentifier() ?? 'guest';

        if (! array_key_exists($key, self::$userCache)) {
            self::$userCache[$key] = self::forUser($user);
        }

        return self::$userCache[$key];
    }

    /**
     * Moneda del cliente de una orden (para comprobantes y reportes).
     */
    public static function forOrder(?ServiceOrder $order): Currency
    {
        if ($order) {
            $customer = $order->relationLoaded('customer')
                ? $order->customer
                : $order->customer()->first();

            if ($customer) {
                return self::forUser($customer);
            }
        }

        return self::current();
    }

    /** @var array<int|string, Currency> */
    private static array $userCache = [];

    private static function fallback(string $code): Currency
    {
        $isEur = $code === 'EUR';

        return new Currency([
            'code' => $isEur ? 'EUR' : 'ARS',
            'name' => $isEur ? 'Euro' : 'Peso argentino',
            'symbol' => $isEur ? '€' : '$',
            'symbol_position' => $isEur ? 'after' : 'before',
            'symbol_space' => true,
            'decimals' => 2,
            'decimal_separator' => ',',
            'thousand_separator' => '.',
            'locale' => $isEur ? 'es-ES' : 'es-AR',
            'is_active' => true,
        ]);
    }
}
