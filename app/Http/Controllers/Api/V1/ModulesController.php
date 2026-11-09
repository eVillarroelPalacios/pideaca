<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Modulos del catalogo para la app movil.
 *
 * Espeja la seccion "modulos" del panel web sin tocarla: mismo modelo
 * (Module), mismas reglas (descripcion unica, icono SVG opcional) y mismo
 * permiso (pagina modulos). No se puede eliminar un modulo que todavia
 * tenga paginas asociadas.
 */
class ModulesController extends Controller
{
    /**
     * Catalogo completo con la cantidad de paginas asociadas.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $modules = Module::withCount('pages')
            ->orderBy('description')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $modules
                ->map(fn (Module $module) => $this->payload($module))
                ->values()
                ->all(),
            'summary' => [
                'total' => $modules->count(),
                'in_use' => $modules->where('pages_count', '>', 0)->count(),
            ],
        ]);
    }

    /**
     * Alta de un modulo.
     */
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request);

        $module = Module::create([
            'description' => $data['description'],
            'icon' => $data['icon'] ?? null,
        ]);

        $module->loadCount('pages');

        return response()->json([
            'success' => true,
            'message' => 'Módulo creado correctamente.',
            'module' => $this->payload($module),
        ], 201);
    }

    /**
     * Edicion de la descripcion y el icono del modulo.
     */
    public function update(Request $request, Module $module): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request, $module);

        $module->update([
            'description' => $data['description'],
            'icon' => $data['icon'] ?? null,
        ]);

        $module->loadCount('pages');

        return response()->json([
            'success' => true,
            'message' => 'Módulo actualizado correctamente.',
            'module' => $this->payload($module),
        ]);
    }

    /**
     * Baja de un modulo. Se bloquea si todavia tiene paginas asociadas.
     */
    public function destroy(Request $request, Module $module): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        if ($module->pages()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el módulo porque tiene páginas asociadas.',
                'code' => 'MODULE_IN_USE',
            ], 409);
        }

        $module->delete();

        return response()->json([
            'success' => true,
            'message' => 'Módulo eliminado correctamente.',
        ]);
    }

    /**
     * Reglas identicas al controlador web mas los mensajes en espanol.
     */
    private function validated(Request $request, ?Module $module = null): array
    {
        $icon = $request->input('icon');

        $request->merge([
            'description' => trim((string) $request->input('description')),
            'icon' => is_string($icon) ? trim($icon) : $icon,
        ]);

        $unique = Rule::unique('modules', 'description');

        if ($module) {
            $unique = $unique->ignore($module->id);
        }

        return $request->validate([
            'description' => ['required', 'string', 'max:255', $unique],
            'icon' => ['nullable', 'string'],
        ], [
            'description.required' => 'Ingresá el nombre del módulo.',
            'description.unique' => 'Ya existe un módulo con ese nombre.',
            'description.max' => 'El nombre no puede superar los 255 caracteres.',
        ]);
    }

    /**
     * Permiso identico al guard web `page:modulos`.
     */
    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        if ($request->user()?->hasPageUrl('modulos')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
        ], 403);
    }

    /**
     * Forma publica de un modulo, con los datos que usa la tabla web.
     */
    private function payload(Module $module): array
    {
        $pagesCount = (int) ($module->pages_count ?? 0);

        return [
            'id' => $module->id,
            'description' => $module->description,
            'icon' => $module->icon,
            'pages_count' => $pagesCount,
            'can_delete' => $pagesCount === 0,
        ];
    }
}
