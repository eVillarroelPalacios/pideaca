<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UnitOfMeasure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Unidades de medida para la app movil.
 *
 * Espeja la seccion "unidades-medida" del panel web sin tocarla: mismo
 * modelo (UnitOfMeasure), mismas reglas (nombre y simbolo unicos, factor
 * de equivalencia 0.0001-9999.9999) y mismo permiso (pagina
 * unidades-medida). No se puede eliminar una unidad que todavia usen
 * productos, insumos o movimientos de stock.
 */
class UnitsOfMeasureController extends Controller
{
    /**
     * Catalogo completo con la cantidad de usos en productos, insumos y
     * movimientos de stock.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $units = UnitOfMeasure::withCount(['products', 'supplies', 'inventoryMovements'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $units
                ->map(fn (UnitOfMeasure $unit) => $this->payload($unit))
                ->values()
                ->all(),
            'summary' => [
                'total' => $units->count(),
                'in_use' => $units->filter(fn (UnitOfMeasure $unit) => $this->usages($unit) > 0)->count(),
            ],
        ]);
    }

    /**
     * Alta de una unidad de medida.
     */
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request);

        $unit = UnitOfMeasure::create([
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'base_conversion_factor' => $data['base_conversion_factor'],
            'is_integer_only' => (bool) $request->input('is_integer_only', true),
        ]);

        $unit->loadCount(['products', 'supplies', 'inventoryMovements']);

        return response()->json([
            'success' => true,
            'message' => 'Unidad de medida creada correctamente.',
            'unit' => $this->payload($unit),
        ], 201);
    }

    /**
     * Edicion de una unidad de medida.
     */
    public function update(Request $request, UnitOfMeasure $unitOfMeasure): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request, $unitOfMeasure);

        $unitOfMeasure->update([
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'base_conversion_factor' => $data['base_conversion_factor'],
            'is_integer_only' => (bool) $request->input('is_integer_only', true),
        ]);

        $unitOfMeasure->loadCount(['products', 'supplies', 'inventoryMovements']);

        return response()->json([
            'success' => true,
            'message' => 'Unidad de medida actualizada correctamente.',
            'unit' => $this->payload($unitOfMeasure),
        ]);
    }

    /**
     * Baja de una unidad de medida. Se bloquea si todavia la usan
     * productos, insumos o movimientos de stock, igual que en la web.
     */
    public function destroy(Request $request, UnitOfMeasure $unitOfMeasure): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        if ($unitOfMeasure->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la unidad porque la usan productos.',
                'code' => 'UNIT_IN_USE',
            ], 409);
        }

        if ($unitOfMeasure->supplies()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la unidad porque la usan insumos.',
                'code' => 'UNIT_IN_USE',
            ], 409);
        }

        if ($unitOfMeasure->inventoryMovements()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la unidad porque la usan movimientos de stock.',
                'code' => 'UNIT_IN_USE',
            ], 409);
        }

        $unitOfMeasure->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unidad de medida eliminada correctamente.',
        ]);
    }

    /**
     * Reglas identicas al controlador web mas los mensajes en espanol.
     */
    private function validated(Request $request, ?UnitOfMeasure $unit = null): array
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'symbol' => trim((string) $request->input('symbol')),
        ]);

        $uniqueName = Rule::unique('unit_of_measures', 'name');
        $uniqueSymbol = Rule::unique('unit_of_measures', 'symbol');

        if ($unit) {
            $uniqueName = $uniqueName->ignore($unit->id);
            $uniqueSymbol = $uniqueSymbol->ignore($unit->id);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255', $uniqueName],
            'symbol' => ['required', 'string', 'max:20', $uniqueSymbol],
            'base_conversion_factor' => ['required', 'numeric', 'min:0.0001', 'max:9999.9999'],
            'is_integer_only' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Ingresá el nombre de la unidad.',
            'name.unique' => 'Ya existe una unidad con ese nombre.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'symbol.required' => 'Ingresá el símbolo.',
            'symbol.unique' => 'Ya existe una unidad con ese símbolo.',
            'symbol.max' => 'El símbolo no puede superar los 20 caracteres.',
            'base_conversion_factor.required' => 'Ingresá la equivalencia en unidades.',
            'base_conversion_factor.numeric' => 'La equivalencia debe ser un número.',
            'base_conversion_factor.min' => 'La equivalencia debe ser mayor a cero.',
            'base_conversion_factor.max' => 'La equivalencia no puede superar 9999.9999.',
        ]);
    }

    /**
     * Permiso identico al guard web `page:unidades-medida`.
     */
    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        if ($request->user()?->hasPageUrl('unidades-medida')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
        ], 403);
    }

    private function usages(UnitOfMeasure $unit): int
    {
        return (int) ($unit->products_count ?? 0)
            + (int) ($unit->supplies_count ?? 0)
            + (int) ($unit->inventory_movements_count ?? 0);
    }

    /**
     * Forma publica de una unidad, con los datos que usa la tabla web.
     */
    private function payload(UnitOfMeasure $unit): array
    {
        $productsCount = (int) ($unit->products_count ?? 0);
        $suppliesCount = (int) ($unit->supplies_count ?? 0);
        $movementsCount = (int) ($unit->inventory_movements_count ?? 0);

        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'symbol' => $unit->symbol,
            'base_conversion_factor' => (float) $unit->base_conversion_factor,
            'is_integer_only' => (bool) $unit->is_integer_only,
            'products_count' => $productsCount,
            'supplies_count' => $suppliesCount,
            'inventory_movements_count' => $movementsCount,
            'can_delete' => $productsCount === 0 && $suppliesCount === 0 && $movementsCount === 0,
        ];
    }
}
