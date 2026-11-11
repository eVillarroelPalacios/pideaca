<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AdminUsersController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\ContactInquiriesController;
use App\Http\Controllers\Api\V1\CurrenciesController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\GroupStatusesController;
use App\Http\Controllers\Api\V1\GroupsController;
use App\Http\Controllers\Api\V1\MarketController;
use App\Http\Controllers\Api\V1\MarketReportsController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\ModulesController;
use App\Http\Controllers\Api\V1\PagesController;
use App\Http\Controllers\Api\V1\SubGroupsController;
use App\Http\Controllers\Api\V1\TypeUsersController;
use App\Http\Controllers\Api\V1\UnitsOfMeasureController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProfileDriversController;
use App\Http\Controllers\Api\V1\ProfileServicesController;
use App\Http\Controllers\Api\V1\ProfileUsersController;
use App\Http\Controllers\Api\V1\ProviderCatalogController;
use App\Http\Controllers\Api\V1\ProviderCategoryController;
use App\Http\Controllers\Api\V1\ProviderDispatchController;
use App\Http\Controllers\Api\V1\ProviderFinancialHealthController;
use App\Http\Controllers\Api\V1\ProviderInventoryController;
use App\Http\Controllers\Api\V1\ProviderMarketingController;
use App\Http\Controllers\Api\V1\ProviderOptionController;
use App\Http\Controllers\Api\V1\ProviderOrdersController;
use App\Http\Controllers\Api\V1\ProviderSubscriptionsController;
use App\Http\Controllers\Api\V1\ProviderUnitController;
use App\Http\Controllers\Api\V1\ProviderVariantController;
use App\Http\Controllers\Api\V1\PushController;
use App\Http\Controllers\Api\V1\ReportsController;
use App\Http\Controllers\Api\V1\ServiceOrdersController;
use App\Http\Controllers\Api\V1\UserStatusesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API movil (PideAca)
|--------------------------------------------------------------------------
| Todo lo que consumen las apps de Expo/React Native vive aca. Las rutas
| existentes de /api/v1 en routes/web.php siguen siendo para el panel web
| (sesion + CSRF) y no se tocan.
|
| Autenticacion: token Bearer emitido por Sanctum en /auth/login.
| Prefijo final: /api/mobile/v1
*/

