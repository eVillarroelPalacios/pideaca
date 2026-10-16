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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ServiceBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private TypeUser $prestadorType;

    private TypeUser $clienteType;

    private User $providerUser;

    private Provider $provider;

    private User $otherProviderUser;

    private Provider $otherProvider;

    private User $outsiderUser;

    private User $customer;

    private SubGroup $subgroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prestadorType = TypeUser::create(['description' => 'Prestador']);
        $this->clienteType = TypeUser::create(['description' => 'Cliente']);
        UserStatus::create(['status' => 'Activo']);

        // La pagina "Solicitar Servicio" protege los endpoints de solicitud
        // via middleware page:solicitar-servicio.
        $moduleB = Module::firstOrCreate(
            ['description' => 'Servicios del Hogar & Bienestar'],
            ['icon' => null]
        );
        $paginaSolicitar = Page::firstOrCreate(
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

        $this->otherProviderUser = User::create([
            'name' => 'Pedro Prestador',
            'email' => 'pedro@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);

        $this->otherProvider = Provider::create([
            'user_id' => $this->otherProviderUser->id,
            'business_name' => 'Pedro Servicios',
            'is_active' => true,
        ]);

        $this->outsiderUser = User::create([
            'name' => 'Pintor Ajeno',
            'email' => 'pintor@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);

        Provider::create([
            'user_id' => $this->outsiderUser->id,
            'business_name' => 'Pintor Ajeno',
            'is_active' => true,
        ]);

        $this->customer = User::create([
            'name' => 'Lucia Cliente',
            'email' => 'lucia@example.com',
            'type_user_id' => $this->clienteType->id,
            'password' => 'password',
        ]);

        $this->customer->pages()->attach($paginaSolicitar->id);

        $group = Group::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar']);

        $this->subgroup = SubGroup::firstOrCreate(
            ['description' => 'Plomeros', 'group_id' => $group->id]
        );

        $this->provider->subgroups()->attach($this->subgroup->id);
        $this->otherProvider->subgroups()->attach($this->subgroup->id);
    }

    protected function tearDown(): void
    {
        try {
            $paths = ServiceQuote::query()->pluck('attachments_json')
                ->flatten(1)
                ->filter(fn ($adjunto) => is_array($adjunto) && ! empty($adjunto['path']))
                ->pluck('path');

            foreach ($paths as $path) {
                $file = public_path('images/servicios/'.$path);

                if (is_file($file)) {
                    @unlink($file);
                }
            }

            $dir = public_path('images/servicios');

            if (is_dir($dir) && count(scandir($dir)) <= 2) {
                @rmdir($dir);
            }
        } catch (\Throwable $e) {
            // El cleanup no debe enmascarar el resultado del test.
        }

        parent::tearDown();
    }

    // ------------------------------------------------------------- seguridad

    public function test_invitado_recibe_401_en_los_endpoints_de_citas_y_presupuestos(): void
    {
        $orden = $this->cita();
        [$solicitud] = $this->solicitudPresupuesto();

        $this->getJson('/api/v1/service-subgroups')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No autenticado');

        $this->postJson('/api/v1/customer/service-orders/scheduled', [])->assertStatus(401);
        $this->postJson('/api/v1/customer/service-orders/quote', [])->assertStatus(401);
        $this->postJson('/api/v1/provider/service-orders/'.$orden->id.'/quote-proposal', [])->assertStatus(401);
        $this->postJson('/api/v1/customer/service-orders/'.$solicitud->id.'/accept-quote', [])->assertStatus(401);
    }

    public function test_cliente_no_puede_proponer_presupuesto_como_prestador(): void
    {
        [$orden] = $this->solicitudPresupuesto();

        $this->actingAs($this->customer)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/quote-proposal', [
                'proposed_price' => 50000,
                'estimated_time' => '2 dias',
            ])
            ->assertStatus(403);
    }

    public function test_prestador_de_otro_rubro_no_puede_proponer(): void
    {
        [$orden] = $this->solicitudPresupuesto();

        $this->actingAs($this->outsiderUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/quote-proposal', [
                'proposed_price' => 50000,
                'estimated_time' => '2 dias',
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tu comercio no ofrece este servicio.');
    }

    public function test_cliente_no_acepta_presupuestos_de_otro_usuario(): void
    {
        [$orden] = $this->solicitudPresupuesto([
            'status' => ServiceOrder::STATUS_QUOTED,
        ], ['proposed_price' => 50000, 'provider_id' => $this->provider->id]);

        $this->actingAs($this->outsiderUser)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/accept-quote', [])
            ->assertStatus(404);
    }

    // ------------------------------------------------------------ catalogo

    public function test_catalogo_devuelve_los_subgrupos_de_hogar_con_profesionales(): void
    {
        $otroGrupo = Group::firstOrCreate(['description' => 'Auxilio Vial']);
        SubGroup::firstOrCreate(['description' => 'Gomeros', 'group_id' => $otroGrupo->id]);

        $respuesta = $this->actingAs($this->customer)
            ->getJson('/api/v1/service-subgroups')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subgroups.0.description', 'Plomeros')
            ->assertJsonPath('data.subgroups.0.providers_count', 2);

        // Solo los del grupo Hogar & Bienestar: el gomero de Auxilio Vial no entra.
        $nombres = collect($respuesta->json('data.subgroups'))->pluck('description');
        $this->assertSame(['Plomeros'], $nombres->all());
    }

    // -------------------------------------------------------- reserva de cita

    public function test_reserva_cita_entra_al_pool_sin_precio(): void
    {
        $respuesta = $this->reservar();

        $respuesta->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.order_type', 'SCHEDULED')
            ->assertJsonPath('data.order.status', 'REQUESTED')
            ->assertJsonPath('data.order.status_label', 'Solicitado')
            ->assertJsonPath('data.order.escrow_status', 'HOLDING')
            ->assertJsonPath('data.order.fulfillment', 'AT_HOME')
            ->assertJsonPath('data.order.payment_mode', 'DEPOSIT')
            ->assertJsonPath('data.order.payment_method', 'cash')
            ->assertJsonPath('data.order.merchant', null)
            ->assertJsonPath('data.order.total_amount', '0.00')
            ->assertJsonPath('data.order.deposit_amount', '0.00')
            ->assertJsonPath('data.payment.charged', '0.00')
            ->assertJsonPath('data.payment.total', '0.00')
            ->assertJsonPath('data.payment.note', 'El cobro se registra cuando el profesional carga el precio del trabajo.');

        $this->assertDatabaseHas('service_orders', [
            'customer_id' => $this->customer->id,
            'merchant_id' => null,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => 'SCHEDULED',
            'status' => 'REQUESTED',
            'escrow_status' => 'HOLDING',
            'payment_mode' => 'DEPOSIT',
            'deposit_amount' => '0.00',
            'total_amount' => '0.00',
        ]);
    }

    public function test_reserva_registra_el_modo_de_pago_total(): void
    {
        $this->reservar(['payment_mode' => 'FULL'])
            ->assertStatus(201)
            ->assertJsonPath('data.order.payment_mode', 'FULL')
            ->assertJsonPath('data.payment.charged', '0.00')
            ->assertJsonPath('data.payment.remaining', '0.00');
    }

    public function test_reserva_rechazada_por_fecha_o_subgrupo_invalido(): void
    {
        $this->reservar(['scheduled_at' => Carbon::now()->subHour()->format('Y-m-d H:i:s')])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);

        $this->reservar(['sub_group_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sub_group_id']);

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_reserva_rechazada_sin_campos_requeridos(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/scheduled', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'sub_group_id',
                'scheduled_at',
                'fulfillment',
                'payment_method',
                'payment_mode',
            ]);
    }

    public function test_aceptar_cita_asigna_comercio_y_rechaza_solape(): void
    {
        $creada = $this->reservar();
        $creada->assertStatus(201);
        $ordenId = $creada->json('data.order.id');

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$ordenId.'/accept', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'ACCEPTED')
            ->assertJsonPath('data.order.merchant.id', $this->provider->id);

        $this->assertDatabaseHas('service_orders', [
            'id' => $ordenId,
            'status' => 'ACCEPTED',
            'merchant_id' => $this->provider->id,
        ]);

        // El mismo prestador no puede tomar otra cita en el mismo horario.
        $this->reservar()->assertStatus(201);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.ServiceOrder::query()->latest('id')->first()->id.'/accept', [])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Ya tenes una cita en ese horario.');

        // Pero el otro profesional del rubro si puede.
        $this->actingAs($this->otherProviderUser)
            ->postJson('/api/v1/provider/service-orders/'.ServiceOrder::query()->latest('id')->first()->id.'/accept', [])
            ->assertStatus(200);
    }

    // ---------------------------------------------------- solicitud de presupuesto

    public function test_solicitud_de_presupuesto_con_fotos_y_video(): void
    {
        $respuesta = $this->pedirPresupuesto();

        $respuesta->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.order_type', 'QUOTE')
            ->assertJsonPath('data.order.status', 'REQUESTED')
            ->assertJsonPath('data.order.escrow_status', 'HOLDING')
            ->assertJsonPath('data.order.merchant', null)
            ->assertJsonPath('data.order.description', 'Necesito pintar un cuarto de 12 m2')
            ->assertJsonPath('data.quote.status', 'PENDING')
            ->assertJsonPath('data.quote.proposed_price', '0.00');

        $this->assertCount(3, $respuesta->json('data.quote.attachments'));

        $this->assertDatabaseCount('service_orders', 1);
        $this->assertDatabaseCount('service_quotes', 1);

        $adjuntos = ServiceQuote::query()->firstOrFail()->attachments();

        foreach ($adjuntos as $adjunto) {
            $this->assertFileExists(public_path('images/servicios/'.$adjunto['path']));
        }

        $tipos = collect($adjuntos)->pluck('type');
        $this->assertSame(['photo', 'photo', 'video'], $tipos->all());
    }

    public function test_solicitud_de_presupuesto_valida_fotos_y_detalles(): void
    {
        $this->actingAs($this->customer)
            ->post('/api/v1/customer/service-orders/quote', [
                'sub_group_id' => $this->subgroup->id,
                'details' => 'Trabajo a cotizar',
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photos']);

        $this->actingAs($this->customer)
            ->post('/api/v1/customer/service-orders/quote', [
                'sub_group_id' => $this->subgroup->id,
                'photos' => [UploadedFile::fake()->create('notas.txt', 10, 'text/plain')],
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['details', 'photos.0']);

        $this->assertDatabaseCount('service_orders', 0);
    }

    // ----------------------------------------------------- propuesta del prestador

    public function test_prestador_del_rubro_envia_propuesta_y_pasa_a_presupuestado(): void
    {
        [$orden] = $this->solicitudPresupuesto();

        $respuesta = $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/quote-proposal', [
                'proposed_price' => 85000,
                'estimated_time' => '2 a 3 dias',
                'details' => 'Incluye repuestos y mano de obra',
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.status', 'QUOTED')
            ->assertJsonPath('data.order.status_label', 'Presupuestado')
            ->assertJsonPath('data.quote.status', 'PENDING')
            ->assertJsonPath('data.quote.provider.id', $this->provider->id)
            ->assertJsonPath('data.quote.proposed_price', '85000.00')
            ->assertJsonPath('data.quote.estimated_time', '2 a 3 dias');

        $this->assertDatabaseHas('service_orders', ['id' => $orden->id, 'status' => 'QUOTED']);
        $this->assertDatabaseHas('service_quotes', [
            'service_order_id' => $orden->id,
            'provider_id' => $this->provider->id,
            'proposed_price' => '85000.00',
            'status' => 'PENDING',
        ]);

        // Repropuesta en estado QUOTED: actualiza la misma fila.
        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/quote-proposal', [
                'proposed_price' => 90000,
                'estimated_time' => '1 dia',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.quote.proposed_price', '90000.00');

        $this->assertDatabaseCount('service_quotes', 2);
    }

    public function test_dos_profesionales_del_rubro_pueden_proponer_en_el_pool(): void
    {
        [$orden] = $this->solicitudPresupuesto();

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/quote-proposal', [
                'proposed_price' => 80000,
                'estimated_time' => '2 dias',
            ])
            ->assertStatus(200);

        $this->actingAs($this->otherProviderUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/quote-proposal', [
                'proposed_price' => 75000,
                'estimated_time' => '1 dia',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'QUOTED')
            ->assertJsonPath('data.quote.provider.id', $this->otherProvider->id);

        $this->assertDatabaseHas('service_quotes', [
            'service_order_id' => $orden->id,
            'provider_id' => $this->provider->id,
            'proposed_price' => '80000.00',
        ]);
        $this->assertDatabaseHas('service_quotes', [
            'service_order_id' => $orden->id,
            'provider_id' => $this->otherProvider->id,
            'proposed_price' => '75000.00',
        ]);

        $orden->refresh();
        $this->assertSame(2, $orden->quotes->filter(fn ($q) => $q->provider_id)->count());
    }

    public function test_propuesta_rechazada_por_estado_o_precio_invalido(): void
    {
        $this->reservar();

        $cita = ServiceOrder::query()->where('order_type', ServiceOrder::TYPE_SCHEDULED)->firstOrFail();

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$cita->id.'/quote-proposal', [
                'proposed_price' => 50000,
                'estimated_time' => '1 dia',
            ])
            ->assertStatus(409);

        [$enCurso] = $this->solicitudPresupuesto(['status' => ServiceOrder::STATUS_IN_PROGRESS]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$enCurso->id.'/quote-proposal', [
                'proposed_price' => 50000,
                'estimated_time' => '1 dia',
            ])
            ->assertStatus(409)
            ->assertJsonPath('status', 'IN_PROGRESS');

        [$orden] = $this->solicitudPresupuesto();

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/quote-proposal', [
                'proposed_price' => 0,
                'estimated_time' => '1 dia',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['proposed_price']);
    }

    // ----------------------------------------------------- aceptacion 1-click

    public function test_cliente_acepta_propuesta_y_ordena_el_total(): void
    {
        [$orden] = $this->solicitudPresupuesto([
            'status' => ServiceOrder::STATUS_QUOTED,
        ], ['proposed_price' => 85000, 'provider_id' => $this->provider->id]);

        $respuesta = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/accept-quote', [])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.status', 'ACCEPTED')
            ->assertJsonPath('data.order.escrow_status', 'HOLDING')
            ->assertJsonPath('data.order.merchant.id', $this->provider->id)
            ->assertJsonPath('data.order.total_amount', '85000.00')
            ->assertJsonPath('data.order.deposit_amount', '42500.00')
            ->assertJsonPath('data.quote.status', 'ACCEPTED')
            ->assertJsonPath('data.quote.proposed_price', '85000.00');

        $this->assertDatabaseHas('service_orders', [
            'id' => $orden->id,
            'status' => 'ACCEPTED',
            'merchant_id' => $this->provider->id,
            'total_amount' => '85000.00',
            'deposit_amount' => '42500.00',
            'escrow_status' => 'HOLDING',
        ]);

        $this->assertDatabaseHas('service_quotes', [
            'service_order_id' => $orden->id,
            'status' => 'ACCEPTED',
        ]);

        // La orden ya no esta QUOTED: repetir la aceptacion falla.
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/accept-quote', [])
            ->assertStatus(409)
            ->assertJsonPath('status', 'ACCEPTED');
    }

    public function test_aceptar_con_pago_total_cobra_todo_y_rechaza_las_demas_propuestas(): void
    {
        [$orden] = $this->solicitudPresupuesto([
            'status' => ServiceOrder::STATUS_QUOTED,
            'payment_mode' => 'FULL',
        ], ['proposed_price' => 60000, 'provider_id' => $this->otherProvider->id]);

        ServiceQuote::create([
            'service_order_id' => $orden->id,
            'provider_id' => $this->provider->id,
            'proposed_price' => 55000,
            'status' => ServiceQuote::STATUS_PENDING,
            'attachments_json' => [],
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/accept-quote', [
                'quote' => $orden->quotes()->where('provider_id', $this->otherProvider->id)->first()->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.order.merchant.id', $this->otherProvider->id)
            ->assertJsonPath('data.order.total_amount', '60000.00')
            ->assertJsonPath('data.order.deposit_amount', '60000.00');

        // La propuesta que no gano queda rechazada.
        $this->assertDatabaseHas('service_quotes', [
            'service_order_id' => $orden->id,
            'provider_id' => $this->provider->id,
            'status' => 'REJECTED',
        ]);
    }

    public function test_aceptar_falla_si_no_hay_presupuesto_pendiente(): void
    {
        [$sinResponder] = $this->solicitudPresupuesto();

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$sinResponder->id.'/accept-quote', [])
            ->assertStatus(409)
            ->assertJsonPath('status', 'REQUESTED');

        [$sinPropuesta] = $this->solicitudPresupuesto([
            'status' => ServiceOrder::STATUS_QUOTED,
        ], ['proposed_price' => 0]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$sinPropuesta->id.'/accept-quote', [])
            ->assertStatus(409);
    }

    // ------------------------------------------------------------- e2e

    public function test_flujo_completo_de_presupuesto(): void
    {
        $creada = $this->pedirPresupuesto();
        $creada->assertStatus(201);
        $ordenId = $creada->json('data.order.id');

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$ordenId.'/quote-proposal', [
                'proposed_price' => 75000,
                'estimated_time' => '48 horas',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'QUOTED');

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$ordenId.'/accept-quote', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'ACCEPTED')
            ->assertJsonPath('data.order.total_amount', '75000.00')
            ->assertJsonPath('data.order.merchant.id', $this->provider->id)
            ->assertJsonPath('data.quote.status', 'ACCEPTED');

        $this->assertDatabaseHas('service_orders', [
            'id' => $ordenId,
            'status' => 'ACCEPTED',
            'escrow_status' => 'HOLDING',
            'total_amount' => '75000.00',
            'merchant_id' => $this->provider->id,
        ]);
    }

    public function test_flujo_completo_de_cita_programada(): void
    {
        $creada = $this->reservar();
        $creada->assertStatus(201);
        $ordenId = $creada->json('data.order.id');

        // El profesional del rubro acepta la cita del pool.
        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$ordenId.'/accept', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'ACCEPTED')
            ->assertJsonPath('data.order.merchant.id', $this->provider->id);

        // Carga el precio del trabajo: el cliente debe aprobarlo.
        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$ordenId.'/onsite-estimate', [
                'additional_cost' => 15000,
                'details' => 'Cambio de griferia completa',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'QUOTED');

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$ordenId.'/approve-onsite-estimate', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'IN_PROGRESS')
            ->assertJsonPath('data.order.total_amount', '15000.00');

        $this->assertDatabaseHas('service_orders', [
            'id' => $ordenId,
            'status' => 'IN_PROGRESS',
            'merchant_id' => $this->provider->id,
            'deposit_amount' => '7500.00',
            'total_amount' => '15000.00',
            'escrow_status' => 'HOLDING',
        ]);
    }

    // ------------------------------------------------------------- helpers

    private function turno(?string $fecha = null, int $hora = 9): string
    {
        $base = Carbon::parse($fecha ?? Carbon::now()->addDays(2)->toDateString());

        return $base->setTime($hora, 0)->format('Y-m-d H:i:s');
    }

    private function reservar(array $overrides = [])
    {
        $data = array_merge([
            'sub_group_id' => $this->subgroup->id,
            'scheduled_at' => $this->turno(),
            'fulfillment' => 'AT_HOME',
            'payment_method' => 'cash',
            'payment_mode' => 'DEPOSIT',
            'notes' => 'Cambio de griferia del bano',
        ], $overrides);

        return $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/scheduled', $data);
    }

    private function pedirPresupuesto(array $overrides = [])
    {
        $data = array_merge([
            'sub_group_id' => $this->subgroup->id,
            'details' => 'Necesito pintar un cuarto de 12 m2',
            'photos' => [
                UploadedFile::fake()->image('trabajo1.jpg'),
                UploadedFile::fake()->image('trabajo2.png'),
            ],
            'video' => UploadedFile::fake()->create('trabajo.mp4', 512, 'video/mp4'),
        ], $overrides);

        return $this->actingAs($this->customer)
            ->post('/api/v1/customer/service-orders/quote', $data, ['Accept' => 'application/json']);
    }

    private function cita(array $overrides = []): ServiceOrder
    {
        return ServiceOrder::create(array_merge([
            'customer_id' => $this->customer->id,
            'merchant_id' => $this->provider->id,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => ServiceOrder::TYPE_SCHEDULED,
            'status' => ServiceOrder::STATUS_ACCEPTED,
            'scheduled_at' => Carbon::parse($this->turno()),
            'fulfillment' => 'AT_HOME',
            'payment_method' => 'cash',
            'payment_mode' => 'DEPOSIT',
            'deposit_amount' => 10000,
            'visit_fee' => 0,
            'additional_cost' => 0,
            'total_amount' => 20000,
            'escrow_status' => ServiceOrder::ESCROW_HOLDING,
        ], $overrides));
    }

    /**
     * @return array{0: ServiceOrder, 1: ServiceQuote}
     */
    private function solicitudPresupuesto(array $orderOverrides = [], array $quoteOverrides = []): array
    {
        $orden = ServiceOrder::create(array_merge([
            'customer_id' => $this->customer->id,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => ServiceOrder::TYPE_QUOTE,
            'status' => ServiceOrder::STATUS_REQUESTED,
            'description' => 'Pintar un cuarto completo',
            'visit_fee' => 0,
            'additional_cost' => 0,
            'total_amount' => 0,
            'escrow_status' => ServiceOrder::ESCROW_HOLDING,
        ], $orderOverrides));

        $quote = ServiceQuote::create(array_merge([
            'service_order_id' => $orden->id,
            'provider_id' => null,
            'proposed_price' => 0,
            'status' => ServiceQuote::STATUS_PENDING,
            'attachments_json' => [],
        ], $quoteOverrides));

        return [$orden, $quote];
    }
}
