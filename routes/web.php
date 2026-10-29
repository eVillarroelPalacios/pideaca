<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdvertisingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstadosDeLosGruposController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\OrdenesGestionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ProviderServiceController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReporteHogarController;
use App\Http\Controllers\ReportController;

use App\Http\Controllers\DeliveryOrderController;

// Rutas de Reportes Hogar (Servicios)
Route::get('/reportes-hogar', [ReporteHogarController::class, 'index'])->name('reportes.hogar');
Route::get('/reportes-hogar/pdf', [ReporteHogarController::class, 'pdf'])->name('reportes.hogar.pdf');

// Módulo C - Delivery & Billetera del Repartidor
Route::middleware('auth')->group(function () {
    Route::get('/delivery', function () {
        return view('delivery.panel', ['user' => Auth::user()]);
    })->name('delivery.panel');
    Route::get('/dashboard/repartidor', [DeliveryOrderController::class, 'index'])->name('dashboard.repartidor');
    Route::get('/repartidor', [DeliveryOrderController::class, 'index'])->name('repartidor.index');
});

Route::get('/api/v1/delivery/orders', [DeliveryOrderController::class, 'availableOrders'])->name('api.delivery.orders');
Route::get('/api/v1/delivery/orders/mine', [DeliveryOrderController::class, 'myOrders'])->name('api.delivery.orders.mine');
Route::patch('/api/driver/vehicle', [DeliveryOrderController::class, 'updateVehicle'])->name('api.driver.vehicle.update');
Route::post('/api/v1/delivery/orders/{order}/take', [DeliveryOrderController::class, 'takeOrder'])->name('api.delivery.orders.take');
Route::post('/api/v1/delivery/orders/{order}/pickup', [DeliveryOrderController::class, 'markPickedUp'])->name('api.delivery.orders.pickup');
Route::post('/api/v1/delivery/orders/{order}/confirm', [DeliveryOrderController::class, 'confirmDelivery'])->name('api.delivery.orders.confirm');
use App\Http\Controllers\ServiceBookingController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\SubGroupController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TypeUserController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\UserStatusController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VariantController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdvertisingController::class, 'index']);

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::post('/password/forgot', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1')->name('password.forgot');
Route::get('/password/reset/{token}', [PasswordResetController::class, 'showForm'])->name('password.reset');
Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.reset.store');

Route::get('/registro/tipos', [RegisterController::class, 'types'])->name('registro.tipos');
Route::post('/registro', [RegisterController::class, 'store'])->name('registro.store');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Camino directo a la pagina unificada del prestador: entra al dashboard
// con la seccion "Ordenes y Gestion" abierta (la permision de la pagina
// se valida dentro del controlador con redirect, porque el middleware
// page: esta pensado para respuestas JSON).
Route::get('/dashboard/ordenes-gestion', [OrdenesGestionController::class, 'index'])->name('dashboard.ordenes-gestion');

// Cada bloque de administracion pide la pagina que lo maneja en el menu, para
// que el permiso de la API y el del menu sean el mismo.
Route::middleware('page:modulos')->group(function () {
    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
    Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
    Route::put('/modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
    Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');
});

Route::middleware('page:estados-usuarios')->group(function () {
    Route::get('/user-statuses', [UserStatusController::class, 'index'])->name('user-statuses.index');
    Route::post('/user-statuses', [UserStatusController::class, 'store'])->name('user-statuses.store');
    Route::put('/user-statuses/{userStatus}', [UserStatusController::class, 'update'])->name('user-statuses.update');
    Route::delete('/user-statuses/{userStatus}', [UserStatusController::class, 'destroy'])->name('user-statuses.destroy');
});

// Estado de los grupos
Route::middleware('page:estados-grupos')->group(function () {
    Route::get('/group-statuses', [EstadosDeLosGruposController::class, 'index'])->name('group-statuses.index');
    Route::post('/group-statuses', [EstadosDeLosGruposController::class, 'store'])->name('group-statuses.store');
    Route::put('/group-statuses/{groupStatus}', [EstadosDeLosGruposController::class, 'update'])->name('group-statuses.update');
    Route::delete('/group-statuses/{groupStatus}', [EstadosDeLosGruposController::class, 'destroy'])->name('group-statuses.destroy');
});

// Unidades de medida (catalogo global del inventario)
Route::middleware('page:unidades-medida')->group(function () {
    Route::get('/units-of-measure', [UnitOfMeasureController::class, 'index'])->name('units-of-measure.index');
    Route::post('/units-of-measure', [UnitOfMeasureController::class, 'store'])->name('units-of-measure.store');
    Route::put('/units-of-measure/{unitOfMeasure}', [UnitOfMeasureController::class, 'update'])->name('units-of-measure.update');
    Route::delete('/units-of-measure/{unitOfMeasure}', [UnitOfMeasureController::class, 'destroy'])->name('units-of-measure.destroy');
});

Route::middleware('page:tipo-usuarios')->group(function () {
    Route::get('/type-users', [TypeUserController::class, 'index'])->name('type-users.index');
    Route::post('/type-users', [TypeUserController::class, 'store'])->name('type-users.store');
    Route::put('/type-users/{typeUser}', [TypeUserController::class, 'update'])->name('type-users.update');
    Route::delete('/type-users/{typeUser}', [TypeUserController::class, 'destroy'])->name('type-users.destroy');
    Route::get('/type-users/pages-data', [TypeUserController::class, 'pagesData'])->name('type-users.pages-data');
    Route::put('/type-users/{typeUser}/pages', [TypeUserController::class, 'updatePages'])->name('type-users.pages.update');
    Route::post('/type-users/{typeUser}/pages/bulk', [TypeUserController::class, 'bulkPages'])->name('type-users.pages.bulk');
    Route::post('/type-users/{typeUser}/pages/{page}', [TypeUserController::class, 'attachPage'])->name('type-users.pages.attach');
    Route::delete('/type-users/{typeUser}/pages/{page}', [TypeUserController::class, 'detachPage'])->name('type-users.pages.detach');
});

Route::middleware('page:usuarios')->group(function () {
    Route::get('/admin/users', [UsuarioController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/{user}', [UsuarioController::class, 'show'])->name('admin.users.show');
    Route::post('/admin/users', [UsuarioController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{user}', [UsuarioController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [UsuarioController::class, 'destroy'])->name('admin.users.destroy');
    Route::put('/admin/users/{user}/provider', [UsuarioController::class, 'updateProvider'])->name('admin.users.provider.update');
    Route::post('/admin/users/{user}/addresses', [UsuarioController::class, 'storeAddress'])->name('admin.users.addresses.store');
    Route::put('/admin/users/{user}/addresses/{address}', [UsuarioController::class, 'updateAddress'])->name('admin.users.addresses.update');
    Route::delete('/admin/users/{user}/addresses/{address}', [UsuarioController::class, 'destroyAddress'])->name('admin.users.addresses.destroy');
    Route::post('/admin/users/{user}/images', [UsuarioController::class, 'storeImage'])->name('admin.users.images.store');
    Route::delete('/admin/users/{user}/images/{image}', [UsuarioController::class, 'destroyImage'])->name('admin.users.images.destroy');
    Route::post('/admin/users/{user}/pages/{page}', [UsuarioController::class, 'attachPage'])->name('admin.users.pages.attach');
    Route::delete('/admin/users/{user}/pages/{page}', [UsuarioController::class, 'detachPage'])->name('admin.users.pages.detach');
    Route::get('/admin/users/{user}/subgroups', [UsuarioController::class, 'subgroups'])->name('admin.users.subgroups.index');
    Route::post('/admin/users/{user}/subgroups/toggle', [UsuarioController::class, 'toggleSubgroup'])->name('admin.users.subgroups.toggle');
});

Route::middleware('page:paginas')->group(function () {
    Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
    Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
    Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
});

Route::middleware('page:grupos')->group(function () {
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
});

Route::middleware('page:sub-grupos')->group(function () {
    Route::get('/subgroups', [SubGroupController::class, 'index'])->name('subgroups.index');
    Route::post('/subgroups', [SubGroupController::class, 'store'])->name('subgroups.store');
    Route::put('/subgroups/{subgroup}', [SubGroupController::class, 'update'])->name('subgroups.update');
    Route::delete('/subgroups/{subgroup}', [SubGroupController::class, 'destroy'])->name('subgroups.destroy');
});

Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
Route::put('/profile/provider', [ProfileController::class, 'updateProvider'])->name('profile.provider.update');
Route::get('/profile/services', [ProviderServiceController::class, 'index'])->name('profile.services.index');
Route::post('/profile/services', [ProviderServiceController::class, 'store'])->name('profile.services.store');
Route::put('/profile/services/reorder', [ProviderServiceController::class, 'reorder'])->name('profile.services.reorder');
Route::put('/profile/services/{service}', [ProviderServiceController::class, 'update'])->name('profile.services.update');
Route::delete('/profile/services/{service}', [ProviderServiceController::class, 'destroy'])->name('profile.services.destroy');
Route::post('/profile/images', [ProfileController::class, 'storeImage'])->name('profile.images.store');
Route::delete('/profile/images/{image}', [ProfileController::class, 'destroyImage'])->name('profile.images.destroy');
Route::get('/profile/subgroups', [ProfileController::class, 'getProviderSubgroups'])->name('profile.subgroups.index');
Route::post('/profile/subgroups/toggle', [ProfileController::class, 'toggleProviderSubgroup'])->name('profile.subgroups.toggle');
Route::get('/profile/service-types', [ProfileController::class, 'getServiceTypes'])->middleware('page:ordenes-servicio')->name('profile.service-types.index');
Route::post('/profile/service-types/toggle', [ProfileController::class, 'toggleServiceType'])->middleware('page:ordenes-servicio')->name('profile.service-types.toggle');
Route::get('/profile/address', [ProfileController::class, 'getAddress'])->name('profile.address.index');
Route::put('/profile/address', [ProfileController::class, 'saveAddress'])->name('profile.address.update');
Route::get('/countries', [ProfileController::class, 'getCountries'])->name('countries.index');
Route::get('/regions', [ProfileController::class, 'getRegions'])->name('regions.index');

// Modulo A - Fast Delivery
Route::get('/api/providers', [ProviderController::class, 'index'])->name('api.providers.index');
Route::post('/api/providers/{provider}/favorite', [ProviderController::class, 'toggleFavorite'])->name('api.providers.favorite');
Route::get('/api/providers/{provider}/catalog', [CatalogController::class, 'show'])->name('api.providers.catalog');
Route::get('/api/providers/{provider}/orders', [OrderController::class, 'index'])->name('api.providers.orders');
Route::get('/api/orders/mine', [OrderController::class, 'mine'])->name('api.orders.mine');
Route::post('/api/orders', [OrderController::class, 'store'])->name('api.orders.store');
Route::patch('/api/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('api.orders.status');
Route::post('/api/orders/{order}/rate', [OrderController::class, 'rateOrder'])->name('api.orders.rate');
Route::get('/api/me/addresses', [AddressController::class, 'mine'])->name('api.me.addresses');

// Modulo de inventario por comercio
Route::get('/api/v1/provider/inventory', [InventoryController::class, 'index'])->name('api.provider.inventory.index');
Route::put('/api/v1/provider/inventory/settings', [InventoryController::class, 'settings'])->name('api.provider.inventory.settings');
Route::post('/api/v1/provider/inventory/adjust', [InventoryController::class, 'adjust'])->name('api.provider.inventory.adjust');
Route::put('/api/v1/provider/inventory/products/{product}', [InventoryController::class, 'updateProduct'])->name('api.provider.inventory.product.update');
Route::get('/api/v1/provider/inventory/movements', [InventoryController::class, 'movements'])->name('api.provider.inventory.movements');

// Productos del comercio (mi catalogo) con su control de stock
Route::get('/api/v1/provider/products', [ProductController::class, 'index'])->name('api.provider.products.index');
Route::post('/api/v1/provider/products', [ProductController::class, 'store'])->name('api.provider.products.store');
Route::put('/api/v1/provider/products/{product}', [ProductController::class, 'update'])->name('api.provider.products.update');

// Variantes (Individual / Grande / Familiar): el precio que paga el cliente
Route::post('/api/v1/provider/products/{product}/variants', [VariantController::class, 'store'])->name('api.provider.variants.store');
Route::put('/api/v1/provider/variants/{variant}', [VariantController::class, 'update'])->name('api.provider.variants.update');
Route::delete('/api/v1/provider/variants/{variant}', [VariantController::class, 'destroy'])->name('api.provider.variants.destroy');

// Agregados / Extras: grupos de opciones y sus opciones con precio extra
Route::post('/api/v1/provider/products/{product}/option-groups', [OptionController::class, 'storeGroup'])->name('api.provider.option-groups.store');
Route::put('/api/v1/provider/option-groups/{group}', [OptionController::class, 'updateGroup'])->name('api.provider.option-groups.update');
Route::delete('/api/v1/provider/option-groups/{group}', [OptionController::class, 'destroyGroup'])->name('api.provider.option-groups.destroy');
Route::post('/api/v1/provider/option-groups/{group}/options', [OptionController::class, 'storeOption'])->name('api.provider.options.store');
Route::put('/api/v1/provider/options/{option}', [OptionController::class, 'updateOption'])->name('api.provider.options.update');
Route::delete('/api/v1/provider/options/{option}', [OptionController::class, 'destroyOption'])->name('api.provider.options.destroy');

// Categorias y unidades de medida del catalogo
Route::post('/api/v1/provider/categories', [CategoryController::class, 'store'])->name('api.provider.categories.store');
Route::put('/api/v1/provider/categories/{category}', [CategoryController::class, 'update'])->name('api.provider.categories.update');
Route::delete('/api/v1/provider/categories/{category}', [CategoryController::class, 'destroy'])->name('api.provider.categories.destroy');
Route::get('/api/v1/provider/units', [UnitController::class, 'index'])->name('api.provider.units.index');

// Suscripciones y compras recurrentes
Route::get('/api/v1/provider/subscription-plans', [SubscriptionController::class, 'indexPlans'])->name('api.provider.subscription-plans.index');
Route::post('/api/v1/provider/subscription-plans', [SubscriptionController::class, 'storePlan'])->name('api.provider.subscription-plans.store');
Route::get('/api/v1/public/providers/{provider}/subscription-plans', [SubscriptionController::class, 'publicPlans'])->name('api.public.providers.subscription-plans.index');
Route::post('/api/v1/customer/subscriptions', [SubscriptionController::class, 'store'])->name('api.customer.subscriptions.store');
Route::get('/api/v1/customer/subscriptions', [SubscriptionController::class, 'index'])->name('api.customer.subscriptions.index');
Route::put('/api/v1/customer/subscriptions/{subscription}/status', [SubscriptionController::class, 'updateStatus'])->name('api.customer.subscriptions.status');
Route::put('/api/v1/customer/subscriptions/{subscription}/items', [SubscriptionController::class, 'updateItems'])->name('api.customer.subscriptions.items.update');
Route::get('/api/v1/provider/subscriptions/revenue', [SubscriptionController::class, 'revenue'])->name('api.provider.subscriptions.revenue');
Route::put('/api/v1/provider/subscription-payments/{payment}', [SubscriptionController::class, 'updatePayment'])->name('api.provider.subscription-payments.update');

// Motor de retencion automatica (reglas de recuperacion de clientes).
Route::get('/api/v1/provider/marketing/rules', [MarketingController::class, 'index'])->name('api.provider.marketing.rules.index');
Route::put('/api/v1/provider/marketing/rules', [MarketingController::class, 'update'])->name('api.provider.marketing.rules.update');

// Costos de receta y salud financiera del comercio.
Route::get('/api/v1/provider/supplies', [RecipeController::class, 'indexSupplies'])->name('api.provider.supplies.index');
Route::post('/api/v1/provider/supplies', [RecipeController::class, 'storeSupply'])->name('api.provider.supplies.store');
Route::delete('/api/v1/provider/supplies/{supply}', [RecipeController::class, 'destroySupply'])->name('api.provider.supplies.destroy');
Route::get('/api/v1/provider/financial-health', [RecipeController::class, 'financialHealth'])->name('api.provider.financial-health.index');
Route::get('/api/v1/provider/products/{product}/recipe', [RecipeController::class, 'showRecipe'])->name('api.provider.products.recipe.show');
Route::post('/api/v1/provider/products/{product}/recipe', [RecipeController::class, 'storeRecipe'])->name('api.provider.products.recipe.store');

// Reportes del negocio: Prestador (Merchant) y Cliente (Customer).
// La autenticacion y el rol se resuelven dentro del controlador, igual que
// el resto de los endpoints /api/v1 de este modulo.
Route::middleware('page:reportes')->group(function () {
    Route::get('/api/v1/provider/reports/margins', [ReportController::class, 'getMarginAndRecipeCost'])->name('api.provider.reports.margins');
    Route::get('/api/v1/provider/reports/mrr', [ReportController::class, 'getMRRAndSubscriptions'])->name('api.provider.reports.mrr');
    Route::get('/api/v1/provider/reports/commission-savings', [ReportController::class, 'getCommissionSavings'])->name('api.provider.reports.commission-savings');
    Route::get('/api/v1/provider/reports/ltv-risk', [ReportController::class, 'getLTVAndAtRiskCustomers'])->name('api.provider.reports.ltv-risk');
    Route::get('/api/v1/provider/reports/campaigns', [ReportController::class, 'getCampaignPerformance'])->name('api.provider.reports.campaigns');
    Route::get('/api/v1/provider/reports/demand-heatmap', [ReportController::class, 'getDemandHeatmap'])->name('api.provider.reports.demand-heatmap');
    Route::get('/api/v1/provider/reports/stock-forecast', [ReportController::class, 'getSubscriptionStockForecast'])->name('api.provider.reports.stock-forecast');
    Route::get('/api/v1/customer/reports/savings', [ReportController::class, 'getCustomerSavings'])->name('api.customer.reports.savings');
    Route::get('/api/v1/customer/reports/subscriptions', [ReportController::class, 'getActiveSubscriptions'])->name('api.customer.reports.subscriptions');
    Route::get('/api/v1/customer/reports/order-history', [ReportController::class, 'getOrderHistoryAndReorder'])->name('api.customer.reports.order-history');
    Route::get('/api/v1/customer/reports/nutritional', [ReportController::class, 'getNutritionalSummary'])->name('api.customer.reports.nutritional');
});

// Modulo B - solicitud de servicios (pagina "solicitar-servicio" del Cliente).
// El acceso a la API sigue la misma pagina que arma el menu del dashboard.
Route::middleware('page:solicitar-servicio')->group(function () {
    Route::get('/api/v1/service-subgroups', [ServiceBookingController::class, 'getServiceSubgroups'])->name('api.service-subgroups');
    Route::get('/api/v1/service-providers', [ServiceBookingController::class, 'getServiceProviders'])->name('api.service-providers');
    Route::post('/api/v1/customer/service-orders/emergency', [ServiceOrderController::class, 'requestEmergencyService'])->name('api.customer.service-orders.emergency');
    // Seguimiento en vivo de la urgencia recien enviada (polling de estado).
    Route::get('/api/v1/customer/service-orders/{serviceOrder}', [ServiceOrderController::class, 'showForCustomer'])->name('api.customer.service-orders.show');
    Route::post('/api/v1/customer/service-orders/scheduled', [ServiceBookingController::class, 'bookScheduledService'])->name('api.customer.service-orders.scheduled');
    Route::post('/api/v1/customer/service-orders/quote', [ServiceBookingController::class, 'requestQuote'])->name('api.customer.service-orders.quote');
});

// Modulo B - flujo de urgencias 24/7 (seguimiento y cierre del trabajo).
// La autenticacion y el rol (cliente / prestador) se resuelven dentro del
// controlador, igual que el resto de los endpoints /api/v1 de este proyecto.
Route::post('/api/v1/provider/service-orders/{serviceOrder}/accept', [ServiceOrderController::class, 'acceptEmergencyRequest'])->name('api.provider.service-orders.accept');
Route::post('/api/v1/provider/service-orders/{serviceOrder}/dispatch', [ServiceOrderController::class, 'dispatchService'])->name('api.provider.service-orders.dispatch');
Route::post('/api/v1/provider/service-orders/{serviceOrder}/onsite-estimate', [ServiceOrderController::class, 'addOnsiteEstimate'])->name('api.provider.service-orders.onsite-estimate');
// Detalle operativo de una orden (contacto, ubicacion, adjuntos, timeline).
// Se deja fuera del grupo con middleware de pagina porque lo usan tanto
// "Ordenes de Servicio" como "Gestionar Solicitudes".
Route::get('/api/v1/provider/service-orders/{serviceOrder}', [ServiceOrderController::class, 'showForProvider'])->name('api.provider.service-orders.show');
Route::post('/api/v1/customer/service-orders/{serviceOrder}/approve-onsite-estimate', [ServiceOrderController::class, 'approveOnsiteEstimate'])->name('api.customer.service-orders.approve-onsite-estimate');

// Modulo B - citas programadas y presupuestos a medida.
Route::post('/api/v1/provider/service-orders/{serviceOrder}/quote-proposal', [ServiceBookingController::class, 'submitQuoteProposal'])->name('api.provider.service-orders.quote-proposal');
Route::post('/api/v1/customer/service-orders/{serviceOrder}/accept-quote', [ServiceBookingController::class, 'acceptQuoteProposal'])->name('api.customer.service-orders.accept-quote');
Route::post('/api/v1/customer/service-orders/{serviceOrder}/reject-quote', [ServiceBookingController::class, 'rejectQuote'])->name('api.customer.service-orders.reject-quote');

// Modulo B - cancelacion de orden por parte del cliente.
Route::post('/api/v1/customer/service-orders/{serviceOrder}/cancel', [ServiceOrderController::class, 'cancelService'])->name('api.customer.service-orders.cancel');

// Modulo B - cierre de trabajo, liberacion de escrow y calificaciones.
// El rol (merchant / customer) se valida dentro del controlador, igual que
// el resto de los endpoints /api/v1 del modulo.
Route::post('/api/v1/provider/service-orders/{serviceOrder}/complete', [ServiceOrderController::class, 'completeService'])->name('api.provider.service-orders.complete');
Route::post('/api/v1/customer/service-orders/{serviceOrder}/confirm-release', [ServiceOrderController::class, 'confirmAndReleasePayment'])->name('api.customer.service-orders.confirm-release');
Route::post('/api/v1/customer/service-orders/{serviceOrder}/rate', [ServiceOrderController::class, 'rateService'])->name('api.customer.service-orders.rate');
Route::post('/api/v1/provider/service-orders/{serviceOrder}/rate', [ServiceOrderController::class, 'rateService'])->name('api.provider.service-orders.rate');

// Modulo B - el prestador consulta sus ordenes de servicio (pagina
// "Ordenes de Servicio"). El acceso sigue la pagina que arma el menu.
Route::middleware('page:ordenes-servicio')->group(function () {
    Route::get('/api/v1/provider/service-orders', [ServiceOrderController::class, 'indexForProvider'])->name('api.provider.service-orders.index');
});

// Feed unificado del panel "Ordenes y Gestion": servicios + pedidos del
// comercio en una sola respuesta (tabs, busqueda y filtros rapidos).
Route::get('/api/v1/provider/ordenes-gestion', [OrdenesGestionController::class, 'data'])
    ->middleware('page:ordenes-gestion')
    ->name('api.provider.ordenes-gestion');

// Modulo B - el cliente consulta sus propias ordenes de servicio (pagina
// "Mis Servicios"). El acceso sigue la pagina que arma el menu.
Route::middleware('page:mis-servicios')->group(function () {
    Route::get('/api/v1/customer/service-orders', [ServiceOrderController::class, 'indexForCustomer'])->name('api.customer.service-orders.index');
});
