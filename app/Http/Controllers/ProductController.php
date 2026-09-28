<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\UnitOfMeasure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alta y edicion de los productos del comercio. El inventario solo se puede
 * alimentar si el comercio puede cargar sus productos, asi que aca vive el
 * "controlar stock" con su stock inicial, minimo y unidad de medida.
 */
class ProductController extends Controller
{
    public function index(Request $request)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;

        $categories = Category::where('provider_id', $provider->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $products = $provider->products()
            ->with(['category:id,name', 'unitOfMeasure', 'inventory'])
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => $this->payload($product))
            ->values();

        return response()->json([
            'success' => true,
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
            ],
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'is_active' => (bool) $category->is_active,
            ])->values(),
            'unit_of_measures' => UnitOfMeasure::orderBy('id')->get([
                'id', 'name', 'symbol', 'base_conversion_factor', 'is_integer_only',
            ]),
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;
        $data = $this->validated($request, true);

        $this->assertCategory($provider->id, $data['category_id']);
        $this->assertStockRules($data);

        $product = DB::transaction(function () use ($provider, $data) {
            $product = Product::create([
                'provider_id' => $provider->id,
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'is_available' => $data['is_available'] ?? true,
                'sort_order' => (int) $provider->products()->max('sort_order') + 1,
                'track_stock' => (bool) ($data['track_stock'] ?? false),
                'min_stock_alert' => $data['min_stock_alert'] ?? null,
                'unit_of_measure_id' => $data['unit_of_measure_id'] ?? null,
            ]);

            $this->applyStock($provider, $product, $data, true);

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Producto creado correctamente.',
            'product' => $this->payload($product->fresh()->load(['category:id,name', 'unitOfMeasure', 'inventory'])),
        ], 201);
    }

    public function update(Request $request, Product $product)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;

        $product = Product::where('id', $product->id)
            ->where('provider_id', $provider->id)
            ->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'El producto no pertenece a este comercio.',
            ], 403);
        }

        $data = $this->validated($request, false);
        $data['category_id'] = $data['category_id'] ?? $product->category_id;

        $this->assertCategory($provider->id, $data['category_id']);
        $this->assertStockRules($data);

        DB::transaction(function () use ($product, $provider, $data) {
            $product->update([
                'category_id' => $data['category_id'],
                'name' => $data['name'] ?? $product->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $product->description,
                'price' => $data['price'] ?? $product->price,
                'is_available' => $data['is_available'] ?? $product->is_available,
                'track_stock' => (bool) ($data['track_stock'] ?? $product->track_stock),
                'min_stock_alert' => array_key_exists('min_stock_alert', $data)
                    ? $data['min_stock_alert']
                    : $product->min_stock_alert,
                'unit_of_measure_id' => $data['unit_of_measure_id'] ?? $product->unit_of_measure_id,
            ]);

            $this->applyStock($provider, $product, $data, false);
        });

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
            'product' => $this->payload($product->fresh()->load(['category:id,name', 'unitOfMeasure', 'inventory'])),
        ]);
    }

    private function validated(Request $request, bool $creating): array
    {
        $rules = [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => [$creating ? 'required' : 'sometimes', 'integer', 'exists:categories,id'],
            'price' => [$creating ? 'required' : 'sometimes', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
            'is_available' => ['nullable', 'boolean'],
            'track_stock' => ['nullable', 'boolean'],
            'min_stock_alert' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:1000000'],
            'unit_of_measure_id' => ['nullable', 'integer', 'exists:unit_of_measures,id'],
            'initial_stock' => ['nullable', 'numeric', 'decimal:0,3', 'between:-1000000,1000000'],
            'allow_negative_stock' => ['nullable', 'boolean'],
        ];

        return $request->validate($rules);
    }

    private function assertCategory(int $providerId, int $categoryId): void
    {
        $exists = Category::where('provider_id', $providerId)->where('id', $categoryId)->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'category_id' => 'La categoría no pertenece a este comercio.',
            ]);
        }
    }

    /**
     * Si el producto lleva control de stock necesita minimo y unidad, y la
     * carga inicial no puede ser negativa con el stock negativo desactivado.
     */
    private function assertStockRules(array $data): void
    {
        $errores = [];

        if (($data['track_stock'] ?? false) && empty($data['unit_of_measure_id'])) {
            $errores['unit_of_measure_id'] = 'Elegí la unidad de medida para controlar el stock.';
        }

        if (($data['track_stock'] ?? false) && ($data['min_stock_alert'] ?? null) === null) {
            $errores['min_stock_alert'] = 'Definí el stock mínimo para poder avisar cuando falte.';
        }

        $inicial = $data['initial_stock'] ?? null;

        if ($inicial !== null && $inicial < 0 && ! ($data['allow_negative_stock'] ?? false)) {
            $errores['initial_stock'] = 'La carga inicial no puede ser negativa.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    private function applyStock($provider, Product $product, array $data, bool $creating): void
    {
        if (! ($data['track_stock'] ?? false)) {
            return;
        }

        $unit = $data['unit_of_measure_id']
            ? UnitOfMeasure::find($data['unit_of_measure_id'])
            : UnitOfMeasure::orderBy('id')->first();

        if (! $unit) {
            throw ValidationException::withMessages([
                'unit_of_measure_id' => 'Indicá la unidad de medida del producto.',
            ]);
        }

        $inicial = $data['initial_stock'] ?? null;

        if ($inicial !== null && $unit->is_integer_only && (int) $inicial != $inicial) {
            throw ValidationException::withMessages([
                'initial_stock' => 'La unidad "'.$unit->name.'" solo admite cantidades enteras.',
            ]);
        }

        $inventory = ProductInventory::where('product_id', $product->id)
            ->where('provider_id', $provider->id)
            ->first();

        $existia = $inventory !== null;

        if (! $inventory) {
            $inventory = ProductInventory::create([
                'product_id' => $product->id,
                'provider_id' => $provider->id,
                'current_stock' => 0,
                'reserved_stock' => 0,
                'allow_negative_stock' => (bool) ($data['allow_negative_stock'] ?? false),
            ]);
        } elseif (array_key_exists('allow_negative_stock', $data) && $data['allow_negative_stock'] !== null) {
            $inventory->allow_negative_stock = (bool) $data['allow_negative_stock'];
            $inventory->save();
        }

        // En la edicion la cantidad se maneja desde Inventario: aca la carga es
        // siempre adicional a lo que el producto ya tenga.
        $cantidad = round((float) ($inicial ?? 0), 3);

        if ($cantidad == 0) {
            return;
        }

        $inventory->update([
            'current_stock' => round((float) $inventory->current_stock + $cantidad, 3),
        ]);

        InventoryMovement::create([
            'product_id' => $product->id,
            'provider_id' => $provider->id,
            'order_id' => null,
            'movement_type' => $existia ? 'ADJUSTMENT' : 'IN',
            'quantity' => $cantidad,
            'unit_of_measure_id' => $unit->id,
            'unit_conversion_factor' => (float) $unit->base_conversion_factor,
            'quantity_in_base_unit' => round($cantidad * (float) $unit->base_conversion_factor, 3),
            'notes' => 'Carga inicial del producto',
        ]);
    }

    private function payload(Product $product): array
    {
        $inventory = $product->inventory;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'is_available' => (bool) $product->is_available,
            'category_id' => $product->category_id,
            'category_name' => $product->category ? $product->category->name : null,
            'track_stock' => (bool) $product->track_stock,
            'min_stock_alert' => $product->min_stock_alert !== null ? (float) $product->min_stock_alert : null,
            'unit_of_measure_id' => $product->unit_of_measure_id,
            'unit_of_measure' => $product->unitOfMeasure ? [
                'id' => $product->unitOfMeasure->id,
                'name' => $product->unitOfMeasure->name,
                'symbol' => $product->unitOfMeasure->symbol,
                'is_integer_only' => (bool) $product->unitOfMeasure->is_integer_only,
            ] : null,
            'current_stock' => $inventory ? round((float) $inventory->current_stock, 3) : 0.0,
            'allow_negative_stock' => $inventory ? (bool) $inventory->allow_negative_stock : false,
        ];
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
