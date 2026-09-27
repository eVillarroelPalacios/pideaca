<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupStatus;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\EstadosDeLosGruposPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EstadosDeLosGruposTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // El rollback de la transaccion no reinicia la secuencia de `users`,
        // asi que se resetea para que el admin sea siempre el usuario id=1
        // (el seeder asigna la pagina al usuario 1).
        DB::statement('ALTER SEQUENCE IF EXISTS users_id_seq RESTART WITH 1');

        // En una base limpia el primer usuario creado es el id=1.
        $this->admin = User::create([
            'name' => 'Ennio Villarroel',
            'email' => 'ennio@pideaca.com',
            'password' => 'password',
        ]);
    }

    // --- Pagina y asignacion ---

    public function test_seeder_creates_the_page_and_assigns_it_to_user_one(): void
    {
        $this->seed(EstadosDeLosGruposPageSeeder::class);

        $page = Page::where('url', 'estados-grupos')->firstOrFail();

        $this->assertSame('Estado de los grupos', $page->description);
        $this->assertSame('Administrar', $page->module->description);
        $this->assertTrue($this->admin->pages()->where('pages.id', $page->id)->exists());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(EstadosDeLosGruposPageSeeder::class);
        $this->seed(EstadosDeLosGruposPageSeeder::class);

        $this->assertSame(1, Page::where('url', 'estados-grupos')->count());
        $this->assertSame(1, $this->admin->pages()->where('url', 'estados-grupos')->count());
    }

    public function test_dashboard_renders_the_group_statuses_section_for_user_one(): void
    {
        $this->seed(EstadosDeLosGruposPageSeeder::class);

        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertOk()
            ->assertSee("showDashSection('estados-grupos')", false)
            ->assertSee('id="dash-estados-grupos"', false)
            ->assertSee('id="groupstatuses-tbody"', false)
            ->assertSee('loadGroupStatuses()', false)
            ->assertSee('Estado de los grupos');
    }

    // --- Controlador ---

    public function test_group_statuses_endpoints_require_authentication(): void
    {
        $this->getJson('/group-statuses')->assertStatus(401);
        $this->postJson('/group-statuses', ['description' => 'X'])->assertStatus(401);
    }

    public function test_index_returns_the_catalog_with_group_counts(): void
    {
        $group = Group::create([
            'description' => 'Grupo con estado',
            'group_status_id' => GroupStatus::where('description', 'Activo')->value('id'),
        ]);

        $statuses = $this->actingAs($this->admin)
            ->getJson('/group-statuses')
            ->assertOk()
            ->json();

        $this->assertSame(['Activo', 'Desactivo'], array_column($statuses, 'description'));

        $activo = collect($statuses)->firstWhere('description', 'Activo');
        $desactivo = collect($statuses)->firstWhere('description', 'Desactivo');

        $this->assertSame(1, $activo['groups_count']);
        $this->assertSame(0, $desactivo['groups_count']);
        $this->assertSame($group->id, GroupStatus::find($activo['id'])->groups()->value('id'));
    }

    public function test_store_update_and_destroy_a_group_status(): void
    {
        $created = $this->actingAs($this->admin)
            ->postJson('/group-statuses', ['description' => 'En revision'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('group_status.description', 'En revision');

        $id = $created->json('group_status.id');

        $this->actingAs($this->admin)
            ->putJson("/group-statuses/{$id}", ['description' => 'En pausa'])
            ->assertOk()
            ->assertJsonPath('group_status.description', 'En pausa');

        $this->actingAs($this->admin)
            ->deleteJson("/group-statuses/{$id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(GroupStatus::find($id));
    }

    public function test_status_in_use_by_groups_cannot_be_deleted(): void
    {
        $activo = GroupStatus::where('description', 'Activo')->firstOrFail();

        Group::create(['description' => 'Grupo Usador', 'group_status_id' => $activo->id]);

        $this->actingAs($this->admin)
            ->deleteJson("/group-statuses/{$activo->id}")
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No se puede eliminar el estado porque tiene grupos asociados.');

        $this->assertNotNull(GroupStatus::find($activo->id));
    }

    public function test_duplicate_and_unknown_descriptions_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/group-statuses', ['description' => 'Activo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);

        $this->actingAs($this->admin)
            ->postJson('/group-statuses', ['description' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }
}
