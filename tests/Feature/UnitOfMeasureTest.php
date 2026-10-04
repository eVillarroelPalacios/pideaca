<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Module;
use App\Models\Page;
use App\Models\Product;
use App\Models\Provider;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitOfMeasureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private UnitOfMeasure $kilogramo;

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
        $pagina = Page::firstOrCreate(['url' => 'unidades-medida'], [
            'description' => 'Unid. Medidas',
            'module_id' => $modulo->id,
        ]);
        $this->admin->pages()->syncWithoutDetaching([$pagina->id]);

        $this->kilogramo = UnitOfMeasure::create([
            'name' => 'Kilogramo',
            'symbol' => 'kg',
            'base_conversion_factor' => 1,
            'is_integer_only' => false,
        ]);
    }

    public function test_index_returns_the_catalog_with_usage_counts(): void
    {
        $units = $this->actingAs($this->admin)
            ->getJson('/units-of-measure')
            ->assertOk()
            ->json();

        $listed = collect($units)->firstWhere('name', 'Kilogramo');

        $this->assertSame($this->kilogramo->id, $listed['id']);
        $this->assertSame('kg', $listed['symbol']);
        $this->assertSame(0, $listed['products_count']);
        $this->assertSame(0, $listed['supplies_count']);
        $this->assertSame(0, $listed['inventory_movements_count']);
    }

    public function test_store_and_update_handle_the_unit_fields(): void
    {
        $created = $this->actingAs($this->admin)
            ->postJson('/units-of-measure', [
                'name' => 'Litro',
                'symbol' => 'L',
                'base_conversion_factor' => 1,
                'is_integer_only' => false,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unit.is_integer_only', false);

        $id = $created->json('unit.id');

        $this->actingAs($this->admin)
            ->putJson("/units-of-measure/{$id}", [
                'name' => 'Litro',
                'symbol' => 'lt',
                'base_conversion_factor' => 1,
                'is_integer_only' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unit.symbol', 'lt')
            ->assertJsonPath('unit.is_integer_only', true);

        $this->assertNotNull(UnitOfMeasure::find($this->kilogramo->id));
    }

    public function test_store_rejects_duplicated_name_and_symbol(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/units-of-measure', [
                'name' => 'Kilogramo',
                'symbol' => 'gr',
                'base_conversion_factor' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->actingAs($this->admin)
            ->postJson('/units-of-measure', [
                'name' => 'Gramo',
                'symbol' => 'kg',
                'base_conversion_factor' => 0.001,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['symbol']);
    }

    public function test_unit_used_by_a_product_cannot_be_deleted(): void
    {
        $dueno = User::create([
            'name' => 'Comercio Unidades',
            'email' => 'comercio-unidades@example.com',
            'password' => 'password',
        ]);

        $provider = Provider::create([
            'user_id' => $dueno->id,
            'business_name' => 'Comercio Unidades',
        ]);

        $category = Category::create([
            'provider_id' => $provider->id,
            'name' => 'Verduras',
        ]);

        Product::create([
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'name' => 'Papa',
            'price' => 1000,
            'unit_of_measure_id' => $this->kilogramo->id,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/units-of-measure/{$this->kilogramo->id}")
            ->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No se puede eliminar la unidad porque la usan productos.');

        $this->assertNotNull(UnitOfMeasure::find($this->kilogramo->id));
    }

    public function test_unused_unit_can_be_deleted(): void
    {
        $this->actingAs($this->admin)
            ->deleteJson("/units-of-measure/{$this->kilogramo->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(UnitOfMeasure::find($this->kilogramo->id));
    }

    public function test_dashboard_renders_the_units_section(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertOk()
            ->assertSee('id="dash-unidades-medida"', false)
            ->assertSee('id="uom-table"', false)
            ->assertSee('function loadUnitsOfMeasure()', false)
            ->assertSee('function submitUnit(e)', false);
    }
}
