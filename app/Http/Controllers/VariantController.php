<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Variantes del producto (tamaños o formatos como Individual / Grande /
 * Familiar). El precio que paga el cliente es el de la variante: si no hay
 * variante se usa el precio base del producto.
 */
class VariantController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $product = $this->productoPropio($product, $resolved->id);

        if ($product instanceof JsonResponse) {
            return $product;
        }

        $data = $this->validated($request);

        $variant = $product->variants()->create([
            'name' => $data['name'],
            'price' => $data['price'],
            'is_available' => $data['is_available'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Variante creada.',
            'variant' => $this->payload($variant),
        ], 201);
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $variant = ProductVariant::where('id', $variant->id)
            ->whereHas('product', fn ($q) => $q->where('provider_id', $resolved->id))
            ->first();

        if (! $variant) {
            return $this->ajena();
        }

        $data = $this->validated($request, false);

        // Un null explícito no tiene dónde guardarse (columnas NOT NULL):
        // se ignora y queda el valor actual.
        $variant->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json([
            'success' => true,
            'message' => 'Variante actualizada.',
            'variant' => $this->payload($variant->fresh()),
        ]);
    }

    public function destroy(ProductVariant $variant)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $variant = ProductVariant::where('id', $variant->id)
            ->whereHas('product', fn ($q) => $q->where('provider_id', $resolved->id))
            ->first();

        if (! $variant) {
            return $this->ajena();
        }

        $variant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Variante eliminada.',
        ]);
    }

    private function validated(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'min:1', 'max:60'],
            'price' => [$creating ? 'required' : 'sometimes', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
            'is_available' => ['nullable', 'boolean'],
        ]);
    }

    private function productoPropio(Product $product, int $providerId): Product|JsonResponse
    {
        $propio = Product::where('id', $product->id)
            ->where('provider_id', $providerId)
            ->first();

        if (! $propio) {
            return $this->ajena();
        }

        return $propio;
    }

    private function ajena(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'La variante no pertenece a este comercio.',
        ], 403);
    }

    private function payload(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'name' => $variant->name,
            'price' => (float) $variant->price,
            'is_available' => (bool) $variant->is_available,
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
