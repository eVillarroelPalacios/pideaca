<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\TypeUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Tipos de usuario para la app movil.
 *
 * Espeja la seccion "tipo-usuarios" del panel web sin tocarla: mismo modelo
 * (TypeUser), mismas reglas (descripcion unica obligatoria) y mismo permiso
 * (pagina tipo-usuarios). No se puede eliminar un tipo que todavia tenga
 * usuarios asociados. Tambien espeja el modal "Paginas por tipo": plantilla
 * de paginas por tipo con cascada a sus usuarios.
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
     * Plantilla de paginas por tipo (modal "Paginas por tipo" de la web):
     * tipos con su cantidad de usuarios y paginas asignadas, mas el
     * catalogo de paginas con su modulo.
     */
    public function pagesData(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $types = TypeUser::withCount('users')
            ->with('assignedPages')
            ->orderBy('description')
            ->get()
            ->map(fn (TypeUser $type) => [
                'id' => $type->id,
                'description' => $type->description,
                'users_count' => (int) $type->users_count,
                'assigned_page_ids' => $type->assignedPages->pluck('id')->unique()->values()->all(),
            ])
            ->values()
            ->all();

        $pages = Page::with('module')
            ->orderBy('description')
            ->get()
            ->map(fn (Page $page) => [
                'id' => $page->id,
                'description' => $page->description,
                'url' => $page->url,
                'module_id' => $page->module_id,
                'module' => $page->module ? ($page->module->description ?? null) : null,
            ])
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'types' => $types,
            'pages' => $pages,
        ]);
    }

    /**
     * Reemplaza la plantilla de paginas del tipo y la aplica a todos sus
     * usuarios, igual que en la web.
     */
    public function updatePages(Request $request, TypeUser $typeUser): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $request->validate([
            'page_ids' => ['required', 'array'],
            'page_ids.*' => ['integer', 'exists:pages,id'],
        ], [
            'page_ids.required' => 'Indicá las páginas del tipo.',
            'page_ids.array' => 'El formato de páginas no es válido.',
            'page_ids.*.exists' => 'Una de las páginas seleccionadas no existe.',
        ]);

        $pageIds = array_values(array_unique(array_map('intval', $data['page_ids'])));

        DB::transaction(function () use ($typeUser, $pageIds) {
            $typeUser->assignedPages()->sync($pageIds);

            foreach ($typeUser->users as $user) {
                $user->pages()->sync($pageIds);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Páginas actualizadas para el tipo "'.$typeUser->description.'".',
            'users_count' => $typeUser->users()->count(),
        ]);
    }

    /**
     * Agrega o quita paginas de la plantilla en bulk, con cascada a los
     * usuarios del tipo.
     */
    public function bulkPages(Request $request, TypeUser $typeUser): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $request->validate([
            'page_ids' => ['required', 'array'],
            'page_ids.*' => ['integer', 'exists:pages,id'],
            'action' => ['required', 'in:attach,detach'],
        ], [
            'page_ids.required' => 'Indicá las páginas.',
            'page_ids.*.exists' => 'Una de las páginas seleccionadas no existe.',
            'action.in' => 'La acción no es válida.',
        ]);

        $pageIds = array_values(array_unique(array_map('intval', $data['page_ids'])));
        $action = $data['action'];

        if ($pageIds === []) {
            return response()->json([
                'success' => true,
                'message' => 'Sin cambios.',
                'action' => $action,
            ]);
        }

        DB::transaction(function () use ($typeUser, $pageIds, $action) {
            if ($action === 'attach') {
                $typeUser->assignedPages()->syncWithoutDetaching($pageIds);

                foreach ($typeUser->users as $user) {
                    $user->pages()->syncWithoutDetaching($pageIds);
                }
            } else {
                $typeUser->assignedPages()->detach($pageIds);

                foreach ($typeUser->users as $user) {
                    $user->pages()->detach($pageIds);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => $action === 'attach' ? 'Páginas agregadas.' : 'Páginas quitadas.',
            'action' => $action,
            'count' => count($pageIds),
        ]);
    }

    public function attachPage(Request $request, TypeUser $typeUser, Page $page): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        DB::transaction(function () use ($typeUser, $page) {
            $typeUser->assignedPages()->syncWithoutDetaching([$page->id]);

            foreach ($typeUser->users as $user) {
                $user->pages()->syncWithoutDetaching([$page->id]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Página agregada.',
            'page_id' => $page->id,
        ]);
    }

    public function detachPage(Request $request, TypeUser $typeUser, Page $page): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        DB::transaction(function () use ($typeUser, $page) {
            $typeUser->assignedPages()->detach($page->id);

            foreach ($typeUser->users as $user) {
                $user->pages()->detach($page->id);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Página quitada.',
            'page_id' => $page->id,
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
