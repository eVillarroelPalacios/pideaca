<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Group;
use App\Models\GroupStatus;

class GroupController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $groups = Group::with('groupStatus')->orderBy('description')->get();

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
            'group_status_id' => 'nullable|integer|exists:group_statuses,id',
        ]);

        $group = Group::create([
            'description' => $request->description,
            'icon' => $request->icon,
            // Todo grupo nuevo nace activo salvo que se pida lo contrario.
            'group_status_id' => $request->input('group_status_id')
                ?: GroupStatus::where('description', GroupStatus::STATUS_ACTIVE)->value('id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grupo creado correctamente.',
            'group' => $group->load('groupStatus'),
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
            'group_status_id' => 'nullable|integer|exists:group_statuses,id',
        ]);

        $data = [
            'description' => $request->description,
            'icon' => $request->icon,
        ];

        // Si no viene el estado en el request se conserva el actual.
        if ($request->exists('group_status_id')) {
            $data['group_status_id'] = $request->input('group_status_id');
        }

        $group->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Grupo actualizado correctamente.',
            'group' => $group->load('groupStatus'),
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