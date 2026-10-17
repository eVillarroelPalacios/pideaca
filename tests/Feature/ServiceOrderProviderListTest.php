<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Module;
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
 * GET /api/v1/provider/service-orders alimenta la pagina "Ordenes de
 * Servicio" del prestador (Module B). Verifica el guard de pagina del
 * middleware, el acote al propio comercio + pool de sus rubros y los filtros.
 */
class ServiceOrderProviderListTest extends TestCase
{
    use RefreshDatabase;

    private User $providerUser;

    private Provider $provider;

    private User $otherProviderUser;

    private User $customer;

    private SubGroup $subgroup;

    private SubGroup $otherSubgroup;

    private ServiceOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = TypeUser::create(['description' => 'Prestador']);
        $cliente = TypeUser::create(['description' => 'Cliente']);
        $status = UserStatus::create(['status' => 'Activo']);

        $moduleB = Module::firstOrCreate(
            ['description' => 'Servicios del Hogar & Bienestar'],
            ['icon' => null]
        );
        $pagina = Page::firstOrCreate(
            ['description' => 'Ordenes de Servicio'],
            ['url' => 'ordenes-servicio', 'module_id' => $moduleB->id]
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

        // Solo el primer prestador tiene la pagina en el menu (page_user);
        // el segundo depende de la plantilla, que aqui no la incluye.
        $this->providerUser->pages()->attach($pagina->id);

        $group = Group::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar']);

        $this->subgroup = SubGroup::firstOrCreate(
            ['description' => 'Gasistas', 'group_id' => $group->id]
        );
        $this->otherSubgroup = SubGroup::firstOrCreate(
            ['description' => 'Electricistas', 'group_id' => $group->id]
        );

        $this->provider->subgroups()->attach($this->subgroup->id);

        $this->order = $this->crearOrden([
            'status' => ServiceOrder::STATUS_REQUESTED,
        ]);
    }

    public function test_visitante_recibe_401(): void
    {
        $this->getJson('/api/v1/provider/service-orders')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_prestador_sin_la_pagina_recibe_403(): void
    {
        $this->actingAs($this->otherProviderUser)
            ->getJson('/api/v1/provider/service-orders')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_cliente_con_la_pagina_no_pasa_el_guard_de_comercio(): void
    {
        $pagina = Page::where('url', 'ordenes-servicio')->firstOrFail();
        $this->customer->pages()->attach($pagina->id);

        $this->actingAs($this->customer)
            ->getJson('/api/v1/provider/service-orders')
            ->assertStatus(403);
    }

    public function test_prestador_ve_sus_ordenes_y_el_pool_de_su_rubro(): void
    {
        $otro = ServiceOrder::create([
            'customer_id' => $this->customer->id,
            'merchant_id' => Provider::where('user_id', $this->otherProviderUser->id)->first()->id,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'status' => ServiceOrder::STATUS_CANCELLED,
            'latitude' => -34.6037,
            'longitude' => -58.3816,
            'visit_fee' => 0,
            'additional_cost' => 0,
            'total_amount' => 0,
            'escrow_status' => ServiceOrder::ESCROW_REFUNDED,
        ]);

        // Pool del rubro del prestador: todavia sin comercio asignado.
        $pool = $this->crearOrden([
            'merchant_id' => null,
            'sub_group_id' => $this->subgroup->id,
            'status' => ServiceOrder::STATUS_REQUESTED,
        ]);

        // Pool de otro rubro: no lo ve.
        $otroRubro = $this->crearOrden([
            'merchant_id' => null,
            'sub_group_id' => $this->otherSubgroup->id,
            'status' => ServiceOrder::STATUS_REQUESTED,
        ]);

        $response = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders?per_page=10')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.provider.id', $this->provider->id);

        $ids = collect($response->json('data.orders'))->pluck('id');

        $this->assertEqualsCanonicalizing([$this->order->id, $pool->id], $ids->all());
        $this->assertNotContains($otro->id, $ids->all());
        $this->assertNotContains($otroRubro->id, $ids->all());
        $this->assertSame(2, $response->json('data.pagination.total'));
    }

    public function test_filtra_por_estado_y_por_tipo(): void
    {
        $this->crearOrden([
            'status' => ServiceOrder::STATUS_COMPLETED,
            'escrow_status' => ServiceOrder::ESCROW_RELEASED,
        ]);

        $porEstado = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders?status='.ServiceOrder::STATUS_COMPLETED)
            ->assertStatus(200);

        $estados = collect($porEstado->json('data.orders'))->pluck('status');
        $this->assertSame([ServiceOrder::STATUS_COMPLETED], $estados->all());

        $porTipo = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders?order_type='.ServiceOrder::TYPE_QUOTE)
            ->assertStatus(200);

        $this->assertSame([], $porTipo->json('data.orders'));
    }

    public function test_estado_desconocido_es_rechazado(): void
    {
        $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders?status=NO_EXISTE')
            ->assertStatus(422);
    }

    public function test_dashboard_renderiza_la_seccion_del_prestador(): void
    {
        $html = $this->actingAs($this->providerUser)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="dash-ordenes-servicio"', $html);
        $this->assertStringContainsString("showDashSection('ordenes-servicio')", $html);
        $this->assertStringContainsString('/api/v1/provider/service-orders', $html);
        $this->assertStringContainsString('id="fd-os-list"', $html);
        $this->assertStringContainsString('id="fd-os-pool"', $html);
    }

    public function test_seccion_gestion_carga_las_solicitudes_de_servicio_del_prestador(): void
    {
        $pagina = Page::firstOrCreate(
            ['url' => 'gestionar-solicitudes'],
            ['description' => 'Gestion', 'module_id' => Module::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar'], ['icon' => null])->id]
        );
        $this->providerUser->pages()->attach($pagina->id);

        $html = $this->actingAs($this->providerUser)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="dash-gestionar-solicitudes"', $html);
        $this->assertStringContainsString('id="fd-gs-list"', $html);
        // La gestion (modulo Hogar) se alimenta del listado de solicitudes de servicio...
        $this->assertStringContainsString('/api/v1/provider/service-orders', $html);
        $this->assertStringContainsString('/service-orders/\' + orderId + \'/accept', $html);
        // ...y sigue sumando las ordenes del comercio si las hubiera.
        $this->assertStringContainsString('/orders?per_page=50', $html);
        $this->assertStringContainsString('window.fdGsAccept', $html);
        $this->assertStringContainsString('window.fdGsDecide', $html);
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
}
