<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Country;
use App\Models\Group;
use App\Models\GroupStatus;
use App\Models\Module;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\ProductVariant;
use App\Models\Provider;
use App\Models\SubGroup;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderPanelTest extends TestCase
{
    use RefreshDatabase;

    private Provider $provider;

    private Provider $otherProvider;

    private User $owner;

    private User $ownerProviderUser;

    private User $customer;

    private Address $address;

    private int $miCatalogoId = 0;

    private int $pedidosId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $prestador = TypeUser::create(['description' => 'Prestador']);
        TypeUser::create(['description' => 'Cliente']);
        UserStatus::create(['status' => 'Activo']);
        $country = Country::create(['name' => 'Argentina', 'iso_code' => 'AR']);

        // Páginas del módulo Pide acá (ya dadas de alta por FastDeliveryPagesSeeder)
        $pideAca = Module::create(['description' => 'Pide acá']);
        $miCatalogo = Page::create(['description' => 'Mi Catálogo', 'url' => 'mi-catalogo', 'module_id' => $pideAca->id]);
        $pedidos = Page::create(['description' => 'Pedidos', 'url' => 'pedidos', 'module_id' => $pideAca->id]);
        $prestador->assignedPages()->attach([$miCatalogo->id, $pedidos->id]);

        // El prestador del que se va a mirar el panel
        $this->owner = User::create([
            'name' => 'Pizzería Los Hermanos',
            'email' => 'losh@loshermanos.com.ar',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->owner->id,
            'business_name' => 'Pizzería Los Hermanos',
            'is_active' => true,
        ]);

        // El dashboard arma el menú con page_user (no con page_type_user)
        $this->owner->pages()->attach([$miCatalogo->id, $pedidos->id]);
        $this->miCatalogoId = $miCatalogo->id;
        $this->pedidosId = $pedidos->id;

        // Un comercio ajeno, para probar que no se filtra
        $otherOwner = User::create([
            'name' => 'Otro Comercio',
            'email' => 'otro@ejemplo.com',
            'type_user_id' => $prestador->id,
            'password' => 'password',
        ]);

        $this->otherProvider = Provider::create([
            'user_id' => $otherOwner->id,
            'business_name' => 'Otro Comercio',
            'is_active' => true,
        ]);

        $this->customer = User::create([
            'name' => 'Ana Prueba',
            'email' => 'cliente.demo@example.com',
            'password' => 'password',
        ]);

        $this->address = Address::create([
            'user_id' => $this->customer->id,
            'country_id' => $country->id,
            'street' => 'Av. Santa Fe',
            'number' => '1234',
        ]);

        $this->seedCatalog($this->provider);
        $this->seedCatalog($this->otherProvider);
    }

    private function seedCatalog(Provider $provider): void
    {
        $category = Category::create(['provider_id' => $provider->id, 'name' => 'Pizzas a la Piedra']);

        $product = Product::create([
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'name' => 'Pizza Fugazzeta',
            'price' => 9500.00,
            'is_available' => true,
        ]);

        $familiar = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Familiar',
            'price' => 15500.00,
            'is_available' => true,
        ]);

        $group = ProductOptionGroup::create([
            'product_id' => $product->id,
            'name' => 'Agregados',
            'min_choices' => 0,
            'max_choices' => 3,
            'is_required' => false,
        ]);

        ProductOption::create([
            'option_group_id' => $group->id,
            'name' => 'Muzzarella extra',
            'extra_price' => 900.00,
            'is_available' => true,
        ]);
    }

    private function makeOrder(Provider $provider, string $orderNumber, string $status): Order
    {
        $order = Order::create([
            'provider_id' => $provider->id,
            'user_id' => $this->customer->id,
            'address_id' => $this->address->id,
            'order_number' => $orderNumber,
            'status' => $status,
            'payment_method' => 'transfer',
            'notes' => 'Sin cebolla.',
            'subtotal' => 16400.00,
            'delivery_fee' => 3500.00,
            'discount' => 0,
            'total' => 19900.00,
        ]);

        $product = Product::where('provider_id', $provider->id)->firstOrFail();
        $variant = ProductVariant::where('product_id', $product->id)->firstOrFail();

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'quantity' => 1,
            'unit_price' => 15500.00,
            'total_price' => 16400.00,
        ]);

        OrderItemOption::create([
            'order_item_id' => $item->id,
            'group_name' => 'Agregados',
            'option_name' => 'Muzzarella extra',
            'extra_price' => 900.00,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'transferencia_bancaria',
            'status' => Payment::STATUS_PENDING,
            'amount' => 19900.00,
        ]);

        return $order;
    }

    // --- Endpoints del prestador ---

    public function test_owner_can_list_their_orders(): void
    {
        $this->makeOrder($this->provider, 'PANEL-0001', Order::STATUS_PENDING);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders");

        $response->assertOk()->assertJsonPath('success', true);

        $orders = $response->json('orders');

        $this->assertCount(1, $orders);
        $this->assertSame('PANEL-0001', $orders[0]['order_number']);
        $this->assertSame('pending', $orders[0]['status']);

        // Trae todo lo que la vista necesita sin N+1 desde el cliente
        $this->assertSame('Pizza Fugazzeta', $orders[0]['items'][0]['product_name']);
        $this->assertSame('Muzzarella extra', $orders[0]['items'][0]['options'][0]['option_name']);
        $this->assertSame('cliente.demo@example.com', $orders[0]['user']['email']);
        $this->assertSame('Av. Santa Fe', $orders[0]['address']['street']);

        // Nunca se filtra el hash de la contraseña
        $this->assertArrayNotHasKey('password', $orders[0]['user']);

        // El pago acompañante viene listo para el badge
        $this->assertSame('pending', $orders[0]['payments'][0]['status']);
    }

    public function test_orders_endpoint_never_leaks_other_provider_orders(): void
    {
        $this->makeOrder($this->otherProvider, 'AJENO-0001', Order::STATUS_PENDING);

        // El dueño no puede leer los pedidos de otro comercio
        $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->otherProvider->id}/orders")
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        // Tampoco hay pedidos ajenos mezclados en su propio listado
        $orders = $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders")
            ->json('orders');

        $this->assertSame([], $orders);
    }

    public function test_orders_endpoint_requires_authentication(): void
    {
        $this->makeOrder($this->provider, 'PANEL-0002', Order::STATUS_PENDING);

        $this->getJson("/api/providers/{$this->provider->id}/orders")
            ->assertStatus(401);
    }

    public function test_orders_endpoint_filters_by_status(): void
    {
        $this->makeOrder($this->provider, 'PANEL-0003', Order::STATUS_PENDING);
        $this->makeOrder($this->provider, 'PANEL-0004', Order::STATUS_DELIVERED);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders?status=delivered");

        $response->assertOk();
        $this->assertCount(1, $response->json('orders'));
        $this->assertSame('PANEL-0004', $response->json('orders.0.order_number'));

        // Estado que no existe en el dominio: validación, no filtro silencioso
        $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders?status=cualquier_cosa")
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    public function test_orders_endpoint_lists_the_newest_first(): void
    {
        $this->makeOrder($this->provider, 'PANEL-0005', Order::STATUS_PENDING);
        $this->makeOrder($this->provider, 'PANEL-0006', Order::STATUS_PENDING);

        // Se desempata por created_at: sin esto los dos pedidos empatan al segundo
        Order::where('order_number', 'PANEL-0005')
            ->update(['created_at' => now()->subMinute()]);

        $orders = $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders")
            ->json('orders');

        $this->assertSame(['PANEL-0006', 'PANEL-0005'], array_column($orders, 'order_number'));
    }

    public function test_orders_endpoint_filters_by_date_range(): void
    {
        $this->makeOrder($this->provider, 'RANGO-0001', Order::STATUS_PENDING);
        $this->makeOrder($this->provider, 'RANGO-0002', Order::STATUS_PENDING);

        Order::where('order_number', 'RANGO-0002')
            ->update(['created_at' => now()->subDays(5)]);

        $hoy = now()->toDateString();
        $desde = now()->subDays(6)->toDateString();
        $hasta = now()->subDays(4)->toDateString();

        // El rango cubre solamente el pedido viejo
        $orders = $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders?date_from={$desde}&date_to={$hasta}")
            ->assertOk()
            ->json('orders');

        $this->assertSame(['RANGO-0002'], array_column($orders, 'order_number'));

        // Un rango de un solo día llega solo al pedido de hoy
        $orders = $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders?date_from={$hoy}&date_to={$hoy}")
            ->json('orders');

        $this->assertSame(['RANGO-0001'], array_column($orders, 'order_number'));
    }

    public function test_orders_endpoint_rejects_an_inverted_date_range(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders?date_from=2026-10-05&date_to=2026-10-01")
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['date_to']]);

        $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders?date_from=ayer")
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['date_from']]);
    }

    public function test_orders_endpoint_paginates_orders(): void
    {
        $this->makeOrder($this->provider, 'PAG-0001', Order::STATUS_PENDING);
        $this->makeOrder($this->provider, 'PAG-0002', Order::STATUS_PENDING);
        $this->makeOrder($this->provider, 'PAG-0003', Order::STATUS_PENDING);

        $pagina1 = $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders?per_page=2&page=1")
            ->assertOk()
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.total', 3);

        $this->assertCount(2, $pagina1->json('orders'));

        $pagina2 = $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders?per_page=2&page=2")
            ->assertJsonPath('pagination.current_page', 2);

        $this->assertCount(1, $pagina2->json('orders'));
    }

    public function test_orders_endpoint_filters_by_tab_today_and_history(): void
    {
        $this->makeOrder($this->provider, 'TAB-0001', Order::STATUS_PENDING);
        $this->makeOrder($this->provider, 'TAB-0002', Order::STATUS_PENDING);

        Order::where('order_number', 'TAB-0002')
            ->update(['created_at' => now()->subDays(3)]);

        $hoy = $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders?tab=today")
            ->assertOk();

        $this->assertSame(['TAB-0001'], array_column($hoy->json('orders'), 'order_number'));
        $this->assertSame(1, $hoy->json('counts.today'));
        $this->assertSame(1, $hoy->json('counts.history'));

        $historial = $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders?tab=history")
            ->assertOk();

        $this->assertSame(['TAB-0002'], array_column($historial->json('orders'), 'order_number'));

        // Sin pestaña siguen llegando todos, como antes de paginar
        $todos = $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/orders")
            ->assertOk();

        $this->assertCount(2, $todos->json('orders'));
    }

    public function test_owner_can_view_their_own_inactive_catalog(): void
    {
        $this->provider->update(['is_active' => false]);

        // Los pedidos van primero: actingAs() deja logueado al guard para el resto del test
        $this->getJson("/api/providers/{$this->provider->id}/catalog")
            ->assertStatus(404);

        $this->actingAs($this->customer)
            ->getJson("/api/providers/{$this->provider->id}/catalog")
            ->assertStatus(404);

        // El dueño administra su comercio aunque lo tenga pausado
        $this->actingAs($this->owner)
            ->getJson("/api/providers/{$this->provider->id}/catalog")
            ->assertOk()
            ->assertJsonPath('provider.is_active', false);
    }

    // --- Vistas del panel ---

    public function test_dashboard_renders_the_catalog_and_orders_sections_for_a_prestador(): void
    {
        $response = $this->actingAs($this->owner)->get('/dashboard');

        $response->assertOk();

        // Las secciones existen y saben de qué comercio son
        $response->assertSee('id="dash-mi-catalogo"', false);
        $response->assertSee('id="dash-pedidos"', false);
        $response->assertSee('data-provider-id="'.$this->provider->id.'"', false);

        // Está cableado al controlador: el dispatcher dispara los loaders
        $response->assertSee('loadProviderCatalog()', false);
        $response->assertSee('loadProviderOrders()', false);
        $response->assertSee(url('/api/providers'), false);
        $response->assertSee("'/catalog'", false);
        $response->assertSee("'/orders?'", false);

        // Rango de fechas y paginación del listado de pedidos
        $response->assertSee('id="fd-orders-date-from"', false);
        $response->assertSee('id="fd-orders-date-to"', false);
        $response->assertSee('id="fd-orders-pagination"', false);
        $response->assertSee('function fdOrdersRangeChange()', false);
        $response->assertSee('function fdOrdersGoPage(step)', false);

        // Mi Perfil: el enlace "Usuarios" del sidebar no se oculta a los prestadores
        $response->assertSee('id="sidebar-link-usuarios"', false);

        // El prestador sigue entrando en su panel, con el de cliente fuera
        $response->assertSee('id="sidebar-link-negocio"', false);
        $response->assertSee('id="breadcrumb-current">Datos del Negocio</span>', false);
        $response->assertDontSee('id="breadcrumb-current">Contraseñas</span>', false);
        $response->assertSee('class="profile-panel active" data-panel="negocio"', false);
        $response->assertSee("var profilePanelInicial = 'negocio';", false);

        // Ya no queda el placeholder genérico
        $response->assertDontSee('Próximamente podrás gestionar mi catálogo');
        $response->assertDontSee('Próximamente podrás gestionar pedidos');
    }

    public function test_provider_orders_are_split_into_today_and_history_tabs(): void
    {
        $html = $this->actingAs($this->owner)->get('/dashboard')->assertOk()->getContent();

        // Dos pestañas con sus contadores
        $this->assertStringContainsString('id="fd-orders-tab-today"', $html);
        $this->assertStringContainsString('id="fd-orders-tab-history"', $html);
        $this->assertStringContainsString('id="fd-orders-count-today"', $html);
        $this->assertStringContainsString('id="fd-orders-count-history"', $html);
        $this->assertStringContainsString("onclick=\"fdOrdersTab('today')\"", $html);
        $this->assertStringContainsString("onclick=\"fdOrdersTab('history')\"", $html);

        // Las pestañas se filtran en el servidor, con contadores y paginación
        $this->assertStringContainsString('function fdOrdersPaintTabs()', $html);
        $this->assertStringContainsString('function fdOrdersTab(tab)', $html);
        $this->assertStringContainsString('function fdOrdersRender(silent)', $html);
        $this->assertStringContainsString('function fdOrdersGoPage(step)', $html);
        $this->assertStringContainsString('function fdOrdersPaintPagination()', $html);
        $this->assertStringContainsString('function fdOrdersRangeChange()', $html);
        $this->assertStringContainsString("var fdOrdersActive = 'today';", $html);
        $this->assertStringContainsString("fdOrdersActive = tab === 'history' ? 'history' : 'today';", $html);
        $this->assertStringContainsString("'tab=' + encodeURIComponent(fdOrdersActive)", $html);
        $this->assertStringContainsString("'date_from=' + encodeURIComponent(fdOrdersDateFrom())", $html);
        $this->assertStringContainsString("'date_to=' + encodeURIComponent(fdOrdersDateTo())", $html);
        $this->assertStringContainsString("'page=' + fdOrdersPage", $html);

        // El resumen cuenta los pedidos de cada pestaña con los del servidor
        $this->assertStringContainsString("' de hoy · ' + (fdOrdersCounts.history || 0) + ' en historial'", $html);
    }

    public function test_dashboard_hides_the_prestador_sections_from_a_cliente(): void
    {
        $cliente = TypeUser::where('description', 'Cliente')->firstOrFail();
        $clienteUser = User::create([
            'name' => 'Cliente Sin Panel',
            'email' => 'cliente@example.com',
            'type_user_id' => $cliente->id,
            'password' => 'password',
        ]);

        $response = $this->actingAs($clienteUser)->get('/dashboard');

        $response->assertOk()
            ->assertDontSee('id="dash-mi-catalogo"', false)
            ->assertDontSee('id="dash-pedidos"', false);
    }

    public function test_prestador_without_provider_gets_a_clear_message_instead_of_a_crash(): void
    {
        $sinComercio = User::create([
            'name' => 'Prestador Sin Comercio',
            'email' => 'sincomercio@example.com',
            'type_user_id' => TypeUser::where('description', 'Prestador')->firstOrFail()->id,
            'password' => 'password',
        ]);
        $sinComercio->pages()->attach([$this->miCatalogoId, $this->pedidosId]);

        $response = $this->actingAs($sinComercio)->get('/dashboard');

        $response->assertOk()
            ->assertSee('id="dash-mi-catalogo"', false)
            ->assertSee('data-provider-id=""', false);
    }

    // --- Categorías y servicios: solo grupos activos ---

    public function test_provider_categories_hide_deactivated_groups(): void
    {
        $activo = GroupStatus::where('description', GroupStatus::STATUS_ACTIVE)->firstOrFail();
        $desactivo = GroupStatus::where('description', GroupStatus::STATUS_INACTIVE)->firstOrFail();

        $visible = Group::create(['description' => 'Comercio Activo', 'group_status_id' => $activo->id]);
        $oculto = Group::create(['description' => 'Comercio Desactivado', 'group_status_id' => $desactivo->id]);

        SubGroup::create(['description' => 'Pizzerias', 'group_id' => $visible->id]);
        SubGroup::create(['description' => 'Rotiserias', 'group_id' => $oculto->id]);

        $groups = $this->actingAs($this->owner)
            ->getJson('/profile/subgroups')
            ->assertOk()
            ->json('groups');

        $descriptions = array_column($groups, 'description');

        $this->assertContains('Comercio Activo', $descriptions);
        $this->assertNotContains('Comercio Desactivado', $descriptions);
    }

    public function test_provider_categories_keep_groups_without_status_visible(): void
    {
        $sinEstado = Group::create(['description' => 'Grupo Sin Estado']);

        $groups = $this->actingAs($this->owner)
            ->getJson('/profile/subgroups')
            ->assertOk()
            ->json('groups');

        $this->assertContains($sinEstado->description, array_column($groups, 'description'));
    }
}
