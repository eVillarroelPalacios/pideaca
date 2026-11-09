<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\ServiceOrder;
use App\Models\SubGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Sub grupos del catalogo para la app movil.
 *
 * Espeja la seccion "sub-grupos" del panel web sin tocarla: mismo modelo
 * (SubGroup), mismas reglas (descripcion unica obligatoria y grupo
 * existente obligatorio) y mismo permiso (pagina sub-grupos). No se puede
 * eliminar un sub grupo que todavia tenga prestadores u ordenes de
 * servicio asociadas (en la web el borrado arrastra en cascada y aca se
 * bloquea con 409 para no perder historial).
 */
class SubGroupsController extends Controller
{
    /**
     * Catalogo completo con su grupo, la cantidad de prestadores que lo
     * ofrecen y la lista de grupos para armar el filtro y el selector
     * del modal.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $subgroups = SubGroup::with('group')
            ->withCount('providers')
            ->orderBy('description')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subgroups
                ->map(fn (SubGroup $subgroup) => $this->payload($subgroup))
                ->values()
                ->all(),
            'filters' => [
                'groups' => Group::orderBy('description')
                    ->get(['id', 'description'])
                    ->map(fn (Group $group) => [
                        'id' => $group->id,
                        'description' => $group->description,
                    ])
                    ->values()
                    ->all(),
            ],
            'summary' => [
                'total' => $subgroups->count(),
                'in_use' => $subgroups->where('providers_count', '>', 0)->count(),
            ],
        ]);
    }

    /**
     * Alta de un sub grupo.
     */
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request);

        $subgroup = SubGroup::create([
            'description' => $data['description'],
            'group_id' => $data['group_id'],
        ]);

        $subgroup->load('group')->loadCount('providers');

        return response()->json([
            'success' => true,
            'message' => 'Sub grupo creado correctamente.',
            'subgroup' => $this->payload($subgroup),
        ], 201);
    }

    /**
     * Edicion de la descripcion y el grupo del sub grupo.
     */
    public function update(Request $request, SubGroup $subgroup): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request, $subgroup);

        $subgroup->update([
            'description' => $data['description'],
            'group_id' => $data['group_id'],
        ]);

        $subgroup->load('group')->loadCount('providers');

        return response()->json([
            'success' => true,
            'message' => 'Sub grupo actualizado correctamente.',
            'subgroup' => $this->payload($subgroup),
        ]);
    }

    /**
     * Baja de un sub grupo. Se bloquea si todavia tiene prestadores u
     * ordenes de servicio asociadas.
     */
    public function destroy(Request $request, SubGroup $subgroup): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        if ($subgroup->providers()->exists()
            || ServiceOrder::where('sub_group_id', $subgroup->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el sub grupo porque tiene prestadores u órdenes asociadas.',
                'code' => 'SUBGROUP_IN_USE',
            ], 409);
        }

        $subgroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub grupo eliminado correctamente.',
        ]);
    }

    /**
     * Reglas identicas al controlador web mas los mensajes en espanol.
     */
    private function validated(Request $request, ?SubGroup $subgroup = null): array
    {
        $request->merge([
            'description' => trim((string) $request->input('description')),
        ]);

        $unique = Rule::unique('sub_groups', 'description');

        if ($subgroup) {
            $unique = $unique->ignore($subgroup->id);
        }

        return $request->validate([
            'description' => ['required', 'string', 'max:255', $unique],
            'group_id' => ['required', 'integer', 'exists:groups,id'],
        ], [
            'description.required' => 'Ingresá la descripción del sub grupo.',
            'description.unique' => 'Ya existe un sub grupo con esa descripción.',
            'description.max' => 'La descripción no puede superar los 255 caracteres.',
            'group_id.required' => 'Seleccioná un grupo.',
            'group_id.integer' => 'El grupo seleccionado no es válido.',
            'group_id.exists' => 'El grupo seleccionado no existe.',
        ]);
    }

    /**
     * Permiso identico al guard web `page:sub-grupos`.
     */
    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        if ($request->user()?->hasPageUrl('sub-grupos')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
        ], 403);
    }

    /**
     * Forma publica de un sub grupo, con los datos que usa la tabla web.
     */
    private function payload(SubGroup $subgroup): array
    {
        $providersCount = (int) ($subgroup->providers_count ?? 0);

        return [
            'id' => $subgroup->id,
            'description' => $subgroup->description,
            'group_id' => $subgroup->group_id,
            'group' => $subgroup->group ? [
                'id' => $subgroup->group->id,
                'description' => $subgroup->group->description,
            ] : null,
            'providers_count' => $providersCount,
            'can_delete' => $providersCount === 0,
        ];
    }
}
