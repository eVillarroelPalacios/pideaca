<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerSubscription;
use App\Models\Order;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Reportes del cliente de Mercado ("Reportes Comercio") para la app movil.
 *
 * Espeja los cuatro reportes del rol Cliente de ReportController (web):
 * ahorro, suscripciones activas, historico/habitos y resumen nutricional.
 * La unica diferencia es el guard: la web usa la sesion/CSRF con
 * Auth::check(), mientras que la app resuelve el usuario con Sanctum a
 * traves de $request->user().
 */
class MarketReportsController extends Controller
{
    /**
     * GET /api/mobile/v1/reports/comercio/savings
     *
     * Cuanto ahorro el cliente comprando con suscripciones o promociones
     * frente al precio de las mismas piezas vendidas por separado (Express).
     */
    public function savings(Request $request): JsonResponse
    {
        $client = $request->user();

        // Ahorro por suscripciones: diferencia entre la suma de precios
        // unitarios Express y lo que realmente pago por ciclo.
        $subscriptions = CustomerSubscription::where('user_id', $client->id)
            ->with('plan.items.product')
            ->get();

        $subscriptionSavings = 0.0;
        $paidCycles = 0;

        foreach ($subscriptions as $subscription) {
            $payments = $subscription->payments()
                ->where('status', SubscriptionPayment::STATUS_PAID)
                ->get(['id', 'total']);

            foreach ($payments as $payment) {
                $expressValue = $subscription->plan->items->sum(
                    fn ($item) => (float) $item->product->price * (int) $item->quantity
                );

                $subscriptionSavings += max(0, $expressValue - (float) $payment->total);
                $paidCycles++;
            }
        }

        // Ahorro por descuentos en pedidos Express: el campo discount de la
        // orden registra el beneficio aplicado en cada pedido.
        $discountedOrders = Order::where('user_id', $client->id)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->where('discount', '>', 0)
            ->get(['id', 'discount', 'subtotal', 'total', 'created_at']);

        $promotionSavings = round($discountedOrders->sum(fn (Order $order) => (float) $order->discount), 2);
        $totalSaved = round($subscriptionSavings + $promotionSavings, 2);

        return response()->json([
            'success' => true,
            'summary' => [
                'total_saved' => $totalSaved,
                'saved_with_subscriptions' => round($subscriptionSavings, 2),
                'saved_with_promotions' => $promotionSavings,
                'paid_subscription_cycles' => $paidCycles,
                'orders_with_discount' => $discountedOrders->count(),
            ],
            'by_subscription' => $subscriptions->map(fn (CustomerSubscription $subscription) => [
                'subscription_id' => $subscription->id,
                'plan_title' => $subscription->plan?->title,
                'provider' => $subscription->provider?->business_name,
            ])->all(),
        ]);
    }

