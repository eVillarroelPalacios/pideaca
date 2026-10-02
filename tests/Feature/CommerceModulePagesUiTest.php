<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Country;
use App\Models\CustomerSubscription;
use App\Models\Module;
use App\Models\Page;
use App\Models\Product;
use App\Models\Provider;
use App\Models\SubscriptionPlan;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceModulePagesUiTest extends TestCase
{
    use RefreshDatabase;

    private User $prestador;

    private Provider $provider;

    private Product $product;

    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate')->run();

        $comercio = Module::create(['description' => 'Comercio & Gastronomía']);
        $prestadorTipo = TypeUser::create(['description' => 'Prestador']);
        $clienteTipo = TypeUser::create(['description' => 'Cliente']);

        $this->prestador = User::create([
            'name' => 'Pizzeria Test',
            'email' => 'pizzeria@example.com',
            'type_user_id' => $prestadorTipo->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->prestador->id,
            'business_name' => 'Pizzeria Test',
            'is_active' => true,
        ]);

        $category = Category::create([
            'provider_id' => $this->provider->id,
            'name' => 'Pizzas',
        ]);

        $this->product = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $category->id,
            'name' => 'Muzza',
            'price' => 5000,
            'is_available' => true,
        ]);

        $this->cliente = User::create([
            'name' => 'Cliente Test',
            'email' => 'cliente-ui@example.com',
            'type_user_id' => $clienteTipo->id,
            'password' => 'password',
        ]);

        Address::create([
            'user_id' => $this->cliente->id,
            'country_id' => Country::create(['name' => 'Argentina', 'iso_code' => 'AR'])->id,
            'street' => 'Av. Test',
            'number' => '100',
        ]);

        // La migracion crea las paginas y las reparte por tipo de usuario.
        $this->runCommercePagesMigration();

        // Un plan visible, para que el cliente tenga algo que contratar.
        $plan = SubscriptionPlan::create([
            'provider_id' => $this->provider->id,
            'title' => 'Despensa Semanal',
            'frequency' => SubscriptionPlan::FREQUENCY_WEEKLY,
            'price' => 20000,
            'is_active' => true,
        ]);

        $plan->items()->create(['product_id' => $this->product->id, 'quantity' => 1]);
    }

    /**
     * Corre solo la migracion de las paginas del modulo, sin repetir el resto.
     */
    private function runCommercePagesMigration(): void
    {
        $migration = require database_path('migrations/2026_09_28_080000_create_commerce_module_pages.php');
        $migration->up();
    }

    public function test_migration_creates_the_commerce_pages_in_the_right_module(): void
    {
        $pages = Page::whereIn('url', ['finanzas', 'retencion', 'suscripciones', 'mis-suscripciones'])->get();

        $this->assertCount(4, $pages);

        foreach ($pages as $page) {
            $this->assertSame('Comercio & Gastronomía', $page->module->description);
        }
    }

    public function test_pages_are_registered_in_the_registration_template(): void
    {
        $prestadorTipo = TypeUser::where('description', 'Prestador')->first();
        $clienteTipo = TypeUser::where('description', 'Cliente')->first();

        $prestadorUrls = $prestadorTipo->assignedPages()->pluck('pages.url');
        $clienteUrls = $clienteTipo->assignedPages()->pluck('pages.url');

        $this->assertTrue($prestadorUrls->contains('finanzas'));
        $this->assertTrue($prestadorUrls->contains('retencion'));
        $this->assertTrue($prestadorUrls->contains('suscripciones'));
        $this->assertTrue($clienteUrls->contains('mis-suscripciones'));
    }

    public function test_provider_gets_the_provider_pages_and_not_the_customer_one(): void
    {
        $this->actingAs($this->prestador);

        $urls = $this->prestador->pages()->pluck('url');

        $this->assertTrue($urls->contains('finanzas'));
        $this->assertTrue($urls->contains('retencion'));
        $this->assertTrue($urls->contains('suscripciones'));
        $this->assertFalse($urls->contains('mis-suscripciones'));

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('dash-finanzas', false)
            ->assertSee('dash-retencion', false)
            ->assertSee('dash-suscripciones', false)
            ->assertDontSee('id="dash-mis-suscripciones"', false)
            ->assertSee('loadFinances()', false)
            ->assertSee('loadRetention()', false)
            ->assertSee('loadSubRevenue()', false);
    }

    public function test_client_gets_only_the_customer_page(): void
    {
        $this->actingAs($this->cliente);

        $urls = $this->cliente->pages()->pluck('url');

        $this->assertTrue($urls->contains('mis-suscripciones'));
        $this->assertFalse($urls->contains('finanzas'));
        $this->assertFalse($urls->contains('retencion'));
        $this->assertFalse($urls->contains('suscripciones'));

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('dash-mis-suscripciones', false)
            ->assertSee('loadMySubscriptions()', false)
            ->assertDontSee('dash-finanzas', false)
            ->assertDontSee('dash-suscripciones', false);
    }

    public function test_client_can_subscribe_through_the_api_used_by_the_new_page(): void
    {
        $this->subscribeClient();

        $this->getJson('/api/v1/customer/subscriptions')
            ->assertOk()
            ->assertJsonCount(1, 'subscriptions')
            ->assertJsonPath('subscriptions.0.status', CustomerSubscription::STATUS_ACTIVE)
            ->assertJsonPath('subscriptions.0.plan.title', 'Despensa Semanal')
            ->assertJsonPath('subscriptions.0.provider.business_name', 'Pizzeria Test')
            // El editor de sabores se arma con los productos del plan: la
            // distribucion propia se completa en el primer ciclo facturado.
            ->assertJsonPath('subscriptions.0.plan.items.0.name', 'Muzza')
            ->assertJsonPath('subscriptions.0.items', []);
    }

    public function test_provider_sees_finances_retention_and_revenue_from_the_dashboard(): void
    {
        $this->actingAs($this->prestador);

        $health = $this->getJson('/api/v1/provider/financial-health');
        $health->assertOk()->assertJsonPath('summary.total_products', 1);

        $this->getJson('/api/v1/provider/marketing/rules')
            ->assertOk()
            ->assertJsonPath('rules', []);

        $this->subscribeClient();

        $this->actingAs($this->prestador)
            ->getJson('/api/v1/provider/subscriptions/revenue')
            ->assertOk()
            ->assertJsonPath('recurring.active_subscriptions', 1)
            ->assertJsonPath('recurring.by_plan.0.title', 'Despensa Semanal');
    }

    private function subscribeClient(): CustomerSubscription
    {
        $plan = SubscriptionPlan::where('provider_id', $this->provider->id)->firstOrFail();
        $address = Address::where('user_id', $this->cliente->id)->firstOrFail();

        $this->actingAs($this->cliente)
            ->postJson('/api/v1/customer/subscriptions', [
                'subscription_plan_id' => $plan->id,
                'delivery_address_id' => $address->id,
                'payment_method' => 'cash',
                'next_delivery_date' => now()->addDay()->toDateString(),
            ])
            ->assertCreated();

        return CustomerSubscription::where('user_id', $this->cliente->id)->firstOrFail();
    }
}
