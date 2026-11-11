<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\UnitOfMeasure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Inventario del comercio para la app movil.
 *
 * Espeja el panel web `inventario.blade.php` / InventoryController: mismo
 * payload del listado, mismo switch global de control y misma edicion de stock
 * por producto (incluido el movimiento de ajuste cuando cambia la cantidad).
 * La unica diferencia es el guard: Sanctum en vez de la sesion web.
 */
class ProviderInventoryController extends Controller
{
    use ResolvesProvider;

    /**
     * GET /api/mobile/v1/provider/inventory
     *
     * Productos con su stock, categorias, unidades y cuantos estan bajo el
     * minimo. Es el mismo shape que consume la tabla del panel web.
     */
    public function index(Request $request): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $products = $provider->products()
            ->with(['unitOfMeasure', 'inventory', 'category:id,name'])
            ->orderBy('name')
            ->get();

        $items = $products->map(fn (Product $product) => $this->itemPayload($product));

        $categories = Category::where('provider_id', $provider->id)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'has_inventory_control' => $provider->usesInventory(),
            ],
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'sort_order' => $category->sort_order,
                'is_active' => (bool) $category->is_active,
                'products_count' => (int) $category->products_count,
            ])->values(),
            'low_stock_count' => $items->filter(fn (array $item) => $item['low_stock'])->count(),
            'unit_of_measures' => UnitOfMeasure::orderBy('id')->get([
                'id', 'name', 'symbol', 'base_conversion_factor', 'is_integer_only',
            ]),
            'products' => $items->values(),
        ]);
    }

    /**
     * PUT /api/mobile/v1/provider/inventory/settings
     *
     * Interruptor global "Control de inventario".
     */
    public function settings(Request $request): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $data = $request->validate([
            'has_inventory_control' => ['required', 'boolean'],
        ]);

        $provider->update(['has_inventory_control' => (bool) $data['has_inventory_control']]);

        return response()->json([
            'success' => true,
            'message' => 'Configuración de inventario actualizada.',
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'has_inventory_control' => $provider->usesInventory(),
            ],
        ]);
    }

    /**
     * PUT /api/mobile/v1/provider/inventory/products/{product}
     *
     * Edicion de stock de un producto: cantidad actual, minimo, unidad y si
     * permite stock negativo. Cuando cambia la cantidad se asienta un ajuste,
     * igual que en el panel web, para que el historial siga cuadrando.
     */
    public function updateProduct(Request $request, Product $product): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $product = Product::where('id', $product->id)
            ->where('provider_id', $provider->id)
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'El producto no pertenece a este comercio.',
            ], 403);
        }

        $data = $request->validate([
            'track_stock' => ['required', 'boolean'],
            'current_stock' => ['nullable', 'numeric', 'decimal:0,3', 'between:-1000000,1000000'],
            'min_stock_alert' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:1000000'],
            'unit_of_measure_id' => ['nullable', 'integer', 'exists:unit_of_measures,id'],
            'allow_negative_stock' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $unit = ($data['unit_of_measure_id'] ?? null)
            ? UnitOfMeasure::findOrFail($data['unit_of_measure_id'])
            : ($product->unitOfMeasure ?: UnitOfMeasure::orderBy('id')->first());

        $nuevoStock = $data['current_stock'] ?? null;

        if ($nuevoStock !== null && ! $unit) {
            throw ValidationException::withMessages([
                'unit_of_measure_id' => 'Indicá la unidad de medida para asentar el movimiento de stock.',
            ]);
        }

        if ($nuevoStock !== null && $unit && $unit->is_integer_only && (int) $nuevoStock != $nuevoStock) {
            throw ValidationException::withMessages([
                'current_stock' => 'La unidad "'.$unit->name.'" solo admite cantidades enteras.',
            ]);
        }

        $trackStock = (bool) $data['track_stock'];

        $resultado = DB::transaction(function () use ($product, $provider, $data, $nuevoStock, $unit, $trackStock) {
            $inventory = ProductInventory::where('product_id', $product->id)
                ->where('provider_id', $provider->id)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                $inventory = ProductInventory::create([
                    'product_id' => $product->id,
                    'provider_id' => $provider->id,
                    'current_stock' => 0,
                    'reserved_stock' => 0,
                    'allow_negative_stock' => false,
                ]);
            }

            if (array_key_exists('allow_negative_stock', $data) && $data['allow_negative_stock'] !== null) {
                $inventory->allow_negative_stock = (bool) $data['allow_negative_stock'];
            }

            $stockAnterior = round((float) $inventory->current_stock, 3);
            $stockNuevo = $nuevoStock !== null ? round((float) $nuevoStock, 3) : $stockAnterior;

            if ($stockNuevo < 0 && ! $inventory->allow_negative_stock) {
                throw ValidationException::withMessages([
                    'current_stock' => 'No se puede dejar el stock en '.$stockNuevo.' con el stock negativo desactivado.',
                ]);
            }

            $delta = round($stockNuevo - $stockAnterior, 3);

            if ($delta != 0) {
                $inventory->current_stock = $stockNuevo;
            }

            $inventory->save();

            $product->update([
                'track_stock' => $trackStock,
                'min_stock_alert' => $data['min_stock_alert'] ?? null,
                'unit_of_measure_id' => $data['unit_of_measure_id'] ?? $product->unit_of_measure_id,
            ]);

            if ($delta != 0) {
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'provider_id' => $provider->id,
                    'order_id' => null,
                    'movement_type' => 'ADJUSTMENT',
                    'quantity' => $delta,
                    'unit_of_measure_id' => $unit ? $unit->id : null,
                    'unit_conversion_factor' => $unit ? (float) $unit->base_conversion_factor : 1,
                    'quantity_in_base_unit' => $delta,
                    'notes' => $data['notes'] ?? 'Carga manual desde inventario',
                ]);
            }

            return $inventory;
        });

        $product->refresh()->load(['unitOfMeasure', 'inventory']);

        return response()->json([
            'success' => true,
            'message' => 'Stock actualizado.',
            'product' => $this->itemPayload($product),
            'current_stock' => round((float) $resultado->current_stock, 3),
            'low_stock' => $trackStock
                && $product->min_stock_alert !== null
                && (float) $resultado->current_stock < (float) $product->min_stock_alert,
        ]);
    }

    /**
     * Fila del listado de inventario. Compartida entre listado y edicion.
     */
    private function itemPayload(Product $product): array
    {
        $inventory = $product->inventory;
        $current = $inventory ? (float) $inventory->current_stock : 0.0;
        $reserved = $inventory ? (float) $inventory->reserved_stock : 0.0;
        $min = $product->min_stock_alert !== null ? (float) $product->min_stock_alert : null;

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'category_name' => $product->category ? $product->category->name : null,
            'is_available' => (bool) $product->is_available,
            'track_stock' => (bool) $product->track_stock,
            'min_stock_alert' => $min,
            'unit_of_measure' => $product->unitOfMeasure ? [
                'id' => $product->unitOfMeasure->id,
                'name' => $product->unitOfMeasure->name,
                'symbol' => $product->unitOfMeasure->symbol,
                'base_conversion_factor' => (float) $product->unitOfMeasure->base_conversion_factor,
                'is_integer_only' => (bool) $product->unitOfMeasure->is_integer_only,
            ] : null,
            'current_stock' => round($current, 3),
            'reserved_stock' => round($reserved, 3),
            'available_stock' => round($current - $reserved, 3),
            'allow_negative_stock' => $inventory ? (bool) $inventory->allow_negative_stock : false,
            'low_stock' => $product->track_stock && $min !== null && $current < $min,
        ];
    }
}
