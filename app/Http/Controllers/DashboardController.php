<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Group;
use App\Models\Module;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Raiz del proyecto: panel para usuarios autenticados y landing publica
     * para visitantes. La URL del panel deja de tener el segmento /dashboard.
     */
    public function home()
    {
        if (! Auth::check()) {
            return app(AdvertisingController::class)->index();
        }

        return $this->index();
    }

    public function index()
    {
        if (!Auth::check()) {
            return redirect('/');
        }

        $user = Auth::user()->load(['status', 'typeUser']);

        // Rol efectivo de la cuenta activa: gestionada (?managed=, solo Admin)
        // tiene prioridad absoluta sobre el usuario logueado.
        $activeRole = strtolower(trim((string) ($user->typeUser?->description ?? '')));
        if ($user->isAdmin()) {
            $managedId = request()->query('managed');
            if ($managedId) {
                $managedForRole = User::with('typeUser')->find($managedId);
                if ($managedForRole && $managedForRole->typeUser) {
                    $activeRole = strtolower(trim((string) $managedForRole->typeUser->description));
                }
            }
        }

        // Navbar según usuario en gestión (?managed=, solo Admin): el servidor
        // es fuente de verdad y omite del DOM los módulos sin páginas habilitadas.
        $navUser = $user;
        if ($user->isAdmin() && request()->query('managed')) {
            $managedUser = User::find(request()->query('managed'));
            if ($managedUser) {
                $navUser = $managedUser->loadMissing(['status', 'typeUser']);
            }
        }

        // Candidatos del menú: ÚNICAMENTE páginas asignadas directo al usuario
        // en navbar (misma fuente que "Asignar Permisos de Páginas").
        $menuUserIds = [$navUser->id];

        $modules = Module::with(['pages' => function ($q) use ($menuUserIds) {
            $q->whereHas('users', function ($q2) use ($menuUserIds) {
                $q2->whereIn('users.id', $menuUserIds);
            });
            $q->orderBy('description');
        }])->orderBy('description')->get()->filter(function ($module) {
            return $module->pages->isNotEmpty();
        });

        // Filtrado estricto por id, también para el Admin: el módulo solo
        // existe si tiene al menos 1 página asignada; en el dropdown solo
        // van las páginas asignadas.
        $allowedIds = $navUser->pages()->pluck('pages.id')->all();
        $modules = $modules->map(function ($module) use ($allowedIds) {
            $module->setRelation('pages', $module->pages->filter(function ($page) use ($allowedIds) {
                return in_array($page->id, $allowedIds, true);
            })->values());

            return $module;
        })->filter(function ($module) {
            return $module->pages->isNotEmpty();
        })->values();

        // Catálogo completo para "Asignar Permisos de Páginas" (el Admin lo
        // necesita aunque su propio navbar esté filtrado por sus páginas).
        $permissionModules = $user->isAdmin()
            ? Module::with(['pages' => function ($q) { $q->orderBy('description'); }])->orderBy('description')->get()
            : $modules;

        $footerGroups = Group::active()->orderBy('id')->get();

        // Primera página autorizada: destino del guard de paneles de negocio.
        $firstPageUrl = null;
        foreach ($modules as $module) {
            foreach ($module->pages as $page) {
                if (! empty($page->url)) {
                    $firstPageUrl = $page->url;
                    break 2;
                }
            }
        }

        return view('dashboard', [
            'user' => $user,
            'modules' => $modules,
            'footerGroups' => $footerGroups,
            'firstPageUrl' => $firstPageUrl,
            'permissionModules' => $permissionModules,
            'activeRole' => $activeRole,
        ]);
    }
}