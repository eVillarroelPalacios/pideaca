<?php

namespace App\Console\Commands;

use App\Models\Pagina;
use App\Models\Page;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class TestVistasCommand extends Command
{
    protected $signature = 'test:vistas {--url= : Probar solo una URL específica} {--rol= : Probar solo un rol específico}';

    protected $description = 'Audita automáticamente todas las vistas, páginas y rutas esenciales del sistema.';

    /** Descripciones de TypeUser que representan cada rol del sistema. */
    private const ROLE_ALIASES = [
        'cliente' => ['Cliente', 'cliente'],
        'comercio' => ['Prestador', 'Comercio', 'Comerciante', 'prestador', 'comercio'],
        'repartidor' => ['Repartidor', 'repartidor'],
        'admin' => ['Admin', 'Administrador', 'admin', 'administrador'],
    ];

    /** Prioridad al elegir el rol con el que se evalúa una página compartida. */
    private const ROLE_PRIORITY = ['repartidor', 'comercio', 'cliente', 'admin'];

    public function handle(): int
    {
        // El test debe ser de solo lectura: una sesión en memoria evita que
        // StartSession escriba en el driver de sesión configurado (por ejemplo,
        // la tabla sessions de la base de datos).
        config(['session.driver' => 'array']);

        $targetUrl = $this->normalizePath($this->option('url'));
        $targetRole = $this->normalizeRole($this->option('rol'));

        if ($this->option('rol') && $targetRole === null) {
            $this->error('Rol no válido. Usá: cliente, comercio, repartidor o admin.');

            return self::FAILURE;
        }

        $this->info('Auditando vistas del sistema...');
        $this->line('Usuario de cada rol se autentica solo en memoria; no se persisten cambios.');
        $this->newLine();

        $candidates = array_merge($this->collectDatabasePages(), $this->fallbackRoutes());
        $candidates = $this->filterCandidates($candidates, $targetUrl, $targetRole);

        if (empty($candidates)) {
            $this->warn('No hay páginas o rutas que coincidan con los filtros indicados.');

            return self::SUCCESS;
        }

        $representatives = $this->representativeUsers();
        $currentRole = null;
        $rows = [];
        $ok = 0;
        $warnings = 0;
        $failed = 0;

        foreach ($candidates as $candidate) {
            if ($currentRole !== $candidate['role']) {
                Auth::forgetGuards();

                if (isset($representatives[$candidate['role']])) {
                    Auth::login($representatives[$candidate['role']]);
                }

                $currentRole = $candidate['role'];
            }

            $result = $this->performRequest($candidate['url']);
            [$diagnostic, $level] = $this->diagnose($result, $candidate);

            if ($level === 'ok') {
                $ok++;
            } elseif ($level === 'warning') {
                $warnings++;
            } else {
                $failed++;
            }

            $rows[] = [
                $candidate['name'],
                $candidate['url'],
                $this->roleLabel($candidate['role']),
                (string) $result['status'],
                $diagnostic,
            ];
        }

        Auth::forgetGuards();

        $this->table(
            ['Página / Módulo', 'URL', 'Rol Evaluado', 'HTTP Status', 'Diagnóstico / Detalle del Error'],
            $rows
        );

        $this->newLine();
        $this->table(['Resumen', 'Cantidad'], [
            ['Total evaluadas', count($rows)],
            ['Exitosas [OK]', $ok],
            ['Con advertencias [ADVERTENCIA]', $warnings],
            ['Fallidas [ERROR]', $failed],
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Lee dinámicamente las páginas registradas (Page o Pagina / pages o
     * paginas) sin asumir nombres de columnas en español o inglés.
     */
    private function collectDatabasePages(): array
    {
        try {
            $table = $this->resolvePagesTable();

            if (! $table) {
                return [];
            }

            $columns = Schema::getColumnListing($table);
            $urlColumn = $this->firstColumn($columns, ['url']);

            if (! $urlColumn) {
                return [];
            }

            $descriptionColumn = $this->firstColumn($columns, ['description', 'descripcion', 'nombre']);
            $moduleColumn = $this->firstColumn($columns, ['modulo', 'modulo_id', 'module_id']);

            $moduleNames = [];
            if (in_array('module_id', $columns, true) && Schema::hasTable('modules')) {
                $moduleNames = DB::table('modules')->pluck('description', 'id')->all();
            }

            $pageRoles = $this->pageRolesByPivot();
            $pages = [];

            foreach (DB::table($table)->get() as $row) {
                $url = trim((string) ($row->{$urlColumn} ?? ''));

                if ($url === '') {
                    continue;
                }

                $description = $descriptionColumn ? trim((string) ($row->{$descriptionColumn} ?? '')) : '';
                $module = '';

                if ($moduleColumn) {
                    $moduleValue = $row->{$moduleColumn} ?? null;

                    if ($moduleColumn === 'module_id' && isset($moduleNames[$moduleValue])) {
                        $module = (string) $moduleNames[$moduleValue];
                    } elseif ($moduleValue !== null && $moduleValue !== '') {
                        $module = (string) $moduleValue;
                    }
                }

                if ($module !== '' && ! is_numeric($module)) {
                    $name = $description !== '' ? $module . ' · ' . $description : $module;
                } else {
                    $name = $description !== '' ? $description : $url;
                }

                $path = (string) $this->normalizePath($url);
                $isRoute = $this->isRegisteredRoute($path);

                $pages[] = [
                    'name' => $name,
                    'url' => $isRoute ? $path : '/?section=' . rawurlencode(trim($path, '/')),
                    'surface' => 'dashboard',
                    'slug' => $path,
                    'section' => $isRoute ? null : trim($path, '/'),
                    'role' => $pageRoles[$row->id] ?? 'admin',
                ];
            }

            return $pages;
        } catch (Throwable $e) {
            $this->warn('No se pudieron leer las páginas de la base de datos: ' . $e->getMessage());

            return [];
        }
    }

    private function resolvePagesTable(): ?string
    {
        foreach ([Page::class, Pagina::class] as $model) {
            if (! class_exists($model)) {
                continue;
            }

            try {
                $table = (new $model)->getTable();

                if (Schema::hasTable($table)) {
                    return $table;
                }
            } catch (Throwable $e) {
                // Modelo sin conexión disponible: se prueba la siguiente opción.
            }
        }

        foreach (['pages', 'paginas'] as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    /**
     * Rol de cada página según page_type_user / type_users. Si una página la
     * comparten varios roles, gana el más específico (repartidor > comercio >
     * cliente > admin) para no repetir la misma URL.
     */
    private function pageRolesByPivot(): array
    {
        try {
            if (! Schema::hasTable('page_type_user') || ! Schema::hasTable('type_users')) {
                return [];
            }

            $assignments = DB::table('page_type_user')
                ->join('type_users', 'type_users.id', '=', 'page_type_user.type_user_id')
                ->select(['page_type_user.page_id', 'type_users.description'])
                ->get();

            $map = [];

            foreach ($assignments as $assignment) {
                $role = $this->roleFromDescription($assignment->description);

                if ($role === null) {
                    continue;
                }

                $current = $map[$assignment->page_id] ?? null;

                if ($current === null || $this->roleRank($role) < $this->roleRank($current)) {
                    $map[$assignment->page_id] = $role;
                }
            }

            return $map;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function roleFromDescription(?string $description): ?string
    {
        $description = mb_strtolower(trim((string) $description));

        if (Str::contains($description, ['repartidor', 'delivery', 'driver'])) {
            return 'repartidor';
        }

        if (Str::contains($description, ['prestador', 'comercio', 'comerciante'])) {
            return 'comercio';
        }

        if (Str::contains($description, ['cliente', 'customer'])) {
            return 'cliente';
        }

        if (Str::contains($description, ['admin'])) {
            return 'admin';
        }

        return null;
    }

    private function roleRank(string $role): int
    {
        $position = array_search($role, self::ROLE_PRIORITY, true);

        return $position === false ? PHP_INT_MAX : $position;
    }

    /** Rutas mínimas que deben auditarse aunque no existan en la tabla pages. */
    private function fallbackRoutes(): array
    {
        return [
            ['name' => 'Inicio / Dashboard', 'url' => '/', 'role' => 'cliente'],
            ['name' => 'Comercios (cliente)', 'url' => '/comercios', 'role' => 'cliente'],
            ['name' => 'Catálogo del comercio', 'url' => '/comercio/catalogo', 'role' => 'comercio'],
            ['name' => 'Solicitudes del comercio', 'url' => '/solicitudes', 'role' => 'comercio'],
            ['name' => 'Panel del repartidor', 'url' => '/repartidor', 'role' => 'repartidor'],
            ['name' => 'Reportes del repartidor', 'url' => '/repartidor/reportes', 'role' => 'repartidor'],
            ['name' => 'Vehículo del repartidor', 'url' => '/repartidor/vehiculo', 'role' => 'repartidor'],
            ['name' => 'Páginas (admin)', 'url' => '/administrar/paginas', 'role' => 'admin'],
            ['name' => 'Panel de administración', 'url' => '/admin', 'role' => 'admin'],
        ];
    }

    /**
     * Indica si el path corresponde a una ruta GET real. Las páginas del panel
     * guardan slugs (comercios, finanzas, ...): esas se auditan como secciones
     * del dashboard, no como URLs sueltas que devolverían 404.
     */
    private function isRegisteredRoute(string $path): bool
    {
        static $registered = null;

        if ($registered === null) {
            $registered = [];

            foreach (app('router')->getRoutes() as $route) {
                $uri = '/' . ltrim($route->uri(), '/');

                if (str_contains($uri, '{')) {
                    continue;
                }

                $registered[$uri] = true;
            }
        }

        $path = '/' . ltrim($path, '/');

        return isset($registered[$path]) || isset($registered[rtrim($path, '/') ?: '/']);
    }

    /** Busca un usuario representativo por rol (solo lectura). */
    private function representativeUsers(): array
    {
        $users = [];

        foreach (self::ROLE_ALIASES as $role => $aliases) {
            try {
                $user = null;

                if (Schema::hasTable('type_users') && Schema::hasColumn('users', 'type_user_id')) {
                    $user = User::query()
                        ->whereHas('typeUser', fn ($query) => $query->whereIn('description', $aliases))
                        ->orderBy('id')
                        ->first();
                }

                if (! $user && Schema::hasColumn('users', 'role')) {
                    $user = User::query()
                        ->whereIn('role', $aliases)
                        ->orderBy('id')
                        ->first();
                }

                if ($user) {
                    $users[$role] = $user;
                } else {
                    $this->warn("No se encontró un usuario representativo para el rol {$role}; se probará como invitado.");
                }
            } catch (Throwable $e) {
                $this->warn("No se pudo resolver el usuario del rol {$role}: " . $e->getMessage());
            }
        }

        return $users;
    }

    private function filterCandidates(array $candidates, ?string $targetUrl, ?string $targetRole): array
    {
        $filtered = [];
        $seen = [];

        foreach ($candidates as $candidate) {
            $candidatePath = $this->normalizePath($candidate['url']);

            if ($targetUrl !== null) {
                $matchesUrl = $candidate['url'] === $targetUrl
                    || (isset($candidate['slug']) && $candidate['slug'] === $targetUrl)
                    || ($targetUrl !== '/' && $candidatePath === $targetUrl);

                if (! $matchesUrl) {
                    continue;
                }
            }

            if ($targetRole !== null && $candidate['role'] !== $targetRole) {
                continue;
            }

            $key = $candidate['role'] . '|' . $candidate['url'];

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $filtered[] = $candidate;
        }

        return $filtered;
    }

    /**
     * Ejecuta el GET dentro del kernel HTTP. Para poder reportar el archivo y
     * la línea originales, se reemplaza temporalmente el manejador de
     * excepciones por uno que relanza el Throwable en vez de pintar el 500.
     *
     * @return array{status:int, html:string, error:?Throwable}
     */
    private function performRequest(string $url): array
    {
        $originalHandler = app(ExceptionHandler::class);

        $rethrowHandler = new class(app()) extends Handler {
            public function report(Throwable $e)
            {
            }

            public function shouldReport(Throwable $e)
            {
                return false;
            }

            public function render($request, Throwable $e)
            {
                throw $e;
            }

            public function renderForConsole($output, Throwable $e)
            {
                throw $e;
            }
        };

        app()->instance(ExceptionHandler::class, $rethrowHandler);

        try {
            $request = Request::create($url, 'GET');
            $response = app()->handle($request);

            return [
                'status' => $response->getStatusCode(),
                'html' => (string) $response->getContent(),
                'error' => null,
            ];
        } catch (Throwable $e) {
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            return [
                'status' => $status,
                'html' => '',
                'error' => $e,
            ];
        } finally {
            app()->instance(ExceptionHandler::class, $originalHandler);
        }
    }

    /**
     * @return array{0:string, 1:string} Diagnóstico formateado y nivel.
     */
    private function diagnose(array $result, array $candidate = []): array
    {
        $status = (int) $result['status'];

        if ($result['error'] instanceof Throwable) {
            $error = $result['error'];
            $detail = sprintf(
                '%s | %s:%d',
                $error->getMessage(),
                $error->getFile(),
                $error->getLine()
            );

            return ['<fg=red>[ERROR] ' . $this->cleanDetail($detail) . '</>', 'error'];
        }

        if (in_array($status, [403, 404, 500], true) || $status >= 500) {
            $snippet = $this->cleanDetail(strip_tags((string) $result['html']));

            return [
                '<fg=red>[ERROR] HTTP ' . $status . ($snippet !== '' ? ' | ' . Str::limit($snippet, 180) : '') . '</>',
                'error',
            ];
        }

        if ($status === 200) {
            $html = (string) $result['html'];
            $section = $candidate['section'] ?? null;

            if ($section !== null && $section !== '') {
                // Una pagina del panel se audita dentro de su propia seccion:
                // asi el placeholder de otra seccion oculta no da falso positivo.
                $fragment = $this->extractSection($html, (string) $section);

                if ($fragment === null) {
                    return [
                        '<fg=yellow>[ADVERTENCIA] La sección "' . $section . '" no se renderizó en el dashboard (¿permisos o página sin sección?).</>',
                        'warning',
                    ];
                }

                $html = $fragment;
            } elseif (($candidate['url'] ?? '') === '/') {
                // El dashboard incluye secciones ocultas de relleno: se ignoran
                // para no marcar "Próximamente" cuando la vista visible cargó.
                $html = $this->stripHiddenSections($html);
            }

            if (trim($html) === '' || mb_strlen($html) < 300) {
                return ['<fg=yellow>[ADVERTENCIA] Vista vacía o incompleta (HTML de ' . mb_strlen($html) . ' caracteres).</>', 'warning'];
            }

            // El JS inline del dashboard contiene textos como 'Próximamente'
            // que no son un placeholder de la vista: se ignora script/style.
            $html = $this->stripScripts($html);

            $placeholder = $this->detectPlaceholder($html);

            if ($placeholder !== null) {
                return ['<fg=yellow>[ADVERTENCIA] Contiene texto de marcador de posición: "' . $placeholder . '".</>', 'warning'];
            }

            return ['<fg=green>[OK] Vista cargada correctamente.</>', 'ok'];
        }

        return [
            '<fg=yellow>[ADVERTENCIA] HTTP ' . $status . ' (redirección o respuesta no esperada).</>',
            'warning',
        ];
    }

    private function extractSection(string $html, string $slug): ?string
    {
        $pattern = '/<section\b[^>]*id="dash-' . preg_quote($slug, '/') . '"[^>]*>.*?<\/section>/is';

        return preg_match($pattern, $html, $matches) === 1 ? $matches[0] : null;
    }

    private function stripHiddenSections(string $html): string
    {
        $pattern = '/<section\b[^>]*style="[^"]*display\s*:\s*none[^"]*"[^>]*>.*?<\/section>/is';

        return preg_replace($pattern, '', $html) ?? $html;
    }

    private function stripScripts(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;

        return preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html) ?? $html;
    }

    private function detectPlaceholder(string $html): ?string
    {
        $needles = ['Próximamente', 'Proximamente', 'Todavia no tiene seccion', 'Todavía no tiene sección'];

        foreach ($needles as $needle) {
            if (mb_stripos($html, $needle) !== false) {
                return $needle;
            }
        }

        if (str_contains($html, 'PRONTO') || preg_match('/>\s*pronto\s*</i', $html) === 1) {
            return 'PRONTO';
        }

        return null;
    }

    private function cleanDetail(string $detail): string
    {
        $detail = preg_replace('/\s+/', ' ', $detail) ?? $detail;
        $detail = str_replace(['<', '>'], ['‹', '›'], $detail);

        return Str::limit(trim($detail), 600);
    }

    private function normalizePath(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            $url = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
        }

        // Quita query string o fragmento: el comando audita GETs limpios.
        $url = preg_split('/[?#]/', $url, 2)[0] ?? $url;

        return '/' . ltrim($url, '/');
    }

    private function normalizeRole(?string $role): ?string
    {
        $role = mb_strtolower(trim((string) $role));

        if ($role === '') {
            return null;
        }

        return match ($role) {
            'prestador', 'comerciante', 'comercios' => 'comercio',
            'administrador', 'administracion', 'administración' => 'admin',
            'repartidores', 'delivery' => 'repartidor',
            'clientes' => 'cliente',
            default => isset(self::ROLE_ALIASES[$role]) ? $role : null,
        };
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'cliente' => 'Cliente',
            'comercio' => 'Comercio',
            'repartidor' => 'Repartidor',
            'admin' => 'Administrador',
            default => ucfirst($role),
        };
    }

    private function firstColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
