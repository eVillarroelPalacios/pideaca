<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithServiceOrders;
use App\Models\Order;
use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Panel unificado "Ordenes y Gestion" del prestador.
 *
 * Reune en una sola vista las ordenes de servicio (modulo Hogar) y los
 * pedidos del comercio: la pagina del menu ya no tiene dos pantallas
 * separadas ("Ordenes de Servicio" y "Gestionar Solicitudes").
 *
 * - index()  entra al dashboard con la seccion abierta.
 * - data()   devuelve el feed unificado que alimenta la seccion
 *            (tabs, busqueda por cliente o ID y filtros rapidos).
 */
class OrdenesGestionController extends Controller
{
    use InteractsWithServiceOrders;

    /**
     * Estado de una orden de servicio -> pestania del panel.
     */
    private const SERVICE_GROUP = [
        ServiceOrder::STATUS_REQUESTED => 'pending',
        ServiceOrder::STATUS_QUOTED => 'pending',
        ServiceOrder::STATUS_ACCEPTED => 'confirmed',
        ServiceOrder::STATUS_PROVIDER_DISPATCHED => 'confirmed',
        ServiceOrder::STATUS_IN_PROGRESS => 'confirmed',
        ServiceOrder::STATUS_COMPLETED => 'completed',
        ServiceOrder::STATUS_CANCELLED => 'cancelled',
    ];

    /**
     * Estado de un pedido de comercio -> pestania del panel.
     * "Entregado" cuenta como completado, igual que COMPLETED.
     */
    private const COMMERCE_GROUP = [
        Order::STATUS_PENDING => 'pending',
        Order::STATUS_CONFIRMED => 'confirmed',
        Order::STATUS_IN_PREPARATION => 'confirmed',
        Order::STATUS_ON_THE_WAY => 'confirmed',
        Order::STATUS_DELIVERED => 'completed',
        Order::STATUS_CANCELLED => 'cancelled',
    ];

    private const GROUPS = ['all', 'pending', 'confirmed', 'completed', 'cancelled'];

    /**
     * GET /dashboard/ordenes-gestion
     *
     * Camino directo a la pagina: exige la permision de la pagina (con
     * redirect propio, porque el middleware page: devuelve JSON) y deja el
     * dashboard listo con la seccion visible.
     */
    public function index()
    {
        if (! Auth::check()) {
            return redirect('/');
        }

        if (! Auth::user()->hasPageUrl('ordenes-gestion')) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('dashboard', ['section' => 'ordenes-gestion']);
    }

