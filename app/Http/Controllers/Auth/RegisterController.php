<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /**
     * Tipos de cliente que se pueden elegir al registrarse.
     */
    private const REGISTRABLE_TYPES = ['Cliente', 'Prestador'];

    public function types()
    {
        $types = TypeUser::whereIn('description', self::REGISTRABLE_TYPES)
            ->orderBy('description')
            ->get(['id', 'description']);

        return response()->json($types);
    }

    public function store(Request $request)
    {
        $availableTypes = TypeUser::whereIn('description', self::REGISTRABLE_TYPES)
            ->orderBy('description')
            ->get();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'type_user_id' => 'required|integer',
            'password' => 'required|string|min:8|max:255|confirmed',
        ], [
            'name.required' => 'Ingresá el nombre completo.',
            'email.required' => 'Ingresá el correo.',
            'email.email' => 'Ingresá un correo válido.',
            'type_user_id.required' => 'Seleccioná el tipo de cliente.',
            'password.required' => 'Ingresá la contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $email = Str::lower(trim($request->input('email')));

        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ese correo ya está registrado. Intentá iniciar sesión.',
            ], 422);
        }

        $type = $availableTypes->firstWhere('id', (int) $request->input('type_user_id'));

        if (! $type) {
            return response()->json([
                'success' => false,
                'message' => 'Elegí un tipo de cliente válido.',
            ], 422);
        }

        $status = UserStatus::whereRaw('LOWER(status) = ?', ['activo'])->first();

        $name = trim((string) $request->input('name'));

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->type_user_id = $type->id;
        $user->user_status_id = $status?->id;
        $user->password = Hash::make($request->input('password'));
        // Cuenta activa: queda verificada de inmediato, sin validación por correo.
        $user->email_verified_at = now();

        // Las páginas del usuario quedan iguales a las asignadas a su tipo
        // en page_type_user (es lo que alimenta el menú del dashboard).
        DB::transaction(function () use ($user, $type) {
            $user->save();
            $user->pages()->sync($type->assignedPages()->pluck('pages.id')->all());
        });

        return response()->json([
            'success' => true,
            'message' => 'Cuenta creada correctamente. Ya podés iniciar sesión.',
            'pages_count' => $user->pages()->count(),
        ]);
    }
}
