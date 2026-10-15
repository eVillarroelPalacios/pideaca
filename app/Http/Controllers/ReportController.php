<?php

namespace App\Http\Controllers;

use App\Models\CustomerSubscription;
use App\Models\MarketingLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Provider;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Reportes del negocio para Prestador (Merchant) y Cliente (Customer).
 *
 * Cada metodo arma un corte de lectura listo para la interfaz: numeros
 * resumidos, semaforos y recomendaciones, sin paginar el detalle crudo
 * (para eso estan los listados operativos).
 */
class ReportController extends Controller
{
    /**
     * GET /api/v1/provider/reports/margins
     *
     * Rentabilidad por producto: costo de insumos (ficha tecnica) vs. precio
     * de venta. Devuelve margen bruto en pesos y porcentaje, semaforo de
     * riesgo y una recomendacion de precio cuando el margen queda corto.
     */
    public function getMarginAndRecipeCost(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'product_id' => ['nullable', 'integer'],
        ]);

        $query = Product::where('provider_id', $provider->id)
            ->with('recipeItems.supply.unitOfMeasure')
            ->orderBy('name');

        if (isset($data['product_id'])) {
            $query->where('id', $data['product_id']);
        }

        $productos = $query->get();

        $filas = $productos->map(function (Product $product) {
            $costo = $product->calculateCost();
            $precio = (float) $product->price;
            $margenPorcentaje = $product->calculateProfitMargin();
            $margenPesos = $precio > 0 ? round($precio - $costo, 2) : null;

            $semaforo = $this->marginTrafficLight($margenPorcentaje);
            $recomendacion = $this->priceRecommendation($precio, $costo, $margenPorcentaje);

            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'sale_price' => $precio,
                'production_cost' => $costo,
                'gross_margin_amount' => $margenPesos,
                'gross_margin_percent' => $margenPorcentaje,
                'has_recipe' => $product->recipeItems->isNotEmpty(),
                'traffic_light' => $semaforo['level'],
                'traffic_light_label' => $semaforo['label'],
                'recommendation' => $recomendacion,
            ];
        });

        $conMargen = $filas->filter(fn (array $f) => $f['gross_margin_percent'] !== null);
        $promedio = $conMargen->isNotEmpty()
            ? round($conMargen->avg('gross_margin_percent'), 2)
            : null;

        return response()->json([
            'success' => true,
            'summary' => [
                'total_products' => $filas->count(),
                'with_recipe' => $filas->filter(fn (array $f) => $f['has_recipe'])->count(),
                'green' => $filas->filter(fn (array $f) => $f['traffic_light'] === 'green')->count(),
                'yellow' => $filas->filter(fn (array $f) => $f['traffic_light'] === 'yellow')->count(),
                'red' => $filas->filter(fn (array $f) => $f['traffic_light'] === 'red')->count(),
                'average_margin_percent' => $promedio,
            ],
            'products' => $filas->all(),
        ]);
    }

    /**
     * GET /api/v1/provider/reports/mrr
     *
     * Ingreso recurrente mensual de las suscripciones fijas: MRR actual,
     * proyeccion a 30 dias, suscriptores activos vs. cancelados (churn) y
     * ticket promedio por ciclo.
     */
    public function getMRRAndSubscriptions(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'period' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $meses = $data['period'] ?? 1;

        $subscriptions = CustomerSubscription::where('provider_id', $provider->id)
            ->with('plan')
            ->get();

        $porEstado = $subscriptions->groupBy('status')->map->count();

        $activas = $subscriptions
            ->filter(fn (CustomerSubscription $s) => $s->status === CustomerSubscription::STATUS_ACTIVE)
            ->values();

        $canceladas = (int) ($porEstado[CustomerSubscription::STATUS_CANCELLED] ?? 0);
        $totales = $subscriptions->count();
        $churnRate = $totales > 0 ? round($canceladas / $totales * 100, 2) : 0.0;

        $mrr = round($activas->sum(fn (CustomerSubscription $s) => $this->mrrDe($s)), 2);
        $proyeccion30 = round($mrr, 2);

        $porPlan = $activas
            ->groupBy('subscription_plan_id')
            ->map(function (Collection $delPlan, $planId) {
                $plan = $delPlan->first()->plan;

                return [
                    'plan_id' => (int) $planId,
                    'title' => $plan?->title,
                    'frequency' => $plan?->frequency,
                    'subscribers' => $delPlan->count(),
                    'charge_amount' => (float) ($plan?->chargeAmount() ?? 0),
                    'mrr' => round($delPlan->sum(fn (CustomerSubscription $s) => $this->mrrDe($s)), 2),
                ];
            })
            ->values();

        // Ticket promedio: lo que aporta cada suscriptor activo por ciclo.
        $ticketPromedio = $activas->isNotEmpty()
            ? round($activas->avg(fn (CustomerSubscription $s) => $s->plan?->chargeAmount() ?? 0), 2)
            : 0.0;

        $corte = now()->subDays(30 * $meses);
        $cobradoPeriodo = (float) SubscriptionPayment::where('provider_id', $provider->id)
            ->where('status', SubscriptionPayment::STATUS_PAID)
            ->where('paid_at', '>=', $corte)
            ->sum('total');

        return response()->json([
            'success' => true,
            'summary' => [
                'mrr' => $mrr,
                'projection_30_days' => $proyeccion30,
                'active_subscribers' => (int) ($porEstado[CustomerSubscription::STATUS_ACTIVE] ?? 0),
                'paused_subscribers' => (int) ($porEstado[CustomerSubscription::STATUS_PAUSED] ?? 0),
                'payment_failed' => (int) ($porEstado[CustomerSubscription::STATUS_PAYMENT_FAILED] ?? 0),
                'cancelled_subscribers' => $canceladas,
                'churn_rate_percent' => $churnRate,
                'average_ticket' => $ticketPromedio,
                'collected_in_period' => round($cobradoPeriodo, 2),
                'period_months' => $meses,
            ],
            'by_plan' => $porPlan->all(),
        ]);
    }

    /**
     * GET /api/v1/provider/reports/commission-savings
     *
     * Compara lo que cuesta operar en PideAca con el costo estimado de una app
     * tradicional (25%-30% de comision) y devuelve el ahorro neto en dinero.
     */
    public function getCommissionSavings(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $inicio = isset($data['start_date'])
            ? Carbon::parse($data['start_date'])
            : now()->subDays(30)->startOfDay();
        $fin = isset($data['end_date'])
            ? Carbon::parse($data['end_date'])->endOfDay()
            : now();

        $pideacaRate = (float) config('financials.pideaca_commission_percent', 10);
        $tradicionalMin = (float) config('financials.traditional_app_min_percent', 25);
        $tradicionalMax = (float) config('financials.traditional_app_max_percent', 30);

        // Ingreso neto del comercio en el rango: pedidos entregados y cobros
        // de suscripcion pagados. Es la base sobre la que se calcula la comision.
        $ventasPedidos = (float) Order::where('provider_id', $provider->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereBetween('created_at', [$inicio, $fin])
            ->sum('total');

        $ventasSuscripciones = (float) SubscriptionPayment::where('provider_id', $provider->id)
            ->where('status', SubscriptionPayment::STATUS_PAID)
            ->whereBetween('paid_at', [$inicio, $fin])
            ->sum('total');

        $volumen = round($ventasPedidos + $ventasSuscripciones, 2);

        $comisionPideAca = round($volumen * $pideacaRate / 100, 2);
        $comisionTradicionalMin = round($volumen * $tradicionalMin / 100, 2);
        $comisionTradicionalMax = round($volumen * $tradicionalMax / 100, 2);

        $ahorroVsMin = round($comisionTradicionalMin - $comisionPideAca, 2);
        $ahorroVsMax = round($comisionTradicionalMax - $comisionPideAca, 2);

        return response()->json([
            'success' => true,
            'period' => [
                'start_date' => $inicio->toDateString(),
                'end_date' => $fin->toDateString(),
            ],
            'summary' => [
                'gross_volume' => $volumen,
                'orders_revenue' => round($ventasPedidos, 2),
                'subscriptions_revenue' => round($ventasSuscripciones, 2),
                'pideaca_commission_percent' => $pideacaRate,
                'pideaca_commission_amount' => $comisionPideAca,
                'traditional_min_percent' => $tradicionalMin,
                'traditional_min_amount' => $comisionTradicionalMin,
                'traditional_max_percent' => $tradicionalMax,
                'traditional_max_amount' => $comisionTradicionalMax,
                'net_savings_vs_min' => $ahorroVsMin,
                'net_savings_vs_max' => $ahorroVsMax,
                'savings_note' => 'El ahorro es la diferencia entre la comision de una app tradicional y la de PideAca sobre el mismo volumen.',
            ],
        ]);
    }

    /**
     * GET /api/v1/provider/reports/ltv-risk
     *
     * Clientes VIP por valor de vida (LTV) y clientes en riesgo: compraron
     * dos o mas veces pero llevan mas de 20 dias sin pedir.
     */
    public function getLTVAndAtRiskCustomers(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'inactive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'vip_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $diasInactividad = $data['inactive_days'] ?? 20;
        $vipLimit = $data['vip_limit'] ?? 20;
        $umbralRiesgo = now()->subDays($diasInactividad);

        $pedidos = Order::where('provider_id', $provider->id)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->with('user:id,name,email')
            ->get(['id', 'user_id', 'total', 'status', 'created_at']);

        $porCliente = $pedidos->groupBy('user_id');

        $clientes = $porCliente->map(function (Collection $pedidosCliente, $userId) {
            $user = $pedidosCliente->first()->user;
            $ltv = round($pedidosCliente->sum(fn (Order $o) => (float) $o->total), 2);
            $ultimaCompra = $pedidosCliente->max(fn (Order $o) => $o->created_at);

            return [
                'user_id' => (int) $userId,
                'name' => $user?->name,
                'email' => $user?->email,
                'orders_count' => $pedidosCliente->count(),
                'ltv' => $ltv,
                'last_order_at' => $ultimaCompra?->toDateTimeString(),
                'days_since_last_order' => $ultimaCompra
                    ? (int) $ultimaCompra->diffInDays(now())
                    : null,
            ];
        })->values();

        $vip = $clientes
            ->sortByDesc('ltv')
            ->take($vipLimit)
            ->values();

        $enRiesgo = $clientes
            ->filter(fn (array $c) => $c['orders_count'] >= 2
                && $c['last_order_at'] !== null
                && Carbon::parse($c['last_order_at'])->lt($umbralRiesgo))
            ->sortByDesc('ltv')
            ->values();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_customers' => $clientes->count(),
                'vip_count' => $vip->count(),
                'at_risk_count' => $enRiesgo->count(),
                'inactive_days_threshold' => $diasInactividad,
                'vip_ltv_floor' => (float) ($vip->last()['ltv'] ?? 0),
            ],
            'vip_customers' => $vip->all(),
            'at_risk_customers' => $enRiesgo->all(),
        ]);
    }

    /**
     * GET /api/v1/provider/reports/campaigns
     *
     * Conversion y ventas atribuidas a las campanas o mensajes automaticos de
     * WhatsApp: envios, fallos, conversiones y tasa por regla.
     */
    public function getCampaignPerformance(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'campaign_id' => ['nullable', 'integer'],
        ]);

        $logs = MarketingLog::where('provider_id', $provider->id)
            ->with('rule:id,rule_type,discount_code')
            ->get(['id', 'provider_id', 'user_id', 'campaign_rule_id', 'sent_at', 'status']);

        if (isset($data['campaign_id'])) {
            $logs = $logs->filter(
                fn (MarketingLog $log) => (int) $log->campaign_rule_id === (int) $data['campaign_id']
            )->values();
        }

        $porRegla = $logs->groupBy('campaign_rule_id')->map(function (Collection $logsRegla, $ruleId) {
            $rule = $logsRegla->first()->rule;

            $enviados = $logsRegla->filter(fn (MarketingLog $l) => $l->status !== MarketingLog::STATUS_FAILED)->count();
            $fallidos = $logsRegla->filter(fn (MarketingLog $l) => $l->status === MarketingLog::STATUS_FAILED)->count();
            $convertidos = $logsRegla->filter(fn (MarketingLog $l) => $l->status === MarketingLog::STATUS_CONVERTED)->count();

            $tasa = $enviados > 0 ? round($convertidos / $enviados * 100, 2) : 0.0;

            // Ventas atribuidas: pedidos no cancelados de los usuarios que
            // recibieron la regla, posteriores al envio.
            $userIds = $logsRegla
                ->filter(fn (MarketingLog $l) => $l->status !== MarketingLog::STATUS_FAILED)
                ->pluck('user_id')
                ->unique()
                ->filter()
                ->all();

            $ventas = 0.0;

            if ($userIds !== []) {
                $primerEnvio = $logsRegla->where('status', '!=', MarketingLog::STATUS_FAILED)->min('sent_at');

                $ventas = (float) Order::where('provider_id', $provider->id)
                    ->whereIn('user_id', $userIds)
                    ->where('status', '!=', Order::STATUS_CANCELLED)
                    ->when($primerEnvio, fn ($q) => $q->where('created_at', '>=', $primerEnvio))
                    ->sum('total');
            }

            return [
                'rule_id' => $ruleId === null ? null : (int) $ruleId,
                'rule_type' => $rule?->rule_type,
                'discount_code' => $rule?->discount_code,
                'sent' => $enviados,
                'failed' => $fallidos,
                'converted' => $convertidos,
                'conversion_rate_percent' => $tasa,
                'attributed_sales' => round($ventas, 2),
            ];
        })->values();

        $totalEnviados = $porRegla->sum('sent');
        $totalConvertidos = $porRegla->sum('converted');

        return response()->json([
            'success' => true,
            'summary' => [
                'total_sent' => $totalEnviados,
                'total_failed' => $porRegla->sum('failed'),
                'total_converted' => $totalConvertidos,
                'overall_conversion_rate_percent' => $totalEnviados > 0
                    ? round($totalConvertidos / $totalEnviados * 100, 2)
                    : 0.0,
                'attributed_sales_total' => round($porRegla->sum('attributed_sales'), 2),
            ],
            'campaigns' => $porRegla->all(),
        ]);
    }

    /**
     * GET /api/v1/provider/reports/demand-heatmap
     *
     * Matriz de 7 dias x 24 horas con la demanda (pedidos Express vs. pedidos
     * de Suscripciones) para planificar horarios y personal de cocina.
     */
    public function getDemandHeatmap(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $dias = $data['days'] ?? 30;
        $desde = now()->subDays($dias);

        $pedidos = Order::where('provider_id', $provider->id)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->where('created_at', '>=', $desde)
            ->get(['id', 'customer_subscription_id', 'total', 'created_at']);

        $matriz = [];

        for ($dia = 0; $dia < 7; $dia++) {
            for ($hora = 0; $hora < 24; $hora++) {
                $matriz[$dia][$hora] = ['express' => 0, 'subscriptions' => 0, 'total' => 0, 'revenue' => 0.0];
            }
        }

        foreach ($pedidos as $pedido) {
            $dia = (int) $pedido->created_at->dayOfWeekIso - 1;
            $hora = (int) $pedido->created_at->format('G');
            $tipo = $pedido->customer_subscription_id !== null ? 'subscriptions' : 'express';

            $matriz[$dia][$hora][$tipo]++;
            $matriz[$dia][$hora]['total']++;
            $matriz[$dia][$hora]['revenue'] = round($matriz[$dia][$hora]['revenue'] + (float) $pedido->total, 2);
        }

        $nombresDias = [
            0 => 'Lunes', 1 => 'Martes', 2 => 'Miércoles', 3 => 'Jueves',
            4 => 'Viernes', 5 => 'Sábado', 6 => 'Domingo',
        ];

        $filas = [];

        for ($dia = 0; $dia < 7; $dia++) {
            $horas = [];

            for ($hora = 0; $hora < 24; $hora++) {
                $horas[] = [
                    'hour' => $hora,
                    'express' => $matriz[$dia][$hora]['express'],
                    'subscriptions' => $matriz[$dia][$hora]['subscriptions'],
                    'total' => $matriz[$dia][$hora]['total'],
                    'revenue' => $matriz[$dia][$hora]['revenue'],
                ];
            }

            $maximoDia = collect($horas)->max('total');
            $horaPico = collect($horas)->sortByDesc('total')->first();

            $filas[] = [
                'day_index' => $dia,
                'day_name' => $nombresDias[$dia],
                'total_orders' => collect($horas)->sum('total'),
                'peak_hour' => (int) ($horaPico['hour'] ?? 0),
                'peak_hour_orders' => (int) ($maximoDia ?? 0),
                'hours' => $horas,
            ];
        }

        $totalExpress = collect($filas)->sum(fn (array $f) => collect($f['hours'])->sum('express'));
        $totalSuscripciones = collect($filas)->sum(fn (array $f) => collect($f['hours'])->sum('subscriptions'));

        return response()->json([
            'success' => true,
            'period' => [
                'days' => $dias,
                'from' => $desde->toDateString(),
                'to' => now()->toDateString(),
            ],
            'summary' => [
                'total_orders' => $totalExpress + $totalSuscripciones,
                'express_orders' => $totalExpress,
                'subscription_orders' => $totalSuscripciones,
            ],
            'heatmap' => $filas,
        ]);
    }

    /**
     * GET /api/v1/provider/reports/stock-forecast
     *
     * Reporte semanal con la suma exacta de insumos y platos necesarios para
     * abastecer las suscripciones programadas en el rango pedido.
     */
    public function getSubscriptionStockForecast(Request $request): JsonResponse
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $inicio = isset($data['start_date'])
            ? Carbon::parse($data['start_date'])->startOfDay()
            : today();
        $fin = isset($data['end_date'])
            ? Carbon::parse($data['end_date'])->endOfDay()
            : $inicio->copy()->addDays(6)->endOfDay();

        $suscripciones = CustomerSubscription::where('provider_id', $provider->id)
            ->where('status', CustomerSubscription::STATUS_ACTIVE)
            ->with(['plan.items.product', 'items.product'])
            ->get();

        $platos = [];
        $insumos = [];

        foreach ($suscripciones as $subscription) {
            $fecha = $subscription->next_delivery_date
                ? $subscription->next_delivery_date->copy()->startOfDay()
                : null;

            // Cuenta cuantos ciclos de la suscripcion caen dentro del rango.
            // Se avanza la fecha con la frecuencia del plan; si no hay plan o
            // no hay proxima entrega en el rango, no se cuenta nada.
            $ciclos = 0;

            if ($fecha !== null && $subscription->plan) {
                $ref = $fecha->copy();
                $guardia = 0;

                while ($ref->lte($fin) && $guardia < 366) {
                    if ($ref->gte($inicio)) {
                        $ciclos++;
                    }

                    $ref = $subscription->plan->nextDeliveryDateAfter($ref, $ref);
                    $guardia++;
                }
            }

            if ($ciclos === 0) {
                continue;
            }

            foreach ($subscription->itemsForOrder() as $linea) {
                $producto = $linea['product'];
                $cantidad = (int) $linea['quantity'] * $ciclos;

                if (! isset($platos[$producto->id])) {
                    $platos[$producto->id] = [
                        'product_id' => $producto->id,
                        'name' => $producto->name,
                        'quantity' => 0,
                        'subscriptions' => 0,
                    ];
                }

                $platos[$producto->id]['quantity'] += $cantidad;
                $platos[$producto->id]['subscriptions']++;

                // Descomponer el plato en insumos segun su ficha tecnica.
                $producto->loadMissing('recipeItems.supply');

                foreach ($producto->recipeItems as $item) {
                    $supplyId = $item->supply_id;

                    if (! isset($insumos[$supplyId])) {
                        $insumos[$supplyId] = [
                            'supply_id' => $supplyId,
                            'name' => $item->supply->name,
                            'unit_symbol' => $item->supply->unitOfMeasure?->symbol,
                            'cost_per_unit' => (float) $item->supply->cost_per_unit,
                            'quantity_required' => 0.0,
                        ];
                    }

                    $insumos[$supplyId]['quantity_required'] = round(
                        $insumos[$supplyId]['quantity_required'] + (float) $item->quantity_required * $cantidad,
                        3
                    );
                }
            }
        }

        $insumos = array_values($insumos);
        foreach ($insumos as &$insumo) {
            $insumo['estimated_cost'] = round($insumo['quantity_required'] * $insumo['cost_per_unit'], 2);
        }
        unset($insumo);

        return response()->json([
            'success' => true,
            'period' => [
                'start_date' => $inicio->toDateString(),
                'end_date' => $fin->toDateString(),
            ],
            'summary' => [
                'active_subscriptions' => $suscripciones->count(),
                'total_deliveries' => array_sum(array_column($platos, 'quantity')),
                'total_products' => count($platos),
                'total_supplies' => count($insumos),
                'estimated_supplies_cost' => round(array_sum(array_column($insumos, 'estimated_cost')), 2),
            ],
            'products' => array_values($platos),
            'supplies' => $insumos,
        ]);
    }

    /**
     * GET /api/v1/customer/reports/savings
     *
     * Cuanto ahoro el cliente comprando con suscripciones o promociones frente
     * al precio de las mismas piezas vendidas por separado (Express).
     */
    public function getCustomerSavings(Request $request): JsonResponse
    {
        $cliente = $this->currentCustomer();

        if ($cliente instanceof JsonResponse) {
            return $cliente;
        }

        // Ahorro por suscripciones: diferencia entre la suma de precios
        // unitarios Express y lo que realmente pago por ciclo.
        $suscripciones = CustomerSubscription::where('user_id', $cliente->id)
            ->with('plan.items.product')
            ->get();

        $ahorroSuscripciones = 0.0;
        $ciclosCobrados = 0;

        foreach ($suscripciones as $subscription) {
            $pagos = $subscription->payments()
                ->where('status', SubscriptionPayment::STATUS_PAID)
                ->get(['id', 'total']);

            foreach ($pagos as $pago) {
                $valorExpress = $subscription->plan->items->sum(
                    fn ($item) => (float) $item->product->price * (int) $item->quantity
                );

                $ahorroSuscripciones += max(0, $valorExpress - (float) $pago->total);
                $ciclosCobrados++;
            }
        }

        // Ahorro por descuentos en pedidos Express: el campo discount de la
        // orden registra el beneficio aplicado en cada pedido.
        $pedidosDescuento = Order::where('user_id', $cliente->id)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->where('discount', '>', 0)
            ->get(['id', 'discount', 'subtotal', 'total', 'created_at']);

        $ahorroPromociones = round($pedidosDescuento->sum(fn (Order $o) => (float) $o->discount), 2);
        $ahorroTotal = round($ahorroSuscripciones + $ahorroPromociones, 2);

        return response()->json([
            'success' => true,
            'summary' => [
                'total_saved' => $ahorroTotal,
                'saved_with_subscriptions' => round($ahorroSuscripciones, 2),
                'saved_with_promotions' => $ahorroPromociones,
                'paid_subscription_cycles' => $ciclosCobrados,
                'orders_with_discount' => $pedidosDescuento->count(),
            ],
            'by_subscription' => $suscripciones->map(fn (CustomerSubscription $s) => [
                'subscription_id' => $s->id,
                'plan_title' => $s->plan?->title,
                'provider' => $s->provider?->business_name,
            ])->all(),
        ]);
    }

    /**
     * GET /api/v1/customer/reports/subscriptions
     *
     * Resumen de las suscripciones activas del cliente: proximas entregas,
     * productos y estado self-service (activa / pausada).
     */
    public function getActiveSubscriptions(Request $request): JsonResponse
    {
        $cliente = $this->currentCustomer();

        if ($cliente instanceof JsonResponse) {
            return $cliente;
        }

        $subscriptions = CustomerSubscription::where('user_id', $cliente->id)
            ->whereIn('status', [
                CustomerSubscription::STATUS_ACTIVE,
                CustomerSubscription::STATUS_PAUSED,
            ])
            ->with(['plan.items.product', 'items.product', 'provider:id,business_name', 'deliveryAddress:id,street,number'])
            ->orderBy('next_delivery_date')
            ->get();

        $filas = $subscriptions->map(function (CustomerSubscription $subscription) {
            $lineas = $subscription->items->isNotEmpty()
                ? $subscription->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name,
                    'quantity' => (int) $item->quantity,
                ])->values()
                : $subscription->plan->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name,
                    'quantity' => (int) $item->quantity,
                ])->values();

            $proximoPago = $subscription->payments()
                ->where('status', SubscriptionPayment::STATUS_PENDING)
                ->orderBy('period_start')
                ->first();

            return [
                'subscription_id' => $subscription->id,
                'plan_title' => $subscription->plan?->title,
                'provider' => $subscription->provider?->business_name,
                'status' => $subscription->status,
                'status_label' => $subscription->status === CustomerSubscription::STATUS_ACTIVE ? 'Activa' : 'Pausada',
                'frequency' => $subscription->plan?->frequency,
                'charge_amount' => (float) ($subscription->plan?->chargeAmount() ?? 0),
                'next_delivery_date' => $subscription->next_delivery_date?->toDateString(),
                'preferred_delivery_day' => $subscription->preferred_delivery_day,
                'preferred_delivery_time' => $subscription->preferred_delivery_time,
                'payment_method' => $subscription->payment_method,
                'next_charge' => $proximoPago ? [
                    'payment_id' => $proximoPago->id,
                    'total' => (float) $proximoPago->total,
                    'period_start' => $proximoPago->period_start?->toDateString(),
                ] : null,
                'products' => $lineas->all(),
            ];
        });

        return response()->json([
            'success' => true,
            'summary' => [
                'active_count' => $subscriptions
                    ->filter(fn (CustomerSubscription $s) => $s->status === CustomerSubscription::STATUS_ACTIVE)
                    ->count(),
                'paused_count' => $subscriptions
                    ->filter(fn (CustomerSubscription $s) => $s->status === CustomerSubscription::STATUS_PAUSED)
                    ->count(),
                'monthly_commitment' => round($subscriptions
                    ->filter(fn (CustomerSubscription $s) => $s->status === CustomerSubscription::STATUS_ACTIVE)
                    ->sum(fn (CustomerSubscription $s) => $this->mrrDe($s)), 2),
            ],
            'subscriptions' => $filas->all(),
        ]);
    }

    /**
     * GET /api/v1/customer/reports/order-history
     *
     * Platos y comercios mas frecuentes del cliente, para habilitar la opcion
     * de reordenar en 1-Click desde el historial.
     */
    public function getOrderHistoryAndReorder(Request $request): JsonResponse
    {
        $cliente = $this->currentCustomer();

        if ($cliente instanceof JsonResponse) {
            return $cliente;
        }

        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limite = $data['limit'] ?? 10;

        $pedidos = Order::where('user_id', $cliente->id)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->with('items')
            ->with('provider:id,business_name')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'provider_id', 'total', 'status', 'created_at']);

        $platos = $pedidos->flatMap(function (Order $pedido) {
            return $pedido->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'total_price' => (float) $item->total_price,
                'order_id' => $pedido->id,
                'created_at' => $pedido->created_at->toDateTimeString(),
            ]);
        });

        $masPedidos = $platos
            ->groupBy('product_id')
            ->map(function (Collection $ocurrencias, $productId) {
                $primero = $ocurrencias->first();

                return [
                    'product_id' => (int) $productId,
                    'name' => $primero['name'],
                    'times_ordered' => $ocurrencias->count(),
                    'total_quantity' => $ocurrencias->sum('quantity'),
                    'total_spent' => round($ocurrencias->sum('total_price'), 2),
                    'last_ordered_at' => $ocurrencias->max('created_at'),
                    'can_reorder_1_click' => true,
                ];
            })
            ->sortByDesc('times_ordered')
            ->take($limite)
            ->values();

        $comercios = $pedidos->groupBy('provider_id')
            ->map(function (Collection $pedidosComercio) {
                $provider = $pedidosComercio->first()->provider;

                return [
                    'provider_id' => (int) $pedidosComercio->first()->provider_id,
                    'business_name' => $provider?->business_name,
                    'orders_count' => $pedidosComercio->count(),
                    'total_spent' => round($pedidosComercio->sum(fn (Order $o) => (float) $o->total), 2),
                    'last_order_at' => $pedidosComercio->max(fn (Order $o) => $o->created_at)?->toDateTimeString(),
                    'can_reorder_1_click' => true,
                ];
            })
            ->sortByDesc('orders_count')
            ->take($limite)
            ->values();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_orders' => $pedidos->count(),
                'total_spent' => round($pedidos->sum(fn (Order $o) => (float) $o->total), 2),
                'favorite_dish' => $masPedidos->first()['name'] ?? null,
                'favorite_provider' => $comercios->first()['business_name'] ?? null,
            ],
            'top_products' => $masPedidos->all(),
            'top_providers' => $comercios->all(),
        ]);
    }

    /**
     * GET /api/v1/customer/reports/nutritional
     *
     * Resumen mensual de viandas/comidas recibidas agrupadas por categoria
     * (grupo del producto), para que el cliente vea que come con mas frecuencia.
     */
    public function getNutritionalSummary(Request $request): JsonResponse
    {
        $cliente = $this->currentCustomer();

        if ($cliente instanceof JsonResponse) {
            return $cliente;
        }

        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $mes = isset($data['month'])
            ? Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth()
            : now()->startOfMonth();

        $finMes = $mes->copy()->endOfMonth();

        $pedidos = Order::where('user_id', $cliente->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereBetween('created_at', [$mes, $finMes])
            ->with('items.product.category')
            ->get(['id', 'created_at']);

        $porCategoria = [];

        foreach ($pedidos as $pedido) {
            foreach ($pedido->items as $item) {
                $categoria = $item->product?->category;
                $clave = $categoria?->id ?? 0;
                $nombre = $categoria?->description ?? 'Sin categoría';

                if (! isset($porCategoria[$clave])) {
                    $porCategoria[$clave] = [
                        'category_id' => $clave === 0 ? null : $clave,
                        'category_name' => $nombre,
                        'meals_count' => 0,
                        'total_quantity' => 0,
                        'total_spent' => 0.0,
                        'top_products' => [],
                    ];
                }

                $porCategoria[$clave]['meals_count']++;
                $porCategoria[$clave]['total_quantity'] += (int) $item->quantity;
                $porCategoria[$clave]['total_spent'] = round(
                    $porCategoria[$clave]['total_spent'] + (float) $item->total_price,
                    2
                );

                $productoNombre = $item->product_name ?: 'Producto';
                $porCategoria[$clave]['top_products'][$productoNombre] = ($porCategoria[$clave]['top_products'][$productoNombre] ?? 0) + (int) $item->quantity;
            }
        }

        foreach ($porCategoria as &$categoria) {
            arsort($categoria['top_products']);
            $categoria['top_products'] = collect($categoria['top_products'])
                ->take(5)
                ->map(fn (int $cantidad, string $nombre) => ['name' => $nombre, 'quantity' => $cantidad])
                ->values()
                ->all();
        }
        unset($categoria);

        $categorias = collect($porCategoria)->sortByDesc('total_quantity')->values();

        return response()->json([
            'success' => true,
            'period' => [
                'month' => $mes->format('Y-m'),
                'start_date' => $mes->toDateString(),
                'end_date' => $finMes->toDateString(),
            ],
            'summary' => [
                'total_delivered_orders' => $pedidos->count(),
                'total_meals' => $categorias->sum('total_quantity'),
                'total_spent' => round($categorias->sum('total_spent'), 2),
                'main_category' => $categorias->first()['category_name'] ?? null,
            ],
            'categories' => $categorias->all(),
        ]);
    }

    // ------------------------------------------------------------------
    // Helpers internos
    // ------------------------------------------------------------------

    /**
     * Semaforo de riesgo del margen: verde sano, amarillo justo, rojo critico.
     *
     * @return array{level: string, label: string}
     */
    private function marginTrafficLight(?float $percent): array
    {
        if ($percent === null) {
            return ['level' => 'gray', 'label' => 'Sin precio de venta'];
        }

        if ($percent >= 35) {
            return ['level' => 'green', 'label' => 'Saludable'];
        }

        if ($percent >= 20) {
            return ['level' => 'yellow', 'label' => 'Margen justo'];
        }

        return ['level' => 'red', 'label' => 'Riesgo de rentabilidad'];
    }

    /**
     * Precio sugerido para alcanzar el margen minimo recomendado.
     *
     * @return array{suggested_price: float|null, note: string}
     */
    private function priceRecommendation(float $precio, float $costo, ?float $margen): array
    {
        $umbral = (float) config('financials.min_gross_margin_percent', 30);

        if ($precio <= 0) {
            return [
                'suggested_price' => null,
                'note' => 'Cargá un precio de venta para poder calcular el margen.',
            ];
        }

        if ($margen !== null && $margen >= $umbral) {
            return [
                'suggested_price' => null,
                'note' => 'El margen actual supera el mínimo recomendado de '.$umbral.'%.',
            ];
        }

        $sugerido = $costo > 0
            ? round($costo / (1 - $umbral / 100), 2)
            : null;

        return [
            'suggested_price' => $sugerido,
            'note' => $sugerido !== null
                ? 'Para un margen del '.$umbral.'% el precio sugerido es '.$sugerido.'.'
                : 'Sin ficha técnica no se puede sugerir un precio.',
        ];
    }

    /**
     * MRR que aporta una suscripcion: lo que cobra el plan por ciclo,
     * normalizado a un mes segun su frecuencia.
     */
    private function mrrDe(CustomerSubscription $subscription): float
    {
        $plan = $subscription->plan;

        if (! $plan) {
            return 0.0;
        }

        return round($plan->chargeAmount() * $plan->cyclesPerMonth(), 2);
    }

    /**
     * @return Provider|JsonResponse
     */
    private function currentProvider()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $provider = Auth::user()->provider;

        if (! $provider) {
            return response()->json([
                'success' => false,
                'message' => 'Tu usuario no tiene un comercio asociado.',
            ], 403);
        }

        return $provider;
    }

    /**
     * Guardia del rol Cliente: cualquier usuario logueado puede pedir sus
     * propios reportes; el guard se resuelve contra la tabla users.
     *
     * @return User|JsonResponse
     */
    private function currentCustomer()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        return Auth::user();
    }
}
