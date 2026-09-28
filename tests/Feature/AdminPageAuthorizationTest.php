<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los controladores de administracion solo pedian estar autenticado, asi que
 * cualquier usuario logged in (incluso un Cliente) podia administrar modulos,
 * paginas, tipos de usuario y cualquier cuenta. Estos tests fijan que un
 * usuario sin la pagina asignada no entra, y que uno que si la tiene entra.
 */
class AdminPageAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $cliente;

    private User $admin;

    private Page $modulos;

    private Page $usuarios;

    protected function setUp(): void
    {
        parent::setUp();

        $clienteTipo = TypeUser::firstOrCreate(['description' => 'Cliente']);
        $adminTipo = TypeUser::firstOrCreate(['description' => 'Admin']);

        $this->cliente = User::create([
            'name' => 'Cliente Sin Permisos',
            'email' => 'cliente.sin.permisos@pideaca.com',
            'password' => 'password',
            'type_user_id' => $clienteTipo->id,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Con Permisos',
            'email' => 'admin.con.permisos@pideaca.com',
            'password' => 'password',
            'type_user_id' => $adminTipo->id,
        ]);

        $modulo = Module::firstOrCreate(['description' => 'Administrar']);

        $this->modulos = Page::firstOrCreate(['url' => 'modulos'], ['description' => 'Modulos', 'module_id' => $modulo->id]);
        $this->usuarios = Page::firstOrCreate(['url' => 'usuarios'], ['description' => 'Usuarios', 'module_id' => $modulo->id]);
    }

    public function test_usuario_sin_la_pagina_no_puede_listar_modulos(): void
    {
        $this->actingAs($this->cliente)->getJson('/modules')->assertForbidden();
    }

    public function test_usuario_sin_la_pagina_no_puede_crear_modulos(): void
    {
        $this->actingAs($this->cliente)
            ->postJson('/modules', ['description' => 'Modulo inyectado'])
            ->assertForbidden();

        $this->assertDatabaseMissing('modules', ['description' => 'Modulo inyectado']);
    }

    public function test_usuario_sin_la_pagina_no_puede_listar_usuarios(): void
    {
        $this->actingAs($this->cliente)->getJson('/admin/users')->assertForbidden();
    }

    public function test_usuario_sin_la_pagina_no_puede_cambiar_el_tipo_de_otro_usuario(): void
    {
        $this->actingAs($this->cliente)
            ->putJson('/admin/users/'.$this->admin->id, [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'type_user_id' => $this->cliente->type_user_id,
            ])
            ->assertForbidden();

        $this->assertNotSame(
            $this->cliente->type_user_id,
            $this->admin->fresh()->type_user_id
        );
    }

    public function test_usuario_con_la_pagina_asignada_si_puede_administrarla(): void
    {
        $this->admin->pages()->syncWithoutDetaching([$this->modulos->id, $this->usuarios->id]);

        $this->actingAs($this->admin)->getJson('/modules')->assertOk();
        $this->actingAs($this->admin)->getJson('/admin/users')->assertOk();
    }

    public function test_la_plantilla_del_tipo_tambien_da_acceso(): void
    {
        $this->admin->typeUser->assignedPages()->syncWithoutDetaching([$this->modulos->id]);

        $this->actingAs($this->admin)->getJson('/modules')->assertOk();
    }

    public function test_visitante_sigue_recibiendo_401(): void
    {
        $this->getJson('/modules')->assertUnauthorized();
        $this->getJson('/admin/users')->assertUnauthorized();
    }

    public function test_una_pagina_sin_url_no_genera_un_enlace_muerto_en_el_menu(): void
    {
        $modulo = Module::firstOrCreate(['description' => 'Comercio & Gastronomía']);
        $sinUrl = Page::create([
            'description' => 'Gestionar solicitudes',
            'url' => null,
            'module_id' => $modulo->id,
        ]);

        $this->cliente->pages()->syncWithoutDetaching([$this->usuarios->id, $sinUrl->id]);

        $response = $this->actingAs($this->cliente)->get('/dashboard');

        $response->assertOk()
            ->assertDontSee("showDashSection('')", false)
            ->assertDontSee('id="dash-"', false)
            ->assertSee('Gestionar solicitudes');
    }
}
