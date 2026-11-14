<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Country;
use App\Models\Order;
use App\Models\Provider;
use App\Models\User;
use App\Services\DeliveryFeeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Motor de tarifas de envio configurable por comercio.
 *
 * Cubre los tres modos (global, fijo y por distancia), los fallbacks cuando
 * faltan datos y la cotizacion de retiro en local.
 */
class DeliveryFeeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private DeliveryFeeCalculator $calculator;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(DeliveryFeeCalculator::class);
        $this->country = Country::create(['name' => 'Argentina', 'iso_code' => 'AR']);

        config(['fastdelivery.delivery_fee' => 3000]);
    }

    private function makeProvider(array $attributes = []): Provider
    {
        $user = User::factory()->create();

        return Provider::create(array_merge([
            'user_id' => $user->id,
            'business_name' => 'Comercio '.uniqid(),
            'zone' => 'Centro',
        ], $attributes));
    }

    private function makeAddress(?float $latitude = null, ?float $longitude = null): Address
    {
        return Address::create([
            'user_id' => User::factory()->create()->id,
            'country_id' => $this->country->id,
            'street' => 'Calle de prueba',
            'number' => '123',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    /** Comercio con coordenadas de origen, para probar el modo distancia. */
    private function makeProviderWithOrigin(float $latitude = 0.0, float $longitude = 0.0, array $attributes = []): Provider
    {
        $user = User::factory()->create();

        Address::create([
            'user_id' => $user->id,
            'country_id' => $this->country->id,
            'street' => 'Local del comercio',
            'number' => '1',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'is_primary' => true,
        ]);

        return Provider::create(array_merge([
            'user_id' => $user->id,
            'business_name' => 'Comercio '.uniqid(),
            'zone' => 'Centro',
        ], $attributes));
    }

    // ────────────────────────────────────────────────────────────
    // Modo global (comportamiento historico)
    // ────────────────────────────────────────────────────────────

    public function test_modo_global_usa_la_tarifa_general(): void
    {
        $provider = $this->makeProvider();

        $quote = $this->calculator->quote($provider, $this->makeAddress());

        $this->assertSame(DeliveryFeeCalculator::MODE_GLOBAL, $quote['mode']);
        $this->assertSame('global', $quote['source']);
        $this->assertEqualsWithDelta(3000.0, $quote['fee'], 0.001);
        $this->assertNull($quote['distance_km']);
    }

    public function test_un_modo_desconocido_cae_a_la_tarifa_global(): void
    {
        $provider = $this->makeProvider([
            'delivery_fee_mode' => 'inventado',
            'delivery_fee_flat' => 0,
        ]);

        $quote = $this->calculator->quote($provider, $this->makeAddress());

        $this->assertSame(DeliveryFeeCalculator::MODE_GLOBAL, $quote['mode']);
        $this->assertEqualsWithDelta(3000.0, $quote['fee'], 0.001);
    }

    public function test_sin_comercio_usa_la_tarifa_general(): void
    {
        $quote = $this->calculator->quote(null, $this->makeAddress());

        $this->assertSame('global', $quote['source']);
        $this->assertEqualsWithDelta(3000.0, $quote['fee'], 0.001);
    }

    // ────────────────────────────────────────────────────────────
    // Monto fijo por comercio
    // ────────────────────────────────────────────────────────────

    public function test_modo_fijo_usa_el_monto_del_comercio(): void
    {
        $provider = $this->makeProvider([
            'delivery_fee_mode' => DeliveryFeeCalculator::MODE_FLAT,
            'delivery_fee_flat' => 4500,
        ]);

        $quote = $this->calculator->quote($provider, $this->makeAddress());

        $this->assertSame(DeliveryFeeCalculator::MODE_FLAT, $quote['mode']);
        $this->assertSame('flat', $quote['source']);
        $this->assertEqualsWithDelta(4500.0, $quote['fee'], 0.001);
    }

    public function test_modo_fijo_sin_monto_cae_a_la_tarifa_global(): void
    {
        $provider = $this->makeProvider([
            'delivery_fee_mode' => DeliveryFeeCalculator::MODE_FLAT,
            'delivery_fee_flat' => 0,
        ]);

        $quote = $this->calculator->quote($provider, $this->makeAddress());

        $this->assertSame(DeliveryFeeCalculator::MODE_GLOBAL, $quote['mode']);
        $this->assertEqualsWithDelta(3000.0, $quote['fee'], 0.001);
    }

    // ────────────────────────────────────────────────────────────
    // Tarifa por distancia
    // ────────────────────────────────────────────────────────────

    public function test_modo_distancia_calcula_base_mas_km(): void
    {
        $provider = $this->makeProviderWithOrigin(0.0, 0.0, [
            'delivery_fee_mode' => DeliveryFeeCalculator::MODE_DISTANCE,
            'delivery_fee_base' => 1000,
            'delivery_fee_per_km' => 500,
            'delivery_fee_min' => 0,
            'delivery_fee_max' => 0,
        ]);
        $destination = $this->makeAddress(0.0, 0.01);

        $quote = $this->calculator->quote($provider, $destination);
        $distance = $this->calculator->distanceKm($provider, $destination);

        $this->assertSame(DeliveryFeeCalculator::MODE_DISTANCE, $quote['mode']);
        $this->assertSame('distance', $quote['source']);
        $this->assertNotNull($distance);
        $this->assertGreaterThan(1.3, $distance);
        $this->assertLessThan(1.6, $distance);
        $this->assertEqualsWithDelta(round($distance, 2), $quote['distance_km'], 0.001);
        $this->assertEqualsWithDelta(round(1000 + ($distance * 500), 2), $quote['fee'], 0.01);
    }

    public function test_modo_distancia_respeta_el_minimo(): void
    {
        $provider = $this->makeProviderWithOrigin(0.0, 0.0, [
            'delivery_fee_mode' => DeliveryFeeCalculator::MODE_DISTANCE,
            'delivery_fee_base' => 500,
            'delivery_fee_per_km' => 100,
            'delivery_fee_min' => 5000,
        ]);

        $quote = $this->calculator->quote($provider, $this->makeAddress(0.0, 0.01));

        $this->assertEqualsWithDelta(5000.0, $quote['fee'], 0.001);
    }

    public function test_modo_distancia_respeta_el_techo(): void
    {
        $provider = $this->makeProviderWithOrigin(0.0, 0.0, [
            'delivery_fee_mode' => DeliveryFeeCalculator::MODE_DISTANCE,
            'delivery_fee_base' => 1000,
            'delivery_fee_per_km' => 50000,
            'delivery_fee_max' => 2500,
        ]);

        $quote = $this->calculator->quote($provider, $this->makeAddress(0.0, 0.01));

        $this->assertEqualsWithDelta(2500.0, $quote['fee'], 0.001);
    }

    public function test_distancia_sin_coordenadas_cae_a_la_tarifa_fija(): void
    {
        $provider = $this->makeProviderWithOrigin(0.0, 0.0, [
            'delivery_fee_mode' => DeliveryFeeCalculator::MODE_DISTANCE,
            'delivery_fee_base' => 1000,
            'delivery_fee_per_km' => 500,
            'delivery_fee_flat' => 4000,
        ]);

        // La direccion de entrega no tiene coordenadas.
        $quote = $this->calculator->quote($provider, $this->makeAddress());

        $this->assertSame(DeliveryFeeCalculator::MODE_FLAT, $quote['mode']);
        $this->assertSame('flat_fallback', $quote['source']);
        $this->assertEqualsWithDelta(4000.0, $quote['fee'], 0.001);
    }

    public function test_distancia_sin_coordenadas_ni_fija_cae_a_la_global(): void
    {
        $provider = $this->makeProviderWithOrigin(0.0, 0.0, [
            'delivery_fee_mode' => DeliveryFeeCalculator::MODE_DISTANCE,
            'delivery_fee_base' => 1000,
            'delivery_fee_per_km' => 500,
        ]);

        $quote = $this->calculator->quote($provider, $this->makeAddress());

        $this->assertSame(DeliveryFeeCalculator::MODE_GLOBAL, $quote['mode']);
        $this->assertSame('global', $quote['source']);
        $this->assertEqualsWithDelta(3000.0, $quote['fee'], 0.001);
    }

    // ────────────────────────────────────────────────────────────
    // Retiro en local y estimaciones de listado
    // ────────────────────────────────────────────────────────────

    public function test_el_retiro_en_local_no_cobra_envio(): void
    {
        $provider = $this->makeProvider([
            'delivery_fee_mode' => DeliveryFeeCalculator::MODE_FLAT,
            'delivery_fee_flat' => 4500,
        ]);

        $quote = $this->calculator->quote(
            $provider,
            $this->makeAddress(),
            Order::DELIVERY_TYPE_TAKEAWAY
        );

        $this->assertEqualsWithDelta(0.0, $quote['fee'], 0.001);
        $this->assertSame('takeaway', $quote['source']);
    }

    public function test_el_estimado_por_distancia_usa_el_piso(): void
    {
        $provider = $this->makeProvider([
            'delivery_fee_mode' => DeliveryFeeCalculator::MODE_DISTANCE,
            'delivery_fee_base' => 800,
            'delivery_fee_min' => 1500,
            'delivery_fee_per_km' => 100,
        ]);

        $this->assertEqualsWithDelta(1500.0, $this->calculator->estimateFor($provider), 0.001);
    }

    public function test_el_estimado_fijo_usa_el_monto_del_comercio(): void
    {
        $provider = $this->makeProvider([
            'delivery_fee_mode' => DeliveryFeeCalculator::MODE_FLAT,
            'delivery_fee_flat' => 4200,
        ]);

        $this->assertEqualsWithDelta(4200.0, $this->calculator->estimateFor($provider), 0.001);
    }

    public function test_fee_for_devuelve_el_mismo_monto_que_quote(): void
    {
        $provider = $this->makeProvider([
            'delivery_fee_mode' => DeliveryFeeCalculator::MODE_FLAT,
            'delivery_fee_flat' => 4500,
        ]);
        $address = $this->makeAddress();

        $this->assertSame(
            $this->calculator->quote($provider, $address)['fee'],
            $this->calculator->feeFor($provider, $address)
        );
    }
}