Route::prefix('mobile/v1')->group(function () {
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'API PideAca funcionando.',
            'version' => 'v1',
        ]);
    });

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');

    Route::get('/auth/types', [AuthController::class, 'types'])
        ->middleware('throttle:30,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/menu', [MenuController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/contact-inquiries', [ContactInquiriesController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/contact-inquiries/{contactInquiry}', [ContactInquiriesController::class, 'show']);
        Route::post('/contact-inquiries/{contactInquiry}/status', [ContactInquiriesController::class, 'updateStatus']);
        Route::get('/group-statuses', [GroupStatusesController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/group-statuses', [GroupStatusesController::class, 'store']);
        Route::put('/group-statuses/{groupStatus}', [GroupStatusesController::class, 'update']);
        Route::delete('/group-statuses/{groupStatus}', [GroupStatusesController::class, 'destroy']);
        Route::get('/user-statuses', [UserStatusesController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/user-statuses', [UserStatusesController::class, 'store']);
        Route::put('/user-statuses/{userStatus}', [UserStatusesController::class, 'update']);
        Route::delete('/user-statuses/{userStatus}', [UserStatusesController::class, 'destroy']);
        Route::get('/groups', [GroupsController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/groups', [GroupsController::class, 'store']);
        Route::put('/groups/{group}', [GroupsController::class, 'update']);
        Route::delete('/groups/{group}', [GroupsController::class, 'destroy']);
        Route::get('/modules', [ModulesController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/modules', [ModulesController::class, 'store']);
        Route::put('/modules/{module}', [ModulesController::class, 'update']);
        Route::delete('/modules/{module}', [ModulesController::class, 'destroy']);
        Route::get('/pages', [PagesController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/pages', [PagesController::class, 'store']);
        Route::put('/pages/{page}', [PagesController::class, 'update']);
        Route::delete('/pages/{page}', [PagesController::class, 'destroy']);
        Route::get('/subgroups', [SubGroupsController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/subgroups', [SubGroupsController::class, 'store']);
        Route::put('/subgroups/{subgroup}', [SubGroupsController::class, 'update']);
        Route::delete('/subgroups/{subgroup}', [SubGroupsController::class, 'destroy']);
        Route::get('/currencies', [CurrenciesController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/currencies', [CurrenciesController::class, 'store']);
        Route::put('/currencies/{currency}', [CurrenciesController::class, 'update']);
        Route::delete('/currencies/{currency}', [CurrenciesController::class, 'destroy']);
        Route::get('/type-users', [TypeUsersController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/type-users', [TypeUsersController::class, 'store']);
        Route::put('/type-users/{typeUser}', [TypeUsersController::class, 'update']);
        Route::delete('/type-users/{typeUser}', [TypeUsersController::class, 'destroy']);
        Route::get('/type-users/pages-data', [TypeUsersController::class, 'pagesData'])
            ->middleware('throttle:30,1');
        Route::put('/type-users/{typeUser}/pages', [TypeUsersController::class, 'updatePages']);
        Route::post('/type-users/{typeUser}/pages/bulk', [TypeUsersController::class, 'bulkPages']);
        Route::post('/type-users/{typeUser}/pages/{page}', [TypeUsersController::class, 'attachPage']);
        Route::delete('/type-users/{typeUser}/pages/{page}', [TypeUsersController::class, 'detachPage']);
        Route::get('/units-of-measure', [UnitsOfMeasureController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/units-of-measure', [UnitsOfMeasureController::class, 'store']);
        Route::put('/units-of-measure/{unitOfMeasure}', [UnitsOfMeasureController::class, 'update']);
        Route::delete('/units-of-measure/{unitOfMeasure}', [UnitsOfMeasureController::class, 'destroy']);

        // Gestion de Usuarios: mismos paneles y reglas que la seccion web.
        Route::get('/admin-users', [AdminUsersController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/admin-users', [AdminUsersController::class, 'store']);
        Route::put('/admin-users/{user}', [AdminUsersController::class, 'update']);
        Route::delete('/admin-users/{user}', [AdminUsersController::class, 'destroy']);
        Route::post('/admin-users/{user}/pages/{page}', [AdminUsersController::class, 'attachPage']);
        Route::delete('/admin-users/{user}/pages/{page}', [AdminUsersController::class, 'detachPage']);
        Route::post('/admin-users/{user}/addresses', [AdminUsersController::class, 'storeAddress']);
        Route::put('/admin-users/{user}/addresses/{address}', [AdminUsersController::class, 'updateAddress']);

        // Perfil del usuario gestionado (modo managed de la web).
        Route::get('/admin-users/{user}/profile', [AdminUsersController::class, 'profile'])
            ->middleware('throttle:30,1');
        Route::put('/admin-users/{user}/provider', [AdminUsersController::class, 'updateProvider']);
        Route::get('/admin-users/{user}/services', [AdminUsersController::class, 'services'])
            ->middleware('throttle:30,1');
        Route::post('/admin-users/{user}/services', [AdminUsersController::class, 'storeService']);
        Route::put('/admin-users/{user}/services/{service}', [AdminUsersController::class, 'updateService']);
        Route::delete('/admin-users/{user}/services/{service}', [AdminUsersController::class, 'destroyService']);
        Route::post('/admin-users/{user}/images', [AdminUsersController::class, 'storeImage']);
        Route::delete('/admin-users/{user}/images/{image}', [AdminUsersController::class, 'destroyImage']);
        Route::get('/admin-users/{user}/subgroups', [AdminUsersController::class, 'subgroups'])
            ->middleware('throttle:30,1');
        Route::post('/admin-users/{user}/subgroups/toggle', [AdminUsersController::class, 'toggleSubgroup']);
        Route::get('/admin-users/{user}/service-types', [AdminUsersController::class, 'serviceTypes'])
            ->middleware('throttle:30,1');
        Route::post('/admin-users/{user}/service-types/toggle', [AdminUsersController::class, 'toggleServiceType']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::delete('/auth/account', [AuthController::class, 'destroyAccount']);

        // Mi Perfil: mismos paneles y reglas que el dashboard web.
        Route::get('/profile', [ProfileController::class, 'show'])
            ->middleware('throttle:30,1');
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
        Route::put('/profile/provider', [ProfileController::class, 'updateProvider']);
        Route::patch('/profile/vehicle', [ProfileController::class, 'updateVehicle']);
        Route::post('/profile/images', [ProfileController::class, 'storeImage']);
        Route::delete('/profile/images/{image}', [ProfileController::class, 'destroyImage']);
        Route::get('/profile/address', [ProfileController::class, 'address']);
        Route::put('/profile/address', [ProfileController::class, 'saveAddress']);
        Route::get('/profile/countries', [ProfileController::class, 'countries'])
            ->middleware('throttle:30,1');
        Route::get('/profile/regions', [ProfileController::class, 'regions'])
            ->middleware('throttle:30,1');

        Route::get('/profile/services', [ProfileServicesController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/profile/services', [ProfileServicesController::class, 'store']);
        Route::put('/profile/services/reorder', [ProfileServicesController::class, 'reorder']);
        Route::put('/profile/services/{service}', [ProfileServicesController::class, 'update']);
        Route::delete('/profile/services/{service}', [ProfileServicesController::class, 'destroy']);
        Route::get('/profile/subgroups', [ProfileServicesController::class, 'subgroups'])
            ->middleware('throttle:30,1');
        Route::post('/profile/subgroups/toggle', [ProfileServicesController::class, 'toggleSubgroup']);
        Route::get('/profile/service-types', [ProfileServicesController::class, 'serviceTypes'])
            ->middleware('throttle:30,1');
        Route::post('/profile/service-types/toggle', [ProfileServicesController::class, 'toggleServiceType']);

        Route::get('/profile/trusted-drivers', [ProfileDriversController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::put('/profile/trusted-drivers/settings', [ProfileDriversController::class, 'updateSettings']);
        Route::post('/profile/trusted-drivers', [ProfileDriversController::class, 'store']);
        Route::delete('/profile/trusted-drivers/{driver}', [ProfileDriversController::class, 'destroy']);

        Route::get('/profile/users', [ProfileUsersController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::post('/profile/users', [ProfileUsersController::class, 'store']);
        Route::put('/profile/users/{user}', [ProfileUsersController::class, 'update']);
        Route::delete('/profile/users/{user}', [ProfileUsersController::class, 'destroy']);
        Route::post('/profile/users/{user}/pages/{page}', [ProfileUsersController::class, 'attachPage']);
        Route::delete('/profile/users/{user}/pages/{page}', [ProfileUsersController::class, 'detachPage']);

        Route::get('/providers', [CatalogController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/providers/{provider}', [CatalogController::class, 'show'])
            ->middleware('throttle:30,1');
        Route::post('/providers/{provider}/favorite', [CatalogController::class, 'toggleFavorite'])
            ->middleware('throttle:60,1');

        // Mercado: comercios, catalogo, pedidos y suscripciones del cliente.
        Route::prefix('market')->group(function () {
            Route::get('/shops', [MarketController::class, 'shops'])
                ->middleware('throttle:60,1');
            Route::post('/shops/{provider}/favorite', [MarketController::class, 'toggleFavorite'])
                ->middleware('throttle:60,1');
            Route::get('/shops/{provider}/catalog', [MarketController::class, 'catalog'])
                ->middleware('throttle:60,1');
            Route::get('/shops/{provider}/plans', [MarketController::class, 'plans'])
                ->middleware('throttle:30,1');
            Route::get('/addresses', [MarketController::class, 'addresses'])
                ->middleware('throttle:30,1');
            Route::post('/orders', [MarketController::class, 'storeOrder']);
            Route::get('/orders', [MarketController::class, 'myOrders'])
                ->middleware('throttle:60,1');
            Route::post('/orders/{order}/rate', [MarketController::class, 'rateOrder'])
                ->middleware('throttle:30,1');
            Route::get('/subscriptions', [MarketController::class, 'subscriptions'])
                ->middleware('throttle:30,1');
            Route::post('/subscriptions', [MarketController::class, 'storeSubscription']);
            Route::patch('/subscriptions/{subscription}/status', [MarketController::class, 'updateSubscriptionStatus']);
        });

        // Reportes del Módulo Hogar (mismos filtros y KPIs que la web).
        Route::get('/reports/hogar', [ReportsController::class, 'hogar'])
            ->middleware('throttle:30,1');

        // Reportes Comercio para el cliente de Mercado: ahorro, suscripciones,
        // historico/habitos y resumen nutricional (espejo de /api/v1/customer).
        Route::prefix('reports/comercio')->group(function () {
            Route::get('/savings', [MarketReportsController::class, 'savings'])
                ->middleware('throttle:30,1');
            Route::get('/subscriptions', [MarketReportsController::class, 'subscriptions'])
                ->middleware('throttle:30,1');
            Route::get('/order-history', [MarketReportsController::class, 'orderHistory'])
                ->middleware('throttle:30,1');
            Route::get('/nutritional', [MarketReportsController::class, 'nutritional'])
                ->middleware('throttle:30,1');
        });

        Route::get('/service-subgroups', [ServiceOrdersController::class, 'subgroups'])
            ->middleware('throttle:30,1');
        Route::get('/service-orders', [ServiceOrdersController::class, 'index'])
            ->middleware('throttle:30,1');
        Route::get('/service-orders/{serviceOrder}', [ServiceOrdersController::class, 'show']);
        Route::post('/service-orders/emergency', [ServiceOrdersController::class, 'storeEmergency']);
        Route::post('/service-orders/scheduled', [ServiceOrdersController::class, 'storeScheduled']);
        Route::post('/service-orders/quote', [ServiceOrdersController::class, 'storeQuote']);
        Route::post('/service-orders/{serviceOrder}/cancel', [ServiceOrdersController::class, 'cancel']);
        Route::post('/service-orders/{serviceOrder}/accept-quote', [ServiceOrdersController::class, 'acceptQuote']);
        Route::post('/service-orders/{serviceOrder}/reject-quote', [ServiceOrdersController::class, 'rejectQuote']);
        Route::post('/service-orders/{serviceOrder}/rate', [ServiceOrdersController::class, 'rate']);
        Route::post('/service-orders/{serviceOrder}/approve-estimate', [ServiceOrdersController::class, 'approveEstimate']);
        Route::post('/service-orders/{serviceOrder}/confirm-release', [ServiceOrdersController::class, 'confirmRelease']);

        // Terminal Central de Repartos (Driver Pro): espejo movil del panel
        // del repartidor web. El estado online/GPS vive en cache por usuario.
        Route::prefix('driver')->group(function () {
            Route::get('/stats', [DriverController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/status', [DriverController::class, 'status'])
                ->middleware('throttle:60,1');
            Route::put('/status', [DriverController::class, 'updateStatus']);
            Route::get('/orders/available', [DriverController::class, 'availableOrders'])
                ->middleware('throttle:120,1');
            Route::get('/orders/mine', [DriverController::class, 'myOrders'])
                ->middleware('throttle:120,1');
            Route::post('/orders/{order}/take', [DriverController::class, 'takeOrder']);
            Route::post('/orders/{order}/pickup', [DriverController::class, 'markPickedUp']);
            Route::post('/orders/{order}/confirm', [DriverController::class, 'confirmDelivery']);
        });

        Route::prefix('provider')->group(function () {
            Route::get('/orders', [ProviderOrdersController::class, 'index'])
                ->middleware('throttle:30,1');
            Route::get('/orders/{serviceOrder}', [ProviderOrdersController::class, 'show']);
            Route::post('/orders/{serviceOrder}/accept', [ProviderOrdersController::class, 'accept']);
            Route::post('/orders/{serviceOrder}/dispatch', [ProviderOrdersController::class, 'dispatch']);
            Route::post('/orders/{serviceOrder}/estimate', [ProviderOrdersController::class, 'estimate']);
            Route::post('/orders/{serviceOrder}/propose', [ProviderOrdersController::class, 'propose']);
            Route::post('/orders/{serviceOrder}/complete', [ProviderOrdersController::class, 'complete']);

            // Mi Catalogo: espejo movil de los paneles de gestion del comercio.
            Route::prefix('catalog')->group(function () {
                Route::get('/', [ProviderCatalogController::class, 'preview'])
                    ->middleware('throttle:30,1');
                Route::get('/products', [ProviderCatalogController::class, 'products'])
                    ->middleware('throttle:30,1');
                Route::post('/products', [ProviderCatalogController::class, 'storeProduct']);
                Route::put('/products/{product}', [ProviderCatalogController::class, 'updateProduct']);
                Route::post('/products/{product}/variants', [ProviderVariantController::class, 'store']);
                Route::put('/variants/{variant}', [ProviderVariantController::class, 'update']);
                Route::delete('/variants/{variant}', [ProviderVariantController::class, 'destroy']);
                Route::post('/products/{product}/option-groups', [ProviderOptionController::class, 'storeGroup']);
                Route::put('/option-groups/{group}', [ProviderOptionController::class, 'updateGroup']);
                Route::delete('/option-groups/{group}', [ProviderOptionController::class, 'destroyGroup']);
                Route::post('/option-groups/{group}/options', [ProviderOptionController::class, 'storeOption']);
                Route::put('/options/{option}', [ProviderOptionController::class, 'updateOption']);
                Route::delete('/options/{option}', [ProviderOptionController::class, 'destroyOption']);
                Route::post('/categories', [ProviderCategoryController::class, 'store']);
                Route::put('/categories/{category}', [ProviderCategoryController::class, 'update']);
                Route::delete('/categories/{category}', [ProviderCategoryController::class, 'destroy']);
                Route::get('/units', [ProviderUnitController::class, 'index'])
                    ->middleware('throttle:30,1');
            });

            // Inventario: espejo movil del panel de stock del comercio.
            Route::prefix('inventory')->group(function () {
                Route::get('/', [ProviderInventoryController::class, 'index'])
                    ->middleware('throttle:30,1');
                Route::put('/settings', [ProviderInventoryController::class, 'settings']);
                Route::put('/products/{product}', [ProviderInventoryController::class, 'updateProduct']);
            });

            // Centro de Despacho: pedidos de comida y acciones del comercio.
            // La lista de confianza vive en /profile/trusted-drivers.
            Route::prefix('dispatch')->group(function () {
                Route::get('/orders', [ProviderDispatchController::class, 'index'])
                    ->middleware('throttle:60,1');
                Route::patch('/orders/{order}/status', [ProviderDispatchController::class, 'updateStatus']);
                Route::post('/orders/{order}/rate', [ProviderDispatchController::class, 'rateOrder'])
                    ->middleware('throttle:30,1');
                Route::post('/orders/{order}/send-to-pool', [ProviderDispatchController::class, 'sendToPool']);
            });

            // Retencion: campañas automaticas de fidelizacion del comercio.
            Route::prefix('marketing')->group(function () {
                Route::get('/rules', [ProviderMarketingController::class, 'index'])
                    ->middleware('throttle:30,1');
                Route::put('/rules', [ProviderMarketingController::class, 'update']);
            });

            // Salud financiera: costos, margenes, insumos y fichas tecnicas.
            Route::prefix('financial-health')->group(function () {
                Route::get('/', [ProviderFinancialHealthController::class, 'index'])
                    ->middleware('throttle:30,1');
                Route::get('/supplies', [ProviderFinancialHealthController::class, 'supplies'])
                    ->middleware('throttle:30,1');
                Route::post('/supplies', [ProviderFinancialHealthController::class, 'storeSupply']);
                Route::delete('/supplies/{supply}', [ProviderFinancialHealthController::class, 'destroySupply']);
                Route::get('/products/{product}/recipe', [ProviderFinancialHealthController::class, 'showRecipe'])
                    ->middleware('throttle:30,1');
                Route::post('/products/{product}/recipe', [ProviderFinancialHealthController::class, 'storeRecipe']);
            });

            // Suscripciones del comercio: planes, MRR y cobros por confirmar.
            Route::prefix('subscriptions')->group(function () {
                Route::get('/revenue', [ProviderSubscriptionsController::class, 'revenue'])
                    ->middleware('throttle:30,1');
                Route::get('/plans', [ProviderSubscriptionsController::class, 'indexPlans'])
                    ->middleware('throttle:30,1');
                Route::post('/plans', [ProviderSubscriptionsController::class, 'storePlan']);
                Route::put('/plans/{plan}', [ProviderSubscriptionsController::class, 'updatePlan']);
                Route::put('/payments/{payment}', [ProviderSubscriptionsController::class, 'updatePayment']);
            });
        });

        Route::post('/push-tokens', [PushController::class, 'storeToken'])
            ->middleware('throttle:30,1');
        Route::delete('/push-tokens', [PushController::class, 'destroyToken']);
        Route::get('/notifications', [PushController::class, 'index'])
            ->middleware('throttle:60,1');
        Route::post('/notifications/{notification}/read', [PushController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [PushController::class, 'markAllAsRead']);
    });
});
