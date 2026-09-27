<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubGroupGridTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $module = Module::firstOrCreate(['description' => 'Administrar'], ['icon' => null]);
        $page = Page::firstOrCreate(
            ['url' => 'sub-grupos'],
            ['description' => 'Sub Grupos', 'module_id' => $module->id]
        );

        $this->admin = User::create([
            'name' => 'Admin Sub Grupos',
            'email' => 'admin.subgrupos@pideaca.com',
            'password' => 'password',
        ]);
        $this->admin->pages()->syncWithoutDetaching([$page->id]);
    }

    public function test_subgroup_grid_renders_the_group_filter_combo(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertOk()
            ->assertSee('id="subgroup-group-filter"', false)
            ->assertSee('<option value="">Todos los grupos</option>', false)
            ->assertSee('function loadSubGroupFilter()', false)
            ->assertSee('id="subgroup-search"', false);
    }

    public function test_subgroup_grid_filters_by_text_and_selected_group(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertOk()
            ->assertSee("document.getElementById('subgroup-group-filter')", false)
            ->assertSee('String(s.group_id) === groupId', false)
            ->assertSee('function applySubGroupFilters(', false)
            ->assertSee('function filterSubGroups()', false);
    }

    public function test_subgroup_grid_applies_the_filters_when_the_grid_loads(): void
    {
        // El pintado post-carga y post-recarga pasa por los filtros: sin esto
        // la grilla se refrescaba completa ignorando el combo y el buscador.
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertOk()
            ->assertSee('Promise.all([', false)
            ->assertSee('applySubGroupFilters(false);', false)
            ->assertSee('loadSubGroupFilter(),', false);
    }

    public function test_subgroup_grid_does_not_render_the_group_column(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertOk()
            ->assertDontSee('>Grupo</th>', false)
            ->assertDontSee('var groupName =', false);
    }
}
