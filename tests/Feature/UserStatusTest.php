<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Page;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private UserStatus $activo;

    private UserStatus $inactivo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Ennio Villarroel',
            'email' => 'ennio@pideaca.com',
            'password' => 'password',
        ]);

        // El endpoint exige la pagina que maneja el menu.
        $modulo = Module::firstOrCreate(['description' => 'Administrar']);
        $pagina = Page::firstOrCreate(['url' => 'estados-usuarios'], [
            'description' => 'Estados de usuarios',
            'module_id' => $modulo->id,
        ]);
        $this->admin->pages()->syncWithoutDetaching([$pagina->id]);

        $this->activo = UserStatus::create(['status' => 'Activo']);
        $this->inactivo = UserStatus::create(['status' => 'Inactivo']);

        User::create([
            'name' => 'Ana Prueba',
            'email' => 'ana@pideaca.com',
            'password' => 'password',
            'user_status_id' => $this->activo->id,
        ]);
        User::create([
            'name' => 'Beto Prueba',
            'email' => 'beto@pideaca.com',
            'password' => 'password',
            'user_status_id' => $this->activo->id,
        ]);
        User::create([
            'name' => 'Caro Prueba',
            'email' => 'caro@pideaca.com',
            'password' => 'password',
            'user_status_id' => $this->inactivo->id,
        ]);
    }

    public function test_index_returns_the_catalog_with_user_counts(): void
    {
        $statuses = $this->actingAs($this->admin)
            ->getJson('/user-statuses')
            ->assertOk()
            ->json();

        $listed = collect($statuses)->firstWhere('status', 'Activo');
        $otro = collect($statuses)->firstWhere('status', 'Inactivo');

        $this->assertSame(2, $listed['users_count']);
        $this->assertSame(1, $otro['users_count']);
        $this->assertSame($this->activo->id, $listed['id']);

        // El admin sin estado asignado no suma en ninguno de los dos.
        $this->assertNull($this->admin->fresh()->user_status_id);
        $this->assertSame(3, array_sum(array_column($statuses, 'users_count')));
    }

    public function test_store_and_update_return_the_refreshed_user_count(): void
    {
        $created = $this->actingAs($this->admin)
            ->postJson('/user-statuses', ['status' => 'Bloqueado'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user_status.users_count', 0);

        $id = $created->json('user_status.id');

        $this->actingAs($this->admin)
            ->putJson("/user-statuses/{$id}", ['status' => 'Bloqueado 2'])
            ->assertOk()
            ->assertJsonPath('user_status.users_count', 0);
    }

    public function test_status_with_users_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin)
            ->deleteJson("/user-statuses/{$this->activo->id}")
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No se puede eliminar el estado porque tiene usuarios asociados.');

        $this->assertNotNull(UserStatus::find($this->activo->id));
    }

    public function test_dashboard_renders_the_users_column(): void
    {
        $module = Module::firstOrCreate(['description' => 'Administrar'], ['icon' => null]);
        $page = Page::firstOrCreate(
            ['url' => 'estados-usuarios'],
            ['description' => 'Estados de usuarios', 'module_id' => $module->id]
        );
        $this->admin->pages()->syncWithoutDetaching([$page->id]);

        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertOk()
            ->assertSee('id="userstatuses-table"', false)
            ->assertSee('>Usuarios</th>', false)
            ->assertSee('Number(s.users_count || 0)', false);
    }
}
