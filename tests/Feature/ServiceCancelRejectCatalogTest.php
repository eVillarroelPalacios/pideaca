<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Module;
use App\Models\Page;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\ServiceQuote;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Acciones del cliente sobre sus ordenes (cancelar / rechazar propuesta) y
 * el catalogo GET que alimenta la pagina "Solicitar Servicio".
 */
class ServiceCancelRejectCatalogTest extends TestCase
{
    use RefreshDatabase;

    private TypeUser $prestadorType;

    private TypeUser $clienteType;

    private User $providerUser;

    private Provider $provider;

    private User $customer;

    private User $outsider;

    private SubGroup $subgroup;

    private Page $paginaSolicitar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prestadorType = TypeUser::create(['description' => 'Prestador']);
        $this->clienteType = TypeUser::create(['description' => 'Cliente']);
        UserStatus::create(['status' => 'Activo']);

        $moduleB = Module::firstOrCreate(
            ['description' => 'Servicios del Hogar & Bienestar'],
            ['icon' => null]
        );
        $this->paginaSolicitar = Page::firstOrCreate(
            ['description' => 'Solicitar Servicio'],
            ['url' => 'solicitar-servicio', 'module_id' => $moduleB->id]
        );

        $this->providerUser = User::create([
            'name' => 'Mariana Prestadora',
            'email' => 'mariana@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->providerUser->id,
            'business_name' => 'Mariana Hogar',
            'is_active' => true,
        ]);

        $this->customer = User::create([
            'name' => 'Lucia Cliente',
            'email' => 'lucia@example.com',
            'type_user_id' => $this->clienteType->id,
            'password' => 'password',
        ]);

        $this->customer->pages()->attach($this->paginaSolicitar->id);

        $this->outsider = User::create([
            'name' => 'Intruso Cliente',
            'email' => 'intruso@example.com',
            'type_user_id' => $this->clienteType->id,
            'password' => 'password',
        ]);

        $group = Group::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar']);
        config(['catalog.service_group_id' => $group->id]);

        $this->subgroup = SubGroup::firstOrCreate(
            ['description' => 'Plomeros', 'group_id' => $group->id]
        );

