<?php

use App\Http\Middleware\EnsurePageAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'page' => EnsurePageAccess::class,
        ]);

        // La app no tiene pagina /login: el formulario vive en el home.
        // Sin esto, un invitado en cualquier ruta "auth" revienta con
        // "Route [login] not defined" en vez de volver al inicio.
        $middleware->redirectGuestsTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Toda ruta /api/* responde JSON (401, 422, 500), aunque el cliente no
        // mande "Accept: application/json". Sin esto, un invitado en el API
        // seria redirigido al home del sitio web.
        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado. Inicia sesion nuevamente.',
                    'code' => 'UNAUTHENTICATED',
                ], 401);
            }
        });
    })->create();
