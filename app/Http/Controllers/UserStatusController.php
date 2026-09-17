<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserStatus;

class UserStatusController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $statuses = UserStatus::withCount('users')->orderBy('status')->get();

        return response()->json($statuses);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'status' => 'required|string|max:255|unique:user_statuses,status',
        ]);

        $userStatus = UserStatus::create([
            'status' => $request->status,
        ]);

        $userStatus->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Estado de usuario creado correctamente.',
            'user_status' => $userStatus,
        ]);
    }

    public function update(Request $request, UserStatus $userStatus)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'status' => 'required|string|max:255|unique:user_statuses,status,' . $userStatus->id,
        ]);

        $userStatus->update([
            'status' => $request->status,
        ]);

        $userStatus->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Estado de usuario actualizado correctamente.',
            'user_status' => $userStatus,
        ]);
    }

    public function destroy(UserStatus $userStatus)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($userStatus->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el estado porque tiene usuarios asociados.',
            ]);
        }

        $userStatus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Estado de usuario eliminado correctamente.',
        ]);
    }
}
