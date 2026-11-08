<?php

use App\Http\Controllers\Api\V1\AuthController;
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
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::delete('/auth/account', [AuthController::class, 'destroyAccount']);
    });
});
