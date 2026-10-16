<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Page;
use App\Models\Provider;
use App\Models\ServiceType;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El prestador con el Modulo B (pagina "Ordenes de Servicio") configura en su
 * perfil los tipos de servicio que ofrece (urgencias, citas, presupuestos).
 * Un prestador puede ofrecer uno o varios; la relacion vive en la tabla
 * pivote provider_service_types.
 */
class ProviderServiceTypesTest extends TestCase
{
    use RefreshDatabase;

    private TypeUser $prestadorType;

    private TypeUser $clienteType;

    private User $providerUser;

    private Provider $provider;

    private Page $paginaOrdenes;

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
        $this->paginaOrdenes = Page::firstOrCreate(
            ['description' => 'Ordenes de Servicio'],
            ['url' => 'ordenes-servicio', 'module_id' => $moduleB->id]
        );

        $this->providerUser = User::create([
            'name' => 'Proveedor Modulo B',
            'email' => 'prestador.b@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->providerUser->id,
            'business_name' => 'Proveedor Modulo B',
            'is_active' => true,
        ]);

        $this->providerUser->pages()->attach($this->paginaOrdenes->id);

        ServiceType::create(['code' => 'EMERGENCY', 'name' => 'Urgencias 24/7', 'sort_order' => 1, 'is_active' => true]);
        ServiceType::create(['code' => 'SCHEDULED', 'name' => 'Cita programada', 'sort_order' => 2, 'is_active' => true]);
        ServiceType::create(['code' => 'QUOTE', 'name' => 'Presupuesto', 'sort_order' => 3, 'is_active' => true]);
    }

    public function test_invitado_recibe_401(): void
    {
        $this->getJson('/profile/service-types')->assertStatus(401);
        $this->postJson('/profile/service-types/toggle', ['service_type_id' => 1])->assertStatus(401);
    }

    public function test_prestador_sin_el_modulo_b_recibe_403(): void
    {
        $sinModulo = User::create([
            'name' => 'Sin Modulo',
            'email' => 'sinmodulo@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);
        Provider::create([
            'user_id' => $sinModulo->id,
            'business_name' => 'Sin Modulo',
            'is_active' => true,
        ]);

        $this->actingAs($sinModulo)
            ->getJson('/profile/service-types')
            ->assertStatus(403);

        $this->actingAs($sinModulo)
            ->postJson('/profile/service-types/toggle', ['service_type_id' => 1])
            ->assertStatus(403);
    }

    public function test_catalogo_devuelve_los_tres_tipos_y_seleccion_vacia(): void
    {
        $response = $this->actingAs($this->providerUser)
            ->getJson('/profile/service-types')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'service_types');

        $codes = collect($response->json('service_types'))->pluck('code');
        $this->assertSame(['EMERGENCY', 'SCHEDULED', 'QUOTE'], $codes->all());
        $this->assertSame([], $response->json('selected'));
    }

    public function test_solo_devuelve_tipos_activos(): void
    {
        ServiceType::create(['code' => 'LEGACY', 'name' => 'Viejo', 'sort_order' => 9, 'is_active' => false]);

        $response = $this->actingAs($this->providerUser)
            ->getJson('/profile/service-types')
            ->assertStatus(200);

        $codes = collect($response->json('service_types'))->pluck('code');
        $this->assertNotContains('LEGACY', $codes->all());
        $this->assertCount(3, $response->json('service_types'));
    }

    public function test_prestador_activa_y_desactiva_un_tipo(): void
    {
        $tipo = ServiceType::where('code', 'EMERGENCY')->firstOrFail();

        $this->actingAs($this->providerUser)
            ->postJson('/profile/service-types/toggle', ['service_type_id' => $tipo->id])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('selected', [$tipo->id]);

        $this->assertDatabaseHas('provider_service_types', [
            'provider_id' => $this->provider->id,
            'service_type_id' => $tipo->id,
        ]);

        $this->actingAs($this->providerUser)
            ->postJson('/profile/service-types/toggle', ['service_type_id' => $tipo->id])
            ->assertStatus(200)
            ->assertJsonPath('selected', []);

        $this->assertDatabaseMissing('provider_service_types', [
            'provider_id' => $this->provider->id,
            'service_type_id' => $tipo->id,
        ]);
    }

    public function test_prestador_puede_ofrecer_varios_tipos(): void
    {
        $ids = ServiceType::orderBy('sort_order')->pluck('id')->all();

        foreach ($ids as $id) {
            $this->actingAs($this->providerUser)
                ->postJson('/profile/service-types/toggle', ['service_type_id' => $id])
                ->assertStatus(200);
        }

        $this->assertDatabaseCount('provider_service_types', 3);

        $response = $this->actingAs($this->providerUser)
            ->getJson('/profile/service-types')
            ->assertStatus(200);

        $this->assertEqualsCanonicalizing($ids, $response->json('selected'));
    }

    public function test_toggle_da_de_alta_el_prestador_si_no_existe(): void
    {
        $user = User::create([
            'name' => 'Recien Llegado',
            'email' => 'nuevo@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);
        $user->pages()->attach($this->paginaOrdenes->id);

        $tipo = ServiceType::where('code', 'QUOTE')->firstOrFail();

        $this->actingAs($user)
            ->postJson('/profile/service-types/toggle', ['service_type_id' => $tipo->id])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $provider = Provider::where('user_id', $user->id)->first();
        $this->assertNotNull($provider);
        $this->assertDatabaseHas('provider_service_types', [
            'provider_id' => $provider->id,
            'service_type_id' => $tipo->id,
        ]);
    }

    public function test_cliente_con_el_modulo_no_puede_configurar(): void
    {
        $cliente = User::create([
            'name' => 'Cliente Curioso',
            'email' => 'cliente.modulo@example.com',
            'type_user_id' => $this->clienteType->id,
            'password' => 'password',
        ]);
        $cliente->pages()->attach($this->paginaOrdenes->id);

        $tipo = ServiceType::where('code', 'EMERGENCY')->firstOrFail();

        $this->actingAs($cliente)
            ->postJson('/profile/service-types/toggle', ['service_type_id' => $tipo->id])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_tipo_inexistente_es_rechazado(): void
    {
        $this->actingAs($this->providerUser)
            ->postJson('/profile/service-types/toggle', ['service_type_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['service_type_id']);

        $this->actingAs($this->providerUser)
            ->postJson('/profile/service-types/toggle', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['service_type_id']);
    }

    public function test_dashboard_renderiza_el_panel_de_tipos_de_servicio(): void
    {
        $html = $this->actingAs($this->providerUser)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="service-types-list"', $html);
        $this->assertStringContainsString('Tipos de servicio', $html);
        $this->assertStringContainsString('/profile/service-types', $html);
    }
}
