<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AccountActivationMail;
use App\Models\AccountActivationRequest;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AccountActivationController extends Controller
{
    /**
     * Vigencia del enlace enviado por correo.
     */
    public const EXPIRATION_HOURS = 24;

    /**
     * Activa la cuenta al visitar el enlace del correo. Un solo uso.
     */
    public function activate(string $token)
    {
        $request = AccountActivationRequest::where('token', hash('sha256', $token))->first();
        $user = $request ? $request->user : null;
        $valid = $request && $request->isUsable() && $user && $user->email_verified_at === null;

        if ($valid) {
            DB::transaction(function () use ($request, $user) {
                $user->email_verified_at = now();
                $user->user_status_id = UserStatus::firstOrCreate(['status' => 'Activo'])->id;
                $user->save();

                // Cualquier enlace pendiente de esta cuenta queda anulado.
                AccountActivationRequest::where('user_id', $user->id)->update(['used_at' => now()]);
            });
        }

        return view('auth.activate', [
            'activated' => $valid,
            'userName' => $valid ? $user->name : null,
        ]);
    }

    /**
     * Reenvía el enlace de activación. Siempre responde igual para no revelar
     * si el correo existe o si la cuenta ya está activa.
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ], [
            'email.required' => 'Ingresá el correo.',
            'email.email' => 'Ingresá un correo válido.',
        ]);

        $email = Str::lower(trim($request->input('email')));

        $users = User::whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('email_verified_at')
            ->get();

        if ($users->isNotEmpty()) {
            foreach ($users as $user) {
                $plain = Str::random(64);

                DB::transaction(function () use ($user, $plain) {
                    // Un solo enlace vigente por cuenta.
                    AccountActivationRequest::where('user_id', $user->id)->whereNull('used_at')->delete();
                    AccountActivationRequest::create([
                        'user_id' => $user->id,
                        'token' => hash('sha256', $plain),
                        'expires_at' => now()->addHours(self::EXPIRATION_HOURS),
                    ]);
                });

                $user->activation_plain_token = $plain;
            }

            try {
                foreach ($users as $user) {
                    Mail::to($email)->send(new AccountActivationMail(
                        $user->name,
                        route('activation.activate', $user->activation_plain_token)
                    ));
                }
            } catch (\Throwable $e) {
                report($e);

                // Si no salió el correo, los enlaces recién creados no sirven para nada.
                AccountActivationRequest::whereIn('user_id', $users->pluck('id'))->whereNull('used_at')->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'No pudimos enviar el correo en este momento. Intentá de nuevo en unos minutos.',
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Si tu cuenta sigue sin activarse, te enviamos un enlace nuevo. Revisá tu bandeja de entrada.',
        ]);
    }
}
