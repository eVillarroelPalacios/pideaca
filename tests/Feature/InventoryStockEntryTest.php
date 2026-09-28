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
 * Carga y edicion manual de stock. Un producto bornia "sin seguimiento" y sin
 * fila en product_inventory, asi que el circuito tiene que permitir activarle
 * el seguimiento y cargar cantidades sin tener que armar movimientos a mano.
 */
class InventoryStockEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Provider $provider;

    private Category $category;

    private Product $product;

    private User $otro;

    private Provider $otroProvider;

    private Category $otroCategory;

    private Product $otroProduct;

    private UnitOfMeasure $unidad;

    private UnitOfMeasure $docena;

    private string $url = '/api/v1/provider/inventory/products';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitOfMeasureSeeder::class);

        $prestador = TypeUser::create(['description' => 'Prestador']);
        UserStatus::create(['status' => 'Activo']);

        $this->unidad = UnitOfMeasure::where('name', 'Unidad')->first()
            ?? UnitOfMeasure::orderBy('id')->first();
        $this->docena = UnitOfMeasure::where('name', 'Docena')->first();

        $this->owner = User::create([
            'name' => 'Comercio Stock',
            'email' => 'stock@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->owner->id,
            'business_name' => 'Comercio Stock',
            'has_inventory_control' => true,
        ]);

        $this->category = Category::create([
            'provider_id' => $this->provider->id,
            'name' => 'Almacen',
        ]);

        $this->product = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->category->id,
            'name' => 'Harina 000',
            'price' => 1200,
        ]);

        $this->otro = User::create([
            'name' => 'Comercio Otro',
            'email' => 'otro@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->otroProvider = Provider::create([
            'user_id' => $this->otro->id,
            'business_name' => 'Comercio Otro',
        ]);

        $this->otroCategory = Category::create([
            'provider_id' => $this->otroProvider->id,
            'name' => 'Almacen',
        ]);

        $this->otroProduct = Product::create([
            'provider_id' => $this->otroProvider->id,
            'category_id' => $this->otroCategory->id,
            'name' => 'Producto ajeno',
            'price' => 500,
        ]);
    }

    private function payload(Product $product): string
    {
        return $this->url.'/'.$product->id;
    }

    // --- Permisos ---

    public function test_visitante_no_puede_editar_el_stock(): void
    {
        $this->putJson($this->payload($this->product), [
            'track_stock' => true,
            'current_stock' => 10,
        ])->assertUnauthorized();
    }

    public function test_usuario_sin_comercio_no_puede_editar_el_stock(): void
    {
        $cliente = User::create([
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($cliente)
            ->putJson($this->payload($this->product), [
                'track_stock' => true,
                'current_stock' => 10,
            ])
            ->assertForbidden();
    }

    public function test_un_comercio_no_puede_editar_el_stock_de_otro(): void
    {
        $this->actingAs($this->owner)
            ->putJson($this->payload($this->otroProduct), [
                'track_stock' => true,
                'current_stock' => 10,
            ])
            ->assertForbidden();

        $this->assertNull(ProductInventory::where('product_id', $this->otroProduct->id)->first());
    }

    // --- Carga manual ---

    public function test_activa_el_seguimiento_y_carga_el_stock_de_un_producto_sin_inventario(): void
    {
        $this->assertFalse((bool) $this->product->track_stock);
        $this->assertNull($this->product->inventory);

        $response = $this->actingAs($this->owner)
            ->putJson($this->payload($this->product), [
                'track_stock' => true,
                'current_stock' => 25,
                'min_stock_alert' => 5,
                'unit_of_measure_id' => $this->unidad->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.track_stock', true)
            ->assertJsonPath('product.current_stock', 25)
            ->assertJsonPath('product.min_stock_alert', 5)
            ->assertJsonPath('low_stock', false);

        $this->product->refresh();

        $this->assertTrue((bool) $this->product->track_stock);
        $this->assertEquals('5.000', $this->product->min_stock_alert);
        $this->assertEquals($this->unidad->id, $this->product->unit_of_measure_id);
        $this->assertEquals('25.000', $this->product->inventory->current_stock);

        $movimiento = InventoryMovement::where('product_id', $this->product->id)->sole();

        $this->assertSame('ADJUSTMENT', $movimiento->movement_type);
        $this->assertEquals(25, (float) $movimiento->quantity);
        $this->assertEquals($this->unidad->id, $movimiento->unit_of_measure_id);
    }

    public function test_avisa_stock_bajo_cuando_la_cantidad_queda_por_debajo_del_minimo(): void
    {
        $this->actingAs($this->owner)
            ->putJson($this->payload($this->product), [
                'track_stock' => true,
                'current_stock' => 3,
                'min_stock_alert' => 10,
                'unit_of_measure_id' => $this->unidad->id,
            ])
            ->assertOk()
            ->assertJsonPath('low_stock', true)
            ->assertJsonPath('product.low_stock', true);
    }

    public function test_el_listado_refleja_el_stock_cargado(): void
    {
        $this->actingAs($this->owner)
            ->putJson($this->payload($this->product), [
                'track_stock' => true,
                'current_stock' => 40,
                'min_stock_alert' => 8,
                'unit_of_measure_id' => $this->unidad->id,
            ])->assertOk();

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/inventory')
            ->assertOk()
            ->assertJsonPath('products.0.product_id', $this->product->id)
            ->assertJsonPath('products.0.track_stock', true)
            ->assertJsonPath('products.0.current_stock', 40)
            ->assertJsonPath('products.0.min_stock_alert', 8)
            ->assertJsonPath('products.0.low_stock', false)
            ->assertJsonPath('low_stock_count', 0);
    }

    public function test_puede_deshabilitar_el_seguimiento_sin_tocar_el_stock(): void
    {
        $this->actingAs($this->owner)->putJson($this->payload($this->product), [
            'track_stock' => true,
            'current_stock' => 12,
            'min_stock_alert' => 2,
            'unit_of_measure_id' => $this->unidad->id,
        ])->assertOk();

        $this->actingAs($this->owner)
            ->putJson($this->payload($this->product), ['track_stock' => false])
            ->assertOk()
            ->assertJsonPath('product.track_stock', false)
            ->assertJsonPath('product.current_stock', 12);

        $this->assertEquals('12.000', $this->product->fresh()->inventory->current_stock);
    }

    public function test_una_segunda_carga_registra_el_delta_como_ajuste(): void
    {
        $this->actingAs($this->owner)->putJson($this->payload($this->product), [
            'track_stock' => true,
            'current_stock' => 20,
            'min_stock_alert' => 2,
            'unit_of_measure_id' => $this->unidad->id,
        ])->assertOk();

        $this->actingAs($this->owner)
            ->putJson($this->payload($this->product), [
                'track_stock' => true,
                'current_stock' => 8,
                'min_stock_alert' => 2,
                'unit_of_measure_id' => $this->unidad->id,
            ])
            ->assertOk()
            ->assertJsonPath('current_stock', 8);

        $movimientos = InventoryMovement::where('product_id', $this->product->id)->orderBy('id')->get();

        $this->assertCount(2, $movimientos);
        $this->assertEquals(20, (float) $movimientos[0]->quantity);
        $this->assertEquals(-12, (float) $movimientos[1]->quantity);
    }

    public function test_si_la_cantidad_no_cambia_no_asienta_movimiento(): void
    {
        $this->actingAs($this->owner)->putJson($this->payload($this->product), [
            'track_stock' => true,
            'current_stock' => 20,
            'min_stock_alert' => 2,
            'unit_of_measure_id' => $this->unidad->id,
        ])->assertOk();

        $this->actingAs($this->owner)->putJson($this->payload($this->product), [
            'track_stock' => true,
            'min_stock_alert' => 4,
        ])->assertOk();

        $this->assertSame(1, InventoryMovement::where('product_id', $this->product->id)->count());
    }

    // --- Validaciones ---

    public function test_rechaza_stock_negativo_si_no_hay_stock_negativo_permitido(): void
    {
        $this->actingAs($this->owner)
            ->putJson($this->payload($this->product), [
                'track_stock' => true,
                'current_stock' => -5,
                'min_stock_alert' => 1,
                'unit_of_measure_id' => $this->unidad->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_stock']);

        $this->assertNull($this->product->fresh()->inventory);
    }

    public function test_permite_stock_negativo_cuando_esta_habilitado(): void
    {
        $this->actingAs($this->owner)
            ->putJson($this->payload($this->product), [
                'track_stock' => true,
                'current_stock' => -5,
                'min_stock_alert' => 1,
                'allow_negative_stock' => true,
                'unit_of_measure_id' => $this->unidad->id,
            ])
            ->assertOk();

        $this->assertTrue((bool) $this->product->fresh()->inventory->allow_negative_stock);
        $this->assertEquals('-5.000', $this->product->fresh()->inventory->current_stock);
    }

    public function test_rechaza_fracciones_en_unidades_enteras(): void
    {
        $docena = $this->docena;

        if (! $docena) {
            $this->markTestSkipped('El catalogo de unidades no incluye la docena.');
        }

        if (! $docena->is_integer_only) {
            $docena->update(['is_integer_only' => true]);
        }

        $this->actingAs($this->owner)
            ->putJson($this->payload($this->product), [
                'track_stock' => true,
                'current_stock' => 2.5,
                'min_stock_alert' => 1,
                'unit_of_measure_id' => $docena->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_stock']);
    }

    public function test_rechaza_stock_minimo_negativo(): void
    {
        $this->actingAs($this->owner)
            ->putJson($this->payload($this->product), [
                'track_stock' => true,
                'current_stock' => 5,
                'min_stock_alert' => -2,
                'unit_of_measure_id' => $this->unidad->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['min_stock_alert']);
    }

    public function test_exige_track_stock(): void
    {
        $this->actingAs($this->owner)
            ->putJson($this->payload($this->product), ['current_stock' => 5])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['track_stock']);
    }
}
