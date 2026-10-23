<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Module;
use App\Models\Page;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\ServiceQuote;
use App\Models\ServiceRating;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /api/v1/customer/service-orders alimenta la pagina "Mis Servicios"
 * del cliente (Module B). Verifica el guard de pagina, el acote al propio
 * usuario, los filtros y los datos que usa la UI para cada accion.
 */
class ServiceOrderCustomerListTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $otherCustomer;

    private Provider $provider;

    private SubGroup $subgroup;

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
            ['description' => 'Mis Servicios'],
            ['url' => 'mis-servicios', 'module_id' => $moduleB->id]
        );

        $this->customer = User::create([
            'name' => 'Maria Cliente',
            'email' => 'maria@example.com',
            'type_user_id' => $cliente->id,
            'user_status_id' => $status->id,
            'password' => 'password',
        ]);

        $this->otherCustomer = User::create([
            'name' => 'Juan Cliente',
            'email' => 'juan@example.com',
            'type_user_id' => $cliente->id,
            'user_status_id' => $status->id,
            'password' => 'password',
        ]);

        // Solo la primera cliente tiene la pagina en el menu (page_user).
        $this->customer->pages()->attach($pagina->id);

        $providerUser = User::create([
            'name' => 'Plomero Uno',
            'email' => 'plomero@example.com',
            'type_user_id' => $prestador->id,
            'user_status_id' => $status->id,
            'password' => 'password',
        ]);
        $this->provider = Provider::create([
            'user_id' => $providerUser->id,
            'business_name' => 'Plomero Uno',
            'is_active' => true,
        ]);

        $group = Group::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar']);
        config(['catalog.service_group_id' => $group->id]);
        $this->subgroup = SubGroup::firstOrCreate(
            ['description' => 'Gasistas', 'group_id' => $group->id]
        );
        $this->provider->subgroups()->attach($this->subgroup->id);

        $this->order = $this->crearOrden();
    }

    public function test_visitante_recibe_401(): void
    {
        $this->getJson('/api/v1/customer/service-orders')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_cliente_sin_la_pagina_recibe_403(): void
    {
        $this->actingAs($this->otherCustomer)
            ->getJson('/api/v1/customer/service-orders')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_cliente_ve_solo_sus_ordenes_paginadas(): void
    {
        $ajena = $this->crearOrden([
            'customer_id' => $this->otherCustomer->id,
            'status' => ServiceOrder::STATUS_CANCELLED,
            'escrow_status' => ServiceOrder::ESCROW_REFUNDED,
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/customer/service-orders?per_page=10')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer.id', $this->customer->id);

        $ids = collect($response->json('data.orders'))->pluck('id');

        $this->assertSame([$this->order->id], $ids->all());
        $this->assertNotContains($ajena->id, $ids->all());
        $this->assertSame(1, $response->json('data.pagination.total'));
    }

    public function test_filtra_por_estado_y_por_tipo(): void
    {
        $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_QUOTE,
            'status' => ServiceOrder::STATUS_QUOTED,
        ]);

        $porEstado = $this->actingAs($this->customer)
            ->getJson('/api/v1/customer/service-orders?status='.ServiceOrder::STATUS_QUOTED)
            ->assertStatus(200);

        $estados = collect($porEstado->json('data.orders'))->pluck('status');
        $this->assertSame([ServiceOrder::STATUS_QUOTED], $estados->all());

        $porTipo = $this->actingAs($this->customer)
            ->getJson('/api/v1/customer/service-orders?order_type='.ServiceOrder::TYPE_SCHEDULED)
            ->assertStatus(200);

        $this->assertSame([], $porTipo->json('data.orders'));
    }

    public function test_estado_desconocido_es_rechazado(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/customer/service-orders?status=NO_EXISTE')
            ->assertStatus(422);

        // Los mensajes de validacion se emiten siempre en español.
        $this->assertStringContainsString('El campo', $response->json('message'));
    }

    public function test_filtra_por_estado_simplificado(): void
    {
        $cotizada = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_QUOTE,
            'status' => ServiceOrder::STATUS_QUOTED,
        ]);
        $aceptada = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_SCHEDULED,
            'status' => ServiceOrder::STATUS_ACCEPTED,
        ]);
        $pendiente = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'status' => ServiceOrder::STATUS_REQUESTED,
        ]);

        // PRESUPUESTADO agrupa QUOTED + ACCEPTED y deja afuera la pendiente.
        $presupuestado = $this->actingAs($this->customer)
            ->getJson('/api/v1/customer/service-orders?status=PRESUPUESTADO')
            ->assertStatus(200);

        $this->assertEqualsCanonicalizing(
            [$cotizada->id, $aceptada->id],
            collect($presupuestado->json('data.orders'))->pluck('id')->all()
        );

        // PENDIENTE son las REQUESTED (incluye la orden del setUp).
        $espera = $this->actingAs($this->customer)
            ->getJson('/api/v1/customer/service-orders?status=PENDIENTE')
            ->assertStatus(200);

        $this->assertEqualsCanonicalizing(
            [$this->order->id, $pendiente->id],
            collect($espera->json('data.orders'))->pluck('id')->all()
        );
    }

    public function test_el_payload_trae_la_propuesta_pendiente_y_marca_cancellable(): void
    {
        $presupuesto = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_QUOTE,
            'status' => ServiceOrder::STATUS_QUOTED,
        ]);
        ServiceQuote::create([
            'service_order_id' => $presupuesto->id,
            'provider_id' => $this->provider->id,
            'proposed_price' => 12000,
            'details' => 'Cambio de griferia completa',
            'estimated_time' => '2 horas',
            'status' => ServiceQuote::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/customer/service-orders?status='.ServiceOrder::STATUS_QUOTED)
            ->assertStatus(200);

        $payload = collect($response->json('data.orders'))->firstWhere('id', $presupuesto->id);

        $this->assertNotNull($payload);
        $this->assertSame(ServiceQuote::STATUS_PENDING, $payload['quote']['status']);
        $this->assertSame('Cambio de griferia completa', $payload['quote']['details']);
        $this->assertEquals(12000, (float) $payload['quote']['proposed_price']);
        $this->assertSame($this->provider->id, (int) $payload['quote']['provider']['id']);
        $this->assertCount(1, $payload['proposals']);
        $this->assertSame('Gasistas', $payload['sub_group']['description']);
        $this->assertTrue($payload['cancellable']);
        $this->assertFalse($payload['customer_rated']);
    }

    public function test_marca_cuando_el_cliente_ya_califico(): void
    {
        $completada = $this->crearOrden([
            'status' => ServiceOrder::STATUS_COMPLETED,
            'escrow_status' => ServiceOrder::ESCROW_RELEASED,
            'completed_at' => now(),
        ]);
        ServiceRating::create([
            'service_order_id' => $completada->id,
            'reviewer_id' => $this->customer->id,
            'ratee_id' => $this->provider->user_id,
            'stars' => 5,
            'comment' => 'Todo perfecto',
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/customer/service-orders?status='.ServiceOrder::STATUS_COMPLETED)
            ->assertStatus(200);

        $payload = collect($response->json('data.orders'))->firstWhere('id', $completada->id);

        $this->assertTrue($payload['customer_rated']);
        $this->assertFalse($payload['cancellable']);
    }

    public function test_dashboard_renderiza_la_seccion_mis_servicios(): void
    {
        $html = $this->actingAs($this->customer)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="dash-mis-servicios"', $html);
        $this->assertStringContainsString("showDashSection('mis-servicios')", $html);
        $this->assertStringContainsString('/api/v1/customer/service-orders', $html);
        $this->assertStringContainsString('id="fd-mis-list"', $html);

        // Se aisl la seccion con su script para no pisarse con las demas.
        $start = strpos($html, 'id="dash-mis-servicios"');
        $this->assertNotFalse($start);
        $end = strpos($html, '</script>', $start);
        $seccion = substr($html, $start, $end === false ? 12000 : $end - $start + 8);

        // El boton Actualizar se elimino: la lista se refresca sola.
        $this->assertStringNotContainsString('Actualizar', $seccion);
        $this->assertStringContainsString('setInterval(autoRefresh, 10000)', $seccion);

        // Sin pestañas de hoy/historial: lista única con rango de fechas y
        // stepper de pasos con la marca "Ahora".
        $this->assertStringNotContainsString('id="fd-mis-tab-today"', $seccion);
        $this->assertStringNotContainsString('id="fd-mis-tab-history"', $seccion);
        $this->assertStringNotContainsString('window.fdMisTab', $seccion);
        $this->assertStringContainsString('id="fd-mis-date-from"', $seccion);
        $this->assertStringContainsString('id="fd-mis-date-to"', $seccion);
        $this->assertStringContainsString("'Ahora'", $seccion);
        $this->assertStringContainsString('Servicio #', $seccion);

        // Cancelar solicitud lleva icono (circulo con equis) y sin texto.
        $this->assertStringContainsString("act: 'cancel'", $seccion);
        $this->assertStringContainsString("label: ''", $seccion);
        $this->assertStringContainsString('<circle cx="12" cy="12" r="10"/>', $seccion);
        $this->assertStringContainsString('title="\' + esc(title) + \'"', $seccion);
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
