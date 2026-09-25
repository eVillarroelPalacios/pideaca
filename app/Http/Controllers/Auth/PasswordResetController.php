<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Vigencia del enlace enviado por correo.
     */
    public const EXPIRATION_MINUTES = 60;

    /**
     * Pide el enlace de recuperación. Siempre responde igual para no revelar
     * si el correo existe o no.
     */
    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ], [
            'email.required' => 'Ingresá el correo.',
            'email.email' => 'Ingresá un correo válido.',
        ]);

        $email = Str::lower(trim($request->input('email')));

        $users = User::whereRaw('LOWER(email) = ?', [$email])->with('typeUser')->get();

        if ($users->isNotEmpty()) {
            $accounts = [];

            foreach ($users as $user) {
                $plain = Str::random(64);

                DB::transaction(function () use ($user, $plain) {
                    // Un solo enlace vigente por cuenta.
                    PasswordResetRequest::where('user_id', $user->id)->whereNull('used_at')->delete();
                    PasswordResetRequest::create([
                        'user_id' => $user->id,
                        'token' => hash('sha256', $plain),
                        'expires_at' => now()->addMinutes(self::EXPIRATION_MINUTES),
                    ]);
                });

                $accounts[] = [
                    'name' => $user->name,
                    'type' => $user->typeUser->description ?? 'Cuenta',
                    'url' => route('password.reset', $plain),
                ];
            }

            try {
                Mail::to($email)->send(new PasswordResetMail($accounts));
            } catch (\Throwable $e) {
                report($e);

                // Si no salió el correo, los enlaces recién creados no sirven para nada.
                PasswordResetRequest::whereIn('user_id', $users->pluck('id'))->whereNull('used_at')->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'No pudimos enviar el correo en este momento. Intentá de nuevo en unos minutos.',
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Si existe una cuenta con ese correo, te enviamos un enlace de recuperación. Revisá tu bandeja de entrada.',
        ]);
    }

    /**
     * Formulario para elegir la contraseña nueva.
     */
    public function showForm(string $token)
    {
        $request = PasswordResetRequest::where('token', hash('sha256', $token))->first();
        $valid = $request && $request->isUsable();

        return view('auth.reset-password', [
            'token' => $token,
            'valid' => $valid,
            'userName' => $valid ? optional($request->user)->name : null,
        ]);
    }

    /**
     * Guarda la contraseña nueva y consume el enlace.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|max:255|confirmed',
        ], [
            'token.required' => 'El enlace de recuperación no es válido.',
            'password.required' => 'Ingresá la contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $row = PasswordResetRequest::where('token', hash('sha256', $request->input('token')))->first();
        $user = $row ? $row->user : null;

        if (! $row || ! $row->isUsable() || ! $user) {
            return view('auth.reset-password', [
                'token' => $request->input('token'),
                'valid' => false,
                'userName' => null,
            ]);
        }

        DB::transaction(function () use ($row, $user, $request) {
            $user->password = Hash::make($request->input('password'));
            $user->save();

            // Cualquier enlace pendiente de esta cuenta queda anulado.
            PasswordResetRequest::where('user_id', $user->id)->update(['used_at' => now()]);
        });

        return redirect('/?password_reset=1');
    }
}
