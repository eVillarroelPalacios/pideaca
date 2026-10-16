<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Module;
use App\Models\Page;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\ServiceType;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Solicitar Servicio con el mismo esquema que los comercios: el cliente busca
 * y elige una tarjeta de prestador; la orden se le asigna directo al elegido
 * (merchant_id seteado) en vez de entrar al pool del rubro.
 */
class ServiceProviderBrowseTest extends TestCase
{
    use RefreshDatabase;

    private User $providerUser;

    private Provider $provider;

    private User $otherProviderUser;

    private Provider $otherProvider;

    private User $customer;

    private SubGroup $subgroup;

    private Page $paginaSolicitar;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = TypeUser::create(['description' => 'Prestador']);
        $cliente = TypeUser::create(['description' => 'Cliente']);
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
            'name' => 'Gasista Uno',
            'email' => 'gasista@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);
        $this->provider = Provider::create([
            'user_id' => $this->providerUser->id,
            'business_name' => 'Gasista Uno',
            'is_active' => true,
            'zone' => 'Caballito',
        ]);

        $this->otherProviderUser = User::create([
            'name' => 'Electricista Dos',
            'email' => 'electricista@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);
        $this->otherProvider = Provider::create([
            'user_id' => $this->otherProviderUser->id,
            'business_name' => 'Electricista Dos',
            'is_active' => true,
            'zone' => 'Flores',
        ]);

        $this->customer = User::create([
            'name' => 'Maria Cliente',
            'email' => 'maria@example.com',
            'type_user_id' => $cliente->id,
            'password' => 'password',
        ]);
        $this->customer->pages()->attach($this->paginaSolicitar->id);

        $group = Group::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar']);
        $this->subgroup = SubGroup::firstOrCreate(
            ['description' => 'Gasistas', 'group_id' => $group->id]
        );
        $electricidad = SubGroup::firstOrCreate(
            ['description' => 'Electricistas', 'group_id' => $group->id]
        );

        $this->provider->subgroups()->attach($this->subgroup->id);
        $this->otherProvider->subgroups()->attach($electricidad->id);

        $emergencia = ServiceType::firstOrCreate(
            ['code' => 'EMERGENCY'],
            ['name' => 'Urgencias 24/7', 'sort_order' => 1, 'is_active' => true]
        );
        $cita = ServiceType::firstOrCreate(
            ['code' => 'SCHEDULED'],
            ['name' => 'Cita programada', 'sort_order' => 2, 'is_active' => true]
        );
        $presupuesto = ServiceType::firstOrCreate(
            ['code' => 'QUOTE'],
            ['name' => 'Presupuesto', 'sort_order' => 3, 'is_active' => true]
        );

        // Gasista ofrece urgencia y presupuesto; electricista solo cita.
        $this->provider->serviceTypes()->attach([$emergencia->id, $presupuesto->id]);
        $this->otherProvider->serviceTypes()->attach([$cita->id]);
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
        } finally {
            parent::tearDown();
        }
    }

    public function test_invitado_recibe_401_en_el_browse(): void
    {
        $this->getJson('/api/v1/service-providers?type=EMERGENCY')->assertStatus(401);
    }

    public function test_browse_exige_un_tipo_de_servicio_valido(): void
    {
        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);

        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=NOPE')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_browse_devuelve_solo_prestadores_activos_del_tipo_pedido(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=EMERGENCY')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.providers');

        $provider = $response->json('data.providers.0');
        $this->assertSame($this->provider->id, $provider['id']);
        $this->assertSame('Gasista Uno', $provider['business_name']);
        $this->assertSame('Caballito', $provider['zone']);
        $this->assertTrue(collect($provider['service_types'])->contains(fn ($t) => $t['code'] === 'EMERGENCY'));
        $this->assertSame('Gasistas', $provider['subgroups'][0]['description']);
    }

    public function test_browse_filtra_por_tipo_y_por_texto(): void
    {
        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=SCHEDULED')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.providers')
            ->assertJsonPath('data.providers.0.id', $this->otherProvider->id);

        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=EMERGENCY&search=Electricista')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data.providers');

        $this->actingAs($this->customer)
            ->getJson('/api/v1/service-providers?type=EMERGENCY&search=Gasista')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.providers');
    }

    public function test_urgencia_directa_asigna_al_prestador_elegido(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/emergency', [
                'provider_id' => $this->provider->id,
                'latitude' => -34.61,
                'longitude' => -58.43,
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order.merchant_id', $this->provider->id)
            ->assertJsonPath('data.order.sub_group.id', $this->subgroup->id)
            ->assertJsonPath('data.order.status', ServiceOrder::STATUS_REQUESTED);

        $this->assertDatabaseHas('service_orders', [
            'id' => $response->json('data.order.id'),
            'merchant_id' => $this->provider->id,
            'sub_group_id' => $this->subgroup->id,
        ]);
    }

    public function test_cita_directa_asigna_al_prestador_elegido(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/scheduled', [
                'provider_id' => $this->otherProvider->id,
                'scheduled_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'fulfillment' => 'AT_HOME',
                'payment_method' => 'efectivo',
                'payment_mode' => 'DEPOSIT',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.order.merchant_id', $this->otherProvider->id)
            ->assertJsonPath('data.order.status', ServiceOrder::STATUS_REQUESTED);
    }

    public function test_presupuesto_directo_asigna_al_prestador_elegido(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/quote', [
                'provider_id' => $this->provider->id,
                'details' => 'Arreglar una perdida de gas',
                'photos' => [UploadedFile::fake()->image('trabajo.jpg')],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.order.merchant_id', $this->provider->id)
            ->assertJsonPath('data.order.status', ServiceOrder::STATUS_REQUESTED);
    }

    public function test_no_se_puede_elegir_un_prestador_que_no_ofrece_el_tipo(): void
    {
        // Electricista no ofrece EMERGENCY.
        $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/emergency', [
                'provider_id' => $this->otherProvider->id,
                'latitude' => -34.61,
                'longitude' => -58.43,
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_el_prestador_asignado_puede_aceptar_su_urgencia_directa(): void
    {
        $order = $this->actingAs($this->customer)
            ->postJson('/api/v1/customer/service-orders/emergency', [
                'provider_id' => $this->provider->id,
                'latitude' => -34.61,
                'longitude' => -58.43,
                'photo' => UploadedFile::fake()->image('problema.jpg'),
            ])
            ->assertStatus(201)
            ->json('data.order');

        $this->actingAs($this->providerUser)
            ->postJson('/api/v1/provider/service-orders/'.$order['id'].'/accept')
            ->assertStatus(200)
            ->assertJsonPath('data.order.status', ServiceOrder::STATUS_PROVIDER_DISPATCHED)
            ->assertJsonPath('data.order.merchant_id', $this->provider->id);
    }

    public function test_el_browse_respeta_el_middleware_de_la_pagina(): void
    {
        $sinPagina = User::create([
            'name' => 'Sin Pagina',
            'email' => 'sinpagina@example.com',
            'type_user_id' => TypeUser::first()->id,
            'password' => 'password',
        ]);

        $this->actingAs($sinPagina)
            ->getJson('/api/v1/service-providers?type=EMERGENCY')
            ->assertStatus(403);
    }
}
