<?php

namespace Tests\Feature;

use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El panel vive en la raiz del proyecto: la URL /dashboard queda solo como
 * redireccion de compatibilidad para accesos guardados.
 */
class DashboardHomeRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_panel_vive_en_la_raiz_para_usuarios_autenticados(): void
    {
        $response = $this->actingAs($this->makeClient())->get('/');

        $response->assertOk()
            ->assertSee('PideAca - Mi Panel')
            ->assertSee('id="profile-sidebar"', false);
    }

    public function test_la_raiz_muestra_la_landing_publica_para_visitantes(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('PideAca')
            ->assertDontSee('id="profile-sidebar"', false);
    }

    public function test_la_url_vieja_del_panel_redirige_a_la_raiz(): void
    {
        $this->get('/dashboard')
            ->assertStatus(302)
            ->assertRedirect('/');
    }

    public function test_la_url_vieja_redirige_a_la_raiz_tambien_con_sesion(): void
    {
        $user = $this->makeClient();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertStatus(302)
            ->assertRedirect('/');

        // La query se conserva para no perder la seccion abierta.
        $this->actingAs($user)
            ->get('/dashboard?section=pedidos')
            ->assertStatus(302)
            ->assertRedirect('/?section=pedidos');
    }

    public function test_las_subrutas_directas_del_panel_viven_en_la_raiz(): void
    {
        // /repartidor y /ordenes-gestion entran al panel con su seccion abierta
        // desde la raiz del proyecto, sin el segmento /dashboard.
        $this->get('/repartidor')->assertRedirect();
        $this->get('/ordenes-gestion')->assertRedirect();
    }

    private function makeClient(): User
    {
        $type = TypeUser::firstOrCreate(['description' => 'Cliente']);

        return User::create([
            'name' => 'Cliente Raíz',
            'email' => 'cliente.raiz@example.com',
            'type_user_id' => $type->id,
            'password' => 'password',
        ]);
    }
}
