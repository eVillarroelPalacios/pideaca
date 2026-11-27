<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Module;
use App\Models\SiteVisit;
use App\Models\User;
use App\Support\ProviderMenuFilter;
use Illuminate\Support\Facades\Auth;

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
        if (! Auth::check()) {
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

        // Categorías del prestador: oculta módulos de grupos no elegidos.
        // Se usa el usuario efectivo (gestionado si el Admin lo pide).
        $modules = ProviderMenuFilter::filter($navUser, $modules);

        // Catálogo completo para "Asignar Permisos de Páginas" (el Admin lo
        // necesita aunque su propio navbar esté filtrado por sus páginas).
        $permissionModules = $user->isAdmin()
            ? Module::with(['pages' => function ($q) {
                $q->orderBy('description');
            }])->orderBy('description')->get()
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

        // Estadisticas de visitas al sitio: solo se calculan si el usuario
        // ve "Consultas y sugerencias" (evita 7 agregados en cada dashboard
        // que no usa el modal). La vista usa ?? como fallback.
        $visitStats = null;
        if ($modules->flatMap->pages->contains('url', 'consultas-sugerencias')) {
            $visitStats = $this->visitStats();
        }

        // Panel con sesion: nunca se cachea en el navegador. Asi cada visita
        // trae el HTML fresco (con los ?v= vigentes) y no quedan vistas
        // viejas pegadas en moviles.
        return response()->view('dashboard', [
            'user' => $user,
            'modules' => $modules,
            'footerGroups' => $footerGroups,
            'firstPageUrl' => $firstPageUrl,
            'permissionModules' => $permissionModules,
            'activeRole' => $activeRole,
            'visitStats' => $visitStats,
            'androidApkUrl' => config('app.android_apk_url'),
            'androidApkVersion' => config('app.android_version_name'),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * KPIs y rangos (Hoy / 7 dias / 30 dias) para el modal de visitas.
     * Paises ordenados de mayor a menor con % real, total y promedio
     * diario; mas serie diaria con ceros en dias sin trafico.
     */
    protected function visitStats(): array
    {
        $today = today()->toDateString();
        $total = (int) SiteVisit::count();
        $todayCount = (int) SiteVisit::onDay($today)->count();
        // Dias con trafico (normaliza Y-m-d vs Y-m-d H:i:s segun driver).
        $dayKeys = SiteVisit::query()->pluck('visited_on')->map(
            fn ($value) => $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d')
                : substr((string) $value, 0, 10)
        )->unique();
        $days = max(1, $dayKeys->count());

        return [
            'total' => $total,
            'today' => $todayCount,
            'dailyAvg' => (int) round($total / $days),
            'ranges' => [
                'today' => $this->visitRange($today, $today, $total, $days),
                'd7' => $this->visitRange(today()->subDays(6)->toDateString(), $today, $total, $days),
                'd30' => $this->visitRange(today()->subDays(29)->toDateString(), $today, $total, $days),
            ],
        ];
    }

    protected function visitRange(string $from, string $to, int $total, int $days): array
    {
        $rows = SiteVisit::query()
            ->selectRaw("COALESCE(NULLIF(country_code, ''), 'OT') as code, MAX(country_name) as name, COUNT(*) as visits")
            ->whereDate('visited_on', '>=', $from)
            ->whereDate('visited_on', '<=', $to)
            ->groupBy('code')
            ->orderByDesc('visits')
            ->get();

        $palette = ['#ea580c', '#0c2a4d', '#f97316', '#1e40af', '#0d9488', '#7c3aed', '#db2777', '#65a30d'];
        $knownNames = ['MX' => 'México', 'ES' => 'España', 'AR' => 'Argentina', 'CO' => 'Colombia', 'OT' => 'Otros'];
        $countries = [];
        foreach ($rows as $i => $row) {
            $code = strtoupper((string) ($row->code ?: 'OT'));
            $countries[] = [
                'code' => $code,
                'name' => $row->name ?: ($knownNames[$code] ?? $code),
                'flag' => $this->flagFor($code),
                'color' => $palette[$i % count($palette)],
                'visits' => (int) $row->visits,
                'pct' => $total > 0 ? (int) round(((int) $row->visits / $total) * 100) : 0,
                'dailyAvg' => (int) round(((int) $row->visits) / $days),
            ];
        }

        // Top 5 + resto agrupado en Otros para donut y lista compactos.
        if (count($countries) > 5) {
            $top = array_slice($countries, 0, 5);
            $rest = array_slice($countries, 5);
            $restVisits = array_sum(array_column($rest, 'visits'));
            $top[] = [
                'code' => 'OT',
                'name' => 'Otros',
                'flag' => $this->flagFor('OT'),
                'color' => end($palette),
                'visits' => $restVisits,
                'pct' => $total > 0 ? (int) round($restVisits / $total * 100) : 0,
                'dailyAvg' => (int) round($restVisits / $days),
            ];
            $countries = $top;
        }

        // Serie diaria con ceros (agrupa en PHP para no depender del
        // formato de fecha que devuelva cada driver).
        $byDay = [];
        SiteVisit::query()
            ->whereDate('visited_on', '>=', $from)
            ->whereDate('visited_on', '<=', $to)
            ->pluck('visited_on')
            ->each(function ($value) use (&$byDay) {
                $key = $value instanceof \DateTimeInterface
                    ? $value->format('Y-m-d')
                    : substr((string) $value, 0, 10);
                $byDay[$key] = ($byDay[$key] ?? 0) + 1;
            });

        $series = [];
        $cursor = $from;
        while ($cursor <= $to) {
            $series[] = [
                'label' => date('d/m', strtotime($cursor)),
                'visits' => (int) ($byDay[$cursor] ?? 0),
            ];
            $cursor = date('Y-m-d', strtotime($cursor.' +1 day'));
        }

        return ['countries' => $countries, 'days' => $series];
    }

    private function flagFor(string $code): string
    {
        if ($code === 'OT') {
            return '🌍';
        }

        $flag = '';
        foreach (str_split($code) as $ch) {
            $flag .= mb_chr(127397 + ord($ch));
        }

        return $flag;
    }
}
