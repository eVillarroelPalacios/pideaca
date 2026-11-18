<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AccountActivationMail;
use App\Models\AccountActivationRequest;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function types()
    {
        $types = TypeUser::whereIn('id', config('catalog.registrable_type_ids'))
            ->orderBy('description')
            ->get(['id', 'description']);

        return response()->json($types);
    }

    public function store(Request $request)
    {
        $availableTypes = TypeUser::whereIn('id', config('catalog.registrable_type_ids'))
            ->orderBy('description')
            ->get();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'type_user_id' => 'required|integer',
            'password' => 'required|string|min:4|max:255|confirmed',
        ], [
            'name.required' => 'Ingresá el nombre completo.',
            'email.required' => 'Ingresá el correo.',
            'email.email' => 'Ingresá un correo válido.',
            'type_user_id.required' => 'Seleccioná el tipo de cliente.',
            'password.required' => 'Ingresá la contraseña.',
            'password.min' => 'La contraseña debe tener al menos 4 caracteres.',
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

        $status = UserStatus::firstOrCreate(['status' => 'Inactivo']);

        $name = trim((string) $request->input('name'));

        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->type_user_id = $type->id;
        $user->user_status_id = $status?->id;
        $user->password = Hash::make($request->input('password'));
        // Cuenta desactivada: queda pendiente de activación por correo.
        $user->email_verified_at = null;

        // Las páginas del usuario quedan iguales a las asignadas a su tipo
        // en page_type_user (es lo que alimenta el menú del dashboard).
        $plain = Str::random(64);

        DB::transaction(function () use ($user, $type, $plain) {
            $user->save();
            $user->pages()->sync($type->assignedPages()->pluck('pages.id')->all());

            AccountActivationRequest::create([
                'user_id' => $user->id,
                'token' => hash('sha256', $plain),
                'expires_at' => now()->addHours(AccountActivationController::EXPIRATION_HOURS),
            ]);

            // Si el usuario registrado es Repartidor, crear wallet inicial
            if (strcasecmp($type->description, 'Repartidor') === 0) {
                \App\Models\DriverWallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0.00, 'pending_balance' => 0.00]
                );
            }
        });

        try {
            Mail::to($email)->send(new AccountActivationMail(
                $user->name,
                route('activation.activate', $plain)
            ));
        } catch (\Throwable $e) {
            report($e);

            // Sin correo no hay forma de activar: se elimina todo para que
            // pueda registrarse de nuevo.
            $user->delete();

            return response()->json([
                'success' => false,
                'message' => 'No pudimos enviar el correo de activación. Intentá de nuevo en unos minutos.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Te enviamos un correo para activar tu cuenta. Revisá tu bandeja de entrada.',
            'pages_count' => $user->pages()->count(),
        ]);
    }
}
