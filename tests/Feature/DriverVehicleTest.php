<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Persistencia del vehiculo del repartidor (Terminal Central + Perfil).
 */
class DriverVehicleTest extends TestCase
{
    use RefreshDatabase;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $driverType = TypeUser::create(['description' => 'Repartidor']);
        $this->driver = User::factory()->create([
            'name' => 'Carlos Romero',
            'type_user_id' => $driverType->id,
        ]);

        $module = Module::firstOrCreate(['description' => 'Reparto']);
        $page = Page::firstOrCreate(
            ['url' => 'repartidor'],
            ['description' => 'Repartidor', 'module_id' => $module->id]
        );
        $this->driver->pages()->attach($page->id);
    }

    public function test_guarda_el_tipo_de_vehiculo_del_repartidor(): void
    {
        $this->actingAs($this->driver)
            ->patchJson('/api/driver/vehicle', ['vehicle_type' => 'auto'])
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'vehicle_type' => 'auto',
            ]);

        $this->assertSame('auto', $this->driver->refresh()->vehicle_type);
    }

    public function test_guarda_la_patente_en_mayusculas_y_permite_vaciarla(): void
    {
        $this->actingAs($this->driver)
            ->patchJson('/api/driver/vehicle', [
                'vehicle_type' => 'camioneta',
                'vehicle_plate' => 'ab123cd',
            ])
            ->assertOk()
            ->assertJsonPath('vehicle_plate', 'AB123CD');

        $this->assertSame('AB123CD', $this->driver->refresh()->vehicle_plate);

        $this->actingAs($this->driver)
            ->patchJson('/api/driver/vehicle', [
                'vehicle_type' => 'camioneta',
                'vehicle_plate' => '',
            ])
            ->assertOk()
            ->assertJsonPath('vehicle_plate', null);
    }

    public function test_rechaza_un_tipo_de_vehiculo_invalido(): void
    {
        $this->actingAs($this->driver)
            ->patchJson('/api/driver/vehicle', ['vehicle_type' => 'avion'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('vehicle_type');

        $this->assertNull($this->driver->refresh()->vehicle_type);
    }

    public function test_solo_los_repartidores_pueden_actualizar_su_vehiculo(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->patchJson('/api/driver/vehicle', ['vehicle_type' => 'auto'])
            ->assertStatus(403);

        $this->assertNull($customer->refresh()->vehicle_type);
    }

    public function test_el_dashboard_preselecciona_el_vehiculo_y_la_patente_guardados(): void
    {
        $this->driver->update([
            'vehicle_type' => 'bici',
            'vehicle_plate' => 'AB123CD',
        ]);

        $response = $this->actingAs($this->driver)->get('/dashboard?section=repartidor');

        $response->assertOk()
            ->assertSee('id="drv-vehicle"', false)
            ->assertSee('id="profile-vehicle"', false)
            ->assertSee('Mi Vehículo')
            ->assertSee('AB123CD');

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/<option value="bici"\s+selected>/', $html);
    }

    /**
     * Regresión: la tarjeta vivía dentro del panel "Contraseñas", donde nadie
     * la encontraba. Ahora el repartidor tiene su propia entrada "Mi Vehículo"
     * en el sidebar y ese panel es el que abre por defecto.
     */
    public function test_el_repartidor_ve_una_entrada_de_vehiculo_en_el_sidebar_y_el_panel_abre_por_defecto(): void
    {
        $html = $this->actingAs($this->driver)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="sidebar-link-vehiculo"', $html);
        $this->assertStringContainsString('>Mi Vehículo</span>', $html);
        $this->assertStringContainsString("data-panel=\"vehiculo\"", $html);
        $this->assertStringContainsString("var profilePanelInicial = 'vehiculo';", $html);

        // La tarjeta ya no vive dentro del panel de contraseñas.
        $cuentaPos = strpos($html, 'data-panel="cuenta">');
        $vehiculoPos = strpos($html, 'id="profile-vehicle"');
        $this->assertNotFalse($cuentaPos);
        $this->assertNotFalse($vehiculoPos);
        $this->assertLessThan($cuentaPos, $vehiculoPos, 'El vehículo debe estar en su propio panel, antes de Contraseñas.');
    }

    public function test_el_perfil_no_muestra_la_entrada_de_vehiculo_a_usuarios_que_no_son_repartidores(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('id="sidebar-link-vehiculo"', false)
            ->assertDontSee('id="profile-vehicle"', false);
    }
}
