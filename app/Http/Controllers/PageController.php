<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Page;
use App\Models\Module;

class PageController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $pages = Page::with('module')->orderBy('description')->get();

        return response()->json($pages);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:pages,description',
            'url' => 'nullable|string|max:255',
            'module_id' => 'required|integer|exists:modules,id',
        ]);

        $page = Page::create([
            'description' => $request->description,
            'url' => $request->url,
            'module_id' => $request->module_id,
        ]);

        $page->load('module');

        return response()->json([
            'success' => true,
            'message' => 'Página creada correctamente.',
            'page' => $page,
        ]);
    }

    public function update(Request $request, Page $page)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:pages,description,' . $page->id,
            'url' => 'nullable|string|max:255',
            'module_id' => 'required|integer|exists:modules,id',
        ]);

        $page->update([
            'description' => $request->description,
            'url' => $request->url,
            'module_id' => $request->module_id,
        ]);

        $page->load('module');

        return response()->json([
            'success' => true,
            'message' => 'Página actualizada correctamente.',
            'page' => $page,
        ]);
    }

    public function destroy(Page $page)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($page->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la página porque tiene usuarios asignados.',
            ]);
        }

        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Página eliminada correctamente.',
        ]);
    }
}