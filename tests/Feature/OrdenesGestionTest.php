<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Module;
use App\Models\Order;
use App\Models\Page;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel unificado "Ordenes y Gestion" (OrdenesGestionController):
 * la pagina con su url, el feed que junta ordenes de servicio y pedidos
 * del comercio (pestanias, busqueda y filtros rapidos) y el dashboard
 * que abre la seccion.
 */
class OrdenesGestionTest extends TestCase
{
    use RefreshDatabase;

    private User $providerUser;

    private Provider $provider;

    private User $otherProviderUser;

    private User $customer;

    private SubGroup $subgroup;

    private SubGroup $otherSubgroup;

    private Page $pagina;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = TypeUser::create(['description' => 'Prestador']);
        $cliente = TypeUser::create(['description' => 'Cliente']);
        $status = UserStatus::create(['status' => 'Activo']);

        $module = Module::firstOrCreate(
            ['description' => 'Servicios del Hogar & Bienestar'],
            ['icon' => null]
        );
        $this->pagina = Page::firstOrCreate(
            ['description' => 'Órdenes y Gestión'],
            ['url' => 'ordenes-gestion', 'module_id' => $module->id]
        );

        $this->providerUser = User::create([
            'name' => 'Plomero Uno',
            'email' => 'plomero@example.com',
            'type_user_id' => $prestador->id,
            'user_status_id' => $status->id,
            'password' => 'password',
        ]);
        $this->provider = Provider::create([
            'user_id' => $this->providerUser->id,
            'business_name' => 'Plomero Uno',
            'is_active' => true,
        ]);

        $this->otherProviderUser = User::create([
            'name' => 'Plomero Dos',
            'email' => 'plomero2@example.com',
            'type_user_id' => $prestador->id,
            'user_status_id' => $status->id,
            'password' => 'password',
        ]);
        Provider::create([
            'user_id' => $this->otherProviderUser->id,
            'business_name' => 'Plomero Dos',
            'is_active' => true,
        ]);

        $this->customer = User::create([
            'name' => 'Maria Cliente',
            'email' => 'maria@example.com',
            'type_user_id' => $cliente->id,
            'user_status_id' => $status->id,
            'password' => 'password',
        ]);

        $this->providerUser->pages()->attach($this->pagina->id);

        $group = Group::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar']);

        $this->subgroup = SubGroup::firstOrCreate(
            ['description' => 'Gasistas', 'group_id' => $group->id]
        );
        $this->otherSubgroup = SubGroup::firstOrCreate(
            ['description' => 'Electricistas', 'group_id' => $group->id]
        );

