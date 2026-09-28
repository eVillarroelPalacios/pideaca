<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La pagina es la unidad de permiso del sistema: es lo que arma el menu del
 * dashboard y lo que el registro asigna por tipo de usuario. Este middleware
 * usa esa misma fuente de verdad, asi que lo que un usuario no ve en el menu
 * tampoco lo puede escribir por API.
 */
class EnsurePageAccess
{
    public function handle(Request $request, Closure $next, string ...$pages): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if (! $user->hasPageUrl(...$pages)) {
            return response()->json([
                'error' => 'No autorizado',
                'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
            ], 403);
        }

        return $next($request);
    }
}
