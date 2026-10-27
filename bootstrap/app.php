<?php

use App\Http\Middleware\EnsurePageAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
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
        //
    })->create();
