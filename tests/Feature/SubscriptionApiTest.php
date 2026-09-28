<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Country;
use App\Models\CustomerSubscription;
use App\Models\Product;
use App\Models\Provider;
use App\Models\SubscriptionPlan;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Provider $provider;

    private Category $category;

    private Product $product;

    private User $client;

    private Country $country;

    private Address $address;

    private User $otherOwner;

    private Provider $otherProvider;

    private Category $otherCategory;

    private Product $otherProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = TypeUser::create(['description' => 'Prestador']);
        UserStatus::create(['status' => 'Activo']);
        $this->country = Country::create(['name' => 'Argentina', 'iso_code' => 'AR']);

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
            'name' => 'Almacen',
        ]);

        $this->product = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->category->id,
            'name' => 'Yerba Mate',
            'price' => 1500,
            'is_available' => true,
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

        $this->otherProduct = Product::create([
            'provider_id' => $this->otherProvider->id,
            'category_id' => $this->otherCategory->id,
            'name' => 'Agua Mineral',
            'price' => 800,
            'is_available' => true,
        ]);

        $this->client = User::create([
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
            'password' => 'password',
        ]);

        $this->address = Address::create([
            'user_id' => $this->client->id,
            'country_id' => $this->country->id,
            'street' => 'Av. Siempre Viva',
            'number' => '742',
        ]);

    }

    public function test_guest_gets_401_on_protected_endpoints(): void
    {
        $subscription = $this->subscribe($this->createPlan());

        $this->postJson('/api/v1/provider/subscription-plans', [])->assertStatus(401);
        $this->postJson('/api/v1/customer/subscriptions', [])->assertStatus(401);
        $this->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/status', [
            'status' => CustomerSubscription::STATUS_PAUSED,
        ])->assertStatus(401)->assertJsonPath('error', 'No autenticado');
    }

    public function test_guest_gets_401_listing_provider_plans(): void
    {
        $this->getJson('/api/v1/provider/subscription-plans')
            ->assertStatus(401)
            ->assertJson(['error' => 'No autenticado']);
    }

    public function test_provider_lists_own_plans_including_hidden_ones(): void
    {
        $visible = $this->createPlan();
        $hidden = $this->createPlan(isActive: false);

        $otroPlan = SubscriptionPlan::create([
            'provider_id' => $this->otherProvider->id,
            'title' => 'Plan Ajeno',
            'frequency' => SubscriptionPlan::FREQUENCY_MONTHLY,
            'price' => 9000,
            'is_active' => true,
        ]);

        $otroPlan->items()->create(['product_id' => $this->otherProduct->id, 'quantity' => 1]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/subscription-plans')
            ->assertOk()
            ->assertJsonCount(2, 'plans')
            ->assertJsonFragment(['id' => $visible->id, 'is_active' => true])
            ->assertJsonFragment(['id' => $hidden->id, 'is_active' => false])
            ->assertJsonMissing(['title' => 'Plan Ajeno']);
    }

    public function test_client_without_commerce_cannot_create_plans(): void
    {
        $this->actingAs($this->client)
            ->postJson('/api/v1/provider/subscription-plans', [])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_provider_creates_plan_with_items(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/subscription-plans', [
                'title' => 'Despensa Semanal',
                'description' => 'Productos de la semana',
                'frequency' => SubscriptionPlan::FREQUENCY_WEEKLY,
                'price' => 45000,
                'discount_percentage' => 10,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('plan.title', 'Despensa Semanal')
            ->assertJsonPath('plan.is_active', true)
            ->assertJsonPath('plan.discount_amount', 4500)
            ->assertJsonPath('plan.items.0.quantity', 2)
            ->assertJsonPath('plan.items.0.name', 'Yerba Mate');

        $this->assertDatabaseHas('subscription_plans', [
            'provider_id' => $this->provider->id,
            'frequency' => SubscriptionPlan::FREQUENCY_WEEKLY,
            'price' => 45000,
            'discount_percentage' => 10,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('subscription_plan_items', [
            'subscription_plan_id' => SubscriptionPlan::first()->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    public function test_provider_edits_own_plan_and_replaces_items(): void
    {
        $plan = $this->createPlan();

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/subscription-plans', [
                'id' => $plan->id,
                'title' => 'Despensa Quincenal',
                'frequency' => SubscriptionPlan::FREQUENCY_BIWEEKLY,
                'price' => 80000,
                'is_active' => false,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 5],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('plan.title', 'Despensa Quincenal')
            ->assertJsonPath('plan.is_active', false);

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'title' => 'Despensa Quincenal',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('subscription_plan_items', [
            'subscription_plan_id' => $plan->id,
            'quantity' => 5,
        ]);
    }

    public function test_editing_keeps_active_state_when_is_active_is_omitted(): void
    {
        $plan = $this->createPlan();

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/subscription-plans', [
                'id' => $plan->id,
                'title' => 'Otro nombre',
                'frequency' => $plan->frequency,
                'price' => $plan->price,
                'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            ])
            ->assertOk()
            ->assertJsonPath('plan.is_active', true);

        $this->assertTrue($plan->fresh()->is_active);
    }

    public function test_provider_cannot_edit_plan_of_another_commerce(): void
    {
        $plan = $this->createPlan();

        $this->actingAs($this->otherOwner)
            ->postJson('/api/v1/provider/subscription-plans', [
                'id' => $plan->id,
                'title' => 'Secuestro',
                'frequency' => SubscriptionPlan::FREQUENCY_DAILY,
                'price' => 1,
                'items' => [['product_id' => $this->otherProduct->id, 'quantity' => 1]],
            ])
            ->assertStatus(404)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id, 'title' => 'Despensa Semanal']);
    }

    public function test_plan_creation_rejects_products_from_another_commerce(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/subscription-plans', [
                'title' => 'Plan mixto',
                'frequency' => SubscriptionPlan::FREQUENCY_DAILY,
                'price' => 1000,
                'items' => [['product_id' => $this->otherProduct->id, 'quantity' => 1]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('subscription_plans', 0);
    }

    public function test_plan_creation_rejects_duplicated_products(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/subscription-plans', [
                'title' => 'Plan repetido',
                'frequency' => SubscriptionPlan::FREQUENCY_DAILY,
                'price' => 1000,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1],
                    ['product_id' => $this->product->id, 'quantity' => 2],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('subscription_plans', 0);
    }

    public function test_plan_creation_validates_payload(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/subscription-plans', [
                'title' => '',
                'frequency' => 'HOURLY',
                'price' => -5,
                'discount_percentage' => 120,
                'items' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'frequency', 'price', 'discount_percentage', 'items']);
    }

    public function test_public_endpoint_lists_only_active_plans(): void
    {
        $visible = $this->createPlan('Plan Visible', SubscriptionPlan::FREQUENCY_WEEKLY, 45000, 10);
        $hidden = $this->createPlan('Plan Oculto', SubscriptionPlan::FREQUENCY_MONTHLY, 90000, 0);
        $hidden->update(['is_active' => false]);

        $this->getJson('/api/v1/public/providers/'.$this->provider->id.'/subscription-plans')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('provider.id', $this->provider->id)
            ->assertJsonPath('plans.0.id', $visible->id)
            ->assertJsonPath('plans.0.items.0.name', 'Yerba Mate')
            ->assertJsonPath('plans.0.discount_amount', 4500)
            ->assertJsonCount(1, 'plans');

        $this->actingAs($this->client)
            ->getJson('/api/v1/public/providers/'.$this->otherProvider->id.'/subscription-plans')
            ->assertOk()
            ->assertJsonCount(0, 'plans');
    }

    public function test_client_subscribes_to_active_plan(): void
    {
        $plan = $this->createPlan();

        $this->actingAs($this->client)
            ->postJson('/api/v1/customer/subscriptions', [
                'subscription_plan_id' => $plan->id,
                'delivery_address_id' => $this->address->id,
                'payment_method' => 'CASH',
                'next_delivery_date' => today()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('subscription.status', CustomerSubscription::STATUS_ACTIVE)
            ->assertJsonPath('subscription.next_delivery_date', today()->toDateString())
            ->assertJsonPath('subscription.plan.id', $plan->id);

        $this->assertDatabaseHas('customer_subscriptions', [
            'user_id' => $this->client->id,
            'provider_id' => $this->provider->id,
            'subscription_plan_id' => $plan->id,
            'delivery_address_id' => $this->address->id,
            'status' => CustomerSubscription::STATUS_ACTIVE,
            'payment_method' => 'CASH',
        ]);
    }

    public function test_client_cannot_subscribe_to_inactive_plan(): void
    {
        $plan = $this->createPlan();
        $plan->update(['is_active' => false]);

        $this->actingAs($this->client)
            ->postJson('/api/v1/customer/subscriptions', [
                'subscription_plan_id' => $plan->id,
                'delivery_address_id' => $this->address->id,
                'payment_method' => 'CASH',
                'next_delivery_date' => today()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscription_plan_id');

        $this->assertDatabaseCount('customer_subscriptions', 0);
    }

    public function test_client_cannot_subscribe_with_foreign_address(): void
    {
        $plan = $this->createPlan();

        $foreignAddress = Address::create([
            'user_id' => $this->otherOwner->id,
            'country_id' => $this->country->id,
            'street' => 'Otra calle',
            'number' => '1',
        ]);

        $this->actingAs($this->client)
            ->postJson('/api/v1/customer/subscriptions', [
                'subscription_plan_id' => $plan->id,
                'delivery_address_id' => $foreignAddress->id,
                'payment_method' => 'CASH',
                'next_delivery_date' => today()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('delivery_address_id');

        $this->assertDatabaseCount('customer_subscriptions', 0);
    }

    public function test_client_cannot_subscribe_twice_to_the_same_plan(): void
    {
        $plan = $this->createPlan();
        $this->subscribe($plan);

        $this->actingAs($this->client)
            ->postJson('/api/v1/customer/subscriptions', [
                'subscription_plan_id' => $plan->id,
                'delivery_address_id' => $this->address->id,
                'payment_method' => 'CASH',
                'next_delivery_date' => today()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscription_plan_id');

        $this->assertDatabaseCount('customer_subscriptions', 1);
    }

    public function test_client_can_pause_resume_and_cancel_own_subscription(): void
    {
        $plan = $this->createPlan();
        $subscription = $this->subscribe($plan);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/status', ['status' => CustomerSubscription::STATUS_PAUSED])
            ->assertOk()
            ->assertJsonPath('subscription.status', CustomerSubscription::STATUS_PAUSED);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/status', ['status' => CustomerSubscription::STATUS_ACTIVE])
            ->assertOk()
            ->assertJsonPath('subscription.status', CustomerSubscription::STATUS_ACTIVE);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/status', ['status' => CustomerSubscription::STATUS_CANCELLED])
            ->assertOk()
            ->assertJsonPath('subscription.status', CustomerSubscription::STATUS_CANCELLED);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/status', ['status' => CustomerSubscription::STATUS_PAUSED])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(
            CustomerSubscription::STATUS_CANCELLED,
            $subscription->fresh()->status
        );
    }

    public function test_client_cannot_change_subscription_of_another_user(): void
    {
        $plan = $this->createPlan();
        $subscription = $this->subscribe($plan);

        $otroCliente = User::create([
            'name' => 'Otro Cliente',
            'email' => 'otro@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($otroCliente)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/status', ['status' => CustomerSubscription::STATUS_CANCELLED])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertSame(CustomerSubscription::STATUS_ACTIVE, $subscription->fresh()->status);
    }

    public function test_resuming_reactivates_a_past_due_date(): void
    {
        $plan = $this->createPlan();
        $subscription = $this->subscribe($plan, today()->subDays(10)->toDateString(), CustomerSubscription::STATUS_PAUSED);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/status', ['status' => CustomerSubscription::STATUS_ACTIVE])
            ->assertOk();

        $this->assertSame(
            today()->toDateString(),
            $subscription->fresh()->next_delivery_date->toDateString()
        );
    }

    private function createPlan(
        string $title = 'Despensa Semanal',
        string $frequency = SubscriptionPlan::FREQUENCY_WEEKLY,
        float $price = 45000,
        float $discount = 0,
        bool $isActive = true
    ): SubscriptionPlan {
        $plan = SubscriptionPlan::create([
            'provider_id' => $this->provider->id,
            'title' => $title,
            'frequency' => $frequency,
            'price' => $price,
            'discount_percentage' => $discount,
            'is_active' => $isActive,
        ]);

        $plan->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        return $plan;
    }

    private function subscribe(
        SubscriptionPlan $plan,
        ?string $nextDelivery = null,
        string $status = CustomerSubscription::STATUS_ACTIVE
    ): CustomerSubscription {
        return CustomerSubscription::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->provider->id,
            'subscription_plan_id' => $plan->id,
            'status' => $status,
            'next_delivery_date' => $nextDelivery ?? today()->toDateString(),
            'delivery_address_id' => $this->address->id,
            'payment_method' => 'CASH',
        ]);
    }
}
