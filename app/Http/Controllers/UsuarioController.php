<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsuarioController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $users = User::with(['status', 'typeUser', 'primaryAddress.country', 'primaryAddress.province', 'primaryAddress.region', 'provider'])->orderBy('name')->get();

        return response()->json($users);
    }

    public function show(User $user)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user->load([
            'status',
            'typeUser',
            'addresses.country',
            'addresses.province',
            'addresses.region',
            'primaryAddress.country',
            'primaryAddress.province',
            'primaryAddress.region',
            'provider.category',
            'pages.module',
        ]);

        return response()->json($user);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'user_status_id' => 'nullable|integer|exists:user_statuses,id',
            'type_user_id' => 'nullable|integer|exists:type_users,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_status_id' => $request->user_status_id,
            'type_user_id' => $request->type_user_id,
        ]);

        $user->load(['status', 'typeUser', 'primaryAddress.country', 'primaryAddress.province', 'primaryAddress.region', 'provider']);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'user_status_id' => 'nullable|integer|exists:user_statuses,id',
            'type_user_id' => 'nullable|integer|exists:type_users,id',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'user_status_id' => $request->user_status_id,
            'type_user_id' => $request->type_user_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->load(['status', 'typeUser', 'primaryAddress.country', 'primaryAddress.province', 'primaryAddress.region', 'provider']);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'user' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propia cuenta.',
            ]);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
