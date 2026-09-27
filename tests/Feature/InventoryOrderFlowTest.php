<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Country;
use App\Models\InventoryMovement;
use App\Models\Order;
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

class InventoryOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Address $address;

    private User $providerUser;

    private Provider $provider;

    private Category $category;

    private Product $product;

    private UnitOfMeasure $docena;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitOfMeasureSeeder::class);

        TypeUser::create(['description' => 'Cliente']);
        $prestador = TypeUser::create(['description' => 'Prestador']);
        UserStatus::create(['status' => 'Activo']);
        $country = Country::create(['name' => 'Argentina', 'iso_code' => 'AR']);

        $this->providerUser = User::create([
            'name' => 'Kiosco Central',
            'email' => 'kiosco@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->providerUser->id,
            'business_name' => 'Kiosco Central',
            'is_active' => true,
            'has_inventory_control' => true,
        ]);

        $this->category = Category::create([
            'provider_id' => $this->provider->id,
            'name' => 'Almacen',
        ]);

        $this->docena = UnitOfMeasure::where('name', 'Docena')->firstOrFail();

        $this->product = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->category->id,
            'name' => 'Galletitas',
            'price' => 2000,
            'is_available' => true,
            'track_stock' => true,
            'min_stock_alert' => 5,
            'unit_of_measure_id' => $this->docena->id,
        ]);

        $this->client = User::create([
            'name' => 'Ana Compra',
            'email' => 'ana@example.com',
            'password' => 'password',
        ]);

        $this->address = Address::create([
            'user_id' => $this->client->id,
            'country_id' => $country->id,
            'street' => 'Av. Corrientes',
            'number' => '1234',
            'is_primary' => true,
        ]);
    }

    public function test_order_deducts_stock_and_records_a_sale_movement(): void
    {
        $this->setStock(24);

        $this->placeOrder()->assertCreated()->assertJsonPath('success', true);

        $this->assertEqualsWithDelta(12.0, $this->stock(), 0.001);

        $movement = InventoryMovement::sole();
        $this->assertSame('SALE', $movement->movement_type);
        $this->assertSame($this->product->id, $movement->product_id);
        $this->assertSame($this->provider->id, $movement->provider_id);
        $this->assertNotNull($movement->order_id);
        $this->assertSame(Order::sole()->id, $movement->order_id);
        $this->assertSame(1, (int) $movement->quantity);
        $this->assertSame($this->docena->id, $movement->unit_of_measure_id);
        $this->assertEqualsWithDelta(12.0, (float) $movement->unit_conversion_factor, 0.001);
        $this->assertEqualsWithDelta(12.0, (float) $movement->quantity_in_base_unit, 0.001);
    }

    public function test_order_is_rejected_when_stock_is_not_enough(): void
    {
        $this->setStock(5);

        $this->placeOrder()
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.quantity');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertEqualsWithDelta(5.0, $this->stock(), 0.001);
    }

    public function test_order_allows_negative_stock_when_the_product_allows_it(): void
    {
        ProductInventory::create([
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'current_stock' => 3,
            'reserved_stock' => 0,
            'allow_negative_stock' => true,
        ]);

        $this->placeOrder()->assertCreated();

        $this->assertEqualsWithDelta(-9.0, $this->stock(), 0.001);
        $this->assertSame('SALE', InventoryMovement::sole()->movement_type);
    }

    public function test_stock_is_untouched_when_the_provider_does_not_use_inventory(): void
    {
        $this->provider->update(['has_inventory_control' => false]);
        $this->setStock(24);

        $this->placeOrder()->assertCreated();

        $this->assertEqualsWithDelta(24.0, $this->stock(), 0.001);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_stock_is_untouched_when_the_product_does_not_track_stock(): void
    {
        $this->product->update(['track_stock' => false]);
        $this->setStock(24);

        $this->placeOrder()->assertCreated();

        $this->assertEqualsWithDelta(24.0, $this->stock(), 0.001);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_cancelling_the_order_restores_stock_and_records_a_cancelled_sale(): void
    {
        $this->setStock(24);

        $this->placeOrder()->assertCreated();
        $order = Order::sole();

        $this->assertEqualsWithDelta(12.0, $this->stock(), 0.001);

        $this->actingAs($this->providerUser)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_CANCELLED])
            ->assertOk()
            ->assertJsonPath('order.status', Order::STATUS_CANCELLED);

        $this->assertEqualsWithDelta(24.0, $this->stock(), 0.001);

        $cancelled = InventoryMovement::where('movement_type', 'CANCELLED_SALE')->sole();
        $this->assertSame($order->id, $cancelled->order_id);
        $this->assertSame($this->product->id, $cancelled->product_id);
        $this->assertSame($this->docena->id, $cancelled->unit_of_measure_id);
        $this->assertEqualsWithDelta(12.0, (float) $cancelled->quantity_in_base_unit, 0.001);
        $this->assertSame(2, InventoryMovement::count());
    }

    public function test_cancelling_an_order_without_sale_movement_does_not_restore(): void
    {
        $this->provider->update(['has_inventory_control' => false]);
        $this->setStock(24);

        $this->placeOrder()->assertCreated();
        $order = Order::sole();

        $this->provider->update(['has_inventory_control' => true]);

        $this->actingAs($this->providerUser)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_CANCELLED])
            ->assertOk();

        $this->assertEqualsWithDelta(24.0, $this->stock(), 0.001);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_cancelling_is_blocked_before_the_transition_is_allowed(): void
    {
        $this->setStock(24);
        $this->placeOrder()->assertCreated();
        $order = Order::sole();

        $order->update(['status' => Order::STATUS_DELIVERED]);

        $this->actingAs($this->providerUser)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_CANCELLED])
            ->assertStatus(422);

        $this->assertEqualsWithDelta(12.0, $this->stock(), 0.001);
        $this->assertSame(1, InventoryMovement::count());
    }

    public function test_catalog_marks_products_as_out_of_stock(): void
    {
        $this->setStock(0);

        $withStock = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->category->id,
            'name' => 'Azucar',
            'price' => 1500,
            'is_available' => true,
            'track_stock' => true,
        ]);

        ProductInventory::create([
            'product_id' => $withStock->id,
            'provider_id' => $this->provider->id,
            'current_stock' => 8,
            'reserved_stock' => 0,
            'allow_negative_stock' => false,
        ]);

        $flags = $this->catalogFlags();

        $this->assertTrue($flags['Galletitas']);
        $this->assertFalse($flags['Azucar']);
    }

    public function test_catalog_ignores_the_flag_when_inventory_is_disabled(): void
    {
        $this->provider->update(['has_inventory_control' => false]);
        $this->setStock(0);

        $flags = $this->catalogFlags();

        $this->assertFalse($flags['Galletitas']);
    }

    public function test_catalog_ignores_the_flag_when_negative_stock_is_allowed(): void
    {
        ProductInventory::create([
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'current_stock' => 0,
            'reserved_stock' => 0,
            'allow_negative_stock' => true,
        ]);

        $flags = $this->catalogFlags();

        $this->assertFalse($flags['Galletitas']);
    }

    private function placeOrder()
    {
        return $this->actingAs($this->client)->postJson('/api/orders', [
            'provider_id' => $this->provider->id,
            'address_id' => $this->address->id,
            'payment_method' => 'efectivo',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ]);
    }

    private function setStock(float $stock): void
    {
        ProductInventory::create([
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'current_stock' => $stock,
            'reserved_stock' => 0,
            'allow_negative_stock' => false,
        ]);
    }

    private function stock(): float
    {
        $value = ProductInventory::where('product_id', $this->product->id)
            ->where('provider_id', $this->provider->id)
            ->value('current_stock');

        return $value === null ? 0.0 : (float) $value;
    }

    private function catalogFlags(): array
    {
        $categories = $this->getJson("/api/providers/{$this->provider->id}/catalog")
            ->assertOk()
            ->json('categories');

        $flags = [];

        foreach ($categories as $category) {
            foreach ($category['products'] as $product) {
                $this->assertArrayHasKey('is_out_of_stock', $product);
                $flags[$product['name']] = $product['is_out_of_stock'];
            }
        }

        return $flags;
    }
}