    /**
     * GET /api/mobile/v1/reports/comercio/subscriptions
     *
     * Resumen de las suscripciones activas del cliente: proximas entregas,
     * productos y estado self-service (activa / pausada).
     */
    public function subscriptions(Request $request): JsonResponse
    {
        $client = $request->user();

        $subscriptions = CustomerSubscription::where('user_id', $client->id)
            ->whereIn('status', [
                CustomerSubscription::STATUS_ACTIVE,
                CustomerSubscription::STATUS_PAUSED,
            ])
            ->with(['plan.items.product', 'items.product', 'provider:id,business_name', 'deliveryAddress:id,street,number'])
            ->orderBy('next_delivery_date')
            ->get();

        $rows = $subscriptions->map(function (CustomerSubscription $subscription) {
            $lines = $subscription->items->isNotEmpty()
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

            $nextPayment = $subscription->payments()
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
                'next_charge' => $nextPayment ? [
                    'payment_id' => $nextPayment->id,
                    'total' => (float) $nextPayment->total,
                    'period_start' => $nextPayment->period_start?->toDateString(),
                ] : null,
                'products' => $lines->all(),
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
                    ->sum(fn (CustomerSubscription $s) => $this->monthlyMrr($s)), 2),
            ],
            'subscriptions' => $rows->all(),
        ]);
    }

    /**
     * GET /api/mobile/v1/reports/comercio/order-history
     *
     * Platos y comercios mas frecuentes del cliente, para habilitar la
     * opcion de reordenar en 1-Click desde el historial.
     */
    public function orderHistory(Request $request): JsonResponse
    {
        $client = $request->user();

        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = $data['limit'] ?? 10;

        $orders = Order::where('user_id', $client->id)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->with('items')
            ->with('provider:id,business_name')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'provider_id', 'total', 'status', 'created_at']);

        $dishes = $orders->flatMap(function (Order $order) {
            return $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'total_price' => (float) $item->total_price,
                'order_id' => $order->id,
                'provider_id' => $order->provider_id,
                'created_at' => $order->created_at->toDateTimeString(),
            ]);
        });

        $topProducts = $dishes
            ->groupBy('product_id')
            ->map(function (Collection $occurrences, $productId) {
                $first = $occurrences->first();

                return [
                    'product_id' => (int) $productId,
                    'provider_id' => (int) $occurrences->last()['provider_id'],
                    'name' => $first['name'],
                    'times_ordered' => $occurrences->count(),
                    'total_quantity' => $occurrences->sum('quantity'),
                    'total_spent' => round($occurrences->sum('total_price'), 2),
                    'last_ordered_at' => $occurrences->max('created_at'),
                    'can_reorder_1_click' => true,
                ];
            })
            ->sortByDesc('times_ordered')
            ->take($limit)
            ->values();

        $providers = $orders->groupBy('provider_id')
            ->map(function (Collection $providerOrders) {
                $provider = $providerOrders->first()->provider;

                return [
                    'provider_id' => (int) $providerOrders->first()->provider_id,
                    'business_name' => $provider?->business_name,
                    'orders_count' => $providerOrders->count(),
                    'total_spent' => round($providerOrders->sum(fn (Order $order) => (float) $order->total), 2),
                    'last_order_at' => $providerOrders->max(fn (Order $order) => $order->created_at)?->toDateTimeString(),
                    'can_reorder_1_click' => true,
                ];
            })
            ->sortByDesc('orders_count')
            ->take($limit)
            ->values();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_orders' => $orders->count(),
                'total_spent' => round($orders->sum(fn (Order $order) => (float) $order->total), 2),
                'favorite_dish' => $topProducts->first()['name'] ?? null,
                'favorite_provider' => $providers->first()['business_name'] ?? null,
            ],
            'top_products' => $topProducts->all(),
            'top_providers' => $providers->all(),
        ]);
    }

    /**
     * GET /api/mobile/v1/reports/comercio/nutritional
     *
     * Resumen mensual de viandas/comidas recibidas agrupadas por categoria
     * (grupo del producto), para que el cliente vea que come con mas
     * frecuencia.
     */
    public function nutritional(Request $request): JsonResponse
    {
        $client = $request->user();

        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = isset($data['month'])
            ? Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth()
            : now()->startOfMonth();

        $monthEnd = $month->copy()->endOfMonth();

        $orders = Order::where('user_id', $client->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereBetween('created_at', [$month, $monthEnd])
            ->with('items.product.category')
            ->get(['id', 'created_at']);

        $byCategory = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $category = $item->product?->category;
                $key = $category?->id ?? 0;
                $name = $category?->description ?? 'Sin categoría';

                if (! isset($byCategory[$key])) {
                    $byCategory[$key] = [
                        'category_id' => $key === 0 ? null : $key,
                        'category_name' => $name,
                        'meals_count' => 0,
                        'total_quantity' => 0,
                        'total_spent' => 0.0,
                        'top_products' => [],
                    ];
                }

                $byCategory[$key]['meals_count']++;
                $byCategory[$key]['total_quantity'] += (int) $item->quantity;
                $byCategory[$key]['total_spent'] = round(
                    $byCategory[$key]['total_spent'] + (float) $item->total_price,
                    2
                );

                $productName = $item->product_name ?: 'Producto';
                $byCategory[$key]['top_products'][$productName] = ($byCategory[$key]['top_products'][$productName] ?? 0) + (int) $item->quantity;
            }
        }

        foreach ($byCategory as &$category) {
            arsort($category['top_products']);
            $category['top_products'] = collect($category['top_products'])
                ->take(5)
                ->map(fn (int $quantity, string $name) => ['name' => $name, 'quantity' => $quantity])
                ->values()
                ->all();
        }
        unset($category);

        $categories = collect($byCategory)->sortByDesc('total_quantity')->values();

        return response()->json([
            'success' => true,
            'period' => [
                'month' => $month->format('Y-m'),
                'start_date' => $month->toDateString(),
                'end_date' => $monthEnd->toDateString(),
            ],
            'summary' => [
                'total_delivered_orders' => $orders->count(),
                'total_meals' => $categories->sum('total_quantity'),
                'total_spent' => round($categories->sum('total_spent'), 2),
                'main_category' => $categories->first()['category_name'] ?? null,
            ],
            'categories' => $categories->all(),
        ]);
    }

    /**
     * MRR que aporta una suscripcion: lo que cobra el plan por ciclo,
     * normalizado a un mes segun su frecuencia.
     */
    private function monthlyMrr(CustomerSubscription $subscription): float
    {
        $plan = $subscription->plan;

        if (! $plan) {
            return 0.0;
        }

        return round($plan->chargeAmount() * $plan->cyclesPerMonth(), 2);
    }
}
