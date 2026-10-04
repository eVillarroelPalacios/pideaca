<?php

namespace App\Http\Controllers;

use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ABM de unidades de medida. Son globales para la plataforma (las usan todos
 * los comercios en productos, insumos y movimientos de stock), por eso viven
 * en la pagina administrativa "Unid. Medidas".
 */
class UnitOfMeasureController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $units = UnitOfMeasure::withCount(['products', 'supplies', 'inventoryMovements'])
            ->orderBy('name')
            ->get();

        return response()->json($units);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:unit_of_measures,name',
            'symbol' => 'required|string|max:20|unique:unit_of_measures,symbol',
            'base_conversion_factor' => 'required|numeric|min:0.0001|max:9999.9999',
            'is_integer_only' => 'sometimes|boolean',
        ]);

        $unit = UnitOfMeasure::create([
            'name' => $request->name,
            'symbol' => $request->symbol,
            'base_conversion_factor' => $request->base_conversion_factor,
            'is_integer_only' => (bool) $request->input('is_integer_only', true),
        ]);

        $unit->loadCount(['products', 'supplies', 'inventoryMovements']);

        return response()->json([
            'success' => true,
            'message' => 'Unidad de medida creada correctamente.',
            'unit' => $unit,
        ]);
    }

    public function update(Request $request, UnitOfMeasure $unitOfMeasure)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:unit_of_measures,name,' . $unitOfMeasure->id,
            'symbol' => 'required|string|max:20|unique:unit_of_measures,symbol,' . $unitOfMeasure->id,
            'base_conversion_factor' => 'required|numeric|min:0.0001|max:9999.9999',
            'is_integer_only' => 'sometimes|boolean',
        ]);

        $unitOfMeasure->update([
            'name' => $request->name,
            'symbol' => $request->symbol,
            'base_conversion_factor' => $request->base_conversion_factor,
            'is_integer_only' => (bool) $request->input('is_integer_only', true),
        ]);

        $unitOfMeasure->loadCount(['products', 'supplies', 'inventoryMovements']);

        return response()->json([
            'success' => true,
            'message' => 'Unidad de medida actualizada correctamente.',
            'unit' => $unitOfMeasure,
        ]);
    }

    public function destroy(UnitOfMeasure $unitOfMeasure)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($unitOfMeasure->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la unidad porque la usan productos.',
            ]);
        }

        if ($unitOfMeasure->supplies()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la unidad porque la usan insumos.',
            ]);
        }

        if ($unitOfMeasure->inventoryMovements()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la unidad porque la usan movimientos de stock.',
            ]);
        }

        $unitOfMeasure->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unidad de medida eliminada correctamente.',
        ]);
    }
}
