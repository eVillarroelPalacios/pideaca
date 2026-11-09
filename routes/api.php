<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\ContactInquiriesController;
use App\Http\Controllers\Api\V1\GroupStatusesController;
use App\Http\Controllers\Api\V1\MenuController;
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
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::delete('/auth/account', [AuthController::class, 'destroyAccount']);

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
