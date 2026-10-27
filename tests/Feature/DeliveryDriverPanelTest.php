<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Country;
use App\Models\DriverWallet;
use App\Models\Module;
use App\Models\Order;
use App\Models\Page;
use App\Models\Provider;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo C: panel del repartidor dentro del dashboard.
 *
 * Cubre el rediseño de la vista, el listado de pedidos disponibles
 * (incluidos los que están "en preparación") y el ciclo
 * tomar -> gestionar -> confirmar entrega.
 */
class DeliveryDriverPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;

    private Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = User::factory()->create(['name' => 'Carlos Romero']);

        $this->provider = Provider::create([
            'user_id' => User::factory()->create()->id,
            'business_name' => 'Pizzería Los Hermanos',
            'zone' => 'Centro',
        ]);

        // La página "Repartidor" existe solo en la BD real: en los tests se
        // recrea y se le asigna al repartidor, igual que en producción.
        $module = Module::firstOrCreate(['description' => 'Reparto']);
        $page = Page::firstOrCreate(
            ['url' => 'repartidor'],
            ['description' => 'Repartidor', 'module_id' => $module->id]
        );
        $this->driver->pages()->attach($page->id);
    }

    /**
     * Crea un pedido de envío por repartidor, sin conductor por defecto.
     */
    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'provider_id' => $this->provider->id,
            'user_id' => User::factory()->create()->id,
            'order_number' => 'PIDE-TEST-'.uniqid(),
            'status' => Order::STATUS_IN_PREPARATION,
            'subtotal' => 6500,
            'delivery_fee' => 3500,
            'total' => 10000,
            'payment_method' => 'efectivo',
            'delivery_type' => 'delivery',
            'driver_id' => null,
            'delivery_status' => 'pending',
        ], $overrides));
    }

    // ────────────────────────────────────────────────────────────
    // Vista / rediseño
    // ────────────────────────────────────────────────────────────

    public function test_el_panel_del_repartidor_se_renderiza_con_el_diseno_del_modelo(): void
    {
        $response = $this->actingAs($this->driver)->get('/dashboard?section=repartidor');

        $response->assertOk()
            ->assertSee('PideAca')
            ->assertSee('DRIVER')
            ->assertSee('Terminal Central de Repartos')
            ->assertSee('Ganancias')
            ->assertSee('Meta del día')
            ->assertSee('Aceptación')
            ->assertSee('Calificación')
            ->assertSee('Pedidos Disponibles')
            ->assertSee('En Curso / Asignados')
            ->assertSee('Ofertas de Viaje para Aceptar')
            ->assertSee('Radar Sonoro')
            ->assertSee('Vehículo')
            ->assertSee('Zona de cobertura')
            // Colores de la página (no los naranjas genéricos del modelo).
            ->assertSee('#D24C19', false)
            ->assertSee('#0c2a4d', false);
    }

    public function test_el_panel_requiere_autenticacion(): void
    {
        $this->get('/dashboard/repartidor')->assertRedirect();
    }

    /**
     * Regresión: servida la plantilla de la sección aislada la pantalla salía
     * en blanco (display:none) y faltaba el token CSRF para los POST.
     * Ahora la URL directa entra al dashboard con la sección abierta.
     */
    public function test_el_acceso_directo_entra_al_dashboard_con_la_seccion_abierta(): void
    {
        $response = $this->actingAs($this->driver)->get('/dashboard/repartidor');

        $response->assertRedirect(route('dashboard', ['section' => 'repartidor']));

        $html = $this->actingAs($this->driver)
            ->get('/dashboard?section=repartidor')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-csrf="'.csrf_token().'"', $html);
        $this->assertStringContainsString('id="dash-repartidor"', $html);
    }

    /**
     * Regresión: dentro del dashboard unificado la sección se incluye sin
     * $stats y la vista tronaba con "Undefined variable $stats".
     */
    public function test_el_dashboard_incluye_la_seccion_sin_variable_stats(): void
    {
        $response = $this->actingAs($this->driver)->get('/dashboard?section=repartidor');

        $response->assertOk()->assertSee('dash-repartidor', false);
        $this->assertStringNotContainsString('Undefined variable', $response->getContent());
    }

    // ────────────────────────────────────────────────────────────
    // Listado de pedidos disponibles
    // ────────────────────────────────────────────────────────────

    public function test_lista_un_pedido_en_preparacion_sin_conductor_asignado(): void
    {
        $order = $this->makeOrder();

        $response = $this->actingAs($this->driver)->getJson('/api/v1/delivery/orders');

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame([$order->id], collect($response->json('orders'))->pluck('id')->all());
    }

    public function test_no_lista_pedidos_que_ya_tienen_conductor(): void
    {
        $this->makeOrder(['driver_id' => User::factory()->create()->id, 'delivery_status' => 'assigned']);

        $response = $this->actingAs($this->driver)->getJson('/api/v1/delivery/orders');

        $response->assertOk();
        $this->assertSame([], $response->json('orders'));
    }

    public function test_no_lista_pedidos_que_ya_fueron_entregados(): void
    {
        $this->makeOrder([
            'status' => Order::STATUS_DELIVERED,
            'delivery_status' => 'delivered',
        ]);

        $response = $this->actingAs($this->driver)->getJson('/api/v1/delivery/orders');

        $response->assertOk();
        $this->assertSame([], $response->json('orders'));
    }

    public function test_los_pedidos_disponibles_traen_comercio_y_direccion_para_el_itinerario(): void
    {
        $order = $this->makeOrder();

        $response = $this->actingAs($this->driver)->getJson('/api/v1/delivery/orders');
        $first = $response->json('orders.0');

        $this->assertSame('Pizzería Los Hermanos', $first['provider']['business_name']);
        $this->assertArrayHasKey('address', $first);
    }

    // ────────────────────────────────────────────────────────────
    // Tomar / asignar el pedido  ← regresión del bug de $fillable
    // ────────────────────────────────────────────────────────────

    public function test_tomar_pedido_realmente_lo_asigna_al_repartidor(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->driver)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/take')
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();

        // Antes del fix, update() descartaba estos campos en silencio.
        $this->assertSame($this->driver->id, $order->driver_id);
        $this->assertSame('assigned', $order->delivery_status);
    }

    public function test_no_se_puede_tomar_un_pedido_que_ya_esta_asignado(): void
    {
        $order = $this->makeOrder([
            'driver_id' => User::factory()->create()->id,
            'delivery_status' => 'assigned',
        ]);

        $this->actingAs($this->driver)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/take')
            ->assertStatus(422);

        $this->assertNotSame($this->driver->id, $order->refresh()->driver_id);
    }

    public function test_el_pedido_tomado_aparece_en_la_pestana_de_pedidos_en_curso(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->driver)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/take')
            ->assertOk();

        $response = $this->actingAs($this->driver)->getJson('/api/v1/delivery/orders/mine');

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame([$order->id], collect($response->json('orders'))->pluck('id')->all());
    }

    public function test_los_pedidos_de_otro_repartidor_no_aparecen_en_los_mios(): void
    {
        $this->makeOrder([
            'driver_id' => User::factory()->create()->id,
            'delivery_status' => 'assigned',
        ]);

        $response = $this->actingAs($this->driver)->getJson('/api/v1/delivery/orders/mine');

        $response->assertOk();
        $this->assertSame([], $response->json('orders'));
    }

    // ────────────────────────────────────────────────────────────
    // Confirmación de entrega y billetera
    // ────────────────────────────────────────────────────────────

    public function test_confirmar_entrega_con_pin_correcto_acredita_la_ganancia(): void
    {
        $order = $this->makeOrder([
            'driver_id' => $this->driver->id,
            'delivery_status' => 'assigned',
            'delivery_pin' => '4821',
        ]);

        $this->actingAs($this->driver)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/confirm', ['delivery_pin' => '4821'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();

        $this->assertSame('delivered', $order->delivery_status);
        $this->assertSame(Order::STATUS_DELIVERED, $order->status);

        $wallet = DriverWallet::where('user_id', $this->driver->id)->first();
        $this->assertNotNull($wallet);
        $this->assertSame(3500.0, (float) $wallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $this->driver->id,
            'order_id' => $order->id,
            'amount' => 3500,
            'type' => 'credit',
            'status' => 'completed',
        ]);
    }

    public function test_confirmar_entrega_con_pin_incorrecto_no_acredita_nada(): void
    {
        $order = $this->makeOrder([
            'driver_id' => $this->driver->id,
            'delivery_status' => 'assigned',
            'delivery_pin' => '4821',
        ]);

        $this->actingAs($this->driver)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/confirm', ['delivery_pin' => '0000'])
            ->assertStatus(422);

        $this->assertSame('assigned', $order->refresh()->delivery_status);
        $this->assertNull(DriverWallet::where('user_id', $this->driver->id)->first());
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_no_se_puede_confirmar_un_pedido_que_no_me_pertenece(): void
    {
        $order = $this->makeOrder([
            'driver_id' => User::factory()->create()->id,
            'delivery_status' => 'assigned',
            'delivery_pin' => '4821',
        ]);

        $this->actingAs($this->driver)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/confirm', ['delivery_pin' => '4821'])
            ->assertStatus(403);
    }

    // ────────────────────────────────────────────────────────────
    // Paso 1 → 2: retirada del pedido en el comercio
    // ────────────────────────────────────────────────────────────

    public function test_retirar_el_pedido_pasa_de_en_retiro_a_en_entrega(): void
    {
        $order = $this->makeOrder([
            'driver_id' => $this->driver->id,
            'delivery_status' => 'assigned',
        ]);

        $this->actingAs($this->driver)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/pickup')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('picked_up', $order->refresh()->delivery_status);
    }

    public function test_no_se_puede_retirar_un_pedido_que_no_me_pertenece(): void
    {
        $order = $this->makeOrder([
            'driver_id' => User::factory()->create()->id,
            'delivery_status' => 'assigned',
        ]);

        $this->actingAs($this->driver)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/pickup')
            ->assertStatus(403);

        $this->assertSame('assigned', $order->refresh()->delivery_status);
    }

    public function test_no_se_puede_retirar_un_pedido_ya_retirado(): void
    {
        $order = $this->makeOrder([
            'driver_id' => $this->driver->id,
            'delivery_status' => 'picked_up',
        ]);

        $this->actingAs($this->driver)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/pickup')
            ->assertStatus(422);

        $this->assertSame('picked_up', $order->refresh()->delivery_status);
    }

    // ────────────────────────────────────────────────────────────
    // Datos del itinerario y privacidad del PIN
    // ────────────────────────────────────────────────────────────

    public function test_los_mis_pedidos_traen_telefono_del_cliente_y_direccion_del_comercio(): void
    {
        $customer = User::factory()->create(['phone' => '3415551234']);
        $order = $this->makeOrder([
            'user_id' => $customer->id,
            'driver_id' => $this->driver->id,
            'delivery_status' => 'assigned',
            'delivery_pin' => '4821',
        ]);

        $first = $this->actingAs($this->driver)
            ->getJson('/api/v1/delivery/orders/mine')
            ->assertOk()
            ->json('orders.0');

        $this->assertSame('3415551234', $first['user']['phone']);
        $this->assertArrayHasKey('provider', $first);
        $this->assertArrayHasKey('address', $first);
    }

    public function test_trae_la_direccion_de_retiro_del_comercio(): void
    {
        // Regresión: el select de provider no incluia user_id, asi que
        // provider.user (y su direccion) venia siempre null en el JSON.
        $country = Country::create(['name' => 'Argentina', 'iso_code' => 'AR']);
        Address::create([
            'user_id' => $this->provider->user_id,
            'country_id' => $country->id,
            'street' => 'San Martin',
            'number' => '1250',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $this->makeOrder([
            'driver_id' => $this->driver->id,
            'delivery_status' => 'assigned',
        ]);

        $first = $this->actingAs($this->driver)
            ->getJson('/api/v1/delivery/orders/mine')
            ->assertOk()
            ->json('orders.0');

        $this->assertNotNull($first['provider']['user']);
        $this->assertSame('San Martin', data_get($first, 'provider.user.primary_address.street'));
    }

    public function test_el_pin_de_entrega_no_se_filtra_en_la_respuesta(): void
    {
        $this->makeOrder([
            'driver_id' => $this->driver->id,
            'delivery_status' => 'assigned',
            'delivery_pin' => '4821',
        ]);

        $response = $this->actingAs($this->driver)->getJson('/api/v1/delivery/orders/mine');

        $response->assertOk();
        $this->assertArrayNotHasKey('delivery_pin', $response->json('orders.0'));
        $this->assertTrue($response->json('orders.0.has_delivery_pin'));
        $this->assertStringNotContainsString('4821', $response->getContent());
    }

    public function test_los_pedidos_sin_pin_no_marcan_has_delivery_pin(): void
    {
        $this->makeOrder([
            'driver_id' => $this->driver->id,
            'delivery_status' => 'assigned',
        ]);

        $first = $this->actingAs($this->driver)
            ->getJson('/api/v1/delivery/orders/mine')
            ->json('orders.0');

        $this->assertFalse($first['has_delivery_pin']);
    }

    public function test_los_pedidos_retirados_siguen_apareciendo_en_curso(): void
    {
        $order = $this->makeOrder([
            'driver_id' => $this->driver->id,
            'delivery_status' => 'picked_up',
        ]);

        $ids = collect($this->actingAs($this->driver)->getJson('/api/v1/delivery/orders/mine')->json('orders'))
            ->pluck('id')
            ->all();

        $this->assertSame([$order->id], $ids);
    }
}
