<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Country;
use App\Models\CustomerSubscription;
use App\Models\CustomerSubscriptionItem;
use App\Models\Product;
use App\Models\Provider;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cupos por plan, ventana de entrega, sabores por suscripcion, cobro de cada
 * ciclo y reporte de ingresos recurrentes.
 */
class SubscriptionBillingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Provider $provider;

    private Product $product;

    private Product $otroProducto;

    private User $client;

    private Address $address;

    private User $otroCliente;

    private Address $otroAddress;

    private Provider $otherProvider;

    private User $otherOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = TypeUser::create(['description' => 'Prestador']);
        UserStatus::create(['status' => 'Activo']);
        $country = Country::create(['name' => 'Argentina', 'iso_code' => 'AR']);

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

        $category = Category::create([
            'provider_id' => $this->provider->id,
            'name' => 'Almacen',
        ]);

        $this->product = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $category->id,
            'name' => 'Yerba Mate',
            'price' => 1500,
            'is_available' => true,
        ]);

        $this->otroProducto = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $category->id,
            'name' => 'Yerba Sin Verde',
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

        $this->client = User::create([
            'name' => 'Cliente',
            'email' => 'cliente@example.com',
            'password' => 'password',
        ]);

        $this->address = Address::create([
            'user_id' => $this->client->id,
            'country_id' => $country->id,
            'street' => 'Av. Siempre Viva',
            'number' => '742',
        ]);

        $this->otroCliente = User::create([
            'name' => 'Otro Cliente',
            'email' => 'otro@example.com',
            'password' => 'password',
        ]);

        $this->otroAddress = Address::create([
            'user_id' => $this->otroCliente->id,
            'country_id' => $country->id,
            'street' => 'Calle Falsa',
            'number' => '123',
        ]);
    }

    public function test_guest_gets_401_on_revenue(): void
    {
        $this->getJson('/api/v1/provider/subscriptions/revenue')
            ->assertStatus(401)
            ->assertJson(['error' => 'No autenticado']);
    }

    public function test_client_without_commerce_gets_403_on_revenue(): void
    {
        $this->actingAs($this->client)
            ->getJson('/api/v1/provider/subscriptions/revenue')
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------- Cupos

    public function test_plan_stores_the_capacity_and_exposes_the_remaining_slots(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/subscription-plans', [
                'title' => 'Despensa Semanal',
                'frequency' => SubscriptionPlan::FREQUENCY_WEEKLY,
                'price' => 45000,
                'capacity' => 5,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('plan.capacity', 5)
            ->assertJsonPath('plan.available_slots', 5);

        $this->assertDatabaseHas('subscription_plans', ['capacity' => 5]);
    }

    public function test_a_plan_without_capacity_has_no_limit(): void
    {
        $plan = $this->createPlan();

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/subscription-plans', $this->planPayload($plan))
            ->assertCreated()
            ->assertJsonPath('plan.capacity', null)
            ->assertJsonPath('plan.available_slots', null);
    }

    public function test_capacity_can_be_removed_sending_null(): void
    {
        $plan = $this->createPlan(capacity: 3);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/subscription-plans', $this->planPayload($plan, editing: true) + ['capacity' => null])
            ->assertOk()
            ->assertJsonPath('plan.capacity', null);

        $this->assertNull($plan->fresh()->capacity);
    }

    public function test_capacity_must_be_a_positive_number(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/subscription-plans', $this->planPayload($this->createPlan()) + ['capacity' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('capacity');
    }

    public function test_subscription_is_rejected_when_the_plan_is_full(): void
    {
        $plan = $this->createPlan(capacity: 1);

        $this->subscribe($plan, $this->client, $this->address);

        $this->actingAs($this->otroCliente)
            ->postJson('/api/v1/customer/subscriptions', $this->subscriptionPayload($plan, $this->otroAddress))
            ->assertStatus(422)
            ->assertJsonValidationErrors('subscription_plan_id');

        $this->assertDatabaseCount('customer_subscriptions', 1);
    }

    public function test_a_paused_subscription_keeps_holding_its_slot(): void
    {
        $plan = $this->createPlan(capacity: 1);
        $subscription = $this->subscribe($plan, $this->client, $this->address);

        $subscription->update(['status' => CustomerSubscription::STATUS_PAUSED]);

        $this->assertFalse($plan->fresh()->hasCapacity());
        $this->assertSame(0, $plan->fresh()->availableSlots());

        $this->actingAs($this->otroCliente)
            ->postJson('/api/v1/customer/subscriptions', $this->subscriptionPayload($plan, $this->otroAddress))
            ->assertStatus(422);
    }

    public function test_a_cancelled_subscription_frees_the_slot(): void
    {
        $plan = $this->createPlan(capacity: 1);
        $subscription = $this->subscribe($plan, $this->client, $this->address);

        $subscription->update(['status' => CustomerSubscription::STATUS_CANCELLED]);

        $this->assertTrue($plan->fresh()->hasCapacity());

        $this->actingAs($this->otroCliente)
            ->postJson('/api/v1/customer/subscriptions', $this->subscriptionPayload($plan, $this->otroAddress))
            ->assertCreated();
    }

    public function test_lowering_the_capacity_keeps_the_existing_subscribers(): void
    {
        $plan = $this->createPlan(capacity: 3);
        $this->subscribe($plan, $this->client, $this->address);
        $this->subscribe($plan, $this->otroCliente, $this->otroAddress);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/provider/subscription-plans', $this->planPayload($plan, editing: true) + ['capacity' => 1])
            ->assertOk()
            ->assertJsonPath('plan.available_slots', 0);

        // Nadie se cae por sobrecupo: el limite nuevo frena altas, no cancela.
        $this->assertDatabaseCount('customer_subscriptions', 2);
        $this->assertDatabaseMissing('customer_subscriptions', ['status' => CustomerSubscription::STATUS_CANCELLED]);
    }

    // ------------------------------------------------------- Ventana de entrega

    public function test_client_saves_the_preferred_day_and_time(): void
    {
        $plan = $this->createPlan();

        $this->actingAs($this->client)
            ->postJson('/api/v1/customer/subscriptions', $this->subscriptionPayload($plan, $this->address) + [
                'preferred_delivery_day' => 6,
                'preferred_delivery_time' => '18:30',
            ])
            ->assertCreated()
            ->assertJsonPath('subscription.preferred_delivery_day', 6)
            ->assertJsonPath('subscription.preferred_delivery_time', '18:30');

        $this->assertDatabaseHas('customer_subscriptions', [
            'preferred_delivery_day' => 6,
            'preferred_delivery_time' => '18:30:00',
        ]);
    }

    public function test_preferred_day_must_be_a_valid_weekday(): void
    {
        $plan = $this->createPlan();

        $this->actingAs($this->client)
            ->postJson('/api/v1/customer/subscriptions', $this->subscriptionPayload($plan, $this->address) + [
                'preferred_delivery_day' => 8,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('preferred_delivery_day');
    }

    public function test_preferred_time_must_be_a_valid_hour(): void
    {
        $plan = $this->createPlan();

        $this->actingAs($this->client)
            ->postJson('/api/v1/customer/subscriptions', $this->subscriptionPayload($plan, $this->address) + [
                'preferred_delivery_time' => '25:00',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('preferred_delivery_time');
    }

    // --------------------------------------------------------- Sabores del pedido

    public function test_client_can_redistribute_the_plan_quantity_between_its_products(): void
    {
        $plan = $this->createPlanConDosSabores();
        $subscription = $this->subscribe($plan, $this->client, $this->address);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/items', [
                'items' => [['product_id' => $this->otroProducto->id, 'quantity' => 2]],
            ])
            ->assertOk()
            ->assertJsonPath('subscription.items.0.product_id', $this->otroProducto->id)
            ->assertJsonPath('subscription.items.0.quantity', 2);

        $this->assertDatabaseHas('customer_subscription_items', [
            'customer_subscription_id' => $subscription->id,
            'product_id' => $this->otroProducto->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseMissing('customer_subscription_items', [
            'customer_subscription_id' => $subscription->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_client_can_keep_the_original_flavours(): void
    {
        $plan = $this->createPlanConDosSabores();
        $subscription = $this->subscribe($plan, $this->client, $this->address);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/items', [
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1],
                    ['product_id' => $this->otroProducto->id, 'quantity' => 1],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseCount('customer_subscription_items', 2);
    }

    public function test_client_cannot_ask_for_more_than_the_plan_includes(): void
    {
        $plan = $this->createPlanConDosSabores();
        $subscription = $this->subscribe($plan, $this->client, $this->address);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/items', [
                'items' => [['product_id' => $this->product->id, 'quantity' => 5]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('customer_subscription_items', 0);
    }

    public function test_client_cannot_add_products_that_are_not_in_the_plan(): void
    {
        $plan = $this->createPlan();
        $subscription = $this->subscribe($plan, $this->client, $this->address);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/items', [
                'items' => [['product_id' => $this->otroProducto->id, 'quantity' => 2]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_client_cannot_repeat_the_same_flavour(): void
    {
        $plan = $this->createPlanConDosSabores();
        $subscription = $this->subscribe($plan, $this->client, $this->address);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/items', [
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1],
                    ['product_id' => $this->product->id, 'quantity' => 1],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_client_cannot_change_flavours_of_another_subscription(): void
    {
        $plan = $this->createPlanConDosSabores();
        $subscription = $this->subscribe($plan, $this->client, $this->address);

        $this->actingAs($this->otroCliente)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/items', [
                'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            ])
            ->assertStatus(403);
    }

    public function test_flavours_cannot_change_on_a_cancelled_subscription(): void
    {
        $plan = $this->createPlanConDosSabores();
        $subscription = $this->subscribe($plan, $this->client, $this->address);
        $subscription->update(['status' => CustomerSubscription::STATUS_CANCELLED]);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/items', [
                'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            ])
            ->assertStatus(422);
    }

    public function test_guest_gets_401_when_changing_flavours(): void
    {
        $subscription = $this->subscribe($this->createPlanConDosSabores(), $this->client, $this->address);

        $this->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/items', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
        ])->assertStatus(401);
    }

    public function test_subscription_starts_with_the_chosen_flavours(): void
    {
        $plan = $this->createPlanConDosSabores();

        $this->actingAs($this->client)
            ->postJson('/api/v1/customer/subscriptions', $this->subscriptionPayload($plan, $this->address) + [
                'items' => [['product_id' => $this->otroProducto->id, 'quantity' => 2]],
            ])
            ->assertCreated()
            ->assertJsonPath('subscription.items.0.quantity', 2);

        $this->assertDatabaseCount('customer_subscription_items', 1);
    }

    public function test_items_for_order_prefers_the_client_choice_and_falls_back_to_the_plan(): void
    {
        $plan = $this->createPlanConDosSabores();
        $subscription = $this->subscribe($plan, $this->client, $this->address);
        $subscription->load('plan.items');

        $this->assertCount(2, $subscription->itemsForOrder());

        CustomerSubscriptionItem::create([
            'customer_subscription_id' => $subscription->id,
            'product_id' => $this->otroProducto->id,
            'quantity' => 2,
        ]);

        $subscription->refresh()->load('plan.items');

        $elegidos = $subscription->itemsForOrder();

        $this->assertCount(1, $elegidos);
        $this->assertSame($this->otroProducto->id, $elegidos->first()['product']->id);
        $this->assertSame(2, $elegidos->first()['quantity']);
    }

    // ------------------------------------------------------------------ Cobros

    public function test_guest_gets_401_listing_own_subscriptions(): void
    {
        $this->getJson('/api/v1/customer/subscriptions')
            ->assertStatus(401)
            ->assertJson(['error' => 'No autenticado']);
    }

    public function test_client_lists_own_subscriptions_with_plan_flavours_and_charges(): void
    {
        $plan = $this->createPlanConDosSabores();
        $subscription = $this->subscribe($plan, $this->client, $this->address);
        $subscription->items()->create(['product_id' => $this->otroProducto->id, 'quantity' => 2]);
        $this->createPayment($subscription, SubscriptionPayment::STATUS_PAID, paidAt: now());

        // Una suscripcion de otro cliente no puede aparecer en la lista.
        $this->subscribe($this->createPlan(), $this->otroCliente, $this->otroAddress);

        $this->actingAs($this->client)
            ->getJson('/api/v1/customer/subscriptions')
            ->assertOk()
            ->assertJsonCount(1, 'subscriptions')
            ->assertJsonPath('subscriptions.0.id', $subscription->id)
            ->assertJsonPath('subscriptions.0.status', CustomerSubscription::STATUS_ACTIVE)
            ->assertJsonPath('subscriptions.0.provider.business_name', 'Comercio Uno')
            ->assertJsonPath('subscriptions.0.plan.title', 'Despensa Sabores')
            ->assertJsonPath('subscriptions.0.items.0.quantity', 2)
            ->assertJsonPath('subscriptions.0.payments.0.status', SubscriptionPayment::STATUS_PAID);
    }

    public function test_subscription_list_is_empty_for_a_client_without_subscriptions(): void
    {
        $this->actingAs($this->client)
            ->getJson('/api/v1/customer/subscriptions')
            ->assertOk()
            ->assertJsonPath('subscriptions', []);
    }

    public function test_provider_can_confirm_a_pending_charge(): void
    {
        $payment = $this->createPayment();

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/subscription-payments/'.$payment->id, ['status' => 'PAID'])
            ->assertOk()
            ->assertJsonPath('payment.status', SubscriptionPayment::STATUS_PAID);

        $this->assertNotNull($payment->fresh()->paid_at);
    }

    public function test_provider_can_mark_a_charge_as_failed_and_the_subscription_stops(): void
    {
        $subscription = $this->subscribe($this->createPlan(), $this->client, $this->address);
        $payment = $this->createPayment($subscription);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/subscription-payments/'.$payment->id, [
                'status' => 'FAILED',
                'failure_reason' => 'no se pudo cobrar',
            ])
            ->assertOk()
            ->assertJsonPath('payment.status', SubscriptionPayment::STATUS_FAILED);

        $this->assertSame('no se pudo cobrar', $payment->fresh()->failure_reason);
        $this->assertSame(CustomerSubscription::STATUS_PAYMENT_FAILED, $subscription->fresh()->status);
    }

    public function test_client_cannot_reactivate_while_a_charge_is_unpaid(): void
    {
        $subscription = $this->subscribe($this->createPlan(), $this->client, $this->address);
        $subscription->update(['status' => CustomerSubscription::STATUS_PAYMENT_FAILED]);
        $payment = $this->createPayment($subscription, SubscriptionPayment::STATUS_FAILED);

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/status', ['status' => 'ACTIVE'])
            ->assertStatus(422);

        $this->assertSame(CustomerSubscription::STATUS_PAYMENT_FAILED, $subscription->fresh()->status);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/subscription-payments/'.$payment->id, ['status' => 'PAID'])
            ->assertOk();

        $this->actingAs($this->client)
            ->putJson('/api/v1/customer/subscriptions/'.$subscription->id.'/status', ['status' => 'ACTIVE'])
            ->assertOk()
            ->assertJsonPath('subscription.status', CustomerSubscription::STATUS_ACTIVE);
    }

    public function test_provider_cannot_confirm_a_charge_of_another_commerce(): void
    {
        $payment = $this->createPayment();

        $this->actingAs($this->otherOwner)
            ->putJson('/api/v1/provider/subscription-payments/'.$payment->id, ['status' => 'PAID'])
            ->assertStatus(404);

        $this->assertSame(SubscriptionPayment::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_client_cannot_confirm_charges(): void
    {
        $payment = $this->createPayment();

        $this->actingAs($this->client)
            ->putJson('/api/v1/provider/subscription-payments/'.$payment->id, ['status' => 'PAID'])
            ->assertStatus(403);

        $this->assertSame(SubscriptionPayment::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_an_already_paid_charge_cannot_be_paid_again(): void
    {
        $payment = $this->createPayment(status: SubscriptionPayment::STATUS_PAID);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/subscription-payments/'.$payment->id, ['status' => 'PAID'])
            ->assertStatus(422);
    }

    public function test_charge_status_is_validated(): void
    {
        $payment = $this->createPayment();

        $this->actingAs($this->owner)
            ->putJson('/api/v1/provider/subscription-payments/'.$payment->id, ['status' => 'REFUNDED'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    // ---------------------------------------------------------------- Reporte

    public function test_revenue_report_projects_the_monthly_fixed_income(): void
    {
        $plan = $this->createPlan(frequency: SubscriptionPlan::FREQUENCY_WEEKLY, price: 45000);
        $this->subscribe($plan, $this->client, $this->address);

        // 45.000 por semana, unas 4,29 semanas al mes.
        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/subscriptions/revenue')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('recurring.active_subscriptions', 1)
            ->assertJsonPath('recurring.mrr', round(45000 * 30 / 7, 2))
            ->assertJsonPath('recurring.by_plan.0.subscribers', 1)
            ->assertJsonPath('recurring.by_plan.0.title', $plan->title)
            ->assertJsonPath('recurring.by_plan.0.mrr', round(45000 * 30 / 7, 2))
            ->assertJsonPath('recurring.by_frequency.WEEKLY', round(45000 * 30 / 7, 2));
    }

    public function test_revenue_report_uses_the_charge_amount_after_discount(): void
    {
        $plan = $this->createPlan(frequency: SubscriptionPlan::FREQUENCY_MONTHLY, price: 30000, discount: 15);
        $this->subscribe($plan, $this->client, $this->address);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/subscriptions/revenue')
            ->assertOk()
            ->assertJsonPath('recurring.by_plan.0.charge_amount', 25500)
            ->assertJsonPath('recurring.mrr', 25500);
    }

    public function test_revenue_report_separates_collected_from_pending_cash(): void
    {
        $subscription = $this->subscribe($this->createPlan(), $this->client, $this->address);

        $this->createPayment($subscription, SubscriptionPayment::STATUS_PAID, paidAt: now()->subDays(5));
        $this->createPayment($subscription, SubscriptionPayment::STATUS_PAID, paidAt: now()->subDays(40));
        $this->createPayment($subscription, SubscriptionPayment::STATUS_PENDING);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/subscriptions/revenue')
            ->assertOk()
            ->assertJsonPath('cash.collected_last_30_days', 45000)
            ->assertJsonPath('cash.collected_previous_30_days', 45000)
            ->assertJsonPath('cash.pending_amount', 45000)
            ->assertJsonPath('pending_charges.0.status', SubscriptionPayment::STATUS_PENDING)
            ->assertJsonPath('pending_charges.0.customer.name', 'Cliente');
    }

    public function test_revenue_report_separates_delivery_fees_from_the_plan_income(): void
    {
        $subscription = $this->subscribe($this->createPlan(), $this->client, $this->address);

        $payment = $this->createPayment($subscription, SubscriptionPayment::STATUS_PAID, paidAt: now());
        $payment->update(['subtotal' => 45000, 'discount' => 0, 'delivery_fee' => 3500, 'total' => 48500]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/subscriptions/revenue')
            ->assertOk()
            ->assertJsonPath('cash.collected_last_30_days', 48500)
            ->assertJsonPath('cash.from_plans_last_30_days', 45000)
            ->assertJsonPath('cash.delivery_fees_last_30_days', 3500)
            ->assertJsonPath('recurring.mrr_basis', 'precio del plan con descuento, sin envio');
    }

    public function test_revenue_report_groups_the_history_by_month(): void
    {
        $subscription = $this->subscribe($this->createPlan(), $this->client, $this->address);

        $this->createPayment($subscription, SubscriptionPayment::STATUS_PAID, periodStart: today()->startOfMonth());
        $this->createPayment($subscription, SubscriptionPayment::STATUS_FAILED, periodStart: today()->startOfMonth());
        $this->createPayment($subscription, SubscriptionPayment::STATUS_PAID, periodStart: today()->subMonth()->startOfMonth());

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/subscriptions/revenue?months=2')
            ->assertOk()
            ->assertJsonCount(2, 'history')
            ->assertJsonPath('history.0.period', today()->subMonth()->format('Y-m'))
            ->assertJsonPath('history.0.collected', 45000)
            ->assertJsonPath('history.1.period', today()->format('Y-m'))
            ->assertJsonPath('history.1.collected', 45000)
            ->assertJsonPath('history.1.pending', 0)
            ->assertJsonPath('history.1.failed', 45000)
            ->assertJsonPath('history.1.charges', 2);
    }

    public function test_revenue_report_ignores_other_commerces(): void
    {
        $otroPlan = SubscriptionPlan::create([
            'provider_id' => $this->otherProvider->id,
            'title' => 'Plan ajeno',
            'frequency' => SubscriptionPlan::FREQUENCY_MONTHLY,
            'price' => 99999,
            'is_active' => true,
        ]);

        $suscripcionAjena = CustomerSubscription::create([
            'user_id' => $this->otroCliente->id,
            'provider_id' => $this->otherProvider->id,
            'subscription_plan_id' => $otroPlan->id,
            'status' => CustomerSubscription::STATUS_ACTIVE,
            'next_delivery_date' => today()->toDateString(),
            'delivery_address_id' => $this->otroAddress->id,
            'payment_method' => 'CASH',
        ]);

        $this->createPayment($suscripcionAjena, SubscriptionPayment::STATUS_PAID, paidAt: now());

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/subscriptions/revenue')
            ->assertOk()
            ->assertJsonPath('recurring.mrr', 0)
            ->assertJsonPath('cash.collected_last_30_days', 0);
    }

    public function test_revenue_report_counts_paused_and_failed_subscriptions(): void
    {
        $plan = $this->createPlan(capacity: 5);

        $pausada = $this->subscribe($plan, $this->client, $this->address);
        $pausada->update(['status' => CustomerSubscription::STATUS_PAUSED]);

        $fallida = $this->subscribe($plan, $this->otroCliente, $this->otroAddress);
        $fallida->update(['status' => CustomerSubscription::STATUS_PAYMENT_FAILED]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/subscriptions/revenue')
            ->assertOk()
            ->assertJsonPath('recurring.active_subscriptions', 0)
            ->assertJsonPath('recurring.paused_subscriptions', 1)
            ->assertJsonPath('recurring.payment_failed', 1)
            ->assertJsonPath('recurring.mrr', 0);
    }

    public function test_revenue_report_validates_the_months_filter(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/provider/subscriptions/revenue?months=99')
            ->assertStatus(422)
            ->assertJsonValidationErrors('months');
    }

    // ------------------------------------------------------------- Auxiliares

    private function createPlan(
        string $frequency = SubscriptionPlan::FREQUENCY_WEEKLY,
        float $price = 45000,
        float $discount = 0,
        ?int $capacity = null,
        int $quantity = 2
    ): SubscriptionPlan {
        $plan = SubscriptionPlan::create([
            'provider_id' => $this->provider->id,
            'title' => 'Plan '.strtolower($frequency),
            'frequency' => $frequency,
            'price' => $price,
            'discount_percentage' => $discount,
            'capacity' => $capacity,
            'is_active' => true,
        ]);

        $plan->items()->create([
            'product_id' => $this->product->id,
            'quantity' => $quantity,
        ]);

        return $plan;
    }

    private function createPlanConDosSabores(): SubscriptionPlan
    {
        $plan = SubscriptionPlan::create([
            'provider_id' => $this->provider->id,
            'title' => 'Despensa Sabores',
            'frequency' => SubscriptionPlan::FREQUENCY_WEEKLY,
            'price' => 45000,
            'discount_percentage' => 0,
            'is_active' => true,
        ]);

        $plan->items()->create(['product_id' => $this->product->id, 'quantity' => 1]);
        $plan->items()->create(['product_id' => $this->otroProducto->id, 'quantity' => 1]);

        return $plan;
    }

    private function subscribe(SubscriptionPlan $plan, User $user, Address $address): CustomerSubscription
    {
        return CustomerSubscription::create([
            'user_id' => $user->id,
            'provider_id' => $plan->provider_id,
            'subscription_plan_id' => $plan->id,
            'status' => CustomerSubscription::STATUS_ACTIVE,
            'next_delivery_date' => today()->toDateString(),
            'delivery_address_id' => $address->id,
            'payment_method' => 'CASH',
        ]);
    }

    private function createPayment(
        ?CustomerSubscription $subscription = null,
        string $status = SubscriptionPayment::STATUS_PENDING,
        ?string $paidAt = null,
        ?string $periodStart = null
    ): SubscriptionPayment {
        $subscription ??= $this->subscribe($this->createPlan(), $this->client, $this->address);

        return SubscriptionPayment::create([
            'customer_subscription_id' => $subscription->id,
            'provider_id' => $subscription->provider_id,
            'user_id' => $subscription->user_id,
            'gateway' => 'manual',
            'status' => $status,
            'attempt' => 1,
            'subtotal' => 45000,
            'discount' => 0,
            'delivery_fee' => 0,
            'total' => 45000,
            'period_start' => $periodStart ?? today()->startOfMonth()->toDateString(),
            'period_end' => today()->addWeek()->toDateString(),
            'paid_at' => $paidAt,
        ]);
    }

    private function planPayload(SubscriptionPlan $plan, bool $editing = false): array
    {
        return array_filter([
            'id' => $editing ? $plan->id : null,
            'title' => $plan->title,
            'description' => $plan->description,
            'frequency' => $plan->frequency,
            'price' => (float) $plan->price,
            'discount_percentage' => (float) $plan->discount_percentage,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
        ], fn ($value) => $value !== null);
    }

    private function subscriptionPayload(SubscriptionPlan $plan, Address $address): array
    {
        return [
            'subscription_plan_id' => $plan->id,
            'delivery_address_id' => $address->id,
            'payment_method' => 'CASH',
            'next_delivery_date' => today()->addDay()->toDateString(),
        ];
    }
}
