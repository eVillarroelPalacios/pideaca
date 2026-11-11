<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Provider;
use Illuminate\Http\Request;

/**
 * Resuelve el comercio del usuario autenticado con Sanctum.
 *
 * Los endpoints de gestion del catalogo son espejo de los del panel web
 * (ProductController, CategoryController, etc.), que obtienen el comercio con
 * Auth::user()->provider. En la app movil el guard es Sanctum, por eso el
 * usuario se toma de $request->user() pero la regla de negocio es la misma:
 * sin comercio asociado se responde 403.
 */
trait ResolvesProvider
{
    protected function providerOrFail(Request $request): Provider
    {
        $provider = $request->user()?->provider;

        if (! $provider) {
            abort(response()->json([
                'success' => false,
                'message' => 'Tu usuario no tiene un comercio asociado.',
                'code' => 'NOT_A_PROVIDER',
            ], 403));
        }

        return $provider;
    }
}
