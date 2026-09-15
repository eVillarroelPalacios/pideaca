<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Module;

class ModuleController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $modules = Module::withCount('pages')->orderBy('description')->get();

        return response()->json($modules);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:modules,description',
        ]);

        $module = Module::create([
            'description' => $request->description,
        ]);

        $module->loadCount('pages');

        return response()->json([
            'success' => true,
            'message' => 'Módulo creado correctamente.',
            'module' => $module,
        ]);
    }

    public function update(Request $request, Module $module)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:modules,description,' . $module->id,
        ]);

        $module->update([
            'description' => $request->description,
        ]);

        $module->loadCount('pages');

        return response()->json([
            'success' => true,
            'message' => 'Módulo actualizado correctamente.',
            'module' => $module,
        ]);
    }

    public function destroy(Module $module)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($module->pages()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el módulo porque tiene páginas asociadas.',
            ]);
        }

        $module->delete();

        return response()->json([
            'success' => true,
            'message' => 'Módulo eliminado correctamente.',
        ]);
    }
}
