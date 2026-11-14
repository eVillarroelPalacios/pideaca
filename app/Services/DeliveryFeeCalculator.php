<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Order;
use App\Models\Provider;

/**
 * Motor de tarifas de envio.
 *
 * Resuelve el costo de envio combinando la configuracion del comercio con
 * la distancia entre el local y la direccion de entrega. Es el unico lugar
 * donde se decide el monto: los controladores solo guardan el resultado.
 *
 * Modos:
 * - global:   valor de config('fastdelivery.delivery_fee') (comportamiento historico).
 * - flat:     monto fijo configurado por el comercio.
 * - distance: base + (km * precio_por_km), con piso y techo.
 *
 * Nunca rompe: si faltan coordenadas cae a la tarifa plana del comercio y,
 * si tampoco hay, a la global.
 */
class DeliveryFeeCalculator
{
    public const MODE_GLOBAL = 'global';

    public const MODE_FLAT = 'flat';

    public const MODE_DISTANCE = 'distance';

    public const MODES = [self::MODE_GLOBAL, self::MODE_FLAT, self::MODE_DISTANCE];

    /** Radio medio de la Tierra en kilometros. */
    private const EARTH_RADIUS_KM = 6371.0;

    /** Factor de correccion para aproximar el recorrido real por calles. */
    private const ROAD_FACTOR = 1.3;

    /**
     * Cotiza el envio para un comercio y una direccion.
     *
     * @return array{fee: float, distance_km: float|null, mode: string, source: string}
     */
    public function quote(?Provider $provider, ?Address $destination, ?string $deliveryType = Order::DELIVERY_TYPE_DELIVERY): array
    {
        if ($deliveryType === Order::DELIVERY_TYPE_TAKEAWAY) {
            return [
                'fee' => 0.0,
                'distance_km' => null,
                'mode' => self::MODE_GLOBAL,
                'source' => 'takeaway',
            ];
        }

        $global = (float) config('fastdelivery.delivery_fee');

        if (! $provider) {
            return [
                'fee' => round($global, 2),
                'distance_km' => null,
                'mode' => self::MODE_GLOBAL,
                'source' => 'global',
            ];
        }

        $mode = $this->mode($provider);
        $flat = (float) ($provider->delivery_fee_flat ?? 0);

        if ($mode === self::MODE_FLAT && $flat > 0) {
            return [
                'fee' => round($flat, 2),
                'distance_km' => null,
                'mode' => self::MODE_FLAT,
                'source' => 'flat',
            ];
        }

        if ($mode === self::MODE_DISTANCE) {
            $distance = $this->distanceKm($provider, $destination);

            if ($distance !== null) {
                return [
                    'fee' => $this->distanceFee($provider, $distance),
                    'distance_km' => round($distance, 2),
                    'mode' => self::MODE_DISTANCE,
                    'source' => 'distance',
                ];
            }

            // Sin coordenadas: no se puede calcular la distancia. Cae a la
            // tarifa plana del comercio si existe; si no, a la global.
        }

        if ($flat > 0) {
            return [
                'fee' => round($flat, 2),
                'distance_km' => null,
                'mode' => self::MODE_FLAT,
                'source' => $mode === self::MODE_DISTANCE ? 'flat_fallback' : 'flat',
            ];
        }

        return [
            'fee' => round($global, 2),
            'distance_km' => null,
            'mode' => self::MODE_GLOBAL,
            'source' => 'global',
        ];
    }

    /**
     * Solo el monto, para los controladores que no necesitan el detalle.
     */
    public function feeFor(?Provider $provider, ?Address $destination, ?string $deliveryType = Order::DELIVERY_TYPE_DELIVERY): float
    {
        return $this->quote($provider, $destination, $deliveryType)['fee'];
    }

    /**
     * Precio de referencia para listados, antes de elegir direccion.
     * En modo distancia devuelve el piso (base o minimo).
     */
    public function estimateFor(Provider $provider): float
    {
        $mode = $this->mode($provider);
        $flat = (float) ($provider->delivery_fee_flat ?? 0);

        if ($mode === self::MODE_FLAT && $flat > 0) {
            return round($flat, 2);
        }

        if ($mode === self::MODE_DISTANCE) {
            $base = (float) ($provider->delivery_fee_base ?? 0);
            $min = (float) ($provider->delivery_fee_min ?? 0);
            $value = max($base, $min);

            if ($value > 0) {
                return round($value, 2);
            }
        }

        if ($flat > 0) {
            return round($flat, 2);
        }

        return round((float) config('fastdelivery.delivery_fee'), 2);
    }

    public function mode(Provider $provider): string
    {
        $mode = (string) ($provider->delivery_fee_mode ?? self::MODE_GLOBAL);

        return in_array($mode, self::MODES, true) ? $mode : self::MODE_GLOBAL;
    }

    /**
     * Distancia en km entre el comercio y la direccion de entrega, con el
     * factor de calle aplicado. Devuelve null si falta alguna coordenada.
     */
    public function distanceKm(Provider $provider, ?Address $destination): ?float
    {
        if (! $destination) {
            return null;
        }

        $provider->loadMissing('user.primaryAddress');
        $origin = $provider->user?->primaryAddress;

        if (! $origin) {
            return null;
        }

        $lat1 = $origin->latitude !== null ? (float) $origin->latitude : null;
        $lon1 = $origin->longitude !== null ? (float) $origin->longitude : null;
        $lat2 = $destination->latitude !== null ? (float) $destination->latitude : null;
        $lon2 = $destination->longitude !== null ? (float) $destination->longitude : null;

        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        $distance = $this->haversine($lat1, $lon1, $lat2, $lon2) * self::ROAD_FACTOR;

        return round($distance, 3);
    }

    private function distanceFee(Provider $provider, float $distanceKm): float
    {
        $base = (float) ($provider->delivery_fee_base ?? 0);
        $perKm = (float) ($provider->delivery_fee_per_km ?? 0);
        $min = (float) ($provider->delivery_fee_min ?? 0);
        $max = (float) ($provider->delivery_fee_max ?? 0);

        $fee = $base + ($distanceKm * $perKm);

        if ($min > 0) {
            $fee = max($fee, $min);
        }

        if ($max > 0 && $fee > $max) {
            $fee = $max;
        }

        return round($fee, 2);
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }
}
