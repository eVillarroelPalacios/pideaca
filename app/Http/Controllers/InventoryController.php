<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\UnitOfMeasure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public const MANUAL_MOVEMENT_TYPES = ['IN', 'OUT', 'ADJUSTMENT'];

    public const ALL_MOVEMENT_TYPES = ['IN', 'OUT', 'ADJUSTMENT', 'SALE', 'CANCELLED_SALE'];

    public function index(Request $request)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;

        $products = $provider->products()
            ->with(['unitOfMeasure', 'inventory'])
            ->orderBy('name')
            ->get();

        $items = $products->map(function (Product $product) {
            $inventory = $product->inventory;
            $current = $inventory ? (float) $inventory->current_stock : 0.0;
            $reserved = $inventory ? (float) $inventory->reserved_stock : 0.0;
            $min = $product->min_stock_alert !== null ? (float) $product->min_stock_alert : null;

            return [
                'product_id' => $product->id,
                'name' => $product->name,
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
                'low_stock' => $product->track_stock && $min !== null && $current < $min,
            ];
        });

        return response()->json([
            'success' => true,
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'has_inventory_control' => $provider->usesInventory(),
            ],
            'low_stock_count' => $items->filter(fn (array $item) => $item['low_stock'])->count(),
            'unit_of_measures' => UnitOfMeasure::orderBy('id')->get([
                'id', 'name', 'symbol', 'base_conversion_factor', 'is_integer_only',
            ]),
            'products' => $items->values(),
        ]);
    }

    public function settings(Request $request)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $data = $request->validate([
            'has_inventory_control' => ['required', 'boolean'],
        ]);

        $resolved->update(['has_inventory_control' => (bool) $data['has_inventory_control']]);

        return response()->json([
            'success' => true,
            'message' => 'Configuración de inventario actualizada.',
            'provider' => [
                'id' => $resolved->id,
                'business_name' => $resolved->business_name,
                'has_inventory_control' => $resolved->usesInventory(),
            ],
        ]);
    }

    public function adjust(Request $request)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'movement_type' => ['required', Rule::in(self::MANUAL_MOVEMENT_TYPES)],
            'quantity' => ['required', 'numeric', 'decimal:0,3', 'between:-1000000,1000000'],
            'unit_of_measure_id' => ['required', 'integer', 'exists:unit_of_measures,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::where('id', $data['product_id'])
            ->where('provider_id', $provider->id)
            ->first();

        if (! $product) {
            throw ValidationException::withMessages([
                'product_id' => 'El producto no pertenece a este comercio.',
            ]);
        }

        $unit = UnitOfMeasure::findOrFail($data['unit_of_measure_id']);
        $type = $data['movement_type'];
        $quantity = (float) $data['quantity'];

        if ($type !== 'ADJUSTMENT' && $quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad debe ser mayor que cero.',
            ]);
        }

        if ($type === 'ADJUSTMENT' && $quantity == 0) {
            throw ValidationException::withMessages([
                'quantity' => 'El ajuste no puede ser cero: indicá la diferencia (positiva suma, negativa resta).',
            ]);
        }

        if ($unit->is_integer_only && (int) $quantity != $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'La unidad "'.$unit->name.'" solo admite cantidades enteras.',
            ]);
        }

        $factor = (float) $unit->base_conversion_factor;
        $quantityInBase = round($quantity * $factor, 3);

        $delta = match ($type) {
            'IN' => abs($quantityInBase),
            'OUT' => -abs($quantityInBase),
            default => $quantityInBase,
        };

        $result = DB::transaction(function () use ($provider, $product, $unit, $type, $quantity, $factor, $quantityInBase, $delta, $data) {
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

            $newStock = round(((float) $inventory->current_stock) + $delta, 3);

            if ($newStock < 0 && ! $inventory->allow_negative_stock) {
                throw ValidationException::withMessages([
                    'quantity' => 'El movimiento dejaría el stock en '.$newStock.'. Activá el stock negativo o registrá una entrada.',
                ]);
            }

            $inventory->update(['current_stock' => $newStock]);

            $movement = InventoryMovement::create([
                'product_id' => $product->id,
                'provider_id' => $provider->id,
                'order_id' => null,
                'movement_type' => $type,
                'quantity' => round($quantity, 3),
                'unit_of_measure_id' => $unit->id,
                'unit_conversion_factor' => round($factor, 4),
                'quantity_in_base_unit' => $quantityInBase,
                'notes' => $data['notes'] ?? null,
            ]);

            return ['inventory' => $inventory, 'movement' => $movement];
        });

        return response()->json([
            'success' => true,
            'message' => 'Movimiento registrado.',
            'movement' => $result['movement']->load(['product:id,name', 'unitOfMeasure:id,name,symbol']),
            'current_stock' => (float) $result['inventory']->current_stock,
            'delta' => $delta,
        ], 201);
    }

    public function movements(Request $request)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $provider = $resolved;

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = InventoryMovement::where('provider_id', $provider->id)
            ->with(['product:id,name', 'unitOfMeasure:id,name,symbol'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($data['from'])) {
            $query->where('created_at', '>=', $request->date('from')->startOfDay());
        }

        if (! empty($data['to'])) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        if (! empty($data['product_id'])) {
            $query->where('product_id', (int) $data['product_id']);
        }

        $movements = $query->paginate((int) ($data['per_page'] ?? 15))->withQueryString();

        return response()->json([
            'success' => true,
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
            ],
            'filters' => [
                'from' => $data['from'] ?? null,
                'to' => $data['to'] ?? null,
                'product_id' => isset($data['product_id']) ? (int) $data['product_id'] : null,
            ],
            'movement_types' => self::ALL_MOVEMENT_TYPES,
            'movements' => $movements,
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
