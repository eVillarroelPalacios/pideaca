<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Grupos de opciones del producto ("Agregados", "Extras") y sus opciones
 * ("Muzzarella extra +$900"). min_choices / max_choices son las reglas que
 * valida el servidor al armar el pedido.
 */
class OptionController extends Controller
{
    // --- Grupos ---

    public function storeGroup(Request $request, Product $product)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $product = $this->productoPropio($product, $resolved->id);

        if ($product instanceof JsonResponse) {
            return $product;
        }

        $data = $this->validatedGroup($request);
        $this->assertReglas($data);

        $group = $product->optionGroups()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Grupo creado.',
            'group' => $this->groupPayload($group),
        ], 201);
    }

    public function updateGroup(Request $request, ProductOptionGroup $group)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $group = $this->grupoPropio($group, $resolved->id);

        if ($group instanceof JsonResponse) {
            return $group;
        }

        $data = $this->validatedGroup($request, false);
        $data = $this->mergeReglas($group, $data);
        $this->assertReglas($data);

        $group->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json([
            'success' => true,
            'message' => 'Grupo actualizado.',
            'group' => $this->groupPayload($group->fresh()->load('options')),
        ]);
    }

    public function destroyGroup(ProductOptionGroup $group)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $group = $this->grupoPropio($group, $resolved->id);

        if ($group instanceof JsonResponse) {
            return $group;
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grupo eliminado.',
        ]);
    }

    // --- Opciones ---

    public function storeOption(Request $request, ProductOptionGroup $group)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $group = $this->grupoPropio($group, $resolved->id);

        if ($group instanceof JsonResponse) {
            return $group;
        }

        $data = $this->validatedOption($request);

        $option = $group->options()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Opción creada.',
            'option' => $this->optionPayload($option),
        ], 201);
    }

    public function updateOption(Request $request, ProductOption $option)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $option = ProductOption::where('id', $option->id)
            ->whereHas('optionGroup.product', fn ($q) => $q->where('provider_id', $resolved->id))
            ->first();

        if (! $option) {
            return $this->ajena('La opción no pertenece a este comercio.');
        }

        $data = $this->validatedOption($request, false);
        $option->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json([
            'success' => true,
            'message' => 'Opción actualizada.',
            'option' => $this->optionPayload($option->fresh()),
        ]);
    }

    public function destroyOption(ProductOption $option)
    {
        $resolved = $this->currentProvider();

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $option = ProductOption::where('id', $option->id)
            ->whereHas('optionGroup.product', fn ($q) => $q->where('provider_id', $resolved->id))
            ->first();

        if (! $option) {
            return $this->ajena('La opción no pertenece a este comercio.');
        }

        $option->delete();

        return response()->json([
            'success' => true,
            'message' => 'Opción eliminada.',
        ]);
    }

    // --- Validaciones ---

    private function validatedGroup(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'min:1', 'max:60'],
            'min_choices' => ['nullable', 'integer', 'min:0', 'max:20'],
            'max_choices' => ['nullable', 'integer', 'min:1', 'max:20'],
            'is_required' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * min_choices / max_choices son obligatorios: con "sometimes" no se podria
     * validar la regla entre ambos si viene solo uno.
     */
    private function mergeReglas(ProductOptionGroup $group, array $data): array
    {
        $data['min_choices'] = array_key_exists('min_choices', $data) && $data['min_choices'] !== null
            ? (int) $data['min_choices']
            : (int) $group->min_choices;
        $data['max_choices'] = array_key_exists('max_choices', $data) && $data['max_choices'] !== null
            ? (int) $data['max_choices']
            : (int) $group->max_choices;

        return $data;
    }

    private function assertReglas(array $data): void
    {
        $min = $data['min_choices'] ?? 0;
        $max = $data['max_choices'] ?? 1;

        if ($min > $max) {
            throw ValidationException::withMessages([
                'max_choices' => 'El máximo de elecciones no puede ser menor que el mínimo.',
            ]);
        }
    }

    private function validatedOption(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'min:1', 'max:60'],
            'extra_price' => [$creating ? 'required' : 'sometimes', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
            'is_available' => ['nullable', 'boolean'],
        ]);
    }

    // --- Acceso ---

    private function productoPropio(Product $product, int $providerId): Product|JsonResponse
    {
        $propio = Product::where('id', $product->id)
            ->where('provider_id', $providerId)
            ->first();

        if (! $propio) {
            return $this->ajena('El producto no pertenece a este comercio.');
        }

        return $propio;
    }

    private function grupoPropio(ProductOptionGroup $group, int $providerId): ProductOptionGroup|JsonResponse
    {
        $propio = ProductOptionGroup::where('id', $group->id)
            ->whereHas('product', fn ($q) => $q->where('provider_id', $providerId))
            ->first();

        if (! $propio) {
            return $this->ajena('El grupo no pertenece a este comercio.');
        }

        return $propio;
    }

    private function ajena(string $mensaje): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $mensaje,
        ], 403);
    }

    private function groupPayload(ProductOptionGroup $group): array
    {
        return [
            'id' => $group->id,
            'product_id' => $group->product_id,
            'name' => $group->name,
            'min_choices' => (int) $group->min_choices,
            'max_choices' => (int) $group->max_choices,
            'is_required' => (bool) $group->is_required,
            'options' => $group->options
                ->sortBy('id')
                ->map(fn ($o) => $this->optionPayload($o))
                ->values(),
        ];
    }

    private function optionPayload(ProductOption $option): array
    {
        return [
            'id' => $option->id,
            'option_group_id' => $option->option_group_id,
            'name' => $option->name,
            'extra_price' => (float) $option->extra_price,
            'is_available' => (bool) $option->is_available,
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
