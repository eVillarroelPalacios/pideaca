<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Country;
use App\Models\Favorite;
use App\Models\Group;
use App\Models\GroupStatus;
use App\Models\Module;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\ProductVariant;
use App\Models\Provider;
use App\Models\ProviderImage;
use App\Models\SubscriptionPlan;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPanelTest extends TestCase
{
    use RefreshDatabase;

    private TypeUser $clienteType;

    private TypeUser $prestadorType;

    private User $client;

    private Address $clientAddress;

    private Provider $provider;

    private User $providerUser;

    private User $otherClient;

    private Page $comerciosPage;

    private Product $product;

    private ProductVariant $variant;

    private ProductOption $option;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clienteType = TypeUser::create(['description' => 'Cliente']);
        $this->prestadorType = TypeUser::create(['description' => 'Prestador']);
        $country = Country::create(['name' => 'Argentina', 'iso_code' => 'AR']);

        $pideAca = Module::create(['description' => 'Pide acá']);
        $comercios = Page::create(['description' => 'Comercios', 'url' => 'comercios', 'module_id' => $pideAca->id]);
        $misPedidos = Page::create(['description' => 'Mis Pedidos', 'url' => 'mis-pedidos', 'module_id' => $pideAca->id]);
        $this->comerciosPage = $comercios;
        $this->clienteType->assignedPages()->attach([$comercios->id, $misPedidos->id]);

        $this->providerUser = User::create([
            'name' => 'Pizzería Los Hermanos',
            'email' => 'losh@loshermanos.com.ar',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->providerUser->id,
            'business_name' => 'Pizzería Los Hermanos',
            'is_active' => true,
        ]);

        $category = Category::create(['provider_id' => $this->provider->id, 'name' => 'Pizzas a la Piedra']);

        $this->product = Product::create([
            'provider_id' => $this->provider->id,
            'category_id' => $category->id,
            'name' => 'Pizza Fugazzeta',
            'price' => 9500.00,
            'is_available' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Familiar',
            'price' => 15500.00,
            'is_available' => true,
        ]);

        $group = ProductOptionGroup::create([
            'product_id' => $this->product->id,
            'name' => 'Agregados',
            'min_choices' => 0,
            'max_choices' => 3,
            'is_required' => false,
        ]);

        $this->option = ProductOption::create([
            'option_group_id' => $group->id,
            'name' => 'Muzzarella extra',
            'extra_price' => 900.00,
            'is_available' => true,
        ]);

        $this->client = User::create([
            'name' => 'Ana Prueba',
            'email' => 'cliente.demo@example.com',
            'type_user_id' => $this->clienteType->id,
            'password' => 'password',
        ]);

        $this->clientAddress = Address::create([
            'user_id' => $this->client->id,
            'country_id' => $country->id,
            'street' => 'Av. Santa Fe',
            'number' => '1234',
            'is_primary' => true,
        ]);

        // El dashboard arma el menú con page_user (no con page_type_user)
        $this->client->pages()->attach([$comercios->id, $misPedidos->id]);

        $this->otherClient = User::create([
            'name' => 'Otro Cliente',
            'email' => 'otro@ejemplo.com',
            'type_user_id' => $this->clienteType->id,
            'password' => 'password',
        ]);
    }

    // --- Listado de comercios ---

    public function test_providers_listing_is_public_and_only_shows_published_commerces(): void
    {
        $pausedUser = User::create([
            'name' => 'Comercio Pausado',
            'email' => 'pausado@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);
        $hidden = Provider::create([
            'user_id' => $pausedUser->id,
            'business_name' => 'Comercio Pausado',
            'is_active' => false,
        ]);
        Category::create(['provider_id' => $hidden->id, 'name' => 'Otra']);

        $response = $this->getJson('/api/providers');

        $response->assertOk()->assertJsonPath('success', true);

        $providers = $response->json('providers');

        $this->assertCount(1, $providers);
        $this->assertSame($this->provider->id, $providers[0]['id']);
        $this->assertSame('Pizzería Los Hermanos', $providers[0]['business_name']);
        $this->assertSame(1, $providers[0]['categories_count']);
        $this->assertSame(1, $providers[0]['products_count']);

        // Los limites del modulo viajan con el listado (para estimar el envio)
        $this->assertSame(
            (float) config('fastdelivery.delivery_fee'),
            (float) $response->json('settings.delivery_fee')
        );
    }

    public function test_providers_listing_filters_by_name(): void
    {
        $otherProviderUser = User::create([
            'name' => 'Gomería',
            'email' => 'gomeria@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);
        Provider::create([
            'user_id' => $otherProviderUser->id,
            'business_name' => 'Gomería Don Pepe',
            'is_active' => true,
        ]);

        $found = $this->getJson('/api/providers?search=gomer')->json('providers');
        $this->assertCount(1, $found);
        $this->assertSame('Gomería Don Pepe', $found[0]['business_name']);

        $empty = $this->getJson('/api/providers?search=zzzz')->json('providers');
        $this->assertSame([], $empty);
    }

    public function test_providers_listing_reports_active_subscription_plans(): void
    {
        $otroUser = User::create([
            'name' => 'Sin Planes',
            'email' => 'sinplanes@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);
        $otro = Provider::create([
            'user_id' => $otroUser->id,
            'business_name' => 'Kiosco Sin Planes',
            'is_active' => true,
        ]);

        SubscriptionPlan::create([
            'provider_id' => $this->provider->id,
            'title' => 'Mega Ganga',
            'frequency' => SubscriptionPlan::FREQUENCY_WEEKLY,
            'price' => 5500,
            'discount_percentage' => 10,
            'is_active' => true,
        ]);
        SubscriptionPlan::create([
            'provider_id' => $this->provider->id,
            'title' => 'Plan oculto',
            'frequency' => SubscriptionPlan::FREQUENCY_MONTHLY,
            'price' => 9000,
            'discount_percentage' => 0,
            'is_active' => false,
        ]);

        $porId = collect($this->getJson('/api/providers')->json('providers'))->keyBy('id');

        // Solo los planes visibles habilitan el boton Suscribirse de la tarjeta
        $this->assertSame(1, $porId[$this->provider->id]['active_subscription_plans_count']);
        $this->assertSame(0, $porId[$otro->id]['active_subscription_plans_count']);
    }

    public function test_providers_listing_filters_only_commerces_with_active_plans(): void
    {
        $otroUser = User::create([
            'name' => 'Comercio Sin Planes',
            'email' => 'comercio.sinplanes@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);
        $otro = Provider::create([
            'user_id' => $otroUser->id,
            'business_name' => 'Kiosco Sin Planes',
            'is_active' => true,
        ]);

        // Sin el filtro sigue llegando el listado completo (lo usa la pagina Comercios)
        $this->assertCount(2, $this->getJson('/api/providers')->json('providers'));

        SubscriptionPlan::create([
            'provider_id' => $this->provider->id,
            'title' => 'Mega Ganga',
            'frequency' => SubscriptionPlan::FREQUENCY_WEEKLY,
            'price' => 5500,
            'discount_percentage' => 0,
            'is_active' => true,
        ]);
        SubscriptionPlan::create([
            'provider_id' => $this->provider->id,
            'title' => 'Oculto',
            'frequency' => SubscriptionPlan::FREQUENCY_MONTHLY,
            'price' => 9000,
            'discount_percentage' => 0,
            'is_active' => false,
        ]);

        $conPlanes = $this->getJson('/api/providers?only_with_plans=1')->json('providers');

        // El combo de comercios de Nueva suscripcion usa este filtro
        $this->assertCount(1, $conPlanes);
        $this->assertSame($this->provider->id, $conPlanes[0]['id']);
        $this->assertNotContains($otro->id, array_column($conPlanes, 'id'));

        // only_with_plans=0 no filtra nada
        $this->assertCount(2, $this->getJson('/api/providers?only_with_plans=0')->json('providers'));
    }

    public function test_providers_listing_includes_the_publicidad_image(): void
    {
        // Sin imagen registrada el valor queda null salvo que exista el archivo
        // provider_<id>.<ext> del sitio de publicidad: se usa un id alto para
        // que ese fallback no encuentre nada.
        $sinImagenUser = User::create([
            'name' => 'Comercio Sin Imagen',
            'email' => 'sin.imagen@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);
        Provider::query()->insert([
            'id' => 9999,
            'user_id' => $sinImagenUser->id,
            'business_name' => 'Comercio Sin Imagen',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $listado = collect($this->getJson('/api/providers')->json('providers'))->keyBy('id');
        $this->assertNull($listado[9999]['publicidad_image']);

        ProviderImage::create([
            'provider_id' => $this->provider->id,
            'image_path' => 'provider_'.$this->provider->id.'.png',
            'image_type' => 'publicidad',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $conImagen = collect($this->getJson('/api/providers')->json('providers'))->keyBy('id');

        $this->assertSame(
            asset('images/publicidad/provider_'.$this->provider->id.'.png'),
            $conImagen[$this->provider->id]['publicidad_image']
        );
    }

    public function test_favorite_toggle_requires_authentication(): void
    {
        $this->postJson("/api/providers/{$this->provider->id}/favorite")->assertStatus(401);
        $this->getJson('/api/providers?favorite=1')->assertStatus(401);
    }

    public function test_client_can_favorite_and_unfavorite_a_commerce(): void
    {
        $add = $this->actingAs($this->client)
            ->postJson("/api/providers/{$this->provider->id}/favorite");

        $add->assertOk()->assertJsonPath('success', true)->assertJsonPath('is_favorite', true);
        $this->assertSame(1, $add->json('favorites_count'));
        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->client->id,
            'provider_id' => $this->provider->id,
        ]);

        // Un segundo clic lo quita (y el contador baja)
        $remove = $this->actingAs($this->client)
            ->postJson("/api/providers/{$this->provider->id}/favorite");

        $remove->assertOk()->assertJsonPath('is_favorite', false);
        $this->assertSame(0, $remove->json('favorites_count'));
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->client->id,
            'provider_id' => $this->provider->id,
        ]);
    }

    public function test_providers_listing_filters_favorites_and_flags_them(): void
    {
        $otroUser = User::create([
            'name' => 'Kiosco',
            'email' => 'kiosco@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);
        $otro = Provider::create([
            'user_id' => $otroUser->id,
            'business_name' => 'Kiosco El Sol',
            'is_active' => true,
        ]);

        // Sin favoritos la pestaña viene vacía pero el total sigue disponible
        $sinFavoritos = $this->actingAs($this->client)->getJson('/api/providers?favorite=1');
        $sinFavoritos->assertOk();
        $this->assertSame([], $sinFavoritos->json('providers'));
        $this->assertSame(0, $sinFavoritos->json('favorites_count'));
        $this->assertSame(2, $sinFavoritos->json('total_count'));

        Favorite::create(['user_id' => $this->client->id, 'provider_id' => $this->provider->id]);

        $favoritos = $this->actingAs($this->client)->getJson('/api/providers?favorite=1');
        $favoritos->assertOk();
        $this->assertCount(1, $favoritos->json('providers'));
        $this->assertSame($this->provider->id, $favoritos->json('providers.0.id'));
        $this->assertTrue($favoritos->json('providers.0.is_favorite'));
        $this->assertSame(1, $favoritos->json('favorites_count'));
        $this->assertSame(2, $favoritos->json('total_count'));

        // En el listado completo cada tarjeta sabe si esta marcada
        $todos = $this->actingAs($this->client)->getJson('/api/providers');
        $porId = collect($todos->json('providers'))->keyBy('id');
        $this->assertTrue($porId[$this->provider->id]['is_favorite']);
        $this->assertFalse($porId[$otro->id]['is_favorite']);

        // Los favoritos de otro cliente no se filtran ni se cuentan
        Favorite::create(['user_id' => $this->otherClient->id, 'provider_id' => $otro->id]);
        $ajenos = $this->actingAs($this->client)->getJson('/api/providers?favorite=1');
        $this->assertCount(1, $ajenos->json('providers'));
        $this->assertSame($this->provider->id, $ajenos->json('providers.0.id'));
    }

    public function test_dashboard_renders_the_favorite_shops_tabs(): void
    {
        $html = $this->actingAs($this->client)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="fd-shops-tabs"', $html);
        $this->assertStringContainsString("fdShopsTab('favorites')", $html);
        $this->assertStringContainsString('function fdToggleFavorite(', $html);
        $this->assertStringContainsString('function fdShopsTab(', $html);

        // Los botones de la seccion quedaron solo-icono: accesibles por
        // title/aria-label y sin etiqueta de texto visible.
        $tabs = substr($html, strpos($html, 'id="fd-shops-tabs"'), 3000);
        $this->assertStringContainsString('aria-label="Mis favoritos"', $tabs);
        $this->assertStringContainsString('aria-label="Todos los comercios"', $tabs);
        $this->assertMatchesRegularExpression('/id="fd-shops-tab-all"[^>]*>\s*<svg/', $tabs);
        $this->assertDoesNotMatchRegularExpression('/<\/svg>\s*Mis favoritos/', $tabs);
        $this->assertDoesNotMatchRegularExpression('/<\/svg>\s*Todos los comercios/', $tabs);

        $header = substr($html, strpos($html, 'id="fd-shops-search"'), 600);
        $this->assertStringContainsString('aria-label="Actualizar comercios"', $header);
        $this->assertDoesNotMatchRegularExpression('/<\/svg>\s*Actualizar\s*<\/button>/', $header);
    }

    public function test_providers_listing_only_returns_the_modules_category(): void
    {
        $gastronomia = Group::firstOrCreate(['description' => 'Comercio & Gastronomía']);
        $hogar = Group::firstOrCreate(['description' => 'Servicios del Hogar & Bienestar']);
        $modulo = Module::firstOrCreate(['description' => 'Comercio & Gastronomía']);

        $this->provider->update(['category_id' => $gastronomia->id]);

        $otroUser = User::create([
            'name' => 'Plomería',
            'email' => 'plomeria@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);
        Provider::create([
            'user_id' => $otroUser->id,
            'business_name' => 'Plomería Express',
            'is_active' => true,
            'category_id' => $hogar->id,
        ]);

        $sinRubroUser = User::create([
            'name' => 'Comercio sin rubro',
            'email' => 'sinrubro@example.com',
            'type_user_id' => $this->prestadorType->id,
            'password' => 'password',
        ]);
        Provider::create([
            'user_id' => $sinRubroUser->id,
            'business_name' => 'Comercio Sin Rubro',
            'is_active' => true,
        ]);

        // Dentro del modulo solo aparecen los de esa categoria
        $enModulo = $this->getJson('/api/providers?module_id='.$modulo->id)->json('providers');
        $this->assertCount(1, $enModulo);
        $this->assertSame($this->provider->id, $enModulo[0]['id']);

        // Sin modulo sigue llegando el listado completo
        $this->assertCount(3, $this->getJson('/api/providers')->json('providers'));

        // La pagina "Comercios" le pasa su modulo al listado
        $this->actingAs($this->client)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-module-id="'.$this->comerciosPage->module_id.'"', false);

        // Un modulo que no existe no pasa la validacion
        $this->getJson('/api/providers?module_id=99999')->assertStatus(422);
    }

    // --- Mis pedidos ---

    public function test_my_orders_requires_authentication(): void
    {
        $this->getJson('/api/orders/mine')->assertStatus(401);
    }

    public function test_my_orders_returns_only_the_clients_orders(): void
    {
        $mine = $this->makeOrder($this->client, 'MIO-0001', Order::STATUS_PENDING);
        $this->makeOrder($this->otherClient, 'AJENO-0001', Order::STATUS_PENDING);

        $response = $this->actingAs($this->client)->getJson('/api/orders/mine');

        $response->assertOk()->assertJsonPath('success', true);

        $orders = $response->json('orders');

        $this->assertCount(1, $orders);
        $this->assertSame('MIO-0001', $orders[0]['order_number']);
        $this->assertSame('Pizzería Los Hermanos', $orders[0]['provider']['business_name']);
        $this->assertSame('Av. Santa Fe', $orders[0]['address']['street']);
        $this->assertSame('Pizza Fugazzeta', $orders[0]['items'][0]['product_name']);
        $this->assertArrayNotHasKey('password', $orders[0]);
        $this->assertSame($mine->id, $orders[0]['id']);
    }

    public function test_my_orders_filters_by_status(): void
    {
        $this->makeOrder($this->client, 'MIO-0002', Order::STATUS_PENDING);
        $this->makeOrder($this->client, 'MIO-0003', Order::STATUS_DELIVERED);

        $delivered = $this->actingAs($this->client)
            ->getJson('/api/orders/mine?status=delivered')
            ->json('orders');

        $this->assertCount(1, $delivered);
        $this->assertSame('MIO-0003', $delivered[0]['order_number']);

        $this->actingAs($this->client)
            ->getJson('/api/orders/mine?status=cualquier_cosa')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    // --- Direcciones ---

    public function test_addresses_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/me/addresses')->assertStatus(401);
    }

    public function test_addresses_endpoint_returns_only_the_clients_addresses(): void
    {
        $otherCountry = Country::create(['name' => 'Chile', 'iso_code' => 'CL']);
        Address::create([
            'user_id' => $this->otherClient->id,
            'country_id' => $otherCountry->id,
            'street' => 'Calle Ajena',
            'number' => '1',
        ]);

        $addresses = $this->actingAs($this->client)
            ->getJson('/api/me/addresses')
            ->assertOk()
            ->json('addresses');

        $this->assertCount(1, $addresses);
        $this->assertSame($this->clientAddress->id, $addresses[0]['id']);
        $this->assertTrue($addresses[0]['is_primary']);
        $this->assertStringContainsString('Av. Santa Fe', $addresses[0]['formatted']);
    }

    // --- Circuito completo ---

    public function test_client_browses_catalog_places_an_order_and_sees_it_in_mine(): void
    {
        // 1. Ve el listado de comercios
        $providers = $this->getJson('/api/providers')->json('providers');
        $this->assertSame($this->provider->id, $providers[0]['id']);

        // 2. Abre el catalogo del comercio
        $catalog = $this->getJson("/api/providers/{$this->provider->id}/catalog?only_available=1");
        $catalog->assertOk();

        $categories = $catalog->json('categories');
        $this->assertSame('Pizzas a la Piedra', $categories[0]['name']);
        $this->assertSame('Pizza Fugazzeta', $categories[0]['products'][0]['name']);

        // 3. Confirma el pedido (el precio lo fija el backend, no el cliente)
        $order = $this->actingAs($this->client)->postJson('/api/orders', [
            'provider_id' => $this->provider->id,
            'address_id' => $this->clientAddress->id,
            'payment_method' => 'efectivo',
            'notes' => 'Sin cebolla.',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'variant_id' => $this->variant->id,
                    'quantity' => 2,
                    'options' => [$this->option->id],
                ],
            ],
        ]);

        $order->assertCreated()->assertJsonPath('success', true);
        $this->assertSame(32800.00, (float) $order->json('order.subtotal'));
        $this->assertSame(36300.00, (float) $order->json('order.total'));

        // 4. Aparece en "Mis Pedidos"
        $mine = $this->actingAs($this->client)->getJson('/api/orders/mine')->json('orders');

        $this->assertCount(1, $mine);
        $this->assertSame($order->json('order.order_number'), $mine[0]['order_number']);
        $this->assertSame('pending', $mine[0]['status']);
        $this->assertSame('Pizzería Los Hermanos', $mine[0]['provider']['business_name']);
        $this->assertSame(32800.00, (float) $mine[0]['subtotal']);
        $this->assertSame(3500.00, (float) $mine[0]['delivery_fee']);
        $this->assertSame(36300.00, (float) $mine[0]['total']);
        $this->assertSame('Muzzarella extra', $mine[0]['items'][0]['options'][0]['option_name']);

        // 5. Y el comercio lo ve en su propio panel
        $providerOrders = $this->actingAs($this->providerUser)
            ->getJson("/api/providers/{$this->provider->id}/orders")
            ->json('orders');

        $this->assertCount(1, $providerOrders);
        $this->assertSame($mine[0]['order_number'], $providerOrders[0]['order_number']);
    }

    public function test_client_cannot_use_the_providers_orders_endpoint(): void
    {
        $this->makeOrder($this->client, 'MIO-0004', Order::STATUS_PENDING);

        $this->actingAs($this->client)
            ->getJson("/api/providers/{$this->provider->id}/orders")
            ->assertStatus(403);
    }

    // --- Vistas del panel ---

    public function test_dashboard_renders_the_client_sections(): void
    {
        $response = $this->actingAs($this->client)->get('/dashboard');

        $response->assertOk()
            ->assertSee('id="dash-comercios"', false)
            ->assertSee('id="dash-mis-pedidos"', false)
            ->assertSee('loadComercios()', false)
            ->assertSee('loadMyOrders()', false)
            ->assertSee('id="fd-shop-overlay"', false)
            ->assertSee(url('/api/orders/mine'), false)
            ->assertDontSee('id="sidebar-link-usuarios"', false)
            ->assertSee('id="sidebar-link-cuenta"', false)
            ->assertSee('id="sidebar-link-direccion"', false)
            // Mi Perfil del cliente arranca en "Mi Dirección", sin pasar por el panel del prestador
            ->assertDontSee('id="sidebar-link-negocio"', false)
            ->assertDontSee('id="sidebar-link-horarios"', false)
            ->assertSee('class="sidebar-link active" data-panel="direccion"', false)
            ->assertSee('class="profile-panel active" data-panel="direccion"', false)
            ->assertDontSee('class="profile-panel active" data-panel="cuenta"', false)
            ->assertDontSee('class="profile-panel active" data-panel="negocio"', false)
            ->assertSee('id="breadcrumb-current">Mi Dirección</span>', false)
            ->assertSee("var profilePanelInicial = 'direccion';", false)
            ->assertSee('value="Ana Prueba"', false)
            ->assertSee('value="cliente.demo@example.com"', false)
            ->assertDontSee('Próximamente podrás gestionar comercios aquí.')
            ->assertDontSee('Próximamente podrás gestionar mis pedidos aquí.');
    }

    public function test_shop_confirm_button_sits_inside_the_modal_below_the_cart(): void
    {
        $html = $this->actingAs($this->client)->get('/dashboard')->assertOk()->getContent();

        $cartPos = strpos($html, 'id="fd-shop-cart"');
        $btnPos = strpos($html, 'id="fd-shop-submit"');

        $this->assertNotFalse($cartPos);
        $this->assertNotFalse($btnPos);
        $this->assertGreaterThan($cartPos, $btnPos, 'El botón de confirmar debe ir debajo del carrito (nota para el comercio).');

        $btnTag = substr($html, $btnPos, 400);
        $this->assertStringNotContainsString('position:absolute', $btnTag, 'El botón ya no debe ser flotante.');
        $this->assertStringContainsString('width:100%;background:#D24C19', $btnTag);
        $this->assertStringContainsString('Confirmar compra', $btnTag);
    }

    public function test_dashboard_does_not_show_client_sections_to_a_prestador(): void
    {
        $response = $this->actingAs($this->providerUser)->get('/dashboard');

        $response->assertOk()
            ->assertDontSee('id="dash-comercios"', false)
            ->assertDontSee('id="dash-mis-pedidos"', false);
    }

    public function test_dashboard_shows_a_default_section_on_entry(): void
    {
        // Todas las secciones arrancan con display:none: sin una llamada inicial
        // el dashboard quedaba completamente en blanco hasta hacer clic en el menú.
        $response = $this->actingAs($this->client)->get('/dashboard');

        $response->assertOk()
            ->assertSee("showDashSection('perfil');", false)
            ->assertSee("showDashSection = function(key)", false);
    }

    public function test_dashboard_footer_shows_only_active_group_icons(): void
    {
        $activo = Group::firstOrCreate(['description' => 'Grupo Footer Activo'], [
            'group_status_id' => GroupStatus::firstOrCreate(['description' => GroupStatus::STATUS_ACTIVE])->id,
        ]);
        $inactivo = Group::firstOrCreate(['description' => 'Grupo Footer Inactivo'], [
            'group_status_id' => GroupStatus::firstOrCreate(['description' => GroupStatus::STATUS_INACTIVE])->id,
        ]);

        $activo->update(['icon' => '<svg data-footer="activo"></svg>']);
        $inactivo->update(['icon' => '<svg data-footer="cerrado"></svg>']);

        $html = $this->actingAs($this->client)->get('/dashboard')->assertOk()->getContent();
        $pos = strpos($html, '<footer');
        $this->assertNotFalse($pos);
        $footer = substr($html, $pos);

        $this->assertStringContainsString('data-footer="activo"', $footer);
        $this->assertStringNotContainsString('data-footer="cerrado"', $footer);
        $this->assertSame(1, substr_count($footer, 'class="footer-group-icon"'));
    }

    // --- Seguimiento en vivo ---

    public function test_dashboard_renders_the_live_tracking_helpers(): void
    {
        $response = $this->actingAs($this->client)->get('/dashboard');

        $response->assertOk()
            ->assertSee('var FD_TRACK_STEPS = [', false)
            ->assertSee('function fdTrackHtml(status)', false)
            ->assertSee('function loadMyOrders(silent)', false)
            ->assertSee('loadMyOrders(true)', false)
            ->assertSee('fdStartPolling(key)', false)
            ->assertSee('visibilitychange', false)
            ->assertSee('fd-myorders-summary', false);
    }

    public function test_provider_dashboard_renders_the_status_actions(): void
    {
        $response = $this->actingAs($this->providerUser)->get('/dashboard');

        $response->assertOk()
            ->assertSee('var FD_ORDER_FLOW = {', false)
            ->assertSee('function fdOrderActions(o)', false)
            ->assertSee('function fdUpdateOrderStatus(', false)
            ->assertSee("method: 'PATCH'", false)
            ->assertSee('loadProviderOrders(true)', false);
    }

    public function test_provider_advances_the_order_and_the_client_sees_the_new_status(): void
    {
        $this->actingAs($this->client)->postJson('/api/orders', [
            'provider_id' => $this->provider->id,
            'address_id' => $this->clientAddress->id,
            'payment_method' => 'efectivo',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])->assertCreated();

        $order = Order::firstOrFail();

        // El comercio recorre el flujo; cada salto responde con la orden ya
        // actualizada (es lo que la tarjeta repinta al instante).
        foreach ([Order::STATUS_CONFIRMED, Order::STATUS_IN_PREPARATION] as $status) {
            $this->actingAs($this->providerUser)
                ->patchJson("/api/orders/{$order->id}/status", ['status' => $status])
                ->assertOk()
                ->assertJsonPath('order.status', $status)
                ->assertJsonPath('order.user.email', $this->client->email);
        }

        // El cliente lo ve reflejado en su listado (lo que refresca el polling)
        $mine = $this->actingAs($this->client)->getJson('/api/orders/mine')->json('orders');
        $this->assertSame(Order::STATUS_IN_PREPARATION, $mine[0]['status']);

        // Y no se pueden saltar pasos
        $this->actingAs($this->providerUser)
            ->patchJson("/api/orders/{$order->id}/status", ['status' => Order::STATUS_DELIVERED])
            ->assertStatus(422);

        $this->assertSame(Order::STATUS_IN_PREPARATION, $order->fresh()->status);
    }

    private function makeOrder(User $owner, string $orderNumber, string $status): Order
    {
        $order = Order::create([
            'provider_id' => $this->provider->id,
            'user_id' => $owner->id,
            'address_id' => $this->clientAddress->id,
            'order_number' => $orderNumber,
            'status' => $status,
            'payment_method' => 'efectivo',
            'notes' => null,
            'subtotal' => 16400.00,
            'delivery_fee' => 3500.00,
            'discount' => 0,
            'total' => 19900.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'variant_id' => $this->variant->id,
            'product_name' => $this->product->name,
            'variant_name' => $this->variant->name,
            'quantity' => 1,
            'unit_price' => 15500.00,
            'total_price' => 16400.00,
        ]);

        return $order;
    }
}
