<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Country;
use App\Models\Order;
use App\Models\Provider;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRatingTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $merchantUser;

    private Provider $provider;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $clientType = TypeUser::create(['description' => 'Cliente']);
        $prestadorType = TypeUser::create(['description' => 'Prestador']);
        UserStatus::create(['status' => 'Activo']);
        $country = Country::create(['name' => 'Argentina', 'iso_code' => 'AR']);

        $this->merchantUser = User::create([
            'name' => 'Comercio Don Pepe',
            'email' => 'pepe@example.com',
            'type_user_id' => $prestadorType->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->merchantUser->id,
            'business_name' => 'Comercio Don Pepe',
            'is_active' => true,
        ]);

        $this->client = User::create([
            'name' => 'Ana Compradora',
            'email' => 'ana@example.com',
            'type_user_id' => $clientType->id,
            'password' => 'password',
        ]);

        $address = Address::create([
            'user_id' => $this->client->id,
            'country_id' => $country->id,
            'street' => 'Av. Santa Fe',
            'number' => '123',
            'is_primary' => true,
        ]);

        $this->order = Order::create([
            'provider_id' => $this->provider->id,
            'user_id' => $this->client->id,
            'address_id' => $address->id,
            'order_number' => 'PIDE-2026-9999',
            'status' => Order::STATUS_DELIVERED,
            'subtotal' => 1000,
            'delivery_fee' => 0,
            'discount' => 0,
            'total' => 1000,
            'payment_method' => 'cash',
        ]);
    }

    public function test_customer_can_rate_delivered_order_and_merchant_rating_updates(): void
    {
        $response = $this->actingAs($this->client)
            ->postJson(route('api.orders.rate', $this->order), [
                'stars' => 5,
                'comment' => 'Excelente servicio y rapidez.',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'customer_rated' => true,
                'provider_global_rating' => 5.0,
            ]);

        $this->assertDatabaseHas('order_ratings', [
            'order_id' => $this->order->id,
            'reviewer_id' => $this->client->id,
            'ratee_id' => $this->merchantUser->id,
            'stars' => 5,
        ]);
    }

    public function test_merchant_can_rate_customer_for_delivered_order(): void
    {
        $response = $this->actingAs($this->merchantUser)
            ->postJson(route('api.orders.rate', $this->order), [
                'stars' => 4,
                'comment' => 'Cliente amable y puntual.',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'provider_rated' => true,
            ]);

        $this->assertDatabaseHas('order_ratings', [
            'order_id' => $this->order->id,
            'reviewer_id' => $this->merchantUser->id,
            'ratee_id' => $this->client->id,
            'stars' => 4,
        ]);
    }

    public function test_cannot_rate_order_if_not_delivered(): void
    {
        $this->order->update(['status' => Order::STATUS_IN_PREPARATION]);

        $response = $this->actingAs($this->client)
            ->postJson(route('api.orders.rate', $this->order), [
                'stars' => 5,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Solo se pueden calificar pedidos entregados.',
            ]);
    }
}
