<?php

namespace Tests\Feature;

use App\Models\Country;
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
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ServiceEmergencyFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $providerUser;

    private Provider $provider;

    private User $otherProviderUser;

    private Provider $otherProvider;

    private User $outsiderUser;

    private Provider $outsider;

    private User $customer;

    private SubGroup $subgroup;

    private SubGroup $otherSubgroup;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = TypeUser::create(['description' => 'Prestador']);
        $cliente = TypeUser::create(['description' => 'Cliente']);
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
            'name' => 'Gasista Uno',
            'email' => 'gasista@example.com',
            'type_user_id' => $prestador->id,
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
            'password' => 'password',
        ]);

        $this->otherProvider = Provider::create([
            'user_id' => $this->otherProviderUser->id,
            'business_name' => 'Gasista Dos',
            'is_active' => true,
        ]);

        // Tercer prestador de otro rubro: no puede tocar el pool de gasistas.
        $this->outsiderUser = User::create([
            'name' => 'Jardinero Tres',
            'email' => 'jardinero@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->outsider = Provider::create([
            'user_id' => $this->outsiderUser->id,
            'business_name' => 'Jardinero Tres',
            'is_active' => true,
        ]);

        $this->customer = User::create([
            'name' => 'Maria Cliente',
            'email' => 'maria@example.com',
            'type_user_id' => $cliente->id,
            'password' => 'password',
        ]);

        $this->customer->pages()->attach($paginaSolicitar->id);

        $group = Group::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar']);

        $this->subgroup = SubGroup::firstOrCreate(
            ['description' => 'Gasistas', 'group_id' => $group->id]
        );

        $this->otherSubgroup = SubGroup::firstOrCreate(
            ['description' => 'Electricistas', 'group_id' => $group->id]
        );

        $jardineria = SubGroup::firstOrCreate(
            ['description' => 'Jardineria', 'group_id' => $group->id]
        );

        $this->provider->subgroups()->attach($this->subgroup->id);
        $this->otherProvider->subgroups()->attach($this->subgroup->id);
        $this->outsider->subgroups()->attach($jardineria->id);
    }

    protected function tearDown(): void
    {
        try {
            $paths = ServiceOrder::query()->pluck('photo_path')
                ->merge(ServiceOrder::query()->pluck('audio_path'))
                ->filter();

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

    public function test_invitado_recibe_401_en_los_endpoints_de_urgencias(): void
    {
        $orden = $this->urgencia();

        $this->postJson('/api/v1/customer/service-orders/emergency', [])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No autenticado');
        $this->postJson('/api/v1/provider/service-orders/'.$orden->id.'/accept', [])->assertStatus(401);
        $this->postJson('/api/v1/provider/service-orders/'.$orden->id.'/onsite-estimate', [])->assertStatus(401);
        $this->postJson('/api/v1/customer/service-orders/'.$orden->id.'/approve-onsite-estimate', [])->assertStatus(401);
    }

    public function test_cliente_no_puede_aceptar_ni_estimar_como_prestador(): void
    {
        $orden = $this->urgencia();

        $this->actingAs($this->customer)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/accept', [])
            ->assertStatus(403);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/onsite-estimate', ['additional_cost' => 1500])
            ->assertStatus(403);
    }

    public function test_prestador_no_puede_aprobar_estimados_de_otro_usuario(): void
    {
        $orden = $this->urgencia([
            'status' => ServiceOrder::STATUS_QUOTED,
            'additional_cost' => 1500,
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/approve-onsite-estimate', [])
            ->assertStatus(404);

        $this->actingAs($this->otherProviderUser)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/approve-onsite-estimate', [])
            ->assertStatus(404);
    }

    // ------------------------------------------------------ alta de urgencia

    public function test_crea_urgencia_en_pool_del_subgrupo_sin_precio(): void
    {
        $response = $this->enviarUrgencia();

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.order_type', 'EMERGENCY')
            ->assertJsonPath('data.order.status', 'REQUESTED')
            ->assertJsonPath('data.order.status_label', 'Solicitado')
            ->assertJsonPath('data.order.escrow_status', 'HOLDING')
            ->assertJsonPath('data.order.visit_fee', '0.00')
            ->assertJsonPath('data.order.total_amount', '0.00')
            ->assertJsonPath('data.order.merchant', null)
            ->assertJsonPath('data.order.sub_group.description', 'Gasistas');

        $orden = ServiceOrder::query()->latest('id')->first();

        $this->assertDatabaseHas('service_orders', [
            'id' => $orden->id,
            'customer_id' => $this->customer->id,
            'merchant_id' => null,
            'sub_group_id' => $this->subgroup->id,
            'order_type' => 'EMERGENCY',
            'status' => 'REQUESTED',
            'escrow_status' => 'HOLDING',
            'visit_fee' => '0.00',
            'total_amount' => '0.00',
        ]);

        $this->assertNotNull($orden->photo_path);
        $this->assertNotNull($orden->audio_path);
        $this->assertFileExists(public_path('images/servicios/'.$orden->photo_path));
        $this->assertFileExists(public_path('images/servicios/'.$orden->audio_path));
    }

    public function test_crea_urgencia_sin_ubicacion_y_sin_usar_la_direccion_del_perfil(): void
    {
        $pais = Country::create(['name' => 'Argentina', 'iso_code' => 'AR']);

        $this->customer->addresses()->create([
            'country_id' => $pais->id,
            'street' => 'Av. Maipú',
            'number' => '1234',
            'latitude' => -34.6787,
            'longitude' => -58.4734,
            'is_primary' => true,
        ]);

        // La ventana de urgencia no pide ni envia ubicacion: la orden se crea
        // sin coordenadas, sin importar las de la direccion del perfil.
        $this->actingAs($this->customer)
            ->post('/api/v1/customer/service-orders/emergency', [
                'sub_group_id' => $this->subgroup->id,
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(201);

        $orden = ServiceOrder::query()->latest('id')->first();

        $this->assertNull($orden->latitude);
        $this->assertNull($orden->longitude);
    }

    public function test_rechaza_urgencia_sin_foto(): void
    {
        $this->actingAs($this->customer)
            ->post('/api/v1/customer/service-orders/emergency', [
                'sub_group_id' => $this->subgroup->id,
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);

        $this->enviarUrgencia(['latitude' => 999])->assertStatus(422);
    }

    public function test_rechaza_urgencia_si_el_subgrupo_no_pertenece_a_hogar(): void
    {
        $otroGrupo = Group::firstOrCreate(['description' => 'Comercio & Gastronomia']);
        $ajeno = SubGroup::firstOrCreate(
            ['description' => 'Kioscos', 'group_id' => $otroGrupo->id]
        );

        $this->enviarUrgencia(['sub_group_id' => $ajeno->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sub_group_id']);

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_rechaza_urgencia_con_subgrupo_inexistente(): void
    {
        $this->enviarUrgencia(['sub_group_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sub_group_id']);

        $this->assertDatabaseCount('service_orders', 0);
    }

    // ------------------------------------------------------------- despacho

    public function test_prestador_del_rubro_acepta_el_pool_y_recibe_tracking(): void
    {
        $orden = $this->urgencia(['description' => 'Pérdida de gas en la cocina']);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/accept', [])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.status', 'PROVIDER_DISPATCHED')
            ->assertJsonPath('data.order.merchant.id', $this->provider->id)
            ->assertJsonPath('data.tracking.order_id', $orden->id)
            ->assertJsonPath('data.tracking.status', 'PROVIDER_DISPATCHED')
            ->assertJsonPath('data.tracking.provider.business_name', 'Gasista Uno')
            ->assertJsonPath('data.tracking.problem.description', 'Pérdida de gas en la cocina')
            ->assertJsonPath('data.tracking.location.latitude', '-34.6037000');

        $this->assertDatabaseHas('service_orders', [
            'id' => $orden->id,
            'status' => 'PROVIDER_DISPATCHED',
            'merchant_id' => $this->provider->id,
        ]);
    }

    public function test_prestador_de_otro_rubro_no_puede_tomar_el_pool(): void
    {
        $orden = $this->urgencia();

        $this->actingAs($this->outsiderUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/accept', [])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Tu comercio no ofrece este servicio.');

        $this->assertNull($orden->fresh()->merchant_id);
        $this->assertSame(ServiceOrder::STATUS_REQUESTED, $orden->fresh()->status);
    }

    public function test_dos_prestadores_no_pueden_tomar_la_misma_urgencia(): void
    {
        $orden = $this->urgencia();

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/accept', [])
            ->assertStatus(200);

        // El segundo del mismo rubro pierde la carrera: ya esta tomada.
        $this->actingAs($this->otherProviderUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/accept', [])
            ->assertStatus(409)
            ->assertJsonPath('message', 'La solicitud ya fue tomada por otro profesional.');

        $this->assertSame($this->provider->id, (int) $orden->fresh()->merchant_id);
    }

    public function test_aceptar_en_estado_invalido_es_409(): void
    {
        $ordenEnCurso = $this->urgencia(['status' => ServiceOrder::STATUS_IN_PROGRESS]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$ordenEnCurso->id.'/accept', [])
            ->assertStatus(409)
            ->assertJsonPath('status', 'IN_PROGRESS');
    }

    // ------------------------------------------------- estimado in-sitio

    public function test_tecnico_carga_estimado_y_pasa_a_presupuestado(): void
    {
        $orden = $this->urgencia([
            'merchant_id' => $this->provider->id,
            'status' => ServiceOrder::STATUS_PROVIDER_DISPATCHED,
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/onsite-estimate', [
                'additional_cost' => 1500,
                'details' => 'Cambio de mezclador: repuesto y mano de obra',
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.status', 'QUOTED')
            ->assertJsonPath('data.order.additional_cost', '1500.00');

        $this->assertDatabaseHas('service_orders', [
            'id' => $orden->id,
            'status' => 'QUOTED',
            'additional_cost' => '1500.00',
            'estimate_details' => 'Cambio de mezclador: repuesto y mano de obra',
        ]);
    }

    public function test_estimado_rechazado_por_estado_o_costo_invalido(): void
    {
        $orden = $this->urgencia([
            'merchant_id' => $this->provider->id,
            'status' => ServiceOrder::STATUS_REQUESTED,
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$orden->id.'/onsite-estimate', ['additional_cost' => 1500])
            ->assertStatus(409);

        $enLugar = $this->urgencia([
            'merchant_id' => $this->provider->id,
            'status' => ServiceOrder::STATUS_PROVIDER_DISPATCHED,
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$enLugar->id.'/onsite-estimate', ['additional_cost' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['additional_cost']);
    }

    // ------------------------------------------------------ aprobacion 1-click

    public function test_cliente_aprueba_estimado_y_el_escrow_suma_el_total(): void
    {
        $orden = $this->urgencia([
            'status' => ServiceOrder::STATUS_QUOTED,
            'additional_cost' => 1500,
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$orden->id.'/approve-onsite-estimate', [])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.status', 'IN_PROGRESS')
            ->assertJsonPath('data.order.escrow_status', 'HOLDING')
            ->assertJsonPath('data.order.total_amount', '1500.00');

        $this->assertDatabaseHas('service_orders', [
            'id' => $orden->id,
            'status' => 'IN_PROGRESS',
            'escrow_status' => 'HOLDING',
            'total_amount' => '1500.00',
        ]);
    }

    public function test_aprobar_fall_si_no_hay_estimado_pendiente(): void
    {
        $sinEstimado = $this->urgencia(['status' => ServiceOrder::STATUS_REQUESTED]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$sinEstimado->id.'/approve-onsite-estimate', [])
            ->assertStatus(409);

        $sinCosto = $this->urgencia([
            'status' => ServiceOrder::STATUS_QUOTED,
            'additional_cost' => 0,
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$sinCosto->id.'/approve-onsite-estimate', [])
            ->assertStatus(409);
    }

    // ------------------------------------------------------------- e2e

    public function test_flujo_completo_de_urgencia(): void
    {
        $creada = $this->enviarUrgencia();
        $creada->assertStatus(201);
        $ordenId = $creada->json('data.order.id');

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$ordenId.'/accept', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'PROVIDER_DISPATCHED')
            ->assertJsonPath('data.order.merchant.id', $this->provider->id)
            ->assertJsonPath('data.tracking.order_id', $ordenId);

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$ordenId.'/onsite-estimate', [
                'additional_cost' => 1500,
                'details' => 'Repuesto y mano de obra',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'QUOTED');

        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/'.$ordenId.'/approve-onsite-estimate', [])
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', 'IN_PROGRESS')
            ->assertJsonPath('data.order.total_amount', '1500.00');

        $this->assertDatabaseHas('service_orders', [
            'id' => $ordenId,
            'status' => 'IN_PROGRESS',
            'escrow_status' => 'HOLDING',
            'merchant_id' => $this->provider->id,
            'visit_fee' => '0.00',
            'additional_cost' => '1500.00',
            'total_amount' => '1500.00',
        ]);
    }

    // ------------------------------------------------------------- helpers

    private function enviarUrgencia(array $overrides = [])
    {
        $data = array_merge([
            'sub_group_id' => $this->subgroup->id,
            'latitude' => -34.6037,
            'longitude' => -58.3816,
            'description' => 'Pérdida de gas en la cocina',
            'photo' => UploadedFile::fake()->image('problema.jpg'),
            'audio' => UploadedFile::fake()->create('nota.mp3', 40, 'audio/mpeg'),
        ], $overrides);

        return $this->actingAs($this->customer)
            ->post('/api/v1/customer/service-orders/emergency', $data, ['Accept' => 'application/json']);
    }

    private function urgencia(array $overrides = []): ServiceOrder
    {
        return ServiceOrder::create(array_merge([
            'customer_id' => $this->customer->id,
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
