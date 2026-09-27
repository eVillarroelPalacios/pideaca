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

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Provider $provider;

    private Category $category;

    private Product $product;

    private User $otherOwner;

    private Provider $otherProvider;

    private Category $otherCategory;

    private Product $otherProduct;

    private UnitOfMeasure $unidad;

    private UnitOfMeasure $docena;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitOfMeasureSeeder::class);

        $prestador = TypeUser::create(['description' => 'Prestador']);
        UserStatus::create(['status' => 'Activo']);

        $this->owner = User::create([
            'name' => 'Comercio Uno',
            'email' => 'comercio1@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->owner->id,
            'business_name' => 'Comercio Uno',
        ]);

        $this->category = Category::create([
            'provider_id' => $this->provider->id,
            'name' => 'Almacen',
        ]);

        $this->product = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->category->id,
            'name' => 'Yerba Mate',
            'price' => 1500,
            'is_available' => true,
            'track_stock' => true,
            'min_stock_alert' => 10,
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
        ]);

        $this->otherCategory = Category::create([
            'provider_id' => $this->otherProvider->id,
            'name' => 'Bebidas',
        ]);

        $this->otherProduct = Product::create([
            'provider_id' => $this->otherProvider->id,
            'category_id' => $this->otherCategory->id,
            'name' => 'Agua Mineral',
            'price' => 800,
            'is_available' => true,
        ]);

        $this->unidad = UnitOfMeasure::where('name', 'Unidad')->firstOrFail();
        $this->docena = UnitOfMeasure::where('name', 'Docena')->firstOrFail();
    }

    public function test_guest_gets_401_on_every_endpoint(): void
    {
        $this->getJson('/api/v1/provider/inventory')->assertStatus(401);
        $this->getJson('/api/v1/provider/inventory/movements')->assertStatus(401);
        $this->putJson('/api/v1/provider/inventory/settings', ['has_inventory_control' => true])->assertStatus(401);
        $this->postJson('/api/v1/provider/inventory/adjust', [])->assertStatus(401);
    }

    public function test_user_without_commerce_gets_403(): void
    {
        $client = User::create([
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($client)->getJson('/api/v1/provider/inventory')
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->actingAs($client)->getJson('/api/v1/provider/inventory/movements')->assertStatus(403);
        $this->actingAs($client)->postJson('/api/v1/provider/inventory/adjust', [])->assertStatus(403);
    }

    public function test_index_returns_own_products_with_stock_unit_and_alert(): void
    {
        $this->provider->update(['has_inventory_control' => true]);
        $this->product->update(['unit_of_measure_id' => $this->docena->id]);

        ProductInventory::create([
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'current_stock' => 4,
            'reserved_stock' => 1,
            'allow_negative_stock' => false,
        ]);

        Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->category->id,
            'name' => 'Sin Alerta',
            'price' => 900,
            'is_available' => true,
            'track_stock' => false,
        ]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/provider/inventory')->assertOk();

        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertTrue($json['provider']['has_inventory_control']);
        $this->assertCount(2, $json['products']);
        $this->assertSame(1, $json['low_stock_count']);

        $alerted = collect($json['products'])->firstWhere('product_id', $this->product->id);
        $this->assertSame('doc', $alerted['unit_of_measure']['symbol']);
        $this->assertTrue($alerted['low_stock']);
        $this->assertEqualsWithDelta(4.0, $alerted['current_stock'], 0.001);
        $this->assertEqualsWithDelta(3.0, $alerted['available_stock'], 0.001);

        $this->assertNotContains($this->otherProduct->id, array_column($json['products'], 'product_id'));
        $this->assertCount(6, $json['unit_of_measures']);
    }

    public function test_index_without_previous_inventory_reports_zero_stock(): void
    {
        $json = $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/inventory')
            ->assertOk()
            ->json();

        $row = collect($json['products'])->firstWhere('product_id', $this->product->id);

        $this->assertEqualsWithDelta(0.0, $row['current_stock'], 0.001);
        $this->assertTrue($row['low_stock']);
        $this->assertSame(1, $json['low_stock_count']);
    }

    public function test_settings_toggles_the_flag(): void
    {
        $this->assertFalse($this->provider->refresh()->usesInventory());

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/inventory/settings', ['has_inventory_control' => true])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('provider.has_inventory_control', true);

        $this->assertTrue($this->provider->refresh()->usesInventory());
        $this->assertFalse($this->otherProvider->refresh()->usesInventory());

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/inventory/settings', ['has_inventory_control' => false])
            ->assertOk()
            ->assertJsonPath('provider.has_inventory_control', false);

        $this->assertFalse($this->provider->refresh()->usesInventory());
    }

    public function test_settings_validates_the_payload(): void
    {
        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/inventory/settings', ['has_inventory_control' => 'yes'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('has_inventory_control');

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/inventory/settings', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('has_inventory_control');
    }

    public function test_adjust_registers_in_movement_and_converts_units(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/provider/inventory/adjust', [
            'product_id' => $this->product->id,
            'movement_type' => 'IN',
            'quantity' => 2,
            'unit_of_measure_id' => $this->docena->id,
            'notes' => 'Reposición semanal',
        ])->assertStatus(201);

        $response->assertJsonPath('success', true)
            ->assertJsonPath('movement.movement_type', 'IN');

        $payload = $response->json();
        $this->assertEqualsWithDelta(24.0, $payload['current_stock'], 0.001);
        $this->assertEqualsWithDelta(24.0, $payload['delta'], 0.001);

        $this->assertEqualsWithDelta(24.0, (float) $this->product->inventory()->value('current_stock'), 0.001);

        $movement = InventoryMovement::sole();
        $this->assertSame('IN', $movement->movement_type);
        $this->assertEqualsWithDelta(2.0, (float) $movement->quantity, 0.001);
        $this->assertEqualsWithDelta(12.0, (float) $movement->unit_conversion_factor, 0.001);
        $this->assertEqualsWithDelta(24.0, (float) $movement->quantity_in_base_unit, 0.001);
        $this->assertSame($this->docena->id, $movement->unit_of_measure_id);
        $this->assertSame($this->provider->id, $movement->provider_id);
        $this->assertNull($movement->order_id);
        $this->assertSame('Reposición semanal', $movement->notes);
    }

    public function test_adjust_registers_out_movement_and_decreases_stock(): void
    {
        ProductInventory::create([
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'current_stock' => 30,
            'reserved_stock' => 0,
            'allow_negative_stock' => false,
        ]);

        $out = $this->actingAs($this->owner)->postJson('/api/v1/provider/inventory/adjust', [
            'product_id' => $this->product->id,
            'movement_type' => 'OUT',
            'quantity' => 1,
            'unit_of_measure_id' => $this->docena->id,
        ])->assertStatus(201)->json();

        $this->assertEqualsWithDelta(18.0, $out['current_stock'], 0.001);
        $this->assertEqualsWithDelta(-12.0, $out['delta'], 0.001);

        $this->assertEqualsWithDelta(18.0, (float) $this->product->inventory()->value('current_stock'), 0.001);
        $this->assertEqualsWithDelta(12.0, (float) InventoryMovement::sole()->quantity_in_base_unit, 0.001);
    }

    public function test_adjustment_applies_signed_delta(): void
    {
        ProductInventory::create([
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'allow_negative_stock' => false,
        ]);

        $adj = $this->actingAs($this->owner)->postJson('/api/v1/provider/inventory/adjust', [
            'product_id' => $this->product->id,
            'movement_type' => 'ADJUSTMENT',
            'quantity' => -3,
            'unit_of_measure_id' => $this->unidad->id,
        ])->assertStatus(201)->json();

        $this->assertEqualsWithDelta(7.0, $adj['current_stock'], 0.001);
        $this->assertEqualsWithDelta(-3.0, $adj['delta'], 0.001);

        $movement = InventoryMovement::sole();
        $this->assertEqualsWithDelta(-3.0, (float) $movement->quantity, 0.001);
        $this->assertEqualsWithDelta(-3.0, (float) $movement->quantity_in_base_unit, 0.001);
    }

    public function test_adjust_rejects_product_of_another_commerce(): void
    {
        $this->actingAs($this->owner)->postJson('/api/v1/provider/inventory/adjust', [
            'product_id' => $this->otherProduct->id,
            'movement_type' => 'IN',
            'quantity' => 1,
            'unit_of_measure_id' => $this->unidad->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('product_id');

        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseMissing('product_inventory', ['product_id' => $this->otherProduct->id]);
    }

    public function test_adjust_blocks_negative_stock_when_it_is_not_allowed(): void
    {
        ProductInventory::create([
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'current_stock' => 5,
            'reserved_stock' => 0,
            'allow_negative_stock' => false,
        ]);

        $this->actingAs($this->owner)->postJson('/api/v1/provider/inventory/adjust', [
            'product_id' => $this->product->id,
            'movement_type' => 'OUT',
            'quantity' => 6,
            'unit_of_measure_id' => $this->unidad->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('quantity');

        $this->assertEqualsWithDelta(5.0, (float) $this->product->inventory()->value('current_stock'), 0.001);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_adjust_allows_negative_stock_when_the_product_allows_it(): void
    {
        ProductInventory::create([
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'current_stock' => 1,
            'reserved_stock' => 0,
            'allow_negative_stock' => true,
        ]);

        $negative = $this->actingAs($this->owner)->postJson('/api/v1/provider/inventory/adjust', [
            'product_id' => $this->product->id,
            'movement_type' => 'OUT',
            'quantity' => 5,
            'unit_of_measure_id' => $this->unidad->id,
        ])->assertStatus(201)->json();

        $this->assertEqualsWithDelta(-4.0, $negative['current_stock'], 0.001);

        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_adjust_rejects_fractional_quantity_for_integer_units(): void
    {
        $this->actingAs($this->owner)->postJson('/api/v1/provider/inventory/adjust', [
            'product_id' => $this->product->id,
            'movement_type' => 'IN',
            'quantity' => 1.5,
            'unit_of_measure_id' => $this->unidad->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_adjust_validates_type_quantity_and_zero_adjustment(): void
    {
        $this->actingAs($this->owner)->postJson('/api/v1/provider/inventory/adjust', [
            'product_id' => $this->product->id,
            'movement_type' => 'SALE',
            'quantity' => 1,
            'unit_of_measure_id' => $this->unidad->id,
        ])->assertStatus(422)->assertJsonValidationErrors('movement_type');

        $this->actingAs($this->owner)->postJson('/api/v1/provider/inventory/adjust', [
            'product_id' => $this->product->id,
            'movement_type' => 'IN',
            'quantity' => 0,
            'unit_of_measure_id' => $this->unidad->id,
        ])->assertStatus(422)->assertJsonValidationErrors('quantity');

        $this->actingAs($this->owner)->postJson('/api/v1/provider/inventory/adjust', [
            'product_id' => $this->product->id,
            'movement_type' => 'ADJUSTMENT',
            'quantity' => 0,
            'unit_of_measure_id' => $this->unidad->id,
        ])->assertStatus(422)->assertJsonValidationErrors('quantity');

        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_movements_are_paginated_and_scoped_to_the_commerce(): void
    {
        $old = $this->createMovement($this->product, $this->provider, now()->subDays(5));
        $recent = $this->createMovement($this->product, $this->provider, now());
        $this->createMovement($this->otherProduct, $this->otherProvider, now());

        $json = $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/inventory/movements')
            ->assertOk()
            ->json();

        $this->assertTrue($json['success']);
        $this->assertSame(2, $json['movements']['total']);
        $this->assertSame(1, $json['movements']['current_page']);
        $this->assertSame(15, $json['movements']['per_page']);
        $this->assertNotContains($this->otherProduct->id, array_column($json['movements']['data'], 'product_id'));

        $filtered = $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/inventory/movements?from='.now()->toDateString())
            ->assertOk()
            ->json();

        $this->assertSame(1, $filtered['movements']['total']);
        $this->assertSame($recent->id, $filtered['movements']['data'][0]['id']);

        $byProduct = $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/inventory/movements?product_id='.$this->product->id.'&from='.now()->subDays(10)->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->json();

        $this->assertSame(2, $byProduct['movements']['total']);
        $this->assertContains($old->id, array_column($byProduct['movements']['data'], 'id'));

        $foreignProduct = $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/inventory/movements?product_id='.$this->otherProduct->id)
            ->assertOk()
            ->json();

        $this->assertSame(0, $foreignProduct['movements']['total']);
    }

    public function test_movements_supports_per_page_and_rejects_invalid_filters(): void
    {
        $this->createMovement($this->product, $this->provider, now());

        $json = $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/inventory/movements?per_page=1')
            ->assertOk()
            ->json();

        $this->assertSame(1, $json['movements']['per_page']);
        $this->assertCount(1, $json['movements']['data']);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/inventory/movements?from=ayer')
            ->assertStatus(422)
            ->assertJsonValidationErrors('from');

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/inventory/movements?product_id=999999')
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    private function createMovement(Product $product, Provider $provider, $createdAt): InventoryMovement
    {
        $movement = InventoryMovement::create([
            'product_id' => $product->id,
            'provider_id' => $provider->id,
            'order_id' => null,
            'movement_type' => 'IN',
            'quantity' => 1,
            'unit_of_measure_id' => $this->unidad->id,
            'unit_conversion_factor' => 1,
            'quantity_in_base_unit' => 1,
            'notes' => null,
        ]);

        $movement->created_at = $createdAt;
        $movement->save();

        return $movement->refresh();
    }
}
