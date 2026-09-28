<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\ProductVariant;
use App\Models\Provider;
use App\Models\TypeUser;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\UserStatus;
use Database\Seeders\UnitOfMeasureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Estructura del catalogo que hasta ahora solo existia en el seeder:
 * variantes (Individual / Grande / Familiar), agregados (grupos de opciones y
 * sus opciones con precio extra), categorias editables y unidades medidas.
 */
class CatalogStructureTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Provider $provider;

    private Category $category;

    private Product $producto;

    private User $otro;

    private Provider $otroProvider;

    private Category $otroCategory;

    private Product $otroProducto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitOfMeasureSeeder::class);

        $prestador = TypeUser::create(['description' => 'Prestador']);
        UserStatus::create(['status' => 'Activo']);

        $this->owner = User::create([
            'name' => 'Comercio Estructura',
            'email' => 'estructura@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->owner->id,
            'business_name' => 'Comercio Estructura',
        ]);

        $this->category = Category::create([
            'provider_id' => $this->provider->id,
            'name' => 'Pizzas',
        ]);

        $this->producto = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->category->id,
            'name' => 'Pizza Fugazzeta',
            'price' => 9500,
        ]);

        $this->otro = User::create([
            'name' => 'Comercio Ajeno',
            'email' => 'ajeno-estructura@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->otroProvider = Provider::create([
            'user_id' => $this->otro->id,
            'business_name' => 'Comercio Ajeno',
        ]);

        $this->otroCategory = Category::create([
            'provider_id' => $this->otroProvider->id,
            'name' => 'Pizzas',
        ]);

        $this->otroProducto = Product::create([
            'provider_id' => $this->otroProvider->id,
            'category_id' => $this->otroCategory->id,
            'name' => 'Producto ajeno',
            'price' => 1000,
        ]);
    }

    // --- Variantes ---

    public function test_visitante_no_puede_crear_variantes(): void
    {
        $this->postJson('/api/v1/provider/products/'.$this->producto->id.'/variants', [
            'name' => 'Familiar',
            'price' => 15500,
        ])->assertUnauthorized();
    }

    public function test_no_puede_crear_variantes_en_un_producto_ajeno(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$this->otroProducto->id.'/variants', [
                'name' => 'Familiar',
                'price' => 15500,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_crea_edita_y_borra_variantes(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$this->producto->id.'/variants', [
                'name' => 'Individual',
                'price' => 6500,
            ])
            ->assertCreated()
            ->assertJsonPath('variant.name', 'Individual')
            ->assertJsonPath('variant.price', 6500);

        $variante = ProductVariant::where('product_id', $this->producto->id)->sole();

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/variants/'.$variante->id, [
                'name' => 'Personal',
                'price' => 7000,
                'is_available' => false,
            ])
            ->assertOk()
            ->assertJsonPath('variant.name', 'Personal')
            ->assertJsonPath('variant.price', 7000)
            ->assertJsonPath('variant.is_available', false);

        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/variants/'.$variante->id)
            ->assertOk();

        $this->assertDatabaseMissing('product_variants', ['id' => $variante->id]);
    }

    public function test_no_puede_editar_la_variante_de_otro_comercio(): void
    {
        $ajena = $this->otroProducto->variants()->create([
            'name' => 'Grande',
            'price' => 500,
        ]);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/variants/'.$ajena->id, ['price' => 1])
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/variants/'.$ajena->id)
            ->assertForbidden();

        $this->assertDatabaseHas('product_variants', ['id' => $ajena->id, 'price' => '500.00']);
    }

    public function test_la_variante_requiere_nombre_y_precio(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$this->producto->id.'/variants', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price']);
    }

    public function test_el_listado_de_productos_trae_las_variantes(): void
    {
        $this->producto->variants()->create(['name' => 'Individual', 'price' => 6500]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/products')
            ->assertOk()
            ->assertJsonPath('products.0.variants.0.name', 'Individual')
            ->assertJsonPath('products.0.variants.0.price', 6500)
            ->assertJsonPath('products.0.option_groups', []);
    }

    // --- Agregados (grupos de opciones y opciones) ---

    public function test_crea_un_grupo_con_opciones_y_las_devuelve(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$this->producto->id.'/option-groups', [
                'name' => 'Agregados',
                'min_choices' => 0,
                'max_choices' => 3,
            ])
            ->assertCreated()
            ->assertJsonPath('group.name', 'Agregados')
            ->assertJsonPath('group.max_choices', 3)
            ->assertJsonPath('group.options', []);

        $grupo = ProductOptionGroup::where('product_id', $this->producto->id)->sole();

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/option-groups/'.$grupo->id.'/options', [
                'name' => 'Muzzarella extra',
                'extra_price' => 900,
            ])
            ->assertCreated()
            ->assertJsonPath('option.extra_price', 900);

        $opcion = ProductOption::where('option_group_id', $grupo->id)->sole();

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/options/'.$opcion->id, ['is_available' => false])
            ->assertOk()
            ->assertJsonPath('option.is_available', false);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/products')
            ->assertOk()
            ->assertJsonPath('products.0.option_groups.0.options.0.name', 'Muzzarella extra')
            ->assertJsonPath('products.0.option_groups.0.options.0.is_available', false);
    }

    public function test_el_maximo_de_elecciones_no_puede_ser_menor_que_el_minimo(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$this->producto->id.'/option-groups', [
                'name' => 'Agregados',
                'min_choices' => 5,
                'max_choices' => 2,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['max_choices']);
    }

    public function test_al_borrar_el_grupo_se_borran_sus_opciones(): void
    {
        $grupo = $this->producto->optionGroups()->create([
            'name' => 'Extras',
            'min_choices' => 0,
            'max_choices' => 2,
        ]);

        $grupo->options()->create(['name' => 'Jamón crudo', 'extra_price' => 2400]);

        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/option-groups/'.$grupo->id)
            ->assertOk();

        $this->assertDatabaseMissing('product_option_groups', ['id' => $grupo->id]);
        $this->assertDatabaseCount('product_options', 0);
    }

    public function test_no_puede_tocar_agregados_de_otro_comercio(): void
    {
        $grupoAjeno = $this->otroProducto->optionGroups()->create([
            'name' => 'Agregados',
            'min_choices' => 0,
            'max_choices' => 3,
        ]);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/option-groups/'.$grupoAjeno->id.'/options', [
                'name' => 'Intruso',
                'extra_price' => 100,
            ])
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/option-groups/'.$grupoAjeno->id, ['name' => 'Cambiado'])
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/option-groups/'.$grupoAjeno->id)
            ->assertForbidden();

        $this->assertDatabaseHas('product_option_groups', ['id' => $grupoAjeno->id, 'name' => 'Agregados']);
    }

    public function test_al_borrar_el_producto_no_quedan_huerfanos(): void
    {
        $variante = $this->producto->variants()->create(['name' => 'Grande', 'price' => 11500]);
        $grupo = $this->producto->optionGroups()->create([
            'name' => 'Agregados',
            'min_choices' => 0,
            'max_choices' => 3,
        ]);
        $grupo->options()->create(['name' => 'Anchoas', 'extra_price' => 1500]);

        $this->producto->delete();

        $this->assertDatabaseMissing('product_variants', ['id' => $variante->id]);
        $this->assertDatabaseCount('product_options', 0);
    }

    // --- Categorías ---

    public function test_renombra_reordena_y_borra_una_categoria_vacia(): void
    {
        $vacia = Category::create([
            'provider_id' => $this->provider->id,
            'name' => 'Bebidas',
        ]);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/categories/'.$vacia->id, [
                'name' => 'Bebidas frías',
                'sort_order' => 5,
            ])
            ->assertOk()
            ->assertJsonPath('category.name', 'Bebidas frías')
            ->assertJsonPath('category.sort_order', 5)
            ->assertJsonPath('category.products_count', 0);

        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/categories/'.$vacia->id)
            ->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $vacia->id]);
    }

    public function test_no_puede_borrar_una_categoria_con_productos(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/categories/'.$this->category->id)
            ->assertStatus(422);

        $this->assertDatabaseHas('categories', ['id' => $this->category->id]);
        $this->assertDatabaseHas('products', ['id' => $this->producto->id]);
    }

    public function test_no_puede_renombrar_a_un_nombre_ya_usado(): void
    {
        Category::create(['provider_id' => $this->provider->id, 'name' => 'Bebidas']);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/categories/'.$this->category->id, ['name' => 'bebidas'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_no_puede_editar_las_categorias_de_otro_comercio(): void
    {
        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/categories/'.$this->otroCategory->id, ['name' => 'Secuestrada'])
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->deleteJson('/api/v1/provider/categories/'.$this->otroCategory->id)
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $this->otroCategory->id, 'name' => 'Pizzas']);
    }

    // --- Unidades de medida ---

    public function test_visitante_no_puede_ver_las_unidades(): void
    {
        $this->getJson('/api/v1/provider/units')->assertUnauthorized();
    }

    public function test_las_unidades_traen_el_conteo_de_productos_propios(): void
    {
        $unidad = UnitOfMeasure::orderBy('id')->first();

        $this->producto->update(['unit_of_measure_id' => $unidad->id]);
        $this->otroProducto->update(['unit_of_measure_id' => $unidad->id]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/units')
            ->assertOk()
            ->assertJsonPath('products_with_unit', 1);

        $unidadJson = collect($response->json('units'))->firstWhere('id', $unidad->id);

        $this->assertNotNull($unidadJson, 'La unidad cargada debe aparecer en el listado.');
        $this->assertSame(1, $unidadJson['products_count']);
        $this->assertSame($unidad->name, $unidadJson['name']);
    }
}
