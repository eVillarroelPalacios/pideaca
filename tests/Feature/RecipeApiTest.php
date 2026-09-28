<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\Provider;
use App\Models\Supply;
use App\Models\TypeUser;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecipeApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Provider $provider;

    private Category $category;

    private UnitOfMeasure $unidad;

    private User $otherOwner;

    private Provider $otherProvider;

    private Category $otherCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = TypeUser::create(['description' => 'Prestador']);
        UserStatus::create(['status' => 'Activo']);

        $this->unidad = UnitOfMeasure::create([
            'name' => 'Unidad',
            'symbol' => 'und',
            'base_conversion_factor' => 1,
            'is_integer_only' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Comercio Uno',
            'email' => 'comercio1@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->owner->id,
            'business_name' => 'Comercio Uno',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'provider_id' => $this->provider->id,
            'name' => 'Empanadas',
        ]);

        $this->otherOwner = User::create([
            'name' => 'Comercio Dos',
            'email' => 'comercio2@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->otherProvider = Provider::create([
            'user_id' => $this->otherOwner->id,
            'business_name' => 'Comercio Dos',
            'is_active' => true,
        ]);

        $this->otherCategory = Category::create([
            'provider_id' => $this->otherProvider->id,
            'name' => 'Bebidas',
        ]);
    }

    public function test_guest_gets_401_on_all_recipe_endpoints(): void
    {
        $producto = $this->producto($this->provider, $this->category, 3500);

        $this->getJson('/api/v1/provider/supplies')->assertStatus(401);
        $this->postJson('/api/v1/provider/supplies', [])->assertStatus(401);
        $this->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [])->assertStatus(401);
        $this->getJson('/api/v1/provider/financial-health')->assertStatus(401);
    }

    public function test_customer_gets_403_on_recipe_endpoints(): void
    {
        $cliente = User::create([
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($cliente)->getJson('/api/v1/provider/supplies')->assertStatus(403);
        $this->actingAs($cliente)->getJson('/api/v1/provider/financial-health')->assertStatus(403);
        $this->actingAs($cliente)->postJson('/api/v1/provider/supplies', [])->assertStatus(403);
    }

    // ------------------------------------------------------------- insumos

    public function test_crea_insumo_con_su_costo(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/supplies', [
                'name' => 'Carne Picada',
                'unit_of_measure_id' => $this->unidad->id,
                'cost_per_unit' => 12500.50,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('supply.name', 'Carne Picada')
            ->assertJsonPath('supply.cost_per_unit', 12500.50)
            ->assertJsonPath('supply.unit_of_measure.symbol', 'und');

        $this->assertDatabaseHas('supplies', [
            'provider_id' => $this->provider->id,
            'name' => 'Carne Picada',
            'unit_of_measure_id' => $this->unidad->id,
            'cost_per_unit' => '12500.50',
        ]);
    }

    public function test_actualiza_insumo_por_id(): void
    {
        $insumo = $this->insumo('Harina 000', 1800);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/supplies', [
                'id' => $insumo->id,
                'name' => 'Harina 000 Premium',
                'unit_of_measure_id' => $this->unidad->id,
                'cost_per_unit' => 2450.75,
            ])
            ->assertStatus(200)
            ->assertJsonPath('supply.id', $insumo->id)
            ->assertJsonPath('supply.name', 'Harina 000 Premium')
            ->assertJsonPath('supply.cost_per_unit', 2450.75);

        $this->assertDatabaseHas('supplies', [
            'id' => $insumo->id,
            'name' => 'Harina 000 Premium',
            'cost_per_unit' => '2450.75',
        ]);
    }

    public function test_actualiza_costo_enviando_solo_el_nombre_existente(): void
    {
        $insumo = $this->insumo('Queso Mozzarella', 9000);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/supplies', [
                'name' => 'Queso Mozzarella',
                'unit_of_measure_id' => $this->unidad->id,
                'cost_per_unit' => 11250,
            ])
            ->assertStatus(200)
            ->assertJsonPath('supply.id', $insumo->id)
            ->assertJsonPath('supply.cost_per_unit', 11250);

        $this->assertSame(1, Supply::where('provider_id', $this->provider->id)->count());
    }

    public function test_no_puede_editar_insumo_de_otro_comercio(): void
    {
        $ajeno = Supply::create([
            'provider_id' => $this->otherProvider->id,
            'name' => 'Insumo Ajeno',
            'unit_of_measure_id' => $this->unidad->id,
            'cost_per_unit' => 1000,
        ]);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/supplies', [
                'id' => $ajeno->id,
                'name' => 'Insumo Ajeno',
                'unit_of_measure_id' => $this->unidad->id,
                'cost_per_unit' => 9999,
            ])
            ->assertStatus(404);

        $this->assertDatabaseHas('supplies', ['id' => $ajeno->id, 'cost_per_unit' => '1000.00']);
    }

    public function test_valida_el_pedido_de_insumo(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/supplies', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'unit_of_measure_id', 'cost_per_unit']);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/supplies', [
                'name' => 'X',
                'unit_of_measure_id' => 999999,
                'cost_per_unit' => 10,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['unit_of_measure_id']);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/supplies', [
                'name' => 'X',
                'unit_of_measure_id' => $this->unidad->id,
                'cost_per_unit' => -5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cost_per_unit']);
    }

    public function test_lista_insumos_con_unidades_de_medida(): void
    {
        $this->insumo('Carne Picada', 12500.50);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/supplies')
            ->assertStatus(200)
            ->assertJsonCount(1, 'supplies')
            ->assertJsonPath('supplies.0.name', 'Carne Picada')
            ->assertJsonPath('supplies.0.cost_per_unit', 12500.50)
            ->assertJsonCount(1, 'units_of_measure')
            ->assertJsonPath('units_of_measure.0.symbol', 'und');
    }

    // -------------------------------------------------------- ficha tecnica

    public function test_asigna_ficha_tecnica_y_calcula_costo_y_margen(): void
    {
        $producto = $this->producto($this->provider, $this->category, 3500);
        $carne = $this->insumo('Carne Picada', 12500.50);
        $harina = $this->insumo('Harina 000', 1800);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [
                'items' => [
                    ['supply_id' => $carne->id, 'quantity_required' => 0.150],
                    ['supply_id' => $harina->id, 'quantity_required' => 0.020],
                ],
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'recipe.items')
            ->assertJsonPath('production_cost', 1911.08)
            ->assertJsonPath('profit_margin', 45.4);

        $this->assertDatabaseCount('product_recipes', 2);
        $this->assertSame(1911.08, $producto->fresh('recipeItems.supply')->calculateCost());
    }

    public function test_ficha_tecnica_conserva_los_tres_decimales(): void
    {
        $producto = $this->producto($this->provider, $this->category, 3500);
        $carne = $this->insumo('Carne Picada', 10000);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [
                'items' => [['supply_id' => $carne->id, 'quantity_required' => 0.125]],
            ])
            ->assertStatus(200)
            ->assertJsonPath('recipe.items.0.quantity_required', 0.125)
            ->assertJsonPath('production_cost', fn ($valor) => abs($valor - 1250.0) < 0.001);

        $this->assertDatabaseHas('product_recipes', [
            'product_id' => $producto->id,
            'quantity_required' => '0.125',
        ]);
    }

    public function test_reemplaza_la_ficha_anterior(): void
    {
        $producto = $this->producto($this->provider, $this->category, 3500);
        $carne = $this->insumo('Carne Picada', 10000);
        $harina = $this->insumo('Harina 000', 1800);

        $this->actingAs($this->owner)->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [
            'items' => [['supply_id' => $carne->id, 'quantity_required' => 0.2]],
        ])->assertStatus(200);

        $this->actingAs($this->owner)->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [
            'items' => [['supply_id' => $harina->id, 'quantity_required' => 0.05]],
        ])->assertStatus(200);

        $this->assertDatabaseCount('product_recipes', 1);
        $this->assertDatabaseHas('product_recipes', [
            'product_id' => $producto->id,
            'supply_id' => $harina->id,
            'quantity_required' => '0.050',
        ]);
    }

    public function test_no_acepta_insumos_de_otro_comercio(): void
    {
        $producto = $this->producto($this->provider, $this->category, 3500);
        $ajeno = Supply::create([
            'provider_id' => $this->otherProvider->id,
            'name' => 'Insumo Ajeno',
            'unit_of_measure_id' => $this->unidad->id,
            'cost_per_unit' => 1000,
        ]);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [
                'items' => [['supply_id' => $ajeno->id, 'quantity_required' => 0.1]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('invalid_supply_ids', [$ajeno->id]);

        $this->assertDatabaseCount('product_recipes', 0);
    }

    public function test_no_puede_tocar_la_receta_de_un_producto_ajeno(): void
    {
        $ajeno = $this->producto($this->otherProvider, $this->otherCategory, 2000);
        $insumo = $this->insumo('Carne Picada', 10000);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$ajeno->id.'/recipe', [
                'items' => [['supply_id' => $insumo->id, 'quantity_required' => 0.1]],
            ])
            ->assertStatus(404);

        $this->assertDatabaseCount('product_recipes', 0);
    }

    public function test_valida_la_ficha_tecnica(): void
    {
        $producto = $this->producto($this->provider, $this->category, 3500);
        $insumo = $this->insumo('Carne Picada', 10000);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', ['items' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [
                'items' => [['supply_id' => $insumo->id, 'quantity_required' => 0]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity_required']);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [
                'items' => [
                    ['supply_id' => $insumo->id, 'quantity_required' => 0.1],
                    ['supply_id' => $insumo->id, 'quantity_required' => 0.2],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.supply_id']);
    }

    public function test_consulta_la_ficha_tecnica_guardada(): void
    {
        $producto = $this->producto($this->provider, $this->category, 3500);
        $carne = $this->insumo('Carne Picada', 12500.50);

        $this->actingAs($this->owner)->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [
            'items' => [['supply_id' => $carne->id, 'quantity_required' => 0.150]],
        ])->assertStatus(200);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/products/'.$producto->id.'/recipe')
            ->assertStatus(200)
            ->assertJsonPath('recipe.items.0.supply_name', 'Carne Picada')
            ->assertJsonPath('recipe.items.0.quantity_required', 0.15)
            ->assertJsonPath('recipe.items.0.cost_per_unit', 12500.50)
            ->assertJsonPath('recipe.items.0.line_cost', 1875.08)
            ->assertJsonPath('production_cost', 1875.08);
    }

    // ------------------------------------------------------ salud financiera

    public function test_financial_health_muestra_precio_costo_y_margen(): void
    {
        $producto = $this->producto($this->provider, $this->category, 3500);
        $carne = $this->insumo('Carne Picada', 12500.50);
        $harina = $this->insumo('Harina 000', 1800);

        $this->actingAs($this->owner)->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [
            'items' => [
                ['supply_id' => $carne->id, 'quantity_required' => 0.150],
                ['supply_id' => $harina->id, 'quantity_required' => 0.020],
            ],
        ])->assertStatus(200);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/financial-health')
            ->assertStatus(200)
            ->assertJsonPath('summary.min_margin_percent', 30)
            ->assertJsonPath('summary.total_products', 1)
            ->assertJsonPath('summary.products_with_alert', 0)
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.product_id', $producto->id)
            ->assertJsonPath('products.0.sale_price', 3500)
            ->assertJsonPath('products.0.production_cost', 1911.08)
            ->assertJsonPath('products.0.profit_margin', 45.4)
            ->assertJsonPath('products.0.has_recipe', true)
            ->assertJsonPath('products.0.alerts', []);
    }

    public function test_alerta_cuando_el_margen_es_menor_al_30(): void
    {
        $producto = $this->producto($this->provider, $this->category, 2000);
        $carne = $this->insumo('Carne Picada', 12500.50);

        $this->actingAs($this->owner)->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [
            'items' => [['supply_id' => $carne->id, 'quantity_required' => 0.150]],
        ])->assertStatus(200);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/financial-health')
            ->assertStatus(200)
            ->assertJsonPath('summary.products_with_alert', 1)
            ->assertJsonPath('products.0.production_cost', 1875.08)
            ->assertJsonPath('products.0.profit_margin', 6.25)
            ->assertJsonPath('products.0.alerts.0.code', 'margen_bajo')
            ->assertJsonPath('products.0.alerts.0.severity', 'warning')
            ->assertJsonPath('products.0.alerts.0.message', 'Margen del 6,25% , por debajo del 30% recomendado. Considera ajustar el precio de venta.');
    }

    public function test_el_umbral_de_alerta_es_configurable(): void
    {
        config()->set('financials.min_gross_margin_percent', 5);

        $producto = $this->producto($this->provider, $this->category, 2000);
        $carne = $this->insumo('Carne Picada', 12500.50);

        $this->actingAs($this->owner)->postJson('/api/v1/provider/products/'.$producto->id.'/recipe', [
            'items' => [['supply_id' => $carne->id, 'quantity_required' => 0.150]],
        ])->assertStatus(200);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/financial-health')
            ->assertStatus(200)
            ->assertJsonPath('summary.min_margin_percent', 5)
            ->assertJsonPath('summary.products_with_alert', 0)
            ->assertJsonCount(0, 'products.0.alerts');
    }

    public function test_producto_sin_ficha_tecnica_avisa_que_el_margen_no_es_real(): void
    {
        $this->producto($this->provider, $this->category, 3500, 'Empanada Sin Receta');

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/financial-health')
            ->assertStatus(200)
            ->assertJsonPath('products.0.has_recipe', false)
            ->assertJsonPath('products.0.production_cost', 0)
            ->assertJsonPath('products.0.alerts.0.code', 'sin_ficha_tecnica')
            ->assertJsonPath('products.0.alerts.0.severity', 'info');
    }

    public function test_producto_sin_precio_no_calcula_margen(): void
    {
        $this->producto($this->provider, $this->category, 0, 'Empanada Sin Precio');
        $carne = $this->insumo('Carne Picada', 10000);

        $producto = Product::where('provider_id', $this->provider->id)->first();
        ProductRecipe::create([
            'product_id' => $producto->id,
            'supply_id' => $carne->id,
            'quantity_required' => 0.1,
        ]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/financial-health')
            ->assertStatus(200)
            ->assertJsonPath('products.0.production_cost', fn ($valor) => abs($valor - 1000.0) < 0.001)
            ->assertJsonPath('products.0.profit_margin', null)
            ->assertJsonPath('products.0.alerts.0.code', 'precio_no_definido');
    }

    public function test_financial_health_no_muestra_productos_de_otros_comercios(): void
    {
        $this->producto($this->provider, $this->category, 3500, 'Mia');
        $this->producto($this->otherProvider, $this->otherCategory, 2000, 'Ajena');

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/financial-health')
            ->assertStatus(200)
            ->assertJsonPath('summary.total_products', 1)
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', 'Mia');
    }

    public function test_financial_health_pagina(): void
    {
        foreach (range(1, 3) as $i) {
            $this->producto($this->provider, $this->category, 1000 * $i, 'Producto '.$i);
        }

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/financial-health?per_page=2')
            ->assertStatus(200)
            ->assertJsonCount(2, 'products')
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('pagination.last_page', 2);
    }

    // ------------------------------------------------------------- modelos

    public function test_calculo_de_margen_usa_la_formula_indicada(): void
    {
        $producto = $this->producto($this->provider, $this->category, 4000);
        $insumo = $this->insumo('Insumo', 5000);

        $producto->recipeItems()->create(['supply_id' => $insumo->id, 'quantity_required' => 0.5]);

        $costo = $producto->fresh('recipeItems.supply')->calculateCost();
        $margen = $producto->fresh('recipeItems.supply')->calculateProfitMargin();

        $this->assertSame(2500.0, $costo);
        $this->assertSame(37.5, $margen);
        $this->assertSame(round((($producto->price - $costo) / $producto->price) * 100, 2), $margen);
    }

    public function test_producto_sin_receta_cuesta_cero_y_sin_precio_no_tiene_margen(): void
    {
        $producto = $this->producto($this->provider, $this->category, 3500);

        $this->assertSame(0.0, $producto->calculateCost());
        $this->assertSame(100.0, $producto->calculateProfitMargin());

        $sinPrecio = $this->producto($this->provider, $this->category, 0, 'Sin Precio');
        $this->assertNull($sinPrecio->calculateProfitMargin());
    }

    public function test_sin_unidad_de_carga_no_hay_consultas_n_plus_uno(): void
    {
        $insumo = $this->insumo('Carne Picada', 1000);

        foreach (range(1, 3) as $i) {
            $producto = $this->producto($this->provider, $this->category, 3500, 'Producto '.$i);
            $producto->recipeItems()->create(['supply_id' => $insumo->id, 'quantity_required' => 0.1]);
        }

        DB::enableQueryLog();
        $this->actingAs($this->owner)->getJson('/api/v1/provider/financial-health')->assertStatus(200);
        $consultas = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 1 proveedor del usuario autenticado + 1 conteo + 1 productos + 2 del eager loading.
        $this->assertLessThanOrEqual(5, $consultas, 'La cantidad de consultas no deberia crecer con la cantidad de productos');
    }

    private function insumo(string $nombre, float $costo): Supply
    {
        return Supply::create([
            'provider_id' => $this->provider->id,
            'name' => $nombre,
            'unit_of_measure_id' => $this->unidad->id,
            'cost_per_unit' => $costo,
        ]);
    }

    private function producto(Provider $provider, Category $category, float $precio, ?string $nombre = null): Product
    {
        return Product::create([
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'name' => $nombre ?? 'Empanada de Carne',
            'price' => $precio,
            'is_available' => true,
        ]);
    }
}
