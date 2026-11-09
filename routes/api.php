<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AdminUsersController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\ContactInquiriesController;
use App\Http\Controllers\Api\V1\CurrenciesController;
use App\Http\Controllers\Api\V1\GroupStatusesController;
use App\Http\Controllers\Api\V1\GroupsController;
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
use App\Http\Controllers\Api\V1\ProviderOrdersController;
use App\Http\Controllers\Api\V1\PushController;
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

        Route::prefix('provider')->group(function () {
            Route::get('/orders', [ProviderOrdersController::class, 'index'])
                ->middleware('throttle:30,1');
            Route::get('/orders/{serviceOrder}', [ProviderOrdersController::class, 'show']);
            Route::post('/orders/{serviceOrder}/accept', [ProviderOrdersController::class, 'accept']);
            Route::post('/orders/{serviceOrder}/dispatch', [ProviderOrdersController::class, 'dispatch']);
            Route::post('/orders/{serviceOrder}/estimate', [ProviderOrdersController::class, 'estimate']);
            Route::post('/orders/{serviceOrder}/propose', [ProviderOrdersController::class, 'propose']);
            Route::post('/orders/{serviceOrder}/complete', [ProviderOrdersController::class, 'complete']);
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
