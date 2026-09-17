<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Group;

class GroupController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $groups = Group::orderBy('description')->get();

        return response()->json($groups);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:groups,description',
            'icon' => 'nullable|string',
        ]);

        $group = Group::create([
            'description' => $request->description,
            'icon' => $request->icon,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grupo creado correctamente.',
            'group' => $group,
        ]);
    }

    public function update(Request $request, Group $group)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:groups,description,' . $group->id,
            'icon' => 'nullable|string',
        ]);

        $group->update([
            'description' => $request->description,
            'icon' => $request->icon,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grupo actualizado correctamente.',
            'group' => $group,
        ]);
    }

    public function destroy(Group $group)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($group->subgroups()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el grupo porque tiene sub grupos asociados.',
            ]);
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grupo eliminado correctamente.',
        ]);
    }
}