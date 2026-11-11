<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unidades de medida para la app movil. Son globales de la plataforma y las
 * administra el panel de Administrar, por eso aca solo se consultan junto con
 * cuantos productos propios las usan (mismo payload que UnitController web).
 */
class ProviderUnitController extends Controller
{
    use ResolvesProvider;

    public function index(Request $request): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        // No se usa $provider->products() porque esa relacion ordena por
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
}
