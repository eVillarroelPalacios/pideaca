<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Paginas del sistema para la app movil.
 *
 * Espeja la seccion "paginas" del panel web sin tocarla: mismo modelo
 * (Page), mismas reglas (descripcion unica obligatoria, URL opcional y
 * modulo existente obligatorio) y mismo permiso (pagina paginas). No se
 * puede eliminar una pagina que todavia tenga usuarios asignados.
 */
class PagesController extends Controller
{
    /**
     * Catalogo completo con su modulo, la cantidad de usuarios asignados y
     * la lista de modulos para armar el filtro y el selector del modal.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $pages = Page::with('module')
            ->withCount('users')
            ->orderBy('description')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pages
                ->map(fn (Page $page) => $this->payload($page))
                ->values()
                ->all(),
            'filters' => [
                'modules' => Module::orderBy('description')
                    ->get(['id', 'description'])
                    ->map(fn (Module $module) => [
                        'id' => $module->id,
                        'description' => $module->description,
                    ])
                    ->values()
                    ->all(),
            ],
            'summary' => [
                'total' => $pages->count(),
                'in_use' => $pages->where('users_count', '>', 0)->count(),
            ],
        ]);
    }

    /**
     * Alta de una pagina.
     */
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request);

        $page = Page::create([
            'description' => $data['description'],
            'url' => $data['url'] ?? null,
            'module_id' => $data['module_id'],
        ]);

        $page->load('module')->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Página creada correctamente.',
            'page' => $this->payload($page),
        ], 201);
    }

    /**
     * Edicion de la descripcion, la URL y el modulo de la pagina.
     */
    public function update(Request $request, Page $page): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        $data = $this->validated($request, $page);

        $page->update([
            'description' => $data['description'],
            'url' => $data['url'] ?? null,
            'module_id' => $data['module_id'],
        ]);

        $page->load('module')->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Página actualizada correctamente.',
            'page' => $this->payload($page),
        ]);
    }

    /**
     * Baja de una pagina. Se bloquea si todavia tiene usuarios asignados.
     */
    public function destroy(Request $request, Page $page): JsonResponse
    {
        if ($error = $this->denyUnlessAllowed($request)) {
            return $error;
        }

        if ($page->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la página porque tiene usuarios asignados.',
                'code' => 'PAGE_IN_USE',
            ], 409);
        }

        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Página eliminada correctamente.',
        ]);
    }

    /**
     * Reglas identicas al controlador web mas los mensajes en espanol.
     */
    private function validated(Request $request, ?Page $page = null): array
    {
        $request->merge([
            'description' => trim((string) $request->input('description')),
            'url' => is_string($request->input('url')) ? trim((string) $request->input('url')) : $request->input('url'),
        ]);

        // La web guarda '' cuando la URL viene vacia: se normaliza a null.
        if ($request->input('url') === '') {
            $request->merge(['url' => null]);
        }

        $unique = Rule::unique('pages', 'description');

        if ($page) {
            $unique = $unique->ignore($page->id);
        }

        return $request->validate([
            'description' => ['required', 'string', 'max:255', $unique],
            'url' => ['nullable', 'string', 'max:255'],
            'module_id' => ['required', 'integer', 'exists:modules,id'],
        ], [
            'description.required' => 'Ingresá la descripción de la página.',
            'description.unique' => 'Ya existe una página con esa descripción.',
            'description.max' => 'La descripción no puede superar los 255 caracteres.',
            'url.max' => 'La URL no puede superar los 255 caracteres.',
            'module_id.required' => 'Seleccioná un módulo.',
            'module_id.integer' => 'El módulo seleccionado no es válido.',
            'module_id.exists' => 'El módulo seleccionado no existe.',
        ]);
    }

    /**
     * Permiso identico al guard web `page:paginas`.
     */
    private function denyUnlessAllowed(Request $request): ?JsonResponse
    {
        if ($request->user()?->hasPageUrl('paginas')) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Tu usuario no tiene la pagina requerida para esta operacion.',
        ], 403);
    }

    /**
     * Forma publica de una pagina, con los datos que usa la tabla web.
     */
    private function payload(Page $page): array
    {
        $usersCount = (int) ($page->users_count ?? 0);

        return [
            'id' => $page->id,
            'description' => $page->description,
            'url' => $page->url,
            'module_id' => $page->module_id,
            'module' => $page->module ? [
                'id' => $page->module->id,
                'description' => $page->module->description,
            ] : null,
            'users_count' => $usersCount,
            'can_delete' => $usersCount === 0,
        ];
    }
}
