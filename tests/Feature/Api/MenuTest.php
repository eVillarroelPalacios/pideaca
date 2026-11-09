<?php

namespace Tests\Feature\Api;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_requires_a_token(): void
    {
        $this->getJson('/api/mobile/v1/menu')->assertStatus(401);
    }

    public function test_returns_only_pages_assigned_to_the_user(): void
    {
        $client = $this->makeClient();

        $hogar = Module::create(['description' => 'Hogar']);
        $mercado = Module::create(['description' => 'Mercado']);

        $mine = Page::create(['description' => 'Solicitudes', 'url' => 'solicitar-servicio', 'module_id' => $hogar->id]);
        $notMine = Page::create(['description' => 'Mis servicios', 'url' => 'mis-servicios', 'module_id' => $hogar->id]);
        $otherModule = Page::create(['description' => 'Comercios', 'url' => 'comercios', 'module_id' => $mercado->id]);

        $client->pages()->sync([$mine->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/menu')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.first_page_url', 'solicitar-servicio');

        $modules = $response->json('data.modules');

        // Mercado queda afuera: no tiene páginas asignadas a este usuario.
        $this->assertCount(1, $modules);
        $this->assertSame('Hogar', $modules[0]['description']);
        $this->assertCount(1, $modules[0]['pages']);
        $this->assertSame($mine->id, $modules[0]['pages'][0]['id']);
        $this->assertSame('Solicitudes', $modules[0]['pages'][0]['description']);
        $this->assertSame('solicitar-servicio', $modules[0]['pages'][0]['url']);

        $this->assertNotContains($notMine->id, array_column($modules[0]['pages'], 'id'));
        $this->assertNotContains($otherModule->id, array_column($modules[0]['pages'], 'id'));
    }

    public function test_administrar_goes_last_and_first_page_url_skips_empty_urls(): void
    {
        $client = $this->makeClient();

        $admin = Module::create(['description' => 'Administrar']);
        $hogar = Module::create(['description' => 'Hogar']);

        $soon = Page::create(['description' => 'Novedades', 'url' => null, 'module_id' => $hogar->id]);
        $profile = Page::create(['description' => 'Mi Perfil', 'url' => 'perfil', 'module_id' => $admin->id]);

        $client->pages()->sync([$soon->id, $profile->id]);

        $response = $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/menu')
            ->assertOk();

        $modules = $response->json('data.modules');

        $this->assertSame(['Hogar', 'Administrar'], array_column($modules, 'description'));

        // La página sin url viene igual (la app la muestra como Próximamente).
        $this->assertNull($modules[0]['pages'][0]['url']);

        // first_page_url salta la página sin url.
        $this->assertSame('perfil', $response->json('data.first_page_url'));
    }

    public function test_empty_menu_returns_empty_modules_and_null_first_page(): void
    {
        $client = $this->makeClient();

        $this->actingAs($client, 'sanctum')
            ->getJson('/api/mobile/v1/menu')
            ->assertOk()
            ->assertJsonPath('data.modules', [])
            ->assertJsonPath('data.first_page_url', null);
    }

    private function makeClient(): User
    {
        $type = TypeUser::create(['description' => 'Cliente']);
        $status = UserStatus::create(['status' => 'Activo']);

        return User::create([
            'name' => 'Cliente Menú',
            'email' => 'menu@example.com',
            'type_user_id' => $type->id,
            'user_status_id' => $status->id,
            'password' => 'secret123',
        ]);
    }
}
