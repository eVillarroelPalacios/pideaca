<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GroupStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Estados de los grupos para la app movil.
 *
 * Espeja la seccion "estados-grupos" del panel web sin tocarla: mismo
 * modelo (GroupStatus), mismas reglas (descripcion unica) y mismo permiso
 * (pagina estados-grupos). Un estado en uso por algun grupo no se puede
 * eliminar, igual que en la web.
 */
class GroupStatusesController extends Controller
{
    /**
     * Catalogo completo con la cantidad de grupos que usa cada estado.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $statuses = GroupStatus::withCount('groups')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $statuses
                ->map(fn (GroupStatus $status) => $this->payload($status))
                ->values()
                ->all(),
            'summary' => [
                'total' => $statuses->count(),
                'in_use' => $statuses->where('groups_count', '>', 0)->count(),
            ],
        ]);
    }

    /**
     * Alta de un estado de grupo.
     */
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request);

        $groupStatus = GroupStatus::create(['description' => $data['description']]);
        $groupStatus->loadCount('groups');

        return response()->json([
            'success' => true,
            'message' => 'Estado de grupo creado correctamente.',
            'group_status' => $this->payload($groupStatus),
        ], 201);
    }

    /**
     * Edicion del nombre de un estado.
     */
    public function update(Request $request, GroupStatus $groupStatus): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request, $groupStatus);

        $groupStatus->update(['description' => $data['description']]);
        $groupStatus->loadCount('groups');

        return response()->json([
            'success' => true,
            'message' => 'Estado de grupo actualizado correctamente.',
            'group_status' => $this->payload($groupStatus),
        ]);
    }

    /**
     * Baja de un estado. Se bloquea si todavia tiene grupos asociados.
     */
    public function destroy(Request $request, GroupStatus $groupStatus): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        if ($groupStatus->groups()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el estado porque tiene grupos asociados.',
                'code' => 'GROUP_STATUS_IN_USE',
            ], 409);
        }

        $groupStatus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Estado de grupo eliminado correctamente.',
        ]);
    }

    /**
     * Reglas identicas al controlador web (descripcion obligatoria y unica).
     */
    private function validated(Request $request, ?GroupStatus $groupStatus = null): array
    {
        $request->merge([
            'description' => trim((string) $request->input('description')),
        ]);

        $unique = Rule::unique('group_statuses', 'description');

        if ($groupStatus) {
            $unique = $unique->ignore($groupStatus->id);
        }

        return $request->validate([
            'description' => ['required', 'string', 'max:255', $unique],
        ], [
            'description.required' => 'Ingresá el nombre del estado.',
            'description.unique' => 'Ya existe un estado con ese nombre.',
            'description.max' => 'El nombre no puede superar los 255 caracteres.',
        ]);
    }

    /**
     * Permiso identico al guard web `page:estados-grupos`.
     */
    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        if ($request->user()?->hasPageUrl('estados-grupos')) {
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
    private function payload(GroupStatus $status): array
    {
        $groupsCount = (int) ($status->groups_count ?? 0);

        return [
            'id' => $status->id,
            'description' => $status->description,
            'groups_count' => $groupsCount,
            'can_delete' => $groupsCount === 0,
        ];
    }
}
