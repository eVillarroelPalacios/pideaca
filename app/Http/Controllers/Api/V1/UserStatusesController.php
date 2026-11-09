<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Estados de los usuarios para la app movil.
 *
 * Espeja la seccion "estados-usuarios" del panel web sin tocarla: mismo
 * modelo (UserStatus), mismas reglas (status unico) y mismo permiso
 * (pagina estados-usuarios). Un estado en uso por algun usuario no se
 * puede eliminar, igual que en la web.
 */
class UserStatusesController extends Controller
{
    /**
     * Catalogo completo con la cantidad de usuarios que usa cada estado.
     * El orden alfabetico es el mismo que aplica el controlador web.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $statuses = UserStatus::withCount('users')->orderBy('status')->get();

        return response()->json([
            'success' => true,
            'data' => $statuses
                ->map(fn (UserStatus $status) => $this->payload($status))
                ->values()
                ->all(),
            'summary' => [
                'total' => $statuses->count(),
                'in_use' => $statuses->where('users_count', '>', 0)->count(),
            ],
        ]);
    }

    /**
     * Alta de un estado de usuario.
     */
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request);

        $userStatus = UserStatus::create(['status' => $data['status']]);
        $userStatus->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Estado de usuario creado correctamente.',
            'user_status' => $this->payload($userStatus),
        ], 201);
    }

    /**
     * Edicion del nombre de un estado.
     */
    public function update(Request $request, UserStatus $userStatus): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request, $userStatus);

        $userStatus->update(['status' => $data['status']]);
        $userStatus->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Estado de usuario actualizado correctamente.',
            'user_status' => $this->payload($userStatus),
        ]);
    }

    /**
     * Baja de un estado. Se bloquea si todavia tiene usuarios asociados.
     */
    public function destroy(Request $request, UserStatus $userStatus): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        if ($userStatus->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el estado porque tiene usuarios asociados.',
                'code' => 'USER_STATUS_IN_USE',
            ], 409);
        }

        $userStatus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Estado de usuario eliminado correctamente.',
        ]);
    }

    /**
     * Reglas identicas al controlador web (estado obligatorio y unico).
     */
    private function validated(Request $request, ?UserStatus $userStatus = null): array
    {
        $request->merge([
            'status' => trim((string) $request->input('status')),
        ]);

        $unique = Rule::unique('user_statuses', 'status');

        if ($userStatus) {
            $unique = $unique->ignore($userStatus->id);
        }

        return $request->validate([
            'status' => ['required', 'string', 'max:255', $unique],
        ], [
            'status.required' => 'Ingresá el nombre del estado.',
            'status.unique' => 'Ya existe un estado con ese nombre.',
            'status.max' => 'El nombre no puede superar los 255 caracteres.',
        ]);
    }

    /**
     * Permiso identico al guard web `page:estados-usuarios`.
     */
    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        if ($request->user()?->hasPageUrl('estados-usuarios')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
        ], 403);
    }

    /**
     * Forma publica de un estado, con los datos que usa la tabla web.
     */
    private function payload(UserStatus $status): array
    {
        $usersCount = (int) ($status->users_count ?? 0);

        return [
            'id' => $status->id,
            'status' => $status->status,
            'users_count' => $usersCount,
            'can_delete' => $usersCount === 0,
        ];
    }
}
