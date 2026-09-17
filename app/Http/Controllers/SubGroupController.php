<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SubGroup;

class SubGroupController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $subgroups = SubGroup::with('group')->orderBy('description')->get();

        return response()->json($subgroups);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:sub_groups,description',
            'group_id' => 'required|exists:groups,id',
        ]);

        $subgroup = SubGroup::create([
            'description' => $request->description,
            'group_id' => $request->group_id,
        ]);

        $subgroup->load('group');

        return response()->json([
            'success' => true,
            'message' => 'Sub grupo creado correctamente.',
            'subgroup' => $subgroup,
        ]);
    }

    public function update(Request $request, SubGroup $subgroup)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:sub_groups,description,' . $subgroup->id,
            'group_id' => 'required|exists:groups,id',
        ]);

        $subgroup->update([
            'description' => $request->description,
            'group_id' => $request->group_id,
        ]);

        $subgroup->load('group');

        return response()->json([
            'success' => true,
            'message' => 'Sub grupo actualizado correctamente.',
            'subgroup' => $subgroup,
        ]);
    }

    public function destroy(SubGroup $subgroup)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $subgroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub grupo eliminado correctamente.',
        ]);
    }
}