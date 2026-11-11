<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Models\CustomerSubscription;
use App\Models\Product;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionBillingService;
use App\Support\CurrencyResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Suscripciones del comercio para la app movil.
 *
 * Espeja SubscriptionController del panel web: planes, ingreso recurrente,
 * cobros pendientes e historial por periodo. La unica diferencia es el guard:
 * Sanctum en vez de la sesion web.
 */
class ProviderSubscriptionsController extends Controller
{
    use ResolvesProvider;

    /**
     * GET /api/mobile/v1/provider/subscriptions/plans
     *
     * Planes del propio comercio, activos y ocultos, para que el panel movil
     * pueda editarlos. La vista publica solo muestra los activos.
     */
    public function indexPlans(Request $request): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $plans = SubscriptionPlan::where('provider_id', $provider->id)
            ->with('items.product')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'plans' => $plans->map(fn (SubscriptionPlan $plan) => $this->planPayload($plan))->all(),
        ]);
    }

    /**
     * POST /api/mobile/v1/provider/subscriptions/plans
     *
     * Alta y edicion de planes. Sin "id" crea un plan; con "id" lo edita,
     * siempre que sea del comercio que llama. Los productos y el precio se
     * resuelven contra la base, no se confian al cliente.
     */
    public function storePlan(Request $request): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'frequency' => ['required', 'string', 'in:'.implode(',', SubscriptionPlan::FREQUENCIES)],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1', 'max:'.config('fastdelivery.max_items_per_order')],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('fastdelivery.max_quantity_per_item')],
        ]);

        $ids = array_column($data['items'], 'product_id');

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'items' => 'No repitas el mismo producto dentro del plan.',
            ]);
        }

        $this->resolveProducts($provider, $ids);

        $plan = null;

        if (isset($data['id'])) {
            $plan = SubscriptionPlan::where('provider_id', $provider->id)
                ->where('id', $data['id'])
                ->first();

            if (! $plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'El plan no existe en tu comercio.',
                ], 404);
            }
        }

        $editing = $plan !== null;

        $isActive = $request->has('is_active')
            ? $request->boolean('is_active')
            : ($editing ? $plan->is_active : true);

        // has() y no ?? para que "capacity": null sirva para sacar el limite.
        $capacity = $request->has('capacity')
            ? $data['capacity']
            : ($editing ? $plan->capacity : null);

        $plan = DB::transaction(function () use ($data, $provider, $plan, $editing, $isActive, $capacity) {
            $plan = $editing
                ? SubscriptionPlan::whereKey($plan->id)->lockForUpdate()->first()
                : new SubscriptionPlan(['provider_id' => $provider->id]);

            $plan->fill([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'frequency' => $data['frequency'],
                'price' => $data['price'],
                'discount_percentage' => $data['discount_percentage'] ?? 0,
                'capacity' => $capacity,
                'is_active' => $isActive,
            ]);

            $plan->save();

            $plan->items()->delete();

            foreach ($data['items'] as $item) {
                $plan->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $plan;
        });

        return response()->json([
            'success' => true,
            'message' => $editing ? 'Plan actualizado.' : 'Plan creado correctamente.',
            'plan' => $this->planPayload($plan->loadMissing('items.product')),
        ], $editing ? 200 : 201);
    }

    /**
     * PUT /api/mobile/v1/provider/subscriptions/plans/{plan}
     *
     * Actualiza un plan existente del comercio. Reutiliza las mismas reglas
     * del alta para que la validacion y el payload sean identicos.
     */
    public function updatePlan(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        if ((int) $plan->provider_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'El plan no existe en tu comercio.',
            ], 404);
        }

        $request->merge(['id' => $plan->id]);

        return $this->storePlan($request);
    }

    /**
     * GET /api/mobile/v1/provider/subscriptions/revenue
     *
     * Caja fija del comercio: cuanto entra por mes con las suscripciones
     * activas, cuanto se cobro realmente, cobros por confirmar e historial.
     */
    public function revenue(Request $request): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $data = $request->validate([
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $meses = $data['months'] ?? 3;

        $subscriptions = CustomerSubscription::where('provider_id', $provider->id)
            ->with('plan')
            ->get();

        $porEstado = $subscriptions->groupBy('status')->map->count();

        $activas = $subscriptions
            ->filter(fn (CustomerSubscription $s) => $s->status === CustomerSubscription::STATUS_ACTIVE)
            ->values();

        $mrr = $activas->sum(fn (CustomerSubscription $s) => $this->mrrDe($s));

        $porPlan = $activas
            ->groupBy('subscription_plan_id')
            ->map(function (Collection $delPlan, $planId) {
                $plan = $delPlan->first()->plan;
                $suscriptores = $delPlan->count();

                return [
                    'plan_id' => (int) $planId,
                    'title' => $plan?->title,
                    'frequency' => $plan?->frequency,
                    'price' => (float) ($plan?->price ?? 0),
                    'charge_amount' => (float) ($plan?->chargeAmount() ?? 0),
                    'subscribers' => $suscriptores,
                    'capacity' => $plan?->capacity,
                    'available_slots' => $plan?->availableSlots(),
                    'mrr' => round($delPlan->sum(fn (CustomerSubscription $s) => $this->mrrDe($s)), 2),
                ];
            })
            ->values();

        $porFrecuencia = $porPlan
            ->groupBy('frequency')
            ->map(fn (Collection $planes) => round($planes->sum('mrr'), 2));

        $hoy = today();
        $inicioMesActual = $hoy->copy()->startOfMonth();
        $corte30 = now()->subDays(30);
        $corte60 = now()->subDays(60);

        $cobrado = (float) SubscriptionPayment::where('provider_id', $provider->id)
            ->where('status', SubscriptionPayment::STATUS_PAID)
            ->where('paid_at', '>=', $corte60)
            ->sum('total');

        $cobrado30 = (float) SubscriptionPayment::where('provider_id', $provider->id)
            ->where('status', SubscriptionPayment::STATUS_PAID)
            ->where('paid_at', '>=', $corte30)
            ->sum('total');

        // El envio no es caja fija: se cobra por pedido, asi que va aparte para
        // que el comercio no lo lea como parte del ingreso recurrente.
        $envios30 = (float) SubscriptionPayment::where('provider_id', $provider->id)
            ->where('status', SubscriptionPayment::STATUS_PAID)
            ->where('paid_at', '>=', $corte30)
            ->sum('delivery_fee');

        $historial = SubscriptionPayment::where('provider_id', $provider->id)
            ->where('period_start', '>=', $inicioMesActual->copy()->subMonths($meses - 1)->toDateString())
            ->selectRaw(
                "to_char(period_start, 'YYYY-MM') as period,"
                ." sum(case when status = 'PAID' then total else 0 end) as collected,"
                ." sum(case when status = 'PENDING' then total else 0 end) as pending,"
                ." sum(case when status = 'FAILED' then total else 0 end) as failed,"
                ." sum(case when status = 'REFUNDED' then total else 0 end) as refunded,"
                .' count(*) as charges'
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => $row->period,
                'collected' => round((float) $row->collected, 2),
                'pending' => round((float) $row->pending, 2),
                'failed' => round((float) $row->failed, 2),
                'refunded' => round((float) $row->refunded, 2),
                'charges' => (int) $row->charges,
            ]);

        $pendientes = SubscriptionPayment::where('provider_id', $provider->id)
            ->where('status', SubscriptionPayment::STATUS_PENDING)
            ->with(['subscription.plan', 'user:id,name,email'])
            ->orderBy('period_start')
            ->limit(10)
            ->get()
            ->map(fn (SubscriptionPayment $p) => $this->paymentPayload($p));

        return response()->json([
            'success' => true,
            'currency' => CurrencyResolver::defaultCurrency()->code,
            'recurring' => [
                'mrr' => round($mrr, 2),
                'mrr_basis' => 'precio del plan con descuento, sin envio',
                'active_subscriptions' => $activas->count(),
                'paused_subscriptions' => (int) ($porEstado[CustomerSubscription::STATUS_PAUSED] ?? 0),
                'payment_failed' => (int) ($porEstado[CustomerSubscription::STATUS_PAYMENT_FAILED] ?? 0),
                'cancelled' => (int) ($porEstado[CustomerSubscription::STATUS_CANCELLED] ?? 0),
                'by_plan' => $porPlan,
                'by_frequency' => $porFrecuencia,
            ],
            'cash' => [
                'collected_last_30_days' => round($cobrado30, 2),
                'collected_previous_30_days' => round($cobrado - $cobrado30, 2),
                'from_plans_last_30_days' => round($cobrado30 - $envios30, 2),
                'delivery_fees_last_30_days' => round($envios30, 2),
                'pending_amount' => round((float) SubscriptionPayment::where('provider_id', $provider->id)
                    ->where('status', SubscriptionPayment::STATUS_PENDING)
                    ->sum('total'), 2),
                'failed_last_30_days' => (int) SubscriptionPayment::where('provider_id', $provider->id)
                    ->where('status', SubscriptionPayment::STATUS_FAILED)
                    ->where('updated_at', '>=', $corte30)
                    ->count(),
            ],
            'history' => $historial,
            'pending_charges' => $pendientes,
        ]);
    }

    /**
     * PUT /api/mobile/v1/provider/subscriptions/payments/{payment}
     *
     * Confirmacion manual de un ciclo: el comercio recibio el pago por efectivo
     * o transferencia, o el cobro fallo y queda registrado como tal.
     */
    public function updatePayment(
        Request $request,
        SubscriptionPayment $payment,
        SubscriptionBillingService $billing
    ): JsonResponse {
        $provider = $this->providerOrFail($request);

        if ((int) $payment->provider_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'El cobro no pertenece a tu comercio.',
            ], 404);
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.SubscriptionPayment::STATUS_PAID.','.SubscriptionPayment::STATUS_FAILED],
            'failure_reason' => ['nullable', 'string', 'max:250'],
        ]);

        if ($payment->status === SubscriptionPayment::STATUS_PAID) {
            return response()->json([
                'success' => false,
                'message' => 'Ese cobro ya estaba confirmado.',
            ], 422);
        }

        $payment = $data['status'] === SubscriptionPayment::STATUS_PAID
            ? $billing->markPaid($payment)
            : $billing->markFailed($payment, $data['failure_reason'] ?? 'Cobro rechazado por el comercio.');

        return response()->json([
            'success' => true,
            'message' => 'Cobro actualizado.',
            'payment' => $this->paymentPayload($payment->load(['subscription.plan', 'user:id,name,email'])),
        ]);
    }

    /**
     * Productos del comercio, con el nombre de los que no le pertenecen.
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, Product>
     */
    private function resolveProducts($provider, array $ids)
    {
        $products = Product::where('provider_id', $provider->id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $errors = [];

        foreach ($ids as $id) {
            if (! $products->has($id)) {
                $errors[] = 'El producto '.$id.' no pertenece a tu comercio.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['items' => $errors]);
        }

        return $products;
    }

    /**
     * Aporte mensual de una suscripcion activa al ingreso recurrente.
     */
    private function mrrDe(CustomerSubscription $subscription): float
    {
        $plan = $subscription->plan;

        if (! $plan) {
            return 0.0;
        }

        return round($plan->chargeAmount() * $plan->cyclesPerMonth(), 2);
    }

    private function paymentPayload(SubscriptionPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'status' => $payment->status,
            'gateway' => $payment->gateway,
            'attempt' => (int) $payment->attempt,
            'subtotal' => (float) $payment->subtotal,
            'discount' => (float) $payment->discount,
            'delivery_fee' => (float) $payment->delivery_fee,
            'total' => (float) $payment->total,
            'currency' => $payment->currency,
            'period_start' => $payment->period_start?->toDateString(),
            'period_end' => $payment->period_end?->toDateString(),
            'paid_at' => $payment->paid_at?->toDateTimeString(),
            'failure_reason' => $payment->failure_reason,
            'order_id' => $payment->order_id,
            'subscription' => $payment->subscription ? [
                'id' => $payment->subscription->id,
                'status' => $payment->subscription->status,
                'plan' => $payment->subscription->plan?->title,
            ] : null,
            'customer' => $payment->user ? [
                'id' => $payment->user->id,
                'name' => $payment->user->name,
                'email' => $payment->user->email,
            ] : null,
        ];
    }

    private function planPayload(SubscriptionPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'title' => $plan->title,
            'description' => $plan->description,
            'frequency' => $plan->frequency,
            'price' => (float) $plan->price,
            'discount_percentage' => (float) $plan->discount_percentage,
            'discount_amount' => $plan->discountAmount(),
            'charge_amount' => $plan->chargeAmount(),
            'capacity' => $plan->capacity,
            'available_slots' => $plan->availableSlots(),
            'is_active' => (bool) $plan->is_active,
            'items' => $plan->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product?->name,
                'image_path' => $item->product?->image_path,
                'quantity' => $item->quantity,
                'unit_price' => $item->product?->price,
                'line_total' => $item->product ? $plan->lineTotal($item) : null,
            ])->values(),
        ];
    }
}
