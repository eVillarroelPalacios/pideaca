<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DriverWallet;
use App\Models\TypeUser;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Autenticación de las apps móviles.
 *
 * La app manda email + password y recibe un token Bearer. Ese token viaja en
 * cada request como: Authorization: Bearer <token>. No hay sesiones ni CSRF
 * porque no es un navegador.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ], [
            'email.required' => 'Ingresá tu correo electrónico.',
            'email.email' => 'Ingresá un correo electrónico válido.',
            'password.required' => 'Ingresá tu contraseña.',
        ]);

        $email = Str::lower(trim($data['email']));

        // Mismo criterio que el login web: el mismo email puede tener varias
        // cuentas (por ejemplo, Cliente y Prestador) y la contraseña decide.
        $users = User::whereRaw('LOWER(email) = ?', [$email])
            ->with(['typeUser', 'status'])
            ->get();

        $user = $users->first(function (User $candidate) use ($data) {
            return Hash::check($data['password'], $candidate->password);
        });

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
                'code' => 'INVALID_CREDENTIALS',
            ], 422);
        }

        $device = trim((string) ($data['device_name'] ?? '')) ?: 'mobile';

        return response()->json([
            'success' => true,
            'message' => 'Sesión iniciada correctamente.',
            'token' => $user->createToken($device)->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'type_user_id' => 'required|integer',
            'password' => 'required|string|min:8|max:255|confirmed',
            'device_name' => 'nullable|string|max:100',
        ], [
            'name.required' => 'Ingresá tu nombre completo.',
            'email.required' => 'Ingresá tu correo electrónico.',
            'email.email' => 'Ingresá un correo electrónico válido.',
            'type_user_id.required' => 'Seleccioná el tipo de cuenta.',
            'password.required' => 'Ingresá tu contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $email = Str::lower(trim($data['email']));

        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ese correo ya está registrado. Intentá iniciar sesión.',
                'code' => 'EMAIL_TAKEN',
            ], 422);
        }

        $type = TypeUser::whereIn('id', config('catalog.registrable_type_ids'))
            ->find((int) $data['type_user_id']);

        if (! $type) {
            return response()->json([
                'success' => false,
                'message' => 'Seleccioná un tipo de cuenta válido.',
                'code' => 'INVALID_USER_TYPE',
            ], 422);
        }

        $status = UserStatus::whereRaw('LOWER(status) = ?', ['activo'])->first();

        $user = new User;
        $user->name = trim($data['name']);
        $user->email = $email;
        $user->type_user_id = $type->id;
        $user->user_status_id = $status?->id;
        $user->password = Hash::make($data['password']);
        $user->email_verified_at = now();

        DB::transaction(function () use ($user, $type) {
            $user->save();

            // El menú web y los permisos por página salen de esta relación.
            $user->pages()->sync($type->assignedPages()->pluck('pages.id')->all());

            if (strcasecmp($type->description, 'Repartidor') === 0) {
                DriverWallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => 0.00, 'pending_balance' => 0.00]
                );
            }
        });

        $user->load(['typeUser', 'status']);
        $device = trim((string) ($data['device_name'] ?? '')) ?: 'mobile';

        return response()->json([
            'success' => true,
            'message' => 'Cuenta creada correctamente.',
            'token' => $user->createToken($device)->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function types()
    {
        $types = TypeUser::whereIn('id', config('catalog.registrable_type_ids'))
            ->orderBy('description')
            ->get(['id', 'description']);

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['typeUser', 'status']);

        return response()->json([
            'success' => true,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * Baja de cuenta desde la app (requisito de App Store y Google Play).
     *
     * Desactiva la cuenta y revoca todos los tokens. No borra físicamente al
     * usuario para no romper pedidos, pagos ni historial; el Admin puede
     * eliminarlo después desde el panel si corresponde.
     */
    public function destroyAccount(Request $request)
    {
        $user = $request->user();

        $status = UserStatus::whereRaw('LOWER(status) IN (?, ?)', ['inactivo', 'inactive'])->first()
            ?? UserStatus::create(['status' => 'Inactivo']);

        DB::transaction(function () use ($user, $status) {
            $user->tokens()->delete();
            $user->forceFill(['user_status_id' => $status->id])->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Tu cuenta fue desactivada. No podrás volver a iniciar sesión.',
        ]);
    }

    /**
     * Forma única de representar al usuario en la API. Si mañana cambia el
     * esquema, se toca solo acá.
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->typeUser?->description,
            'is_admin' => $user->isAdmin(),
            'is_driver' => $user->isDriver(),
            'status' => $user->status?->status,
            'vehicle_type' => $user->vehicle_type,
            'vehicle_plate' => $user->vehicle_plate,
        ];
    }
}
