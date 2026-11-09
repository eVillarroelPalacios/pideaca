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
        $user = $request->user()->loadMissing(['status', 'typeUser']);

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
}
