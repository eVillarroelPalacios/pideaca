<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Provider;
use App\Models\TypeUser;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\UserStatus;
use Database\Seeders\UnitOfMeasureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alta y edicion de productos con "controlar stock". Sin esto el inventario no
 * se puede alimentar: el comercio no tenia forma de cargar un producto con su
 * stock inicial, minimo y unidad de medida.
 */
class ProviderProductsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Provider $provider;

    private Category $category;

    private User $otro;

    private Provider $otroProvider;

    private Category $otroCategory;

    private UnitOfMeasure $unidad;

    private string $url = '/api/v1/provider/products';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitOfMeasureSeeder::class);

        $prestador = TypeUser::create(['description' => 'Prestador']);
        UserStatus::create(['status' => 'Activo']);

        $this->unidad = UnitOfMeasure::where('name', 'Unidad')->first()
            ?? UnitOfMeasure::orderBy('id')->first();

        $this->owner = User::create([
            'name' => 'Comercio Productos',
            'email' => 'productos@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->owner->id,
            'business_name' => 'Comercio Productos',
        ]);

        $this->category = Category::create([
            'provider_id' => $this->provider->id,
            'name' => 'Almacen',
        ]);

        $this->otro = User::create([
            'name' => 'Comercio Ajeno',
            'email' => 'ajeno@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->otroProvider = Provider::create([
            'user_id' => $this->otro->id,
            'business_name' => 'Comercio Ajeno',
        ]);

        $this->otroCategory = Category::create([
            'provider_id' => $this->otroProvider->id,
            'name' => 'Almacen',
        ]);
    }

    // --- Permisos ---

    public function test_visitante_no_puede_crear_productos(): void
    {
        $this->postJson($this->url, [
            'name' => 'Empanada',
            'category_id' => $this->category->id,
            'price' => 1500,
        ])->assertUnauthorized();
    }

    public function test_usuario_sin_comercio_no_puede_crear_productos(): void
    {
        $cliente = User::create([
            'name' => 'Cliente',
            'email' => 'cliente-productos@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($cliente)
            ->postJson($this->url, [
                'name' => 'Empanada',
                'category_id' => $this->otroCategory->id,
                'price' => 1500,
            ])
            ->assertForbidden();
    }

    public function test_no_se_puede_crear_en_la_categoria_de_otro_comercio(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url, [
                'name' => 'Empanada',
                'category_id' => $this->otroCategory->id,
                'price' => 1500,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_no_se_puede_editar_un_producto_de_otro_comercio(): void
    {
        $ajeno = Product::create([
            'provider_id' => $this->otroProvider->id,
            'category_id' => $this->otroCategory->id,
            'name' => 'Ajeno',
            'price' => 100,
        ]);

        $this->actingAs($this->owner)
            ->putJson($this->url.'/'.$ajeno->id, ['name' => 'Secuestrado'])
            ->assertForbidden();

        $this->assertSame('Ajeno', $ajeno->fresh()->name);
    }

    // --- Alta con control de stock ---

    public function test_crea_un_producto_sin_control_de_stock(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url, [
                'name' => 'Empanada de carne',
                'category_id' => $this->category->id,
                'price' => 1500,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.track_stock', false)
            ->assertJsonPath('product.current_stock', 0);

        $this->assertDatabaseHas('products', [
            'provider_id' => $this->provider->id,
            'name' => 'Empanada de carne',
        ]);
    }

    public function test_crea_un_producto_con_stock_inicial_minimo_y_unidad(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url, [
                'name' => 'Harina 000',
                'category_id' => $this->category->id,
                'price' => 1200,
                'track_stock' => true,
                'initial_stock' => 30,
                'min_stock_alert' => 6,
                'unit_of_measure_id' => $this->unidad->id,
            ])
            ->assertCreated()
            ->assertJsonPath('product.track_stock', true)
            ->assertJsonPath('product.current_stock', 30)
            ->assertJsonPath('product.min_stock_alert', 6)
            ->assertJsonPath('product.unit_of_measure_id', $this->unidad->id);

        $product = Product::where('name', 'Harina 000')->sole();

        $this->assertEquals('30.000', $product->inventory->current_stock);
        $this->assertTrue((bool) $product->track_stock);

        $movimiento = InventoryMovement::where('product_id', $product->id)->sole();

        $this->assertSame('IN', $movimiento->movement_type);
        $this->assertEquals(30, (float) $movimiento->quantity);
    }

    public function test_control_de_stock_exige_unidad_y_minimo(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url, [
                'name' => 'Harina 000',
                'category_id' => $this->category->id,
                'price' => 1200,
                'track_stock' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['unit_of_measure_id', 'min_stock_alert']);
    }

    public function test_rechaza_carga_inicial_negativa(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url, [
                'name' => 'Harina 000',
                'category_id' => $this->category->id,
                'price' => 1200,
                'track_stock' => true,
                'initial_stock' => -4,
                'min_stock_alert' => 2,
                'unit_of_measure_id' => $this->unidad->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['initial_stock']);
    }

    public function test_exige_nombre_categoria_y_precio(): void
    {
        $this->actingAs($this->owner)
            ->postJson($this->url, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'category_id', 'price']);
    }

    // --- Edicion ---

    public function test_activa_el_seguimiento_desde_la_edicion(): void
    {
        $product = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->category->id,
            'name' => 'Queso',
            'price' => 3000,
        ]);

        $this->actingAs($this->owner)
            ->putJson($this->url.'/'.$product->id, [
                'track_stock' => true,
                'initial_stock' => 12,
                'min_stock_alert' => 3,
                'unit_of_measure_id' => $this->unidad->id,
            ])
            ->assertOk()
            ->assertJsonPath('product.track_stock', true)
            ->assertJsonPath('product.current_stock', 12);

        $this->assertTrue((bool) $product->fresh()->track_stock);
        $this->assertEquals('12.000', $product->fresh()->inventory->current_stock);
    }

    public function test_la_edicion_no_toca_el_seguimiento_si_no_viene_en_el_cuerpo(): void
    {
        $product = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->category->id,
            'name' => 'Queso',
            'price' => 3000,
            'track_stock' => true,
            'min_stock_alert' => 3,
            'unit_of_measure_id' => $this->unidad->id,
        ]);

        $this->actingAs($this->owner)
            ->putJson($this->url.'/'.$product->id, ['name' => 'Queso crema'])
            ->assertOk()
            ->assertJsonPath('product.name', 'Queso crema')
            ->assertJsonPath('product.track_stock', true)
            ->assertJsonPath('product.min_stock_alert', 3);

        $this->assertTrue((bool) $product->fresh()->track_stock);
    }

    public function test_la_edicion_suma_al_stock_y_lo_deja_asentado(): void
    {
        $product = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->category->id,
            'name' => 'Queso',
            'price' => 3000,
            'track_stock' => true,
            'min_stock_alert' => 3,
            'unit_of_measure_id' => $this->unidad->id,
        ]);

        ProductInventory::create([
            'product_id' => $product->id,
            'provider_id' => $this->provider->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'allow_negative_stock' => false,
        ]);

        $this->actingAs($this->owner)
            ->putJson($this->url.'/'.$product->id, [
                'track_stock' => true,
                'initial_stock' => 5,
                'min_stock_alert' => 3,
                'unit_of_measure_id' => $this->unidad->id,
            ])
            ->assertOk()
            ->assertJsonPath('product.current_stock', 15);

        $movimiento = InventoryMovement::where('product_id', $product->id)->sole();

        $this->assertSame('ADJUSTMENT', $movimiento->movement_type);
        $this->assertEquals(5, (float) $movimiento->quantity);
    }

    public function test_el_listado_devuelve_categorias_unidades_y_productos(): void
    {
        Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->category->id,
            'name' => 'Queso',
            'price' => 3000,
        ]);

        $this->actingAs($this->owner)
            ->getJson($this->url)
            ->assertOk()
            ->assertJsonPath('products.0.name', 'Queso')
            ->assertJsonPath('categories.0.name', 'Almacen')
            ->assertJsonPath('unit_of_measures.0.name', 'Unidad');
    }

    public function test_el_listado_no_muestra_productos_de_otros_comercios(): void
    {
        Product::create([
            'provider_id' => $this->otroProvider->id,
            'category_id' => $this->otroCategory->id,
            'name' => 'Ajeno',
            'price' => 100,
        ]);

        $this->actingAs($this->owner)
            ->getJson($this->url)
            ->assertOk()
            ->assertJsonCount(0, 'products');
    }
}
