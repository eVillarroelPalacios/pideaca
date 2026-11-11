<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\InteractsWithServiceOrders;
use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\ServiceRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Reportes del Módulo Hogar para la app móvil.
 *
 * Espeja la consulta y los KPIs de ReporteHogarController (web):
 * - Cliente: total gastado, señas en custodia, finalizados, cancelaciones
 *   y reputación de las reseñas que dejó.
 * - Prestador: total facturado, señas, efectividad de cotizaciones,
 *   trabajos pendientes/en proceso, finalizados, cancelaciones y reputación
 *   recibida.
 *
 * Devuelve las órdenes completas para que la app arme las tarjetas de
 * detalle sin una segunda llamada.
 */
class ReportsController extends Controller
{
    use InteractsWithServiceOrders;

    public function hogar(Request $request): JsonResponse
    {
        $user = $request->user();
        $isPrestador = strcasecmp((string) ($user->typeUser?->description ?? ''), 'Prestador') === 0;

        $data = $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'estado' => ['nullable', 'string', 'in:'.implode(',', array_merge(ServiceOrder::STATUSES, ServiceOrder::DISPLAY_STATUSES))],
            'tipo' => ['nullable', 'string', 'in:'.implode(',', ServiceOrder::ORDER_TYPES)],
        ]);

        $query = ServiceOrder::query()->with([
            'customer:id,name,email,phone',
            'merchant.user:id,name,email,phone',
            'subGroup:id,description',
            'ratings',
            'quotes',
        ]);

        if ($isPrestador) {
            $provider = $user->provider;

            if (! $provider) {
                return $this->emptyResponse(true, $request);
            }

            $query->where('merchant_id', $provider->id);
        } else {
            $query->where('customer_id', $user->id);
        }

        if (! empty($data['fecha_desde'])) {
            $query->where('created_at', '>=', Carbon::parse($data['fecha_desde'])->startOfDay());
        }

        if (! empty($data['fecha_hasta'])) {
            $query->where('created_at', '<=', Carbon::parse($data['fecha_hasta'])->endOfDay());
        }

        if (! empty($data['estado'])) {
            $query->whereIn('status', ServiceOrder::legacyStatusesFor($data['estado']));
        }

        if (! empty($data['tipo'])) {
            $query->where('order_type', $data['tipo']);
        }

        $orders = $query->latest()->get();

        $completed = $orders->where('status', ServiceOrder::STATUS_COMPLETED);
        $cancelados = $orders->where('status', ServiceOrder::STATUS_CANCELLED)->count();

        if ($isPrestador) {
            $totalEconomico = $completed->sum(fn ($order) => (float) ($order->total_amount ?? 0));
            $senasCustodia = $completed->sum(fn ($order) => (float) ($order->deposit_amount ?? 0));
            $totalQuotes = $orders->where('order_type', ServiceOrder::TYPE_QUOTE)->count();
            $acceptedQuotes = $orders
                ->where('order_type', ServiceOrder::TYPE_QUOTE)
                ->where('status', ServiceOrder::STATUS_COMPLETED)
                ->count();

            $kpis = [
                'total_economico' => round((float) $totalEconomico, 2),
                'senas_custodia' => round((float) $senasCustodia, 2),
                'pendientes' => $orders->whereIn('status', [ServiceOrder::STATUS_REQUESTED, ServiceOrder::STATUS_PENDING])->count(),
                'en_proceso' => $orders->whereIn('status', [
                    ServiceOrder::STATUS_ACCEPTED,
                    ServiceOrder::STATUS_PROVIDER_DISPATCHED,
                    ServiceOrder::STATUS_IN_PROGRESS,
                    ServiceOrder::STATUS_QUOTED,
                ])->count(),
                'completados' => $completed->count(),
                'cancelados' => $cancelados,
                'efectividad_cotizaciones' => $totalQuotes > 0
                    ? round(($acceptedQuotes / $totalQuotes) * 100, 1)
                    : 0,
                'promedio_reputacion' => 0.0,
                'total_resenas' => 0,
            ];

            $ratings = ServiceRating::query()->where('ratee_id', $user->id)->get();
        } else {
            $totalGastado = $completed->sum(fn ($order) => (float) ($order->total_amount ?? 0));

            // El backend usa HOLDING/RELEASED/REFUNDED. Se aceptan además los
            // valores heredados (HELD/DEPOSITED) por compatibilidad con datos
            // viejos, igual que la intención del reporte web.
            $senasCustodia = $orders
                ->whereIn('escrow_status', ['HOLDING', 'HELD', 'DEPOSITED'])
                ->sum(fn ($order) => (float) ($order->deposit_amount ?? 0));

            $kpis = [
                'total_economico' => round((float) $totalGastado, 2),
                'senas_custodia' => round((float) $senasCustodia, 2),
                'completados' => $completed->count(),
                'cancelados' => $cancelados,
                'promedio_reputacion' => 0.0,
                'total_resenas' => 0,
            ];

            $ratings = ServiceRating::query()->where('reviewer_id', $user->id)->get();
        }

        // La reputación se calcula sobre todas las reseñas del usuario, sin
        // depender de los filtros de fecha/estado (igual que la web).
        $kpis['promedio_reputacion'] = $ratings->count() > 0
            ? round((float) $ratings->avg('stars'), 1)
            : 0.0;
        $kpis['total_resenas'] = $ratings->count();

        return response()->json([
            'success' => true,
            'is_prestador' => $isPrestador,
            'kpis' => $kpis,
            'data' => $orders
                ->map(fn (ServiceOrder $order) => $this->orderPayload($order))
                ->values()
                ->all(),
            'filters' => [
                'statuses' => ServiceOrder::STATUSES,
                'order_types' => ServiceOrder::ORDER_TYPES,
                'estado' => $data['estado'] ?? null,
                'tipo' => $data['tipo'] ?? null,
                'fecha_desde' => $data['fecha_desde'] ?? null,
                'fecha_hasta' => $data['fecha_hasta'] ?? null,
            ],
            'meta' => [
                'total' => $orders->count(),
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Respuesta vacía para un prestador sin comercio asociado: la app muestra
     * los KPIs en cero y el estado vacío, igual que la vista web.
     */
    private function emptyResponse(bool $isPrestador, Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'is_prestador' => $isPrestador,
            'kpis' => $isPrestador
                ? [
                    'total_economico' => 0,
                    'senas_custodia' => 0,
                    'pendientes' => 0,
                    'en_proceso' => 0,
                    'completados' => 0,
                    'cancelados' => 0,
                    'efectividad_cotizaciones' => 0,
                    'promedio_reputacion' => 0,
                    'total_resenas' => 0,
                ]
                : [
                    'total_economico' => 0,
                    'senas_custodia' => 0,
                    'completados' => 0,
                    'cancelados' => 0,
                    'promedio_reputacion' => 0,
                    'total_resenas' => 0,
                ],
            'data' => [],
            'filters' => [
                'statuses' => ServiceOrder::STATUSES,
                'order_types' => ServiceOrder::ORDER_TYPES,
                'estado' => $request->query('estado'),
                'tipo' => $request->query('tipo'),
                'fecha_desde' => $request->query('fecha_desde'),
                'fecha_hasta' => $request->query('fecha_hasta'),
            ],
            'meta' => [
                'total' => 0,
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
        ]);
    }
}
