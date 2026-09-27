<?php

namespace App\Http\Controllers;

use App\Models\GroupStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EstadosDeLosGruposController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $statuses = GroupStatus::withCount('groups')->orderBy('id')->get();

        return response()->json($statuses);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:group_statuses,description',
        ]);

        $groupStatus = GroupStatus::create([
            'description' => $request->description,
        ]);

        $groupStatus->loadCount('groups');

        return response()->json([
            'success' => true,
            'message' => 'Estado de grupo creado correctamente.',
            'group_status' => $groupStatus,
        ]);
    }

    public function update(Request $request, GroupStatus $groupStatus)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:group_statuses,description,' . $groupStatus->id,
        ]);

        $groupStatus->update([
            'description' => $request->description,
        ]);

        $groupStatus->loadCount('groups');

        return response()->json([
            'success' => true,
            'message' => 'Estado de grupo actualizado correctamente.',
            'group_status' => $groupStatus,
        ]);
    }

    public function destroy(GroupStatus $groupStatus)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($groupStatus->groups()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el estado porque tiene grupos asociados.',
            ]);
        }

        $groupStatus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Estado de grupo eliminado correctamente.',
        ]);
    }
}
