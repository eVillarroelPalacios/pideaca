<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Grupos del catalogo para la app movil.
 *
 * Espeja la seccion "grupos" del panel web sin tocarla: mismo modelo
 * (Group), mismas reglas (descripcion unica, icono SVG opcional y estado
 * existente) y mismo permiso (pagina grupos). Un grupo nuevo nace Activo
 * salvo que se indique otro estado, y no se puede eliminar si tiene
 * sub grupos asociados.
 */
class GroupsController extends Controller
{
    /**
     * Catalogo completo con su estado y la cantidad de sub grupos. Tambien
     * devuelve los estados disponibles para armar el selector del modal.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $groups = Group::with('groupStatus')
            ->withCount('subgroups')
            ->orderBy('description')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $groups
                ->map(fn (Group $group) => $this->payload($group))
                ->values()
                ->all(),
            'filters' => [
                'group_statuses' => GroupStatus::orderBy('id')
                    ->get(['id', 'description'])
                    ->map(fn (GroupStatus $status) => [
                        'id' => $status->id,
                        'description' => $status->description,
                    ])
                    ->values()
                    ->all(),
                'default_group_status_id' => $this->activeStatusId(),
            ],
        ]);
    }

    /**
     * Alta de un grupo.
     */
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request);

        $group = Group::create([
            'description' => $data['description'],
            'icon' => $data['icon'] ?? null,
            // Todo grupo nuevo nace activo salvo que se pida lo contrario.
            'group_status_id' => $data['group_status_id'] ?? $this->activeStatusId(),
        ]);

        $group->load('groupStatus')->loadCount('subgroups');

        return response()->json([
            'success' => true,
            'message' => 'Grupo creado correctamente.',
            'group' => $this->payload($group),
        ], 201);
    }

    /**
     * Edicion de la descripcion, el icono y el estado del grupo.
     */
    public function update(Request $request, Group $group): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request, $group);

        $changes = [
            'description' => $data['description'],
            'icon' => $data['icon'] ?? null,
        ];

        // Si no viene el estado en el request se conserva el actual.
        if ($request->exists('group_status_id')) {
            $changes['group_status_id'] = $data['group_status_id'] ?? null;
        }

        $group->update($changes);
        $group->load('groupStatus')->loadCount('subgroups');

        return response()->json([
            'success' => true,
            'message' => 'Grupo actualizado correctamente.',
            'group' => $this->payload($group),
        ]);
    }

    /**
     * Baja de un grupo. Se bloquea si todavia tiene sub grupos asociados.
     */
    public function destroy(Request $request, Group $group): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        if ($group->subgroups()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el grupo porque tiene sub grupos asociados.',
                'code' => 'GROUP_IN_USE',
            ], 409);
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grupo eliminado correctamente.',
        ]);
    }

    /**
     * Reglas identicas al controlador web mas los mensajes en espanol.
     */
    private function validated(Request $request, ?Group $group = null): array
    {
        $icon = $request->input('icon');

        $request->merge([
            'description' => trim((string) $request->input('description')),
            'icon' => is_string($icon) ? trim($icon) : $icon,
        ]);

        $unique = Rule::unique('groups', 'description');

        if ($group) {
            $unique = $unique->ignore($group->id);
        }

        return $request->validate([
            'description' => ['required', 'string', 'max:255', $unique],
            'icon' => ['nullable', 'string'],
            'group_status_id' => ['nullable', 'integer', 'exists:group_statuses,id'],
        ], [
            'description.required' => 'Ingresá el nombre del grupo.',
            'description.unique' => 'Ya existe un grupo con ese nombre.',
            'description.max' => 'El nombre no puede superar los 255 caracteres.',
            'group_status_id.integer' => 'El estado seleccionado no es válido.',
            'group_status_id.exists' => 'El estado seleccionado no existe.',
        ]);
    }

    /**
     * Permiso identico al guard web `page:grupos`.
     */
    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        if ($request->user()?->hasPageUrl('grupos')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
        ], 403);
    }

    /**
     * ID del estado "Activo" configurado en el entorno.
     */
    private function activeStatusId(): ?int
    {
        $id = config('catalog.group_status_active_id');

        return $id === null || $id === '' ? null : (int) $id;
    }

    /**
     * Forma publica de un grupo, con los datos que usa la tabla web.
     */
    private function payload(Group $group): array
    {
        $subgroupsCount = (int) ($group->subgroups_count ?? 0);

        return [
            'id' => $group->id,
            'description' => $group->description,
            'icon' => $group->icon,
            'group_status_id' => $group->group_status_id,
            'group_status' => $group->groupStatus ? [
                'id' => $group->groupStatus->id,
                'description' => $group->groupStatus->description,
            ] : null,
            'subgroups_count' => $subgroupsCount,
            'can_delete' => $subgroupsCount === 0,
        ];
    }
}
