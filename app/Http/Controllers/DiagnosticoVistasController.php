<?php

namespace App\Http\Controllers;

use App\Models\DriverWallet;
use App\Models\Order;
use App\Models\Page;
use App\Models\Provider;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class DiagnosticoVistasController extends Controller
{
    /**
     * Simula el ciclo completo de un pedido dentro de una transaccion que
     * SIEMPRE se revierte: crear -> asignar repartidor -> entregar y acreditar.
     * No persiste nada en la base de datos real.
     */
    public function testFlujoSimulado()
    {
        $this->autorizarAdministrador();

        $pasos = [];
        $respuesta = [];

        DB::beginTransaction();

        try {
            $cliente = $this->usuarioParaRol('cliente');
            $comercio = $this->usuarioParaRol('comercio');
            $repartidor = $this->usuarioParaRol('repartidor');

            if (! $cliente) {
                throw new \RuntimeException('No se encontró un usuario representativo con rol Cliente.');
            }

            if (! $repartidor) {
                throw new \RuntimeException('No se encontró un usuario representativo con rol Repartidor.');
            }

            $provider = null;

            if ($comercio) {
                $provider = Provider::query()->where('user_id', $comercio->id)->first();
            }

            $provider = $provider ?: Provider::query()->orderBy('id')->first();

            if (! $provider) {
                throw new \RuntimeException('No hay ningún comercio (provider) registrado para asociar el pedido de prueba.');
            }

            // 1. Crear pedido (POST).
            $pedido = Order::create([
                'provider_id' => $provider->id,
                'user_id' => $cliente->id,
                'order_number' => Order::nextNumber(),
                'status' => Order::STATUS_PENDING,
                'subtotal' => 1000.00,
                'delivery_fee' => 500.00,
                'discount' => 0.00,
                'total' => 1500.00,
                'payment_method' => 'cash',
                'delivery_type' => Order::DELIVERY_TYPE_DELIVERY,
                'delivery_status' => 'pending',
                'notes' => 'Pedido de prueba generado por el centro de diagnóstico.',
            ]);

            if (! $pedido->exists) {
                throw new \RuntimeException('El pedido de prueba no pudo crearse.');
            }

            $pasos[] = [
                'paso' => 1,
                'accion' => 'Crear pedido',
                'status' => 'success',
                'detalle' => 'Pedido ' . $pedido->order_number . ' creado en estado "' . $pedido->status . '".',
            ];

            // 2. El repartidor toma el pedido (PUT / cambio de estado).
            $pedido->update([
                'driver_id' => $repartidor->id,
                'delivery_status' => 'assigned',
            ]);
            $pedido->refresh();

            if ((int) $pedido->driver_id !== (int) $repartidor->id) {
                throw new \RuntimeException('El repartidor no quedó asignado al pedido.');
            }

            $pasos[] = [
                'paso' => 2,
                'accion' => 'Repartidor toma el pedido',
                'status' => 'success',
                'detalle' => 'Asignado al repartidor #' . $repartidor->id . ' (delivery_status: ' . $pedido->delivery_status . ').',
            ];

            // 3. El repartidor finaliza la entrega y se acredita la tarifa.
            $tarifa = (float) ($pedido->delivery_fee > 0 ? $pedido->delivery_fee : 500.00);

            $pedido->update([
                'delivery_status' => 'delivered',
                'status' => Order::STATUS_DELIVERED,
            ]);

            $wallet = DriverWallet::firstOrCreate(
                ['user_id' => $repartidor->id],
                ['balance' => 0, 'pending_balance' => 0]
            );

            $wallet->balance = (float) $wallet->balance + $tarifa;
            $wallet->save();

            WalletTransaction::create([
                'user_id' => $repartidor->id,
                'order_id' => $pedido->id,
                'amount' => $tarifa,
                'type' => 'credit',
                'status' => 'completed',
                'description' => 'Pago por entrega de pedido #' . $pedido->order_number . ' (prueba)',
            ]);

            $pedido->refresh();

            if ($pedido->status !== Order::STATUS_DELIVERED) {
                throw new \RuntimeException('El pedido no quedó en estado "delivered" al finalizar.');
            }

            $pasos[] = [
                'paso' => 3,
                'accion' => 'Repartidor finaliza la entrega',
                'status' => 'success',
                'detalle' => 'Estado "' . $pedido->status . '". Ganancia acreditada: $'
                    . number_format($tarifa, 2) . ' (billetera #' . $wallet->id . ').',
            ];

            $respuesta = [
                'status' => 'success',
                'mensaje' => 'Ciclo completo verificado. La transacción se revirtió: ningún dato quedó guardado.',
                'pasos' => $pasos,
                'resumen' => [
                    'pedido' => $pedido->order_number,
                    'estado_final' => $pedido->status,
                    'repartidor_id' => $repartidor->id,
                    'tarifa' => $tarifa,
                    'rollback' => true,
                ],
            ];
        } catch (Throwable $e) {
            $respuesta = [
                'status' => 'error',
                'mensaje' => $e->getMessage(),
                'error' => [
                    'mensaje' => $e->getMessage(),
                    'archivo' => $e->getFile(),
                    'linea' => $e->getLine(),
                ],
                'pasos' => $pasos,
            ];
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }

        return response()->json($respuesta);
    }

    /**
     * Ejecuta una vista como si la pidiera otro rol, solo en memoria.
     * Se autentica con Auth::onceUsingId() (no persiste en sesion) y se
     * restaura el usuario original al terminar. Estrictamente lectura.
     */
    public function auditar(Request $request)
    {
        $this->autorizarAdministrador();

        $url = (string) $request->query('url', '/');
        $rol = (string) $request->query('rol', '');

        $esInterna = str_starts_with($url, '/')
            && ! str_starts_with($url, '//')
            && ! str_contains($url, '://')
            && ! str_starts_with($url, '/admin/diagnostico-vistas');

        if (! $esInterna) {
            return response('URL no válida para auditoría.', 400)
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $usuarioOriginal = Auth::user();
        $representante = $this->usuarioParaRol($rol);

        if ($representante) {
            Auth::onceUsingId($representante->id);
        }

        $peticionOriginal = app('request');
        $driverOriginal = config('session.driver');

        // Evita que el subrequest escriba sesiones en base de datos.
        config(['session.driver' => 'array']);

        try {
            $respuesta = app()->handle(Request::create($url, 'GET'));

            $salida = response($respuesta->getContent(), $respuesta->getStatusCode())
                ->header('Content-Type', $respuesta->headers->get('Content-Type') ?: 'text/html; charset=UTF-8');

            if ($ubicacion = $respuesta->headers->get('Location')) {
                $salida->header('Location', $ubicacion);
            }

            return $salida;
        } catch (Throwable $e) {
            $detalle = htmlspecialchars(
                $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine(),
                ENT_QUOTES,
                'UTF-8'
            );

            return response(
                '<!doctype html><html><head><meta charset="utf-8"><title>Error 500</title></head>'
                . '<body style="font-family:system-ui;padding:32px;background:#fef2f2;color:#991b1b;">'
                . '<h1 style="margin:0 0 12px;">Error al renderizar la vista</h1>'
                . '<pre style="white-space:pre-wrap;font-size:13px;">' . $detalle . '</pre>'
                . '</body></html>',
                500
            )->header('Content-Type', 'text/html; charset=UTF-8');
        } finally {
            app()->instance('request', $peticionOriginal);
            config(['session.driver' => $driverOriginal]);

            if ($usuarioOriginal) {
                Auth::setUser($usuarioOriginal);
            }
        }
    }

    /**
     * El panel simula roles para leer cualquier página, por eso solo un
     * administrador puede abrirlo. No altera la autenticación existente.
     */
    private function autorizarAdministrador(): void
    {
        $usuario = Auth::user();
        $tipo = mb_strtolower((string) optional($usuario?->typeUser)->description);
        $rol = mb_strtolower((string) ($usuario?->role ?? ''));

        $esAdmin = $usuario !== null
            && (Str::contains($tipo, ['admin', 'administrador'])
                || Str::contains($rol, ['admin', 'administrador']));

        abort_unless($esAdmin, 403, 'El centro de diagnóstico es solo para administradores.');
    }

    private function usuarioParaRol(string $rol): ?User
    {
        $roles = [
            'cliente' => ['Cliente', 'cliente'],
            'comercio' => ['Prestador', 'Comercio', 'Comerciante', 'prestador', 'comercio'],
            'repartidor' => ['Repartidor', 'repartidor'],
            'admin' => ['Admin', 'Administrador', 'admin', 'administrador'],
        ];

        $alias = $roles[$rol] ?? null;

        if ($alias === null) {
            return null;
        }

        try {
            if (Schema::hasTable('type_users') && Schema::hasColumn('users', 'type_user_id')) {
                $usuario = User::query()
                    ->whereHas('typeUser', fn ($query) => $query->whereIn('description', $alias))
                    ->orderBy('id')
                    ->first();

                if ($usuario) {
                    return $usuario;
                }
            }

            if (Schema::hasColumn('users', 'role')) {
                return User::query()->whereIn('role', $alias)->orderBy('id')->first();
            }
        } catch (Throwable $e) {
            // Sin acceso a usuarios: se audita con la sesion actual.
        }

        return null;
    }
    /**
     * Panel de diagnostico en vivo. Solo lee la tabla de paginas para armar
     * el listado; las peticiones de auditoria las hace el navegador por GET.
     */
    public function index()
    {
        $this->autorizarAdministrador();

        return view('admin.diagnostico-vistas', [
            'paginas' => $this->paginas(),
        ]);
    }

    private function paginas(): array
    {
        $filas = [];

        try {
            $pages = Page::with(['module', 'typeUsers'])
                ->orderBy('module_id')
                ->orderBy('description')
                ->get();

            foreach ($pages as $page) {
                $path = $this->normalizePath((string) $page->url);

                if ($path === null) {
                    continue;
                }

                $esRuta = $this->isRegisteredRoute($path);
                $slug = trim($path, '/');

                $filas[] = [
                    'modulo' => $page->module->description ?? 'General',
                    'pagina' => $page->description ?: $path,
                    'url' => $esRuta ? $path : '/?section=' . rawurlencode($slug),
                    'section' => $esRuta ? null : $slug,
                    'rol' => $this->roleForPage($page),
                ];
            }
        } catch (Throwable $e) {
            // Sin base disponible: el panel sigue funcionando con las rutas clave.
        }

        foreach ($this->rutasClave() as $clave) {
            $duplicada = collect($filas)->contains(
                fn ($fila) => $fila['url'] === $clave['url'] && $fila['rol'] === $clave['rol']
            );

            if (! $duplicada) {
                $filas[] = $clave;
            }
        }

        return $filas;
    }

    private function rutasClave(): array
    {
        return [
            ['modulo' => 'Público', 'pagina' => 'Inicio / Dashboard', 'url' => '/', 'section' => null, 'rol' => 'cliente'],
            ['modulo' => 'Mercado', 'pagina' => 'Comercios', 'url' => '/?section=comercios', 'section' => 'comercios', 'rol' => 'cliente'],
            ['modulo' => 'Mercado', 'pagina' => 'Mis Pedidos', 'url' => '/?section=mis-pedidos', 'section' => 'mis-pedidos', 'rol' => 'cliente'],
            ['modulo' => 'Mercado', 'pagina' => 'Pedidos', 'url' => '/?section=pedidos', 'section' => 'pedidos', 'rol' => 'comercio'],
            ['modulo' => 'Mercado', 'pagina' => 'Catálogo', 'url' => '/?section=mi-catalogo', 'section' => 'mi-catalogo', 'rol' => 'comercio'],
            ['modulo' => 'Mercado', 'pagina' => 'Inventario', 'url' => '/?section=inventario', 'section' => 'inventario', 'rol' => 'comercio'],
            ['modulo' => 'Mercado', 'pagina' => 'Salud financiera', 'url' => '/?section=finanzas', 'section' => 'finanzas', 'rol' => 'comercio'],
            ['modulo' => 'Mercado', 'pagina' => 'Retención', 'url' => '/?section=retencion', 'section' => 'retencion', 'rol' => 'comercio'],
            ['modulo' => 'Mercado', 'pagina' => 'Suscripciones', 'url' => '/?section=suscripciones', 'section' => 'suscripciones', 'rol' => 'comercio'],
            ['modulo' => 'Mercado', 'pagina' => 'Mis Suscripciones', 'url' => '/?section=mis-suscripciones', 'section' => 'mis-suscripciones', 'rol' => 'cliente'],
            ['modulo' => 'Hogar', 'pagina' => 'Solicitudes', 'url' => '/?section=solicitar-servicio', 'section' => 'solicitar-servicio', 'rol' => 'cliente'],
            ['modulo' => 'Hogar', 'pagina' => 'Mis servicios', 'url' => '/?section=mis-servicios', 'section' => 'mis-servicios', 'rol' => 'cliente'],
            ['modulo' => 'Hogar', 'pagina' => 'Órdenes y Gestión', 'url' => '/?section=ordenes-gestion', 'section' => 'ordenes-gestion', 'rol' => 'comercio'],
            ['modulo' => 'Hogar', 'pagina' => 'Reportes Hogar', 'url' => '/reportes-hogar', 'section' => null, 'rol' => 'comercio'],
            ['modulo' => 'Reparto', 'pagina' => 'Repartidor', 'url' => '/?section=repartidor', 'section' => 'repartidor', 'rol' => 'repartidor'],
            ['modulo' => 'Reparto', 'pagina' => 'Reportes Repartidor', 'url' => '/repartidor/reportes', 'section' => null, 'rol' => 'repartidor'],
            ['modulo' => 'Reparto', 'pagina' => 'Vehículo del repartidor', 'url' => '/repartidor/vehiculo', 'section' => null, 'rol' => 'repartidor'],
            ['modulo' => 'Administrar', 'pagina' => 'Páginas', 'url' => '/?section=paginas', 'section' => 'paginas', 'rol' => 'admin'],
            ['modulo' => 'Administrar', 'pagina' => 'Usuarios', 'url' => '/?section=usuarios', 'section' => 'usuarios', 'rol' => 'admin'],
            ['modulo' => 'Administrar', 'pagina' => 'Panel de administración', 'url' => '/admin', 'section' => null, 'rol' => 'admin'],
        ];
    }

    private function roleForPage(Page $page): string
    {
        $descripciones = $page->typeUsers
            ->pluck('description')
            ->map(fn ($descripcion) => mb_strtolower((string) $descripcion));

        if ($descripciones->contains(fn ($d) => str_contains($d, 'repartidor'))) {
            return 'repartidor';
        }

        if ($descripciones->contains(fn ($d) => str_contains($d, 'prestador') || str_contains($d, 'comercio'))) {
            return 'comercio';
        }

        if ($descripciones->contains(fn ($d) => str_contains($d, 'cliente'))) {
            return 'cliente';
        }

        return 'admin';
    }

    private function isRegisteredRoute(string $path): bool
    {
        foreach (app('router')->getRoutes() as $route) {
            $uri = '/' . ltrim($route->uri(), '/');

            if (str_contains($uri, '{')) {
                continue;
            }

            if ($uri === $path) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            $url = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
        }

        $url = preg_split('/[?#]/', $url, 2)[0] ?? $url;

        return '/' . ltrim($url, '/');
    }
}
