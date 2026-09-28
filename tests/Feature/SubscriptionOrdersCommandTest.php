<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Country;
use App\Models\CustomerSubscription;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Provider;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Database\Seeders\UnitOfMeasureSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SubscriptionOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    private Provider $provider;

    private Product $product;

    private User $client;

    private Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitOfMeasureSeeder::class);

        $prestador = TypeUser::create(['description' => 'Prestador']);
        UserStatus::create(['status' => 'Activo']);
        $country = Country::create(['name' => 'Argentina', 'iso_code' => 'AR']);

        $owner = User::create([
            'name' => 'Comercio Uno',
            'email' => 'comercio1@example.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $owner->id,
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
            'track_stock' => false,
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
    }

    public function test_generates_one_order_per_due_subscription_and_advances_each_frequency(): void
    {
        $esperado = [
            SubscriptionPlan::FREQUENCY_DAILY => today()->addDay()->toDateString(),
            SubscriptionPlan::FREQUENCY_WEEKLY => today()->addWeek()->toDateString(),
            SubscriptionPlan::FREQUENCY_BIWEEKLY => today()->addWeeks(2)->toDateString(),
            SubscriptionPlan::FREQUENCY_MONTHLY => today()->addMonth()->toDateString(),
        ];

        $subscriptions = [];

        foreach ($esperado as $frequency => $nextDate) {
            $subscriptions[] = $this->subscribe($this->createPlan($frequency));
        }

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $this->assertDatabaseCount('orders', 4);

        foreach ($subscriptions as $subscription) {
            $this->assertSame(
                $esperado[$subscription->plan->frequency],
                $subscription->fresh()->next_delivery_date->toDateString()
            );
        }
    }

    public function test_order_uses_plan_price_and_discount_with_plan_items(): void
    {
        $plan = $this->createPlan(SubscriptionPlan::FREQUENCY_WEEKLY, 45000, 10);
        $subscription = $this->subscribe($plan);

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $order = Order::firstOrFail();

        $deliveryFee = (float) config('fastdelivery.delivery_fee');

        $this->assertSame('pending', $order->status);
        $this->assertEquals(45000.00, (float) $order->subtotal);
        $this->assertEquals(4500.00, (float) $order->discount);
        $this->assertEquals($deliveryFee, (float) $order->delivery_fee);
        $this->assertEquals(45000 - 4500 + $deliveryFee, (float) $order->total);
        $this->assertSame($subscription->id, $order->customer_subscription_id);
        $this->assertSame($this->client->id, $order->user_id);
        $this->assertSame($this->provider->id, $order->provider_id);
        $this->assertSame($this->address->id, $order->address_id);
        $this->assertSame('CASH', $order->payment_method);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Yerba Mate',
            'quantity' => 2,
            'unit_price' => 1500,
            'total_price' => 3000,
        ]);

        $this->assertSame($order->id, $subscription->orders()->first()->id);
    }

    public function test_ignores_subscriptions_that_are_not_active_or_not_due(): void
    {
        $this->subscribe($this->createPlan(), today()->addDays(3)->toDateString());
        $this->subscribe($this->createPlan(), today()->toDateString(), CustomerSubscription::STATUS_PAUSED);
        $this->subscribe($this->createPlan(), today()->toDateString(), CustomerSubscription::STATUS_CANCELLED);

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_running_twice_the_same_day_does_not_duplicate_orders(): void
    {
        $subscription = $this->subscribe($this->createPlan());

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();
        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertSame(
            today()->addWeek()->toDateString(),
            $subscription->fresh()->next_delivery_date->toDateString()
        );
    }

    public function test_accepts_a_date_option_to_process_a_specific_day(): void
    {
        $subscription = $this->subscribe(
            $this->createPlan(SubscriptionPlan::FREQUENCY_MONTHLY),
            '2027-01-31'
        );

        $this->artisan('subscriptions:generate-orders', ['--date' => '2027-01-31'])->assertSuccessful();

        $this->assertDatabaseCount('orders', 1);

        // 31/01 + 1 mes no desborda a marzo.
        $this->assertSame(
            '2027-02-28',
            $subscription->fresh()->next_delivery_date->toDateString()
        );
    }

    public function test_skips_backlog_and_schedules_the_next_cycle_in_the_future(): void
    {
        $subscription = $this->subscribe(
            $this->createPlan(SubscriptionPlan::FREQUENCY_WEEKLY),
            today()->subDays(21)->toDateString()
        );

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(
            today()->addWeek()->toDateString(),
            $subscription->fresh()->next_delivery_date->toDateString()
        );
    }

    public function test_deducts_stock_and_records_the_sale_movement(): void
    {
        $this->provider->update(['has_inventory_control' => true]);
        $this->product->update(['track_stock' => true]);

        $inventory = ProductInventory::create([
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'allow_negative_stock' => false,
        ]);

        $subscription = $this->subscribe($this->createPlan(SubscriptionPlan::FREQUENCY_DAILY, price: 3000, quantity: 3));

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $this->assertSame(7.0, (float) $inventory->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'order_id' => Order::firstOrFail()->id,
            'movement_type' => 'SALE',
            'quantity' => 3,
            'quantity_in_base_unit' => 3,
        ]);
        $this->assertSame(
            today()->addDay()->toDateString(),
            $subscription->fresh()->next_delivery_date->toDateString()
        );
    }

    public function test_keeps_the_subscription_due_when_stock_is_insufficient_and_retries_later(): void
    {
        $this->provider->update(['has_inventory_control' => true]);
        $this->product->update(['track_stock' => true]);

        $inventory = ProductInventory::create([
            'product_id' => $this->product->id,
            'provider_id' => $this->provider->id,
            'current_stock' => 1,
            'reserved_stock' => 0,
            'allow_negative_stock' => false,
        ]);

        $subscription = $this->subscribe($this->createPlan(SubscriptionPlan::FREQUENCY_DAILY, quantity: 3));

        $this->artisan('subscriptions:generate-orders')
            ->expectsOutputToContain('Resumen: 0 generada(s), 0 cobrada(s), 0 pausada(s), 1 pendiente(s).')
            ->assertSuccessful();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertSame(1.0, (float) $inventory->fresh()->current_stock);
        $this->assertSame(CustomerSubscription::STATUS_ACTIVE, $subscription->fresh()->status);
        $this->assertSame(
            today()->toDateString(),
            $subscription->fresh()->next_delivery_date->toDateString()
        );

        // Al reponer stock, la suscripcion pendiente se recupera al dia siguiente.
        $inventory->update(['current_stock' => 10]);

        $this->artisan('subscriptions:generate-orders', ['--date' => today()->addDay()->toDateString()])
            ->assertSuccessful();

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(
            today()->addDays(2)->toDateString(),
            $subscription->fresh()->next_delivery_date->toDateString()
        );
    }

    public function test_pauses_the_subscription_when_the_plan_is_deactivated(): void
    {
        $plan = $this->createPlan();
        $plan->update(['is_active' => false]);

        $subscription = $this->subscribe($plan);

        $this->artisan('subscriptions:generate-orders')
            ->expectsOutputToContain('pausada: el plan esta inactivo.')
            ->assertSuccessful();

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(CustomerSubscription::STATUS_PAUSED, $subscription->fresh()->status);
    }

    public function test_pauses_the_subscription_when_the_provider_is_deactivated(): void
    {
        $subscription = $this->subscribe($this->createPlan());

        $this->provider->update(['is_active' => false]);

        $this->artisan('subscriptions:generate-orders')
            ->expectsOutputToContain('pausada: el comercio esta inactivo.')
            ->assertSuccessful();

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(CustomerSubscription::STATUS_PAUSED, $subscription->fresh()->status);
    }

    public function test_pauses_the_subscription_when_the_plan_has_no_items(): void
    {
        $subscription = $this->subscribe($this->createPlan());
        $subscription->plan->items()->delete();

        $this->artisan('subscriptions:generate-orders')
            ->expectsOutputToContain('pausada: el plan no tiene productos.')
            ->assertSuccessful();

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(CustomerSubscription::STATUS_PAUSED, $subscription->fresh()->status);
    }

    public function test_reports_when_there_is_nothing_to_process(): void
    {
        $this->artisan('subscriptions:generate-orders')
            ->expectsOutputToContain('No hay suscripciones con entrega vencida')
            ->assertSuccessful();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_fails_with_an_invalid_date_option(): void
    {
        $this->artisan('subscriptions:generate-orders', ['--date' => 'no-es-una-fecha'])
            ->expectsOutputToContain('La fecha indicada no es valida')
            ->assertFailed();
    }

    public function test_scheduled_daily_at_six(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'subscriptions:generate-orders'));

        $this->assertCount(1, $events);
        $this->assertSame('0 6 * * *', $events->first()->expression);
    }

    public function test_uses_the_flavours_chosen_by_the_client_instead_of_the_plan_ones(): void
    {
        $otroProducto = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $this->product->category_id,
            'name' => 'Yerba Sin Verde',
            'price' => 1500,
            'is_available' => true,
            'track_stock' => false,
        ]);

        $plan = $this->createPlan(SubscriptionPlan::FREQUENCY_WEEKLY, 45000, 0, 1);
        $plan->items()->create(['product_id' => $otroProducto->id, 'quantity' => 1]);

        $subscription = $this->subscribe($plan);
        $subscription->items()->create(['product_id' => $otroProducto->id, 'quantity' => 2]);

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $order = Order::firstOrFail();

        $this->assertSame(
            [$otroProducto->id => 2],
            $order->items->mapWithKeys(fn ($item) => [$item->product_id => (int) $item->quantity])->all()
        );
    }

    public function test_falls_back_to_plan_items_when_the_client_did_not_choose_flavours(): void
    {
        $plan = $this->createPlan(SubscriptionPlan::FREQUENCY_WEEKLY, 45000, 0, 3);
        $subscription = $this->subscribe($plan);

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $this->assertSame(
            [$this->product->id => 3],
            Order::firstOrFail()->items->mapWithKeys(fn ($item) => [$item->product_id => (int) $item->quantity])->all()
        );
    }

    public function test_next_delivery_lands_on_the_preferred_weekday(): void
    {
        $plan = $this->createPlan(SubscriptionPlan::FREQUENCY_WEEKLY);

        $sabado = 6;
        $subscription = $this->subscribe($plan);
        $subscription->update(['preferred_delivery_day' => $sabado]);

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $proxima = $subscription->fresh()->next_delivery_date;

        $this->assertSame($sabado, $proxima->dayOfWeekIso);
        $this->assertGreaterThan(today()->toDateString(), $proxima->toDateString());
    }

    public function test_preferred_weekday_never_moves_the_delivery_backwards(): void
    {
        // El plan es diario y el cliente quiere sabados: la proxima fecha tiene
        // que avanzar hasta el proximo sabado, no caer en el dia siguiente.
        $plan = $this->createPlan(SubscriptionPlan::FREQUENCY_DAILY);
        $subscription = $this->subscribe($plan, today()->toDateString());
        $subscription->update(['preferred_delivery_day' => 6]);

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $proxima = $subscription->fresh()->next_delivery_date;

        $this->assertSame(6, $proxima->dayOfWeekIso);
        $this->assertGreaterThan(1, (int) today()->diffInDays($proxima, false));
    }

    public function test_preferred_delivery_time_is_left_visible_for_the_kitchen(): void
    {
        $subscription = $this->subscribe($this->createPlan());
        $subscription->update(['preferred_delivery_time' => '18:30']);

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $this->assertStringContainsString('Entrega preferida: 18:30 hs.', Order::firstOrFail()->notes);
    }

    public function test_registers_a_pending_charge_for_the_generated_cycle(): void
    {
        $plan = $this->createPlan(SubscriptionPlan::FREQUENCY_WEEKLY, 45000, 10);
        $subscription = $this->subscribe($plan, today()->toDateString());

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $payment = SubscriptionPayment::firstOrFail();
        $order = Order::firstOrFail();

        $this->assertSame(SubscriptionPayment::STATUS_PENDING, $payment->status);
        $this->assertSame('manual', $payment->gateway);
        $this->assertSame(1, (int) $payment->attempt);
        $this->assertSame($subscription->id, $payment->customer_subscription_id);
        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame($this->provider->id, $payment->provider_id);
        $this->assertSame($this->client->id, $payment->user_id);
        $this->assertEquals(45000.00, (float) $payment->subtotal);
        $this->assertEquals(4500.00, (float) $payment->discount);
        $this->assertEquals((float) $order->total, (float) $payment->total);
        $this->assertSame(today()->toDateString(), $payment->period_start->toDateString());
        $this->assertSame(today()->addWeek()->toDateString(), $payment->period_end->toDateString());
        $this->assertNull($payment->paid_at);
    }

    public function test_manual_gateway_leaves_the_charge_pending_until_the_merchant_confirms_it(): void
    {
        Log::spy();

        $this->subscribe($this->createPlan());

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $this->assertSame(SubscriptionPayment::STATUS_PENDING, SubscriptionPayment::firstOrFail()->status);

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $mensaje) => str_contains($mensaje, 'pendiente de confirmacion (gateway manual)'))
            ->once();
    }

    public function test_webhook_gateway_marks_the_charge_paid_when_the_gateway_accepts_it(): void
    {
        config([
            'subscriptions.billing.gateway' => 'webhook',
            'subscriptions.billing.webhook.url' => 'https://pasarela.example.com/cobros',
        ]);

        Http::fake([
            'pasarela.example.com/*' => Http::response(['id' => 'mp-12345']),
        ]);

        $this->subscribe($this->createPlan());

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $payment = SubscriptionPayment::firstOrFail();

        $this->assertSame(SubscriptionPayment::STATUS_PAID, $payment->status);
        $this->assertSame('mp-12345', $payment->gateway_payment_id);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_webhook_gateway_marks_the_charge_failed_when_the_gateway_rejects_it(): void
    {
        config([
            'subscriptions.billing.gateway' => 'webhook',
            'subscriptions.billing.webhook.url' => 'https://pasarela.example.com/cobros',
        ]);

        Http::fake([
            'pasarela.example.com/*' => Http::response(['message' => 'sin fondos'], 402),
        ]);

        $subscription = $this->subscribe($this->createPlan());

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $payment = SubscriptionPayment::firstOrFail();

        $this->assertSame(SubscriptionPayment::STATUS_FAILED, $payment->status);
        $this->assertStringContainsString('sin fondos', (string) $payment->failure_reason);
        $this->assertSame(CustomerSubscription::STATUS_PAYMENT_FAILED, $subscription->fresh()->status);
    }

    public function test_failed_charge_keeps_the_order_but_stops_the_next_cycle(): void
    {
        config([
            'subscriptions.billing.gateway' => 'webhook',
            'subscriptions.billing.webhook.url' => 'https://pasarela.example.com/cobros',
        ]);

        Http::fake([
            'pasarela.example.com/*' => Http::response([], 500),
        ]);

        $subscription = $this->subscribe($this->createPlan());

        $this->artisan('subscriptions:generate-orders')->assertSuccessful();
        $this->artisan('subscriptions:generate-orders')->assertSuccessful();

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(CustomerSubscription::STATUS_PAYMENT_FAILED, $subscription->fresh()->status);
    }

    private function createPlan(
        string $frequency = SubscriptionPlan::FREQUENCY_WEEKLY,
        float $price = 45000,
        float $discount = 0,
        int $quantity = 2
    ): SubscriptionPlan {
        $plan = SubscriptionPlan::create([
            'provider_id' => $this->provider->id,
            'title' => 'Plan '.strtolower($frequency),
            'frequency' => $frequency,
            'price' => $price,
            'discount_percentage' => $discount,
            'is_active' => true,
        ]);

        $plan->items()->create([
            'product_id' => $this->product->id,
            'quantity' => $quantity,
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
