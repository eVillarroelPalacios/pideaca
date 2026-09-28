<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\UnitOfMeasure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Unidades de medida. Son globales para la plataforma (las crea el seeder y
 * las usan todos los comercios), por eso aca solo se consultan: qué
 * unidades existen y cuántos productos propios están asociados a cada una.
 */
class UnitController extends Controller
{
    public function index(Request $request)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;

        // No se usa $provider->products() porque esa relación ordena por
        // sort_order y Postgres lo rechaza junto con el GROUP BY.
        $porUnidad = Product::where('provider_id', $provider->id)
            ->whereNotNull('unit_of_measure_id')
            ->selectRaw('unit_of_measure_id, COUNT(*) as total')
            ->groupBy('unit_of_measure_id')
            ->pluck('total', 'unit_of_measure_id');

        $units = UnitOfMeasure::orderBy('name')
            ->get()
            ->map(fn (UnitOfMeasure $unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                'symbol' => $unit->symbol,
                'base_conversion_factor' => (float) $unit->base_conversion_factor,
                'is_integer_only' => (bool) $unit->is_integer_only,
                'products_count' => (int) ($porUnidad[$unit->id] ?? 0),
            ]);

        return response()->json([
            'success' => true,
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
            ],
            'units' => $units->values(),
            'products_with_unit' => (int) $porUnidad->sum(),
        ]);
    }

    private function currentProvider()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $provider = Auth::user()->provider;

        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'Tu usuario no tiene un comercio asociado.',
            ], 403);
        }

        return $provider;
    }
}
