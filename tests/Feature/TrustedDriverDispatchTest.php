<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Provider;
use App\Models\ProviderDriver;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\Module;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Despacho cerrado a "repartidores de confianza".
 *
 * Regla central: no existe fallback automatico por tiempo. Si el comercio
 * activa la modalidad, el pedido queda esperando a su lista hasta que el
 * comercio lo desactive o lo envie manualmente al pool general.
 */
class TrustedDriverDispatchTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Provider $provider;

    private TypeUser $driverType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Dueño del comercio']);

        $this->provider = Provider::create([
            'user_id' => $this->owner->id,
            'business_name' => 'Pizzería Los Hermanos',
            'zone' => 'Centro',
        ]);

        $this->driverType = TypeUser::create(['description' => 'Repartidor']);
    }

    private function makeDriver(string $name = 'Repartidor'): User
    {
        return User::factory()->create([
            'name' => $name,
            'type_user_id' => $this->driverType->id,
        ]);
    }

    private function linkTrusted(User $driver, string $status = 'active'): ProviderDriver
    {
        return ProviderDriver::create([
            'provider_id' => $this->provider->id,
            'user_id' => $driver->id,
            'status' => $status,
        ]);
    }

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'provider_id' => $this->provider->id,
            'user_id' => User::factory()->create()->id,
            'order_number' => 'PIDE-TRUST-'.uniqid(),
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

    private function availableOrderIds(User $driver): array
    {
        $response = $this->actingAs($driver)->getJson('/api/v1/delivery/orders');
        $response->assertOk();

        return collect($response->json('orders'))->pluck('id')->all();
    }

    // ────────────────────────────────────────────────────────────
    // Sin modalidad activa: pool general como siempre
    // ────────────────────────────────────────────────────────────

    public function test_sin_modalidad_activa_el_pedido_lo_ve_cualquier_repartidor(): void
    {
        $order = $this->makeOrder();
        $outsider = $this->makeDriver('Sin lista');

        $this->assertSame([$order->id], $this->availableOrderIds($outsider));
    }

    // ────────────────────────────────────────────────────────────
    // Modalidad activa: solo la lista de confianza
    // ────────────────────────────────────────────────────────────

    public function test_con_modalidad_activa_solo_lo_ve_la_lista_de_confianza(): void
    {
        $this->provider->update(['trusted_drivers_enabled' => true]);

        $trusted = $this->makeDriver('Confianza');
        $this->linkTrusted($trusted);
        $outsider = $this->makeDriver('Ajeno');

        $order = $this->makeOrder();

        $this->assertSame([$order->id], $this->availableOrderIds($trusted));
        $this->assertSame([], $this->availableOrderIds($outsider));
    }

    public function test_un_repartidor_inactivo_de_la_lista_no_ve_ni_toma_el_pedido(): void
    {
        $this->provider->update(['trusted_drivers_enabled' => true]);
        $inactive = $this->makeDriver('Inactivo');
        $this->linkTrusted($inactive, 'inactive');

        $order = $this->makeOrder();

        $this->assertSame([], $this->availableOrderIds($inactive));

        $this->actingAs($inactive)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/take')
            ->assertStatus(403);

        $this->assertNull($order->fresh()->driver_id);
    }

    public function test_un_repartidor_ajeno_no_puede_tomar_un_pedido_cerrado(): void
    {
        $this->provider->update(['trusted_drivers_enabled' => true]);
        $trusted = $this->makeDriver('Confianza');
        $this->linkTrusted($trusted);
        $outsider = $this->makeDriver('Ajeno');

        $order = $this->makeOrder();

        $this->actingAs($outsider)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/take')
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $order->refresh();
        $this->assertNull($order->driver_id);
        $this->assertSame('pending', $order->delivery_status);
    }

    public function test_un_repartidor_de_confianza_puede_tomar_el_pedido_cerrado(): void
    {
        $this->provider->update(['trusted_drivers_enabled' => true]);
        $trusted = $this->makeDriver('Confianza');
        $this->linkTrusted($trusted);

        $order = $this->makeOrder();

        $this->actingAs($trusted)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/take')
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertSame($trusted->id, $order->driver_id);
        $this->assertSame('assigned', $order->delivery_status);
    }

    // ────────────────────────────────────────────────────────────
    // Sin fallback automatico por tiempo
    // ────────────────────────────────────────────────────────────

    public function test_el_pedido_no_se_libera_solo_al_pasar_el_tiempo(): void
    {
        $this->provider->update(['trusted_drivers_enabled' => true]);
        $trusted = $this->makeDriver('Confianza');
        $this->linkTrusted($trusted);
        $outsider = $this->makeDriver('Ajeno');

        $order = $this->makeOrder();

        // Pasan varios dias: nada debe mover el pedido al pool general.
        $this->travel(7)->days();

        $this->assertSame([], $this->availableOrderIds($outsider));
        $this->assertSame([$order->id], $this->availableOrderIds($trusted));
        $this->assertNull($order->fresh()->released_to_pool_at);
    }

    public function test_desactivar_la_modalidad_abre_los_pedidos_al_pool_general(): void
    {
        $this->provider->update(['trusted_drivers_enabled' => true]);
        $trusted = $this->makeDriver('Confianza');
        $this->linkTrusted($trusted);
        $outsider = $this->makeDriver('Ajeno');

        $order = $this->makeOrder();

        $this->assertSame([], $this->availableOrderIds($outsider));

        $this->provider->update(['trusted_drivers_enabled' => false]);

        $this->assertSame([$order->id], $this->availableOrderIds($outsider));
    }

    // ────────────────────────────────────────────────────────────
    // Liberacion manual al pool general
    // ────────────────────────────────────────────────────────────

    public function test_el_comercio_puede_enviar_un_pedido_al_pool_general(): void
    {
        $this->provider->update(['trusted_drivers_enabled' => true]);
        $trusted = $this->makeDriver('Confianza');
        $this->linkTrusted($trusted);
        $outsider = $this->makeDriver('Ajeno');

        $order = $this->makeOrder();

        $this->assertSame([], $this->availableOrderIds($outsider));

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/orders/'.$order->id.'/send-to-pool')
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertNotNull($order->released_to_pool_at);
        $this->assertSame([$order->id], $this->availableOrderIds($outsider));

        $this->actingAs($outsider)
            ->postJson('/api/v1/delivery/orders/'.$order->id.'/take')
            ->assertOk();
    }

    public function test_un_pedido_liberado_sigue_abierto_si_se_reactiva_la_modalidad(): void
    {
        $this->provider->update(['trusted_drivers_enabled' => true]);
        $trusted = $this->makeDriver('Confianza');
        $this->linkTrusted($trusted);
        $outsider = $this->makeDriver('Ajeno');

        $order = $this->makeOrder();

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/orders/'.$order->id.'/send-to-pool')
            ->assertOk();

        // El comercio desactiva y vuelve a activar: la liberacion manual es
        // permanente para ese pedido, no se revierte sola.
        $this->provider->update(['trusted_drivers_enabled' => false]);
        $this->provider->update(['trusted_drivers_enabled' => true]);

        $this->assertSame([$order->id], $this->availableOrderIds($outsider));
    }

    public function test_no_se_envia_al_pool_un_pedido_que_ya_tiene_repartidor(): void
    {
        $this->provider->update(['trusted_drivers_enabled' => true]);
        $trusted = $this->makeDriver('Confianza');
        $this->linkTrusted($trusted);

        $order = $this->makeOrder([
            'driver_id' => $trusted->id,
            'delivery_status' => 'assigned',
        ]);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/orders/'.$order->id.'/send-to-pool')
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertNull($order->fresh()->released_to_pool_at);
    }

    // ────────────────────────────────────────────────────────────
    // Gestion de la modalidad y de la lista
    // ────────────────────────────────────────────────────────────

    public function test_el_comercio_activa_y_desactiva_la_modalidad(): void
    {
        $driver = $this->makeDriver();
        $this->linkTrusted($driver);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/trusted-drivers/settings', ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('provider.trusted_drivers_enabled', true);

        $this->assertTrue((bool) $this->provider->fresh()->trusted_drivers_enabled);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/trusted-drivers/settings', ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('provider.trusted_drivers_enabled', false);

        $this->assertFalse((bool) $this->provider->fresh()->trusted_drivers_enabled);
    }

    public function test_no_se_puede_activar_la_modalidad_sin_repartidores(): void
    {
        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/trusted-drivers/settings', ['enabled' => true])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertFalse((bool) $this->provider->fresh()->trusted_drivers_enabled);
    }

    public function test_el_comercio_agrega_y_quita_repartidores_de_confianza(): void
    {
        $driver = $this->makeDriver('Nuevo de confianza');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/trusted-drivers', ['user_id' => $driver->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('provider_drivers', [
            'provider_id' => $this->provider->id,
            'user_id' => $driver->id,
            'status' => 'active',
        ]);

        $link = ProviderDriver::query()
            ->where('provider_id', $this->provider->id)
            ->where('user_id', $driver->id)
            ->firstOrFail();

        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/trusted-drivers/'.$link->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('provider_drivers', ['id' => $link->id]);
    }

    public function test_no_se_quita_al_ultimo_repartidor_con_la_modalidad_activa(): void
    {
        $driver = $this->makeDriver();
        $link = $this->linkTrusted($driver);
        $this->provider->update(['trusted_drivers_enabled' => true]);

        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/trusted-drivers/'.$link->id)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'last_active_driver')
            ->assertJsonPath('provider.trusted_drivers_enabled', true);

        $this->assertDatabaseHas('provider_drivers', ['id' => $link->id]);

        // Al desactivar la modalidad, el comercio recupera el control para
        // vaciar la lista sin dejar pedidos atrapados.
        $this->provider->update(['trusted_drivers_enabled' => false]);

        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/trusted-drivers/'.$link->id)
            ->assertOk();

        $this->assertDatabaseMissing('provider_drivers', ['id' => $link->id]);
    }

    public function test_quitar_al_ultimo_repartidor_puede_desactivar_la_modalidad(): void
    {
        $driver = $this->makeDriver('Último de confianza');
        $link = $this->linkTrusted($driver);
        $this->provider->update(['trusted_drivers_enabled' => true]);

        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/trusted-drivers/'.$link->id.'?disable_mode=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('provider.trusted_drivers_enabled', false)
            ->assertJsonPath('drivers', []);

        $this->assertDatabaseMissing('provider_drivers', ['id' => $link->id]);
        $this->assertFalse((bool) $this->provider->fresh()->trusted_drivers_enabled);
    }

    public function test_no_se_agrega_un_usuario_que_no_es_repartidor(): void
    {
        $client = User::factory()->create(['name' => 'Cliente']);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/trusted-drivers', ['user_id' => $client->id])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('provider_drivers', [
            'provider_id' => $this->provider->id,
            'user_id' => $client->id,
        ]);
    }

    public function test_el_endpoint_de_gestion_devuelve_lista_y_candidatos(): void
    {
        $trusted = $this->makeDriver('Ya en la lista');
        $candidate = $this->makeDriver('Candidato');
        $this->linkTrusted($trusted);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/trusted-drivers')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('drivers.0.user_id', $trusted->id)
            ->assertJsonPath('candidates.0.id', $candidate->id);
    }

    // ────────────────────────────────────────────────────────────
    // Ownership: cada comercio maneja solo lo suyo
    // ────────────────────────────────────────────────────────────

    public function test_otro_comercio_no_puede_tocar_la_lista_ni_los_pedidos_ajenos(): void
    {
        $otherOwner = User::factory()->create(['name' => 'Otro comercio']);
        $otherProvider = Provider::create([
            'user_id' => $otherOwner->id,
            'business_name' => 'Otra pizzería',
        ]);

        $driver = $this->makeDriver();
        $link = ProviderDriver::create([
            'provider_id' => $otherProvider->id,
            'user_id' => $driver->id,
            'status' => 'active',
        ]);

        $order = $this->makeOrder();

        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/trusted-drivers/'.$link->id)
            ->assertNotFound();

        $this->actingAs($otherOwner)
            ->postJson('/api/v1/provider/orders/'.$order->id.'/send-to-pool')
            ->assertNotFound();

        $this->assertDatabaseHas('provider_drivers', ['id' => $link->id]);
        $this->assertNull($order->fresh()->released_to_pool_at);
    }

    public function test_los_endpoints_de_gestion_requieren_autenticacion(): void
    {
        $this->getJson('/api/v1/provider/trusted-drivers')->assertStatus(401);

        $this->putJson('/api/v1/provider/trusted-drivers/settings', ['enabled' => true])
            ->assertStatus(401);
    }

    // ────────────────────────────────────────────────────────────
    // El estado viaja al frontend del comercio
    // ────────────────────────────────────────────────────────────

    public function test_el_panel_del_comercio_muestra_la_configuracion_de_confianza(): void
    {
        $module = Module::firstOrCreate(['description' => 'Pedidos']);
        $page = Page::firstOrCreate(
            ['url' => 'pedidos'],
            ['description' => 'Pedidos', 'module_id' => $module->id]
        );
        $this->owner->pages()->attach($page->id);

        $response = $this->actingAs($this->owner)->get('/?section=pedidos');

        $response->assertOk()
            ->assertSee('id="fd-trusted-toggle"', false)
            ->assertSee('fd-trusted-list', false)
            ->assertSee('Lista de confianza')
            ->assertSee('No se liberan solos por tiempo', false);

        $this->assertStringNotContainsString('Undefined variable', $response->getContent());
    }

    public function test_el_menu_lateral_del_comercio_muestra_repartidores_de_confianza(): void
    {
        $prestador = TypeUser::firstOrCreate(['description' => 'Prestador']);
        $this->owner->update(['type_user_id' => $prestador->id]);

        $response = $this->actingAs($this->owner->fresh())->get('/');

        $response->assertOk()
            ->assertSee('id="sidebar-link-repartidores"', false)
            ->assertSee('data-panel="repartidores"', false)
            ->assertSee('id="fd-td-toggle"', false)
            ->assertSee('id="fd-td-search"', false)
            ->assertSee('id="fd-td-list"', false)
            ->assertSee('Agregar repartidor')
            ->assertSee('Lista de confianza')
            ->assertSee('Patente')
            ->assertSee('Estado')
            ->assertSee('disable_mode=1', false);

        $this->assertStringNotContainsString('Undefined variable', $response->getContent());
    }

    public function test_un_cliente_no_ve_el_acceso_a_repartidores_de_confianza(): void
    {
        $clientType = TypeUser::firstOrCreate(['description' => 'Cliente']);
        $client = User::factory()->create([
            'name' => 'Cliente sin comercio',
            'type_user_id' => $clientType->id,
        ]);

        $this->actingAs($client)
            ->get('/')
            ->assertOk()
            ->assertDontSee('id="sidebar-link-repartidores"', false)
            ->assertDontSee('id="fd-td-toggle"', false);
    }

    public function test_el_endpoint_de_pedidos_del_comercio_expone_el_estado_de_confianza(): void
    {
        $this->provider->update(['trusted_drivers_enabled' => true]);

        $this->actingAs($this->owner)
            ->getJson('/api/providers/'.$this->provider->id.'/orders')
            ->assertOk()
            ->assertJsonPath('provider.trusted_drivers_enabled', true);
    }
}
