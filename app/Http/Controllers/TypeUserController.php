<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TypeUser;

class TypeUserController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $typeUsers = TypeUser::orderBy('description')->get();

        return response()->json($typeUsers);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:type_users,description',
        ]);

        $typeUser = TypeUser::create([
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de usuario creado correctamente.',
            'type_user' => $typeUser,
        ]);
    }

    public function update(Request $request, TypeUser $typeUser)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'description' => 'required|string|max:255|unique:type_users,description,' . $typeUser->id,
        ]);

        $typeUser->update([
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de usuario actualizado correctamente.',
            'type_user' => $typeUser,
        ]);
    }

    public function destroy(TypeUser $typeUser)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($typeUser->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el tipo de usuario porque tiene usuarios asociados.',
            ]);
        }

        $typeUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de usuario eliminado correctamente.',
        ]);
    }
}