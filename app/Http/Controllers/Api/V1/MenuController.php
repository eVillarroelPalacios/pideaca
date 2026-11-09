<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Menú del usuario para la app móvil.
 *
 * Espeja la lógica del navbar web (DashboardController@index) sin tocarla:
 * solo páginas asignadas directo al usuario en page_user (misma fuente que
 * "Asignar Permisos de Páginas"), módulos vacíos afuera y "Administrar"
 * al final. Cada url web se traduce a pantalla móvil en lib/menuRoutes.ts;
 * las páginas sin pantalla se muestran como "Próximamente".
 */
class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user()->loadMissing(['status', 'typeUser']);

        // El Admin puede ver el menu de otra cuenta (modo gestionado de la
        // web: navbar filtrado por las paginas del usuario gestionado).
        $user = $this->resolveManagedUser($request, $actor);

        if ($user instanceof JsonResponse) {
            return $user;
        }

        // Candidatos del menú: ÚNICAMENTE páginas asignadas directo al
        // usuario (paridad estricta con el navbar web).
        $menuUserIds = [$user->id];

        $modules = Module::with(['pages' => function ($query) use ($menuUserIds) {
            $query->whereHas('users', function ($sub) use ($menuUserIds) {
                $sub->whereIn('users.id', $menuUserIds);
            });
            $query->orderBy('description');
        }])->orderBy('description')->get()->filter(function ($module) {
            return $module->pages->isNotEmpty();
        });

        // Filtrado estricto por id, igual que la web.
        $allowedIds = $user->pages()->pluck('pages.id')->all();
        $modules = $modules->map(function ($module) use ($allowedIds) {
            $module->setRelation('pages', $module->pages->filter(function ($page) use ($allowedIds) {
                return in_array($page->id, $allowedIds, true);
            })->values());

            return $module;
        })->filter(function ($module) {
            return $module->pages->isNotEmpty();
        })->values();

        // "Administrar" siempre al final, como en el navbar web.
        $modules = $modules->sortBy(function ($module) {
            return $module->description === 'Administrar' ? 1 : 0;
        })->values();

        $firstPageUrl = null;

        foreach ($modules as $module) {
            foreach ($module->pages as $page) {
                if (! empty($page->url)) {
                    $firstPageUrl = $page->url;
                    break 2;
                }
            }
        }

        return response()->json([
            'success' => true,
            'managed_user' => (int) $user->id === (int) $actor->id ? null : [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'data' => [
                'modules' => $modules->map(function ($module) {
                    return [
                        'id' => $module->id,
                        'description' => $module->description,
                        'pages' => $module->pages->map(function ($page) {
                            return [
                                'id' => $page->id,
                                'description' => $page->description,
                                'url' => $page->url,
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
                'first_page_url' => $firstPageUrl,
            ],
        ]);
    }

    /**
     * Usuario efectivo del menu: el gestionado si el Admin lo pide con
     * `managed_user_id`, si no el propio actor. Devuelve la respuesta de
     * error (403/404) cuando corresponde.
     */
    private function resolveManagedUser(Request $request, $actor)
    {
        $managedId = $request->query('managed_user_id');

        if ($managedId === null || $managedId === '') {
            return $actor;
        }

        if (! $actor->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo un administrador puede gestionar otra cuenta.',
            ], 403);
        }

        $managed = \App\Models\User::find($managedId);

        if (! $managed) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        return $managed->loadMissing(['status', 'typeUser']);
    }
}
