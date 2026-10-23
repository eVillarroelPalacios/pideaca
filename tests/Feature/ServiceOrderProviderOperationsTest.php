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
 * Acciones operativas del prestador sobre sus ordenes de servicio: detalle
 * de la orden, aceptacion con registro de horarios, despacho ("en camino"),
 * modificacion del presupuesto enviado y los adjuntos que subio el cliente.
 */
class ServiceOrderProviderOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $providerUser;

    private Provider $provider;

    private User $otherProviderUser;

    private Provider $otherProvider;

    private User $customer;

    private SubGroup $subgroup;

    private SubGroup $otherSubgroup;

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
            'name' => 'Gasista Uno',
            'email' => 'gasista@example.com',
            'type_user_id' => $prestador->id,
            'user_status_id' => $status->id,
            'password' => 'password',
        ]);
        $this->provider = Provider::create([
            'user_id' => $this->providerUser->id,
            'business_name' => 'Gasista Uno',
            'is_active' => true,
        ]);

        $this->otherProviderUser = User::create([
            'name' => 'Gasista Dos',
            'email' => 'gasista2@example.com',
            'type_user_id' => $prestador->id,
            'user_status_id' => $status->id,
            'password' => 'password',
        ]);
        $this->otherProvider = Provider::create([
            'user_id' => $this->otherProviderUser->id,
            'business_name' => 'Gasista Dos',
            'is_active' => true,
        ]);

        $this->customer = User::create([
            'name' => 'Maria Cliente',
            'email' => 'maria@example.com',
            'type_user_id' => $cliente->id,
            'user_status_id' => $status->id,
            'password' => 'password',
        ]);

        $this->providerUser->pages()->attach($pagina->id);

        $group = Group::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar']);
        config(['catalog.service_group_id' => $group->id]);

        $this->subgroup = SubGroup::firstOrCreate(
            ['description' => 'Gasistas', 'group_id' => $group->id]
        );
        $this->otherSubgroup = SubGroup::firstOrCreate(
            ['description' => 'Electricistas', 'group_id' => $group->id]
        );

        $this->provider->subgroups()->attach($this->subgroup->id);
        $this->otherProvider->subgroups()->attach($this->otherSubgroup->id);
    }

    // -------------------------------------------------------- detalle operativo

    public function test_prestador_ve_el_detalle_de_su_orden(): void
    {
        $orden = $this->crearOrden([
            'status' => ServiceOrder::STATUS_ACCEPTED,
            'accepted_at' => now()->subMinutes(30),
            'started_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders/'.$orden->id)
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.id', $orden->id)
            ->assertJsonPath('data.order.customer.email', 'maria@example.com')
            ->assertJsonPath('data.order.merchant.business_name', 'Gasista Uno');

        $this->assertNotNull($response->json('data.order.accepted_at'));
        $this->assertNotNull($response->json('data.order.started_at'));
    }

    public function test_prestador_ve_el_detalle_de_una_orden_del_pool_de_su_rubro(): void
    {
        $orden = $this->crearOrden(['merchant_id' => null]);

        $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders/'.$orden->id)
            ->assertStatus(200)
            ->assertJsonPath('data.order.id', $orden->id);
    }

    public function test_prestador_no_ve_el_detalle_de_una_orden_de_otro_comercio(): void
    {
        $orden = $this->crearOrden(['merchant_id' => $this->otherProvider->id]);

        $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders/'.$orden->id)
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_prestador_no_ve_el_detalle_de_una_orden_de_otro_rubro(): void
    {
        $orden = $this->crearOrden([
            'merchant_id' => null,
            'sub_group_id' => $this->otherSubgroup->id,
        ]);

        $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders/'.$orden->id)
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tu comercio no ofrece este servicio.');
    }

    public function test_invitado_recibe_401_en_el_detalle_de_la_orden(): void
    {
        $orden = $this->crearOrden();

        $this->getJson('/api/v1/provider/service-orders/'.$orden->id)
            ->assertStatus(401);
    }

    // ------------------------------------------------------- calificaciones

    public function test_el_detalle_marca_cuando_el_prestador_ya_califico(): void
    {
        $orden = $this->crearOrden([
            'status' => ServiceOrder::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $sinCalificar = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders/'.$orden->id)
            ->assertStatus(200)
            ->assertJsonPath('data.order.provider_rated', false)
            ->assertJsonPath('data.order.provider_rating', null);

        $this->assertNotNull($sinCalificar);

        ServiceRating::create([
            'service_order_id' => $orden->id,
            'reviewer_id' => $this->providerUser->id,
            'ratee_id' => $this->customer->id,
            'stars' => 4,
            'comment' => 'Cliente puntual.',
        ]);

        $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders/'.$orden->id)
            ->assertStatus(200)
            ->assertJsonPath('data.order.provider_rated', true)
            ->assertJsonPath('data.order.provider_rating.stars', 4)
            ->assertJsonPath('data.order.provider_rating.comment', 'Cliente puntual.')
            ->assertJsonPath('data.order.customer_rated', false);
    }

    // ------------------------------------------------------ aceptacion y horarios

    public function test_aceptar_una_cita_registra_la_hora_de_aceptacion(): void
    {
        $orden = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_SCHEDULED,
            'merchant_id' => null,
            'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/accept', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'ACCEPTED')
            ->assertJsonPath('data.order.merchant.id', $this->provider->id);

        $orden->refresh();

        $this->assertNotNull($orden->accepted_at);
        // La cita recien se confirma: todavia no salio a trabajar.
        $this->assertNull($orden->started_at);
    }

    public function test_aceptar_una_urgencia_la_deja_en_camino_con_hora_de_inicio(): void
    {
        $orden = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'merchant_id' => null,
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/accept', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'PROVIDER_DISPATCHED');

        $orden->refresh();

        $this->assertNotNull($orden->accepted_at);
        $this->assertNotNull($orden->started_at);
    }

    // ------------------------------------------------------------- despacho

    public function test_despacha_la_cita_aceptada(): void
    {
        $orden = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_SCHEDULED,
            'status' => ServiceOrder::STATUS_ACCEPTED,
            'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/dispatch', [])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.status', 'PROVIDER_DISPATCHED')
            ->assertJsonPath('data.order.display_status', 'EN_PROCESO');

        $orden->refresh();

        $this->assertNotNull($orden->started_at);
    }

    public function test_despacha_el_presupuesto_confirmado_por_el_cliente(): void
    {
        $orden = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_QUOTE,
            'status' => ServiceOrder::STATUS_ACCEPTED,
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/dispatch', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'PROVIDER_DISPATCHED');

        $this->assertNotNull($orden->fresh()->started_at);
    }

    public function test_no_puede_despachar_una_cita_sin_aceptar(): void
    {
        $orden = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_SCHEDULED,
            'status' => ServiceOrder::STATUS_REQUESTED,
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/dispatch', [])
            ->assertStatus(409)
            ->assertJsonPath('status', 'REQUESTED');
    }

    public function test_no_puede_despachar_la_orden_de_otro_comercio(): void
    {
        $orden = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_SCHEDULED,
            'status' => ServiceOrder::STATUS_ACCEPTED,
            'merchant_id' => $this->otherProvider->id,
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/dispatch', [])
            ->assertStatus(404);
    }

    public function test_no_puede_despachar_una_urgencia_ya_en_camino(): void
    {
        $orden = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'status' => ServiceOrder::STATUS_PROVIDER_DISPATCHED,
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/dispatch', [])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Solo las citas y los presupuestos confirmados se pueden marcar como en camino.');
    }

    // -------------------------------------------------- modificacion de presupuesto

    public function test_modifica_el_presupuesto_ya_enviado_al_cliente(): void
    {
        $orden = $this->crearOrden([
            'status' => ServiceOrder::STATUS_QUOTED,
            'additional_cost' => 1000,
            'estimate_details' => 'Primer presupuesto',
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/onsite-estimate', [
                'additional_cost' => 1800,
                'details' => 'Presupuesto corregido',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'QUOTED')
            ->assertJsonPath('data.order.additional_cost', '1800.00')
            ->assertJsonPath('data.order.estimate_details', 'Presupuesto corregido');
    }

    // ---------------------------------------------------- adjuntos del cliente

    public function test_el_listado_expone_los_adjuntos_del_cliente(): void
    {
        $orden = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_QUOTE,
            'merchant_id' => null,
        ]);

        ServiceQuote::create([
            'service_order_id' => $orden->id,
            'provider_id' => null,
            'proposed_price' => 0,
            'status' => ServiceQuote::STATUS_PENDING,
            'attachments_json' => [
                ['type' => 'photo', 'path' => 'quote_'.$orden->id.'_foto_0.jpg'],
                ['type' => 'video', 'path' => 'quote_'.$orden->id.'_video.mp4'],
            ],
        ]);

        $response = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders?per_page=10')
            ->assertStatus(200);

        $payload = collect($response->json('data.orders'))->firstWhere('id', $orden->id);
        $adjuntos = $payload['attachments'];

        $this->assertCount(2, $adjuntos);
        $this->assertSame('photo', $adjuntos[0]['type']);
        $this->assertSame('video', $adjuntos[1]['type']);
        $this->assertStringContainsString('quote_'.$orden->id.'_foto_0.jpg', $adjuntos[0]['url']);
    }

    public function test_el_listado_expone_las_propuestas_del_rubro(): void
    {
        $orden = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_QUOTE,
            'merchant_id' => null,
        ]);

        ServiceQuote::create([
            'service_order_id' => $orden->id,
            'provider_id' => null,
            'proposed_price' => 0,
            'status' => ServiceQuote::STATUS_PENDING,
        ]);

        ServiceQuote::create([
            'service_order_id' => $orden->id,
            'provider_id' => $this->provider->id,
            'proposed_price' => 7500,
            'estimated_time' => '2 horas',
            'status' => ServiceQuote::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders?per_page=10')
            ->assertStatus(200);

        $payload = collect($response->json('data.orders'))->firstWhere('id', $orden->id);

        $this->assertCount(2, $payload['quotes']);
        $this->assertSame('7500.00', $payload['quotes'][0]['proposed_price']);
        $this->assertSame($this->provider->id, $payload['quotes'][0]['provider_id']);
        $this->assertCount(1, $payload['proposals']);
        $this->assertSame('7500.00', $payload['proposals'][0]['proposed_price']);
    }

    public function test_la_cotizacion_de_la_orden_es_la_propuesta_del_profesional(): void
    {
        $orden = $this->crearOrden([
            'order_type' => ServiceOrder::TYPE_QUOTE,
            'merchant_id' => null,
        ]);

        // Contenedor del cliente: no tiene precio.
        ServiceQuote::create([
            'service_order_id' => $orden->id,
            'provider_id' => null,
            'proposed_price' => 0,
            'status' => ServiceQuote::STATUS_PENDING,
        ]);

        ServiceQuote::create([
            'service_order_id' => $orden->id,
            'provider_id' => $this->provider->id,
            'proposed_price' => 7500,
            'estimated_time' => '2 horas',
            'status' => ServiceQuote::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->providerUser)
            ->getJson('/api/v1/provider/service-orders?per_page=10')
            ->assertStatus(200);

        $payload = collect($response->json('data.orders'))->firstWhere('id', $orden->id);

        $this->assertSame($this->provider->id, $payload['quote']['provider_id']);
        $this->assertSame('7500.00', $payload['quote']['proposed_price']);
        $this->assertSame($this->provider->id, $payload['quote']['provider']['id']);

        // Las cotizaciones llegan de la mas nueva a la mas vieja.
        $ids = array_column($payload['quotes'], 'id');
        $this->assertSame($ids, collect($ids)->sortDesc()->values()->all());
    }

    // -------------------------------------------------------------- dashboard

    public function test_el_dashboard_incluye_la_vista_operativa_compartida(): void
    {
        $html = $this->actingAs($this->providerUser)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="fd-so-modal"', $html);
        $this->assertStringContainsString('window.FdServicioUI', $html);
        $this->assertStringContainsString('FdServicioUI.serviceCard', $html);
    }

    // --------------------------------------------------------------- helpers

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