        $this->provider->subgroups()->attach($this->subgroup->id);
    }

    // ------------------------------------------------------------- cancelar

    public function test_invitado_recibe_401_al_cancelar(): void
    {
        $orden = $this->ordenCancellable();

        $this->postJson('/api/v1/customer/service-orders/'.$orden->id.'/cancel', [])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No autenticado');
    }

    public function test_no_puede_cancelar_una_orden_ajena(): void
    {
        $orden = $this->ordenCancellable();

        $this->actingAs($this->outsider)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/cancel', [])
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_no_se_puede_cancelar_en_estado_no_cancelable(): void
    {
        $enCurso = $this->ordenCancellable(['status' => ServiceOrder::STATUS_IN_PROGRESS]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$enCurso->id.'/cancel', [])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 'IN_PROGRESS');

        $cancelada = $this->ordenCancellable(['status' => ServiceOrder::STATUS_CANCELLED]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$cancelada->id.'/cancel', [])
            ->assertStatus(409)
            ->assertJsonPath('status', 'CANCELLED');
    }

    public function test_cliente_cancela_urgencia_y_el_escrow_se_devuelve(): void
    {
        $orden = $this->ordenCancellable([
            'order_type' => ServiceOrder::TYPE_EMERGENCY,
            'visit_fee' => 0,
            'total_amount' => 1500,
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/cancel', [])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.status', 'CANCELLED')
            ->assertJsonPath('data.order.escrow_status', 'REFUNDED');

        $this->assertDatabaseHas('service_orders', [
            'id' => $orden->id,
            'status' => 'CANCELLED',
            'escrow_status' => 'REFUNDED',
        ]);
    }

    public function test_cliente_cancela_cita_aceptada(): void
    {
        $orden = $this->ordenCancellable([
            'order_type' => ServiceOrder::TYPE_SCHEDULED,
            'scheduled_at' => Carbon::now()->addDays(3)->setTime(10, 0),
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/cancel', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'CANCELLED')
            ->assertJsonPath('data.order.status_label', 'Cancelado');

        $this->assertDatabaseHas('service_orders', [
            'id' => $orden->id,
            'status' => 'CANCELLED',
            'escrow_status' => 'REFUNDED',
        ]);
    }

    // ------------------------------------------------------- rechazar oferta

    public function test_invitado_recibe_401_al_rechazar_presupuesto(): void
    {
        [$orden] = $this->solicitudConPropuesta();

        $this->postJson('/api/v1/customer/service-orders/'.$orden->id.'/reject-quote', [])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_no_puede_rechazar_una_propuesta_ajena(): void
    {
        [$orden] = $this->solicitudConPropuesta();

        $this->actingAs($this->outsider)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/reject-quote', [])
            ->assertStatus(404);
    }

    public function test_rechazar_falla_si_no_es_presupuesto_o_no_hay_propuesta(): void
    {
        $cita = $this->ordenCancellable([
            'order_type' => ServiceOrder::TYPE_SCHEDULED,
            'status' => ServiceOrder::STATUS_QUOTED,
            'scheduled_at' => Carbon::now()->addDays(3)->setTime(10, 0),
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$cita->id.'/reject-quote', [])
            ->assertStatus(409);

        [$sinPropuesta] = $this->solicitudConPropuesta(['status' => ServiceOrder::STATUS_REQUESTED], ['proposed_price' => 0]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$sinPropuesta->id.'/reject-quote', [])
            ->assertStatus(409)
            ->assertJsonPath('status', 'REQUESTED');
    }

    public function test_cliente_rechaza_la_propuesta_y_el_prestador_puede_reenviar(): void
    {
        [$orden, $quote] = $this->solicitudConPropuesta();

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/reject-quote', [])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quote.status', 'REJECTED')
            ->assertJsonPath('data.order.status', 'QUOTED');

        $this->assertDatabaseHas('service_quotes', [
            'id' => $quote->id,
            'status' => 'REJECTED',
        ]);
        $this->assertDatabaseHas('service_orders', [
            'id' => $orden->id,
            'status' => 'QUOTED',
        ]);

        // Rechazo repetido: ya no hay propuesta PENDING que decidir.
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/reject-quote', [])
            ->assertStatus(409);

        // El prestador puede mandar otra propuesta sobre la orden QUOTED.
        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/quote-proposal', [
                'proposed_price' => 70000,
                'estimated_time' => '2 dias',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.quote.proposed_price', '70000.00');
    }

    // ------------------------------------------------------------ catalogo

    public function test_invitado_y_usuario_sin_pagina_no_pueden_ver_el_catalogo(): void
    {
        $this->getJson('/api/v1/service-subgroups')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No autenticado');

        $this->actingAs($this->outsider)
            ->getJson('/api/v1/service-subgroups')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_cliente_con_pagina_recibe_los_subgrupos_de_hogar(): void
    {
        // Subgrupo de otro grupo: no debe aparecer en el catalogo del modulo.
        $otroGrupo = Group::firstOrCreate(['description' => 'Abastos']);
        SubGroup::firstOrCreate(['description' => 'Verdulerias', 'group_id' => $otroGrupo->id]);

        $respuesta = $this->actingAs($this->customer)
            ->getJson('/api/v1/service-subgroups')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subgroups.0.description', 'Plomeros')
            ->assertJsonPath('data.subgroups.0.providers_count', 1);

        $nombres = collect($respuesta->json('data.subgroups'))->pluck('description');
        $this->assertSame(['Plomeros'], $nombres->all());
    }

    // ------------------------------------------------------------- helpers

    private function ordenCancellable(array $overrides = []): ServiceOrder
    {
        return ServiceOrder::create(array_merge([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->provider->id,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => ServiceOrder::TYPE_QUOTE,
            'status' => ServiceOrder::STATUS_QUOTED,
            'visit_fee' => 0,
            'additional_cost' => 0,
            'total_amount' => 0,
            'escrow_status' => ServiceOrder::ESCROW_HOLDING,
            'description' => 'Trabajo a cotizar',
        ], $overrides));
    }

    /**
     * @return array{0: ServiceOrder, 1: ServiceQuote}
     */
    private function solicitudConPropuesta(array $orderOverrides = [], array $quoteOverrides = []): array
    {
        $orden = ServiceOrder::create(array_merge([
            'customer_id' => $this->customer->id,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => ServiceOrder::TYPE_QUOTE,
            'status' => ServiceOrder::STATUS_QUOTED,
            'description' => 'Pintar un cuarto completo',
            'visit_fee' => 0,
            'additional_cost' => 0,
            'total_amount' => 0,
            'escrow_status' => ServiceOrder::ESCROW_HOLDING,
        ], $orderOverrides));

        $quote = ServiceQuote::create(array_merge([
            'service_order_id' => $orden->id,
            'provider_id' => $this->provider->id,
            'proposed_price' => 85000,
            'estimated_time' => '2 dias',
            'status' => ServiceQuote::STATUS_PENDING,
            'attachments_json' => [],
        ], $quoteOverrides));

        return [$orden, $quote];
    }
}
