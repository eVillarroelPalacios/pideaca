<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Provider;
use App\Models\Supply;
use App\Models\UnitOfMeasure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecipeController extends Controller
{
    /**
     * POST /api/v1/provider/supplies
     *
     * Alta y actualizacion de insumos. Si viene id actualiza el insumo de ese
     * comercio; si no, da de alta uno nuevo (o actualiza el que ya existe con
     * ese nombre, para no duplicar insumos).
     */
    public function storeSupply(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $datos = $request->validate([
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:150'],
            'unit_of_measure_id' => ['required', 'integer', Rule::exists('unit_of_measures', 'id')],
            'cost_per_unit' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        if (isset($datos['id'])) {
            $supply = Supply::where('provider_id', $provider->id)->find($datos['id']);

            if (! $supply) {
                return response()->json([
                    'success' => false,
                    'message' => 'El insumo no existe o no pertenece a tu comercio.',
                ], 404);
            }
        } else {
            $supply = Supply::firstOrNew([
                'provider_id' => $provider->id,
                'name' => $datos['name'],
            ]);
        }

        $supply->fill([
            'name' => $datos['name'],
            'unit_of_measure_id' => $datos['unit_of_measure_id'],
            'cost_per_unit' => $datos['cost_per_unit'],
        ]);

        $supply->save();

        return response()->json([
            'success' => true,
            'message' => $supply->wasRecentlyCreated ? 'Insumo creado.' : 'Insumo actualizado.',
            'supply' => $this->supplyPayload($supply->fresh('unitOfMeasure')),
        ], $supply->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * GET /api/v1/provider/supplies
     *
     * Listado de insumos del comercio, usado para armar la ficha tecnica.
     */
    public function indexSupplies(): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $supplies = Supply::where('provider_id', $provider->id)
            ->with('unitOfMeasure')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'supplies' => $supplies->map(fn (Supply $supply) => $this->supplyPayload($supply))->all(),
            'units_of_measure' => UnitOfMeasure::orderBy('name')->get()->map(fn (UnitOfMeasure $unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                'symbol' => $unit->symbol,
                'is_integer_only' => $unit->is_integer_only,
            ]),
        ]);
    }

    /**
     * POST /api/v1/provider/products/{product}/recipe
     *
     * Reemplaza la ficha tecnica del producto con la lista enviada. Cada
     * insumo debe pertenecer al mismo comercio que el producto.
     */
    public function storeRecipe(Request $request, Product $product): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($product->provider_id !== $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'El producto no pertenece a tu comercio.',
            ], 404);
        }

        $datos = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.supply_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity_required' => ['required', 'numeric', 'gt:0', 'max:99999.999'],
        ]);

        $insumos = Supply::where('provider_id', $provider->id)
            ->whereIn('id', array_column($datos['items'], 'supply_id'))
            ->pluck('id')
            ->all();

        $ajenos = array_diff(array_column($datos['items'], 'supply_id'), $insumos);

        if ($ajenos !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Uno o mas insumos no existen o no pertenecen a tu comercio.',
                'invalid_supply_ids' => array_values($ajenos),
            ], 422);
        }

        DB::transaction(function () use ($product, $datos) {
            $product->recipeItems()->delete();

            foreach ($datos['items'] as $item) {
                $product->recipeItems()->create([
                    'supply_id' => $item['supply_id'],
                    'quantity_required' => $item['quantity_required'],
                ]);
            }
        });

        $product->load('recipeItems.supply.unitOfMeasure');

        return response()->json([
            'success' => true,
            'message' => 'Ficha tecnica guardada.',
            'recipe' => $this->recipePayload($product),
            'production_cost' => $product->calculateCost(),
            'profit_margin' => $product->calculateProfitMargin(),
        ]);
    }

    /**
     * GET /api/v1/provider/products/{product}/recipe
     */
    public function showRecipe(Product $product): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($product->provider_id !== $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'El producto no pertenece a tu comercio.',
            ], 404);
        }

        $product->load('recipeItems.supply.unitOfMeasure');

        return response()->json([
            'success' => true,
            'recipe' => $this->recipePayload($product),
            'production_cost' => $product->calculateCost(),
            'profit_margin' => $product->calculateProfitMargin(),
        ]);
    }

    /**
     * GET /api/v1/provider/financial-health
     *
     * Precio de venta, costo total de produccion y margen bruto de cada
     * producto, con alerta cuando el margen cae bajo el umbral recomendado.
     */
    public function financialHealth(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $umbral = (float) config('financials.min_gross_margin_percent', 30);
        $porPagina = (int) $request->integer('per_page', (int) config('financials.per_page', 50));

        $productos = Product::where('provider_id', $provider->id)
            ->with('recipeItems.supply')
            ->orderBy('name')
            ->paginate(min(max($porPagina, 1), 200))
            ->withQueryString();

        $filas = $productos->getCollection()->map(function (Product $product) use ($umbral) {
            $costo = $product->calculateCost();
            $margen = $product->calculateProfitMargin();

            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'sale_price' => (float) $product->price,
                'production_cost' => $costo,
                'profit_margin' => $margen,
                'has_recipe' => $product->recipeItems->isNotEmpty(),
                'alerts' => $this->alerts($product, $costo, $margen, $umbral),
            ];
        });

        $conAlerta = $filas->filter(fn (array $fila) => $fila['alerts'] !== [])->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_products' => $productos->total(),
                'products_with_alert' => $conAlerta,
                'min_margin_percent' => $umbral,
            ],
            'pagination' => [
                'current_page' => $productos->currentPage(),
                'last_page' => $productos->lastPage(),
                'per_page' => $productos->perPage(),
                'total' => $productos->total(),
            ],
            'products' => $filas->all(),
        ]);
    }

    /**
     * Alertas visuales del producto para la interfaz.
     *
     * @return array<int, array<string, mixed>>
     */
    private function alerts(Product $product, float $costo, ?float $margen, float $umbral): array
    {
        $alertas = [];

        if ($margen === null) {
            $alertas[] = [
                'code' => 'precio_no_definido',
                'severity' => 'warning',
                'message' => 'El producto no tiene precio de venta, no se puede calcular el margen.',
            ];
        } elseif ($margen < $umbral) {
            $alertas[] = [
                'code' => 'margen_bajo',
                'severity' => 'warning',
                'message' => 'Margen del '.number_format($margen, 2, ',', '.').'% , por debajo del '
                    .number_format($umbral, 0).'% recomendado. Considera ajustar el precio de venta.',
            ];
        }

        if ($product->recipeItems->isEmpty()) {
            $alertas[] = [
                'code' => 'sin_ficha_tecnica',
                'severity' => 'info',
                'message' => 'El producto no tiene ficha tecnica, el costo de produccion es 0 y el margen no es real.',
            ];
        }

        return $alertas;
    }

    /**
     * @return array<string, mixed>
     */
    private function supplyPayload(Supply $supply): array
    {
        return [
            'id' => $supply->id,
            'name' => $supply->name,
            'unit_of_measure_id' => $supply->unit_of_measure_id,
            'unit_of_measure' => $supply->relationLoaded('unitOfMeasure') && $supply->unitOfMeasure ? [
                'id' => $supply->unitOfMeasure->id,
                'name' => $supply->unitOfMeasure->name,
                'symbol' => $supply->unitOfMeasure->symbol,
            ] : null,
            'cost_per_unit' => (float) $supply->cost_per_unit,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recipePayload(Product $product): array
    {
        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'items' => $product->recipeItems->map(fn ($item) => [
                'id' => $item->id,
                'supply_id' => $item->supply_id,
                'supply_name' => $item->supply->name,
                'unit_symbol' => $item->supply->unitOfMeasure?->symbol,
                'quantity_required' => (float) $item->quantity_required,
                'cost_per_unit' => (float) $item->supply->cost_per_unit,
                'line_cost' => $item->lineCost(),
            ])->all(),
        ];
    }

    /**
     * @return Provider|JsonResponse
     */
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