    /**
     * GET /api/v1/provider/ordenes-gestion
     *
     * Feed unificado: mas recientes primero, acotado al propio comercio +
     * pool de sus rubros (servicios) y solo pedidos propios (comercio).
     *
     * Parametros:
     * - group:   pestania (all/pending/confirmed/completed/cancelled).
     * - q:       busqueda por cliente, numero/ID o descripcion del rubro.
     * - quick:   filtro rapido (hoy/ultimos 7 dias/30 dias/emergencias/
     *            presupuestos).
     * - per_page: tope de ordenes devueltas (default 100).
     *
     * counts refleja todos los grupos con el mismo filtro de busqueda y
     * filtro rapido, para que las pestanas siempre muestren sus totales.
     */
    public function data(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'group' => ['nullable', 'string', 'in:'.implode(',', self::GROUPS)],
            'q' => ['nullable', 'string', 'max:120'],
            'quick' => ['nullable', 'string', 'in:all,today,week,month,emergency,quote'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $group = $data['group'] ?? 'all';
        $quick = $data['quick'] ?? 'all';
        $search = trim((string) ($data['q'] ?? ''));
        $perPage = (int) ($data['per_page'] ?? 100);

        // "Emergencias"/"Presupuestos" son tipos de servicio: el comercio
        // no tiene tipo, asi que en esos filtros solo entran servicios.
        $serviceOnly = in_array($quick, ['emergency', 'quote'], true);

        $serviceBase = $this->serviceQuery($provider, $search, $quick, $group);
        $commerceBase = $serviceOnly ? null : $this->commerceQuery($provider, $search, $quick, $group);

        // Los contadores se calculan sin el filtro de pestania para que
        // cada pestania muestre su total con la busqueda actual.
        $counts = $this->counts(
            $this->serviceQuery($provider, $search, $quick, 'all'),
            $commerceBase ? $this->commerceQuery($provider, $search, $quick, 'all') : null
        );

        $serviceOrders = $serviceBase->latest()->limit($perPage)->get();
        $commerceOrders = $commerceBase ? $commerceBase->latest()->limit($perPage)->get() : collect();

        $items = $serviceOrders
            ->map(fn (ServiceOrder $order) => [
                'source' => 'service',
                'group' => self::SERVICE_GROUP[$order->status] ?? 'pending',
                'created_at' => $order->created_at?->format('U.u') ?? '0',
                'raw' => $this->orderPayload($order),
            ])
            ->concat($commerceOrders->map(fn (Order $order) => [
                'source' => 'commerce',
                'group' => self::COMMERCE_GROUP[$order->status] ?? 'pending',
                'created_at' => $order->created_at?->format('U.u') ?? '0',
                'raw' => $order,
            ]))
            ->sortByDesc('created_at')
            ->values()
            ->take($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'provider' => [
                    'id' => $provider->id,
                    'business_name' => $provider->business_name,
                ],
                'items' => $items,
                'counts' => $counts,
                'total' => $counts[$group] ?? 0,
            ],
        ]);
    }

    /**
     * Ordenes de servicio del comercio (o del pool de sus rubros) con los
     * filtros de pestania, busqueda y filtro rapido ya aplicados.
     *
     * @return Builder<ServiceOrder>
     */
    private function serviceQuery($provider, string $search, string $quick, string $group)
    {
        $statuses = $group === 'all' ? [] : $this->statusesFor(self::SERVICE_GROUP, $group);

        return ServiceOrder::query()
            ->forProvider($provider)
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->when($quick === 'emergency', fn ($query) => $query->ofType(ServiceOrder::TYPE_EMERGENCY))
            ->when($quick === 'quote', fn ($query) => $query->ofType(ServiceOrder::TYPE_QUOTE))
            ->when($quick === 'today', fn ($query) => $query->where('created_at', '>=', Carbon::today()))
            ->when($quick === 'week', fn ($query) => $query->where('created_at', '>=', Carbon::today()->subDays(6)))
            ->when($quick === 'month', fn ($query) => $query->where('created_at', '>=', Carbon::today()->subDays(29)))
            ->when($search !== '', function ($query) use ($search) {
                $needle = '%'.$search.'%';

                $query->where(function ($inner) use ($needle, $search) {
                    $inner->where('service_orders.id', ctype_digit($search) ? (int) $search : -1)
                        ->orWhereHas('customer', fn ($customer) => $customer
                            ->where('name', 'ilike', $needle)
                            ->orWhere('email', 'ilike', $needle)
                            ->orWhere('phone', 'ilike', $needle))
                        ->orWhereHas('subGroup', fn ($subgroup) => $subgroup
                            ->where('description', 'ilike', $needle))
                        ->orWhere('address_street', 'ilike', $needle)
                        ->orWhere('description', 'ilike', $needle);
                });
            });
    }

    /**
     * Pedidos del comercio con los mismos filtros. Null cuando el filtro
     * rapido es por tipo de servicio (emergencias/presupuestos).
     *
     * @return Builder<Order>|null
     */
    private function commerceQuery($provider, string $search, string $quick, string $group)
    {
        if ($quick === 'emergency' || $quick === 'quote') {
            return null;
        }

        $statuses = $group === 'all' ? [] : $this->statusesFor(self::COMMERCE_GROUP, $group);

        return Order::query()
            ->where('provider_id', $provider->id)
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->when($quick === 'today', fn ($query) => $query->where('created_at', '>=', Carbon::today()))
            ->when($quick === 'week', fn ($query) => $query->where('created_at', '>=', Carbon::today()->subDays(6)))
            ->when($quick === 'month', fn ($query) => $query->where('created_at', '>=', Carbon::today()->subDays(29)))
            ->when($search !== '', function ($query) use ($search) {
                $needle = '%'.$search.'%';

                $query->where(function ($inner) use ($needle, $search) {
                    $inner->where('orders.id', ctype_digit($search) ? (int) $search : -1)
                        ->orWhere('order_number', 'ilike', $needle)
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'ilike', $needle)
                            ->orWhere('email', 'ilike', $needle)
                            ->orWhere('phone', 'ilike', $needle))
                        ->orWhere('notes', 'ilike', $needle);
                });
            })
            ->with([
                'user:id,name,email,phone',
                'items.options',
                'address:id,street,number,floor_apartment,postal_code,notes',
                'payments:id,order_id,provider,status,amount,created_at',
                'driver:id,name,phone,email',
                'deliveryUser:id,name,phone,email',
            ]);
    }

    /**
     * Estados agrupados por pestania para filtrar en base.
     *
     * @return array<int, string>
     */
    private function statusesFor(array $map, string $group): array
    {
        return collect($map)
            ->filter(fn ($itemGroup) => $itemGroup === $group)
            ->keys()
            ->all();
    }

    /**
     * Conteo por pestania sobre las mismas bases sin filtro de grupo.
     *
     * @param  Builder<ServiceOrder>  $serviceBase
     * @param  Builder<Order>|null  $commerceBase
     * @return array<string, int>
     */
    private function counts($serviceBase, $commerceBase): array
    {
        $counts = ['all' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];

        $serviceCounts = (clone $serviceBase)
            ->groupBy('status')
            ->selectRaw('status, count(*) as total')
            ->pluck('total', 'status');

        foreach ($serviceCounts as $status => $total) {
            $group = self::SERVICE_GROUP[$status] ?? null;
            $counts['all'] += (int) $total;
            if ($group) {
                $counts[$group] += (int) $total;
            }
        }

        if ($commerceBase) {
            $commerceCounts = (clone $commerceBase)
                ->groupBy('status')
                ->selectRaw('status, count(*) as total')
                ->pluck('total', 'status');

            foreach ($commerceCounts as $status => $total) {
                $group = self::COMMERCE_GROUP[$status] ?? null;
                $counts['all'] += (int) $total;
                if ($group) {
                    $counts[$group] += (int) $total;
                }
            }
        }

        return $counts;
    }
}
