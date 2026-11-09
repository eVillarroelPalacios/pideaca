<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TypeUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Tipos de usuario para la app movil.
 *
 * Espeja la seccion "tipo-usuarios" del panel web sin tocarla: mismo modelo
 * (TypeUser), mismas reglas (descripcion unica obligatoria) y mismo permiso
 * (pagina tipo-usuarios). No se puede eliminar un tipo que todavia tenga
 * usuarios asociados.
 */
class TypeUsersController extends Controller
{
    /**
     * Catalogo completo con la cantidad de usuarios de cada tipo.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $types = TypeUser::withCount('users')
            ->orderBy('description')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types
                ->map(fn (TypeUser $type) => $this->payload($type))
                ->values()
                ->all(),
            'summary' => [
                'total' => $types->count(),
                'in_use' => $types->where('users_count', '>', 0)->count(),
            ],
        ]);
    }

    /**
     * Alta de un tipo de usuario.
     */
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request);

        $type = TypeUser::create([
            'description' => $data['description'],
        ]);

        $type->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Tipo de usuario creado correctamente.',
            'type_user' => $this->payload($type),
        ], 201);
    }

    /**
     * Edicion de la descripcion del tipo de usuario.
     */
    public function update(Request $request, TypeUser $typeUser): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request, $typeUser);

        $typeUser->update([
            'description' => $data['description'],
        ]);

        $typeUser->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Tipo de usuario actualizado correctamente.',
            'type_user' => $this->payload($typeUser),
        ]);
    }

    /**
     * Baja de un tipo de usuario. Se bloquea si todavia tiene usuarios
     * asociados.
     */
    public function destroy(Request $request, TypeUser $typeUser): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        if ($typeUser->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el tipo de usuario porque tiene usuarios asociados.',
                'code' => 'TYPE_USER_IN_USE',
            ], 409);
        }

        $typeUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de usuario eliminado correctamente.',
        ]);
    }

    /**
     * Reglas identicas al controlador web mas los mensajes en espanol.
     */
    private function validated(Request $request, ?TypeUser $typeUser = null): array
    {
        $request->merge([
            'description' => trim((string) $request->input('description')),
        ]);

        $unique = Rule::unique('type_users', 'description');

        if ($typeUser) {
            $unique = $unique->ignore($typeUser->id);
        }

        return $request->validate([
            'description' => ['required', 'string', 'max:255', $unique],
        ], [
            'description.required' => 'Ingresá la descripción del tipo de usuario.',
            'description.unique' => 'Ya existe un tipo de usuario con esa descripción.',
            'description.max' => 'La descripción no puede superar los 255 caracteres.',
        ]);
    }

    /**
     * Permiso identico al guard web `page:tipo-usuarios`.
     */
    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        if ($request->user()?->hasPageUrl('tipo-usuarios')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
        ], 403);
    }

    /**
     * Forma publica de un tipo de usuario, con los datos que usa la tabla web.
     */
    private function payload(TypeUser $type): array
    {
        $usersCount = (int) ($type->users_count ?? 0);

        return [
            'id' => $type->id,
            'description' => $type->description,
            'users_count' => $usersCount,
            'can_delete' => $usersCount === 0,
        ];
    }
}