        $this->provider->subgroups()->attach($this->subgroup->id);
    }

    public function test_visitante_recibe_401_en_el_feed(): void
    {
        $this->getJson('/api/v1/provider/ordenes-gestion')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_prestador_sin_la_pagina_recibe_403_en_el_feed(): void
    {
        $this->actingAs($this->otherProviderUser)
            ->getJson('/api/v1/provider/ordenes-gestion')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_index_entra_al_dashboard_con_la_seccion_abierta(): void
    {
        $this->actingAs($this->providerUser)
            ->get('/dashboard/ordenes-gestion')
            ->assertRedirect(route('dashboard', ['section' => 'ordenes-gestion']));
    }

    public function test_index_sin_la_pagina_vuelve_al_dashboard(): void
    {
        $this->actingAs($this->otherProviderUser)
            ->get('/dashboard/ordenes-gestion')
            ->assertRedirect(route('dashboard'));
    }

    public function test_index_de_visitante_va_al_inicio(): void
    {
        $this->get('/dashboard/ordenes-gestion')
            ->assertRedirect('/');
    }

    public function test_el_feed_unifica_servicios_y_pedidos_del_comercio(): void
    {
        // Las columnas de fecha guardan segundos: se avanza el reloj entre
        // creaciones para poder afirmar el orden "mas reciente primero".
        $mia = $this->crearOrden(['status' => ServiceOrder::STATUS_ACCEPTED]);
        $this->travel(1)->seconds();
        $pool = $this->crearOrden([
            'merchant_id' => null,
            'status' => ServiceOrder::STATUS_REQUESTED,
        ]);
        $this->travel(1)->seconds();
        $this->crearPedido(['order_number' => 'PIDE-OG-PENDIENTE', 'status' => Order::STATUS_PENDING]);
        $this->travel(1)->seconds();
        $entregado = $this->crearPedido(['order_number' => 'PIDE-OG-RESUELTO', 'status' => Order::STATUS_DELIVERED]);
        $this->travel(1)->seconds();

        // Pedidos de otro comercio jamas entran.
        $otro = Provider::where('user_id', $this->otherProviderUser->id)->firstOrFail();
        $ajeno = Order::create([
            'provider_id' => $otro->id,
            'user_id' => $this->customer->id,
            'order_number' => 'PIDE-OG-AJENO',
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'efectivo',
            'subtotal' => 100,
            'delivery_fee' => 0,
            'discount' => 0,
            'total' => 100,
        ]);

        $response = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/ordenes-gestion')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.provider.id', $this->provider->id);

        $items = $response->json('data.items');

        $this->assertEqualsCanonicalizing(
            [$mia->id, $pool->id],
            collect($items)->where('source', 'service')->pluck('raw.id')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['PIDE-OG-PENDIENTE', 'PIDE-OG-RESUELTO'],
            collect($items)->where('source', 'commerce')->pluck('raw.order_number')->all()
        );

        $orderNumbers = collect($items)->where('source', 'commerce')->pluck('raw.order_number')->all();
        $this->assertNotContains($ajeno->order_number, $orderNumbers);

        // Mas reciente primero: el pedido entregado fue el ultimo creado.
        $this->assertSame($entregado->id, $items[0]['raw']['id']);
        $this->assertSame('commerce', $items[0]['source']);

        $this->assertSame([
            'all' => 4,
            'pending' => 2,
            'confirmed' => 1,
            'completed' => 1,
            'cancelled' => 0,
        ], $response->json('data.counts'));
        $this->assertSame(4, $response->json('data.total'));
    }

    public function test_pestania_busqueda_y_filtro_rapido_filtran_el_feed(): void
    {
        $this->crearOrden(['status' => ServiceOrder::STATUS_ACCEPTED, 'order_type' => ServiceOrder::TYPE_EMERGENCY]);
        $this->crearOrden([
            'merchant_id' => null,
            'status' => ServiceOrder::STATUS_REQUESTED,
            'order_type' => ServiceOrder::TYPE_QUOTE,
        ]);
        $this->crearPedido(['order_number' => 'PIDE-UNICO']);

        // Pestania: solo los pendientes (presupuesto en pool + pedido).
        $porPestania = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/ordenes-gestion?group=pending')
            ->assertStatus(200);

        $this->assertSame(2, $porPestania->json('data.total'));
        $this->assertEqualsCanonicalizing(
            ['pending', 'pending'],
            collect($porPestania->json('data.items'))->pluck('group')->all()
        );
        $this->assertSame(3, $porPestania->json('data.counts.all'));

        // Busqueda: el numero de pedido unico encuentra solo ese.
        $porBusqueda = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/ordenes-gestion?q=PIDE-UNICO')
            ->assertStatus(200);

        $this->assertSame(1, $porBusqueda->json('data.total'));
        $this->assertSame('commerce', $porBusqueda->json('data.items.0.source'));

        // Filtro rapido por tipo: servicios solamente.
        $emergencias = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/ordenes-gestion?quick=emergency')
            ->assertStatus(200);

        $this->assertSame(1, $emergencias->json('data.total'));
        $this->assertSame('service', $emergencias->json('data.items.0.source'));

        $presupuestos = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/ordenes-gestion?quick=quote')
            ->assertStatus(200);

        $this->assertSame(1, $presupuestos->json('data.total'));
        $this->assertSame('service', $presupuestos->json('data.items.0.source'));
    }

    public function test_pestania_invalida_es_rechazada(): void
    {
        $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/ordenes-gestion?group=no_existe')
            ->assertStatus(422);
    }

    public function test_dashboard_renderiza_la_seccion_unificada(): void
    {
        $html = $this->actingAs($this->providerUser)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="dash-ordenes-gestion"', $html);
        $this->assertStringContainsString("showDashSection('ordenes-gestion')", $html);
        $this->assertStringContainsString('/api/v1/provider/ordenes-gestion', $html);
        $this->assertStringContainsString('id="fd-og-search"', $html);
        $this->assertStringContainsString('Buscar por cliente o ID...', $html);
        $this->assertStringContainsString('Filtros rápidos', $html);
        $this->assertStringContainsString('Todas las órdenes', $html);
        // Sin las paginas viejas asignadas no se dibujan secciones duplicadas.
        $this->assertStringNotContainsString('id="dash-ordenes-servicio"', $html);
        $this->assertStringNotContainsString('id="dash-gestionar-solicitudes"', $html);
    }

    private function crearOrden(array $overrides = []): ServiceOrder
    {
        return ServiceOrder::create(array_merge([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->provider->id,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'status' => ServiceOrder::STATUS_REQUESTED,
            'latitude' => -34.6037,
            'longitude' => -58.3816,
            'visit_fee' => 0,
            'additional_cost' => 0,
            'total_amount' => 0,
            'escrow_status' => ServiceOrder::ESCROW_HOLDING,
        ], $overrides));
    }

    private function crearPedido(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'provider_id' => $this->provider->id,
            'user_id' => $this->customer->id,
            'order_number' => 'PIDE-OG-'.uniqid(),
            'status' => Order::STATUS_PENDING,
            'subtotal' => 100,
            'delivery_fee' => 0,
            'discount' => 0,
            'total' => 100,
            'payment_method' => 'efectivo',
        ], $overrides));
    }
}
