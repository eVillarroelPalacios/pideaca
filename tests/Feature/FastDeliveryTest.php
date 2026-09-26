<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Country;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\ProductVariant;
use App\Models\Provider;
use App\Models\Region;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FastDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private Provider $provider;

    private Product $muzzarella;

    private ProductVariant $familiar;

    private ProductOption $faina;

    private ProductOption $quesoExtra;

    private User $providerUser;

    private User $customer;

    private Address $address;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = TypeUser::create(['description' => 'Prestador']);
        $cliente = TypeUser::create(['description' => 'Cliente']);
        $activo = UserStatus::create(['status' => 'Activo']);

        $this->providerUser = User::create([
            'name' => 'Pizzería Don Pepe',
            'email' => 'donpepe@pideaca.com',
            'password' => 'password',
            'user_status_id' => $activo->id,
            'type_user_id' => $prestador->id,
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->providerUser->id,
            'business_name' => 'Pizzería Don Pepe',
            'is_active' => true,
        ]);

        $category = Category::create([
            'provider_id' => $this->provider->id,
            'name' => 'Pizzas Tradicionales',
        ]);

        $this->muzzarella = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $category->id,
            'name' => 'Pizza Muzzarella',
            'price' => 8500.00,
        ]);

        $this->familiar = ProductVariant::create([
            'product_id' => $this->muzzarella->id,
            'name' => 'Familiar',
            'price' => 12500.00,
        ]);

        ProductVariant::create([
            'product_id' => $this->muzzarella->id,
            'name' => 'Personal',
            'price' => 7500.00,
            'is_available' => false,
        ]);

        $group = ProductOptionGroup::create([
            'product_id' => $this->muzzarella->id,
            'name' => 'Agregados',
            'min_choices' => 0,
            'max_choices' => 3,
        ]);

        $this->faina = ProductOption::create([
            'option_group_id' => $group->id,
            'name' => 'Fainá',
            'extra_price' => 1200.00,
        ]);

        $this->quesoExtra = ProductOption::create([
            'option_group_id' => $group->id,
            'name' => 'Queso extra',
            'extra_price' => 900.00,
        ]);

        $this->customer = User::create([
            'name' => 'Ana Prueba',
            'email' => 'cliente.demo@pideaca.com',
            'password' => 'password',
            'user_status_id' => $activo->id,
            'type_user_id' => $cliente->id,
        ]);

        $country = Country::create(['name' => 'Argentina', 'iso_code' => 'AR']);
        $this->country = $country;

        $this->address = Address::create([
            'user_id' => $this->customer->id,
            'country_id' => $country->id,
            'province_id' => Region::create([
                'country_id' => $country->id,
                'name' => 'Buenos Aires',
                'type' => 'province',
            ])->id,
            'street' => 'Av. Santa Fe',
            'number' => '1234',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'provider_id' => $this->provider->id,
            'address_id' => $this->address->id,
            'payment_method' => 'transfer',
            'items' => [
                [
                    'product_id' => $this->muzzarella->id,
                    'variant_id' => $this->familiar->id,
                    'quantity' => 2,
                    'options' => [$this->faina->id, $this->quesoExtra->id],
                ],
            ],
        ], $overrides);
    }

    // --- Catalogo ---

    public function test_catalog_returns_full_tree_for_provider(): void
    {
        $response = $this->getJson("/api/providers/{$this->provider->id}/catalog");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('provider.business_name', 'Pizzería Don Pepe')
            ->assertJsonPath('categories.0.name', 'Pizzas Tradicionales')
            ->assertJsonPath('categories.0.products.0.name', 'Pizza Muzzarella');

        $this->assertCount(2, $response->json('categories.0.products.0.variants'));
        $this->assertCount(1, $response->json('categories.0.products.0.option_groups'));
        $this->assertCount(2, $response->json('categories.0.products.0.option_groups.0.options'));
    }

    public function test_catalog_can_filter_unavailable_items(): void
    {
        $response = $this->getJson("/api/providers/{$this->provider->id}/catalog?only_available=1");

        $variants = $response->json('categories.0.products.0.variants');
        $names = array_column($variants, 'name');

        $this->assertContains('Familiar', $names);
        $this->assertNotContains('Personal', $names);
    }

    public function test_catalog_hides_inactive_provider(): void
    {
        $this->provider->update(['is_active' => false]);

        $this->getJson("/api/providers/{$this->provider->id}/catalog")
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    // --- Pedidos ---

    public function test_order_is_created_with_server_side_prices(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/orders', $this->payload());

        $response->assertStatus(201)->assertJsonPath('success', true);

        $order = Order::firstOrFail();

        // (12500 + 1200 + 900) * 2 = 29200
        $this->assertSame('29200.00', $order->items->first()->total_price);
        $this->assertSame('12500.00', $order->items->first()->unit_price);
        $this->assertSame('29200.00', $order->subtotal);
        $this->assertSame('32700.00', $order->total);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertCount(2, $order->items->first()->options);
    }

    public function test_order_ignores_prices_sent_by_client(): void
    {
        $this->actingAs($this->customer)->postJson('/api/orders', $this->payload([
            'items' => [[
                'product_id' => $this->muzzarella->id,
                'variant_id' => $this->familiar->id,
                'quantity' => 1,
                'unit_price' => 1,
                'total_price' => 1,
                'product_name' => 'PRODUCTO GRATIS',
            ]],
            'discount' => 999999,
            'delivery_fee' => 0,
        ]))->assertStatus(201);

        $order = Order::with('items')->firstOrFail();

        $this->assertSame('12500.00', $order->items->first()->unit_price);
        $this->assertSame('Pizza Muzzarella', $order->items->first()->product_name);
        $this->assertSame('0.00', $order->discount);
        $this->assertSame('16000.00', $order->total);
    }

    public function test_order_number_is_generated_and_unique(): void
    {
        $this->actingAs($this->customer)->postJson('/api/orders', $this->payload())->assertStatus(201);
        $this->actingAs($this->customer)->postJson('/api/orders', $this->payload())->assertStatus(201);

        $numbers = Order::pluck('order_number');

        $this->assertCount(2, $numbers);
        $this->assertCount(2, $numbers->unique());
        $this->assertTrue($numbers->every(fn ($n) => str_starts_with($n, 'PIDE-')));
    }

    public function test_guest_cannot_create_order(): void
    {
        $this->postJson('/api/orders', $this->payload())->assertStatus(401);
    }

    public function test_order_rejects_address_from_another_user(): void
    {
        $other = User::create([
            'name' => 'Otro',
            'email' => 'otro@pideaca.com',
            'password' => 'password',
        ]);

        $foreignAddress = Address::create([
            'user_id' => $other->id,
            'country_id' => $this->country->id,
            'street' => 'Calle Falsa',
            'number' => '999',
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/orders', $this->payload(['address_id' => $foreignAddress->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('address_id');

        $this->assertSame(0, Order::count());
    }

    public function test_order_rejects_unavailable_variant(): void
    {
        $unavailable = $this->muzzarella->variants()->where('name', 'Personal')->first();

        $this->actingAs($this->customer)
            ->postJson('/api/orders', $this->payload([
                'items' => [[
                    'product_id' => $this->muzzarella->id,
                    'variant_id' => $unavailable->id,
                    'quantity' => 1,
                ]],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.variant_id');

        $this->assertSame(0, Order::count());
    }

    public function test_order_rejects_product_from_another_provider(): void
    {
        $otherProviderUser = User::create([
            'name' => 'Otro Comercio',
            'email' => 'otrocomercio@pideaca.com',
            'password' => 'password',
        ]);

        $otherProvider = Provider::create([
            'user_id' => $otherProviderUser->id,
            'business_name' => 'Empanadería La otra',
            'is_active' => true,
        ]);

        $foreignProduct = Product::create([
            'provider_id' => $otherProvider->id,
            'category_id' => Category::create([
                'provider_id' => $otherProvider->id,
                'name' => 'Empanadas',
            ])->id,
            'name' => 'Empanada ajena',
            'price' => 1000.00,
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/orders', $this->payload([
                'items' => [['product_id' => $foreignProduct->id, 'quantity' => 1]],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.product_id');

        $this->assertSame(0, Order::count());
    }

    public function test_order_rejects_option_from_another_product(): void
    {
        $otherCategory = Category::create([
            'provider_id' => $this->provider->id,
            'name' => 'Empanadas',
        ]);

        $otherProduct = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $otherCategory->id,
            'name' => 'Empanada',
            'price' => 1800.00,
        ]);

        $foreignOption = ProductOption::create([
            'option_group_id' => ProductOptionGroup::create([
                'product_id' => $otherProduct->id,
                'name' => 'Extras',
            ])->id,
            'name' => 'Ajeno',
            'extra_price' => 0.00,
        ]);

        $this->actingAs($this->customer)
            ->postJson('/api/orders', $this->payload([
                'items' => [[
                    'product_id' => $this->muzzarella->id,
                    'variant_id' => $this->familiar->id,
                    'quantity' => 1,
                    'options' => [$foreignOption->id],
                ]],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.options');

        $this->assertSame(0, Order::count());
    }

    public function test_order_rejects_more_options_than_max_choices(): void
    {
        $group = $this->muzzarella->optionGroups()->first();
        $group->update(['max_choices' => 1]);

        $this->actingAs($this->customer)
            ->postJson('/api/orders', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.options');

        $this->assertSame(0, Order::count());
    }

    public function test_order_requires_at_least_one_item(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/orders', $this->payload(['items' => []]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_failed_order_does_not_leave_partial_rows(): void
    {
        $unavailable = $this->muzzarella->variants()->where('name', 'Personal')->first();

        $this->actingAs($this->customer)->postJson('/api/orders', $this->payload([
            'items' => [
                [
                    'product_id' => $this->muzzarella->id,
                    'variant_id' => $this->familiar->id,
                    'quantity' => 1,
                ],
                [
                    'product_id' => $this->muzzarella->id,
                    'variant_id' => $unavailable->id,
                    'quantity' => 1,
                ],
            ],
        ]))->assertStatus(422);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, \App\Models\OrderItem::count());
    }

    public function test_order_uses_product_price_when_no_variant(): void
    {
        $this->actingAs($this->customer)->postJson('/api/orders', $this->payload([
            'items' => [[
                'product_id' => $this->muzzarella->id,
                'quantity' => 1,
            ]],
        ]))->assertStatus(201);

        $order = Order::with('items')->firstOrFail();

        $this->assertSame('8500.00', $order->items->first()->unit_price);
        $this->assertNull($order->items->first()->variant_id);
        $this->assertNull($order->items->first()->variant_name);
    }

    // --- Seguimiento ---

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'provider_id' => $this->provider->id,
            'user_id' => $this->customer->id,
            'address_id' => $this->address->id,
            'order_number' => 'PIDE-TEST-'.uniqid(),
            'status' => Order::STATUS_PENDING,
            'subtotal' => 100,
            'delivery_fee' => 100,
            'discount' => 0,
            'total' => 200,
            'payment_method' => 'efectivo',
        ], $overrides));
    }

    public function test_provider_can_advance_order_status_step_by_step(): void
    {
        $order = $this->makeOrder();

        $flow = [
            Order::STATUS_CONFIRMED,
            Order::STATUS_IN_PREPARATION,
            Order::STATUS_ON_THE_WAY,
            Order::STATUS_DELIVERED,
        ];

        $this->actingAs($this->providerUser);

        foreach ($flow as $status) {
            $this->patchJson("/api/orders/{$order->id}/status", ['status' => $status])
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('status', $status)
                ->assertJsonPath('order.status', $status);
        }

        $this->assertSame(Order::STATUS_DELIVERED, $order->fresh()->status);
    }

    public function test_order_status_cannot_skip_steps(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->providerUser)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_ON_THE_WAY])
            ->assertStatus(422);

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_delivered_order_cannot_change_again(): void
    {
        $order = $this->makeOrder(['status' => Order::STATUS_DELIVERED]);

        $this->actingAs($this->providerUser)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_PENDING])
            ->assertStatus(422);

        $this->assertSame(Order::STATUS_DELIVERED, $order->fresh()->status);
    }

    public function test_provider_can_cancel_order(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->providerUser)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_CANCELLED])
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_CANCELLED);

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_another_provider_cannot_change_order_status(): void
    {
        $order = $this->makeOrder();

        $intruder = User::create([
            'name' => 'Otro Comercio',
            'email' => 'otrocomercio-seguimiento@pideaca.com',
            'password' => 'password',
        ]);

        Provider::create([
            'user_id' => $intruder->id,
            'business_name' => 'Empanaderia Ajena',
            'is_active' => true,
        ]);

        $this->actingAs($intruder)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_CONFIRMED])
            ->assertStatus(403);

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_client_can_cancel_but_not_advance(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->customer)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_CONFIRMED])
            ->assertStatus(422);

        $this->actingAs($this->customer)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_CANCELLED])
            ->assertOk()
            ->assertJsonPath('status', Order::STATUS_CANCELLED);

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_client_cannot_cancel_once_order_is_on_the_way(): void
    {
        $order = $this->makeOrder(['status' => Order::STATUS_ON_THE_WAY]);

        $this->actingAs($this->customer)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_CANCELLED])
            ->assertStatus(422);

        $this->assertSame(Order::STATUS_ON_THE_WAY, $order->fresh()->status);
    }

    public function test_guest_cannot_update_order_status(): void
    {
        $order = $this->makeOrder();

        $this->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_CONFIRMED])
            ->assertStatus(401);

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_update_status_rejects_unknown_status(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->providerUser)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => 'volando'])
            ->assertStatus(422);

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_update_status_response_includes_the_order_card_data(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->providerUser)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_CONFIRMED])
            ->assertOk()
            ->assertJsonPath('order.provider.business_name', $this->provider->business_name)
            ->assertJsonPath('order.user.email', $this->customer->email)
            ->assertJsonStructure(['order' => ['id', 'order_number', 'status', 'items', 'payments', 'address']]);
    }
}
