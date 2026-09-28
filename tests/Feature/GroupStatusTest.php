<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupStatus;
use App\Models\Module;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GroupStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Grupos',
            'email' => 'admin.grupos@pideaca.com',
            'password' => 'password',
        ]);

        $this->admin->pages()->syncWithoutDetaching([
            $this->pagina('estados-grupos')->id,
            $this->pagina('grupos')->id,
        ]);
    }

    /**
     * El permiso de los endpoints de administracion sale de la pagina que
     * maneja el menu, asi que el usuario de prueba necesita tenerla.
     */
    private function pagina(string $url): Page
    {
        $modulo = Module::firstOrCreate(['description' => 'Administrar']);

        return Page::firstOrCreate(['url' => $url], [
            'description' => $url,
            'module_id' => $modulo->id,
        ]);
    }

    // --- Catalogo ---

    public function test_group_statuses_table_is_created_with_the_two_catalog_rows(): void
    {
        $this->assertTrue(Schema::hasTable('group_statuses'));
        $this->assertTrue(Schema::hasColumns('group_statuses', ['id', 'description', 'created_at', 'updated_at']));

        $this->assertSame(
            ['Activo', 'Desactivo'],
            GroupStatus::orderBy('id')->pluck('description')->all()
        );
    }

    public function test_group_status_values_are_unique(): void
    {
        $this->expectException(QueryException::class);

        GroupStatus::create(['description' => 'Activo']);
    }

    // --- Relacion con groups ---

    public function test_groups_are_linked_to_a_status(): void
    {
        $this->assertTrue(Schema::hasColumn('groups', 'group_status_id'));

        $activo = GroupStatus::where('description', GroupStatus::STATUS_ACTIVE)->firstOrFail();
        $desactivo = GroupStatus::where('description', GroupStatus::STATUS_INACTIVE)->firstOrFail();

        $group = Group::create([
            'description' => 'Grupos de prueba',
            'group_status_id' => $activo->id,
        ]);

        $this->assertSame($activo->id, $group->groupStatus->id);
        $this->assertSame('Activo', $group->groupStatus->description);

        $group->update(['group_status_id' => $desactivo->id]);

        $this->assertSame('Desactivo', $group->fresh()->groupStatus->description);
        $this->assertSame([$group->id], $desactivo->groups()->pluck('id')->all());
    }

    public function test_group_status_is_optional_so_existing_rows_keep_working(): void
    {
        $group = Group::create(['description' => 'Grupo sin estado']);

        $this->assertNull($group->fresh()->group_status_id);
        $this->assertNull($group->groupStatus);
    }

    public function test_deleting_a_status_leaves_the_group_without_status(): void
    {
        $status = GroupStatus::create(['description' => 'Temporal']);
        $group = Group::create([
            'description' => 'Grupo de prueba',
            'group_status_id' => $status->id,
        ]);

        $status->delete();

        $this->assertNull($group->fresh()->group_status_id);
    }

    // --- Endpoint de grupos ---

    public function test_new_group_is_created_active_by_default(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/groups', ['description' => 'Grupo Nuevo']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('group.group_status.description', 'Activo');

        $this->assertSame(
            GroupStatus::STATUS_ACTIVE,
            Group::where('description', 'Grupo Nuevo')->firstOrFail()->groupStatus->description
        );
    }

    public function test_group_status_can_be_changed_and_kept(): void
    {
        $desactivo = GroupStatus::where('description', GroupStatus::STATUS_INACTIVE)->firstOrFail();
        $group = Group::create(['description' => 'Grupo A', 'group_status_id' => GroupStatus::where('description', 'Activo')->value('id')]);

        // Cambia el estado
        $this->actingAs($this->admin)
            ->putJson("/groups/{$group->id}", [
                'description' => 'Grupo A',
                'group_status_id' => $desactivo->id,
            ])
            ->assertOk()
            ->assertJsonPath('group.group_status.description', 'Desactivo');

        // Sin tocar el estado se conserva el actual
        $this->actingAs($this->admin)
            ->putJson("/groups/{$group->id}", ['description' => 'Grupo A Editado'])
            ->assertOk()
            ->assertJsonPath('group.group_status.description', 'Desactivo');

        $this->assertSame('Grupo A Editado', $group->fresh()->description);
    }

    public function test_endpoint_rejects_unknown_status(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/groups', ['description' => 'Grupo B', 'group_status_id' => 99999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['group_status_id']);
    }

    public function test_group_listing_includes_the_status(): void
    {
        $activo = GroupStatus::where('description', GroupStatus::STATUS_ACTIVE)->firstOrFail();

        Group::create(['description' => 'Grupo C', 'group_status_id' => $activo->id]);

        $groups = $this->actingAs($this->admin)
            ->getJson('/groups')
            ->assertOk()
            ->json();

        $listed = collect($groups)->firstWhere('description', 'Grupo C');

        $this->assertSame('Activo', $listed['group_status']['description']);
        $this->assertSame($activo->id, $listed['group_status_id']);
    }

    // --- Formulario en el dashboard ---

    public function test_group_form_renders_the_status_combo(): void
    {
        $module = Module::firstOrCreate(['description' => 'Administrar'], ['icon' => null]);
        $page = Page::firstOrCreate(
            ['url' => 'grupos'],
            ['description' => 'Grupos', 'module_id' => $module->id]
        );
        $this->admin->pages()->syncWithoutDetaching([$page->id]);

        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertOk()
            ->assertSee('id="group-status-select"', false)
            ->assertSee('id="group-status-error"', false)
            ->assertSee('loadGroupStatusOptions(', false)
            ->assertSee('fillGroupStatusSelect(', false)
            ->assertSee('/group-statuses');
    }
}
