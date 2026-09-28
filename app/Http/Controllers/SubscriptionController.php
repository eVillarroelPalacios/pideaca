<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\CustomerSubscription;
use App\Models\Product;
use App\Models\Provider;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    /**
     * Alta y edicion de planes del comercio.
     *
     * Sin "id" crea un plan; con "id" lo edita, siempre que sea del comercio
     * que llama (nunca se toca el plan de otro comercio). Los productos y el
     * precio se resuelven contra la base, no se confian al cliente.
     */
    /**
     * Planes del propio comercio, activos y ocultos, para que el panel los
     * pueda editar: la vista publica solo muestra los que estan activos.
     */
    public function indexPlans()
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

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
     * Crear o actualizar un plan del comercio.
     */
    public function storePlan(Request $request)
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

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

        $products = $this->resolveProducts($provider, $ids);

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
     * Planes activos de un comercio: alimentan la vista de suscripciones del
     * cliente, por eso es publico y solo expone planes que se pueden contratar.
     */
    public function publicPlans(Provider $provider)
    {
        $plans = $provider->subscriptionPlans()
            ->active()
            ->with(['items.product:id,name,price,image_path,is_available'])
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'is_active' => (bool) $provider->is_active,
            ],
            'frequencies' => SubscriptionPlan::FREQUENCIES,
            'plans' => $plans->map(fn (SubscriptionPlan $plan) => $this->planPayload($plan)),
        ]);
    }

    /**
     * Alta de suscripcion del cliente logueado.
     */
    public function store(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

        $data = $request->validate([
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'delivery_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'payment_method' => ['required', 'string', 'max:50'],
            'next_delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_delivery_day' => ['nullable', 'integer', 'min:1', 'max:7'],
            'preferred_delivery_time' => ['nullable', 'date_format:H:i'],
            'items' => ['nullable', 'array', 'max:'.config('fastdelivery.max_items_per_order')],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('fastdelivery.max_quantity_per_item')],
        ]);

        $plan = SubscriptionPlan::with('provider')
            ->where('id', $data['subscription_plan_id'])
            ->where('is_active', true)
            ->first();

        if (! $plan || ! $plan->provider || ! $plan->provider->is_active) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => 'El plan no esta disponible.',
            ]);
        }

        $address = Address::where('id', $data['delivery_address_id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $address) {
            throw ValidationException::withMessages([
                'delivery_address_id' => 'La direccion de entrega no pertenece a tu usuario.',
            ]);
        }

        $alreadySubscribed = CustomerSubscription::where('user_id', $user->id)
            ->where('subscription_plan_id', $plan->id)
            ->whereIn('status', [CustomerSubscription::STATUS_ACTIVE, CustomerSubscription::STATUS_PAUSED])
            ->exists();

        if ($alreadySubscribed) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => 'Ya tenes una suscripcion a este plan.',
            ]);
        }

        if (! $plan->hasCapacity()) {
            throw ValidationException::withMessages([
                'subscription_plan_id' => 'Este plan completo no tiene cupos disponibles por el momento.',
            ]);
        }

        $subscription = DB::transaction(function () use ($data, $user, $plan, $address) {
            // El cupo se revalida con la fila del plan bloqueada: dos altas
            // simultaneas no pueden meter al ultimo y pasarse el limite.
            $planBloqueado = SubscriptionPlan::whereKey($plan->id)->lockForUpdate()->first();

            if (! $planBloqueado || ! $planBloqueado->hasCapacity()) {
                throw ValidationException::withMessages([
                    'subscription_plan_id' => 'Este plan completo no tiene cupos disponibles por el momento.',
                ]);
            }

            $subscription = CustomerSubscription::create([
                'user_id' => $user->id,
                'provider_id' => $plan->provider_id,
                'subscription_plan_id' => $plan->id,
                'status' => CustomerSubscription::STATUS_ACTIVE,
                'next_delivery_date' => $data['next_delivery_date'],
                'preferred_delivery_day' => $data['preferred_delivery_day'] ?? null,
                'preferred_delivery_time' => $data['preferred_delivery_time'] ?? null,
                'delivery_address_id' => $address->id,
                'payment_method' => $data['payment_method'],
            ]);

            if (! empty($data['items'])) {
                $this->replaceItems($subscription, $data['items']);
            }

            return $subscription;
        });

        return response()->json([
            'success' => true,
            'message' => 'Suscripcion creada correctamente.',
            'subscription' => $this->subscriptionPayload($subscription->load('items.product')),
        ], 201);
    }

    /**
     * Suscripciones del cliente logueado, con plan, sabores elegidos y los
     * ultimos cobros: es la pantalla "Mis Suscripciones".
     */
    public function index()
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $subscriptions = CustomerSubscription::where('user_id', Auth::id())
            ->with([
                'plan.items.product:id,name,price,image_path',
                'items.product:id,name,price,image_path',
                'provider:id,business_name',
                'payments' => fn ($query) => $query->orderByDesc('id')->limit(6),
            ])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions->map(function (CustomerSubscription $subscription) {
                $payload = $this->subscriptionPayload($subscription);
                $payload['payments'] = $subscription->payments
                    ->map(fn (SubscriptionPayment $payment) => $this->paymentPayload($payment))
                    ->values();

                return $payload;
            })->all(),
        ]);
    }

    /**
     * Pausar, reanudar o cancelar una suscripcion propia.
     */
    public function updateStatus(Request $request, CustomerSubscription $subscription)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', CustomerSubscription::CLIENT_STATUSES)],
        ]);

        if ((int) $subscription->user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tenes acceso a esta suscripcion.',
            ], 403);
        }

        if ($subscription->status === CustomerSubscription::STATUS_PAYMENT_FAILED
            && $data['status'] === CustomerSubscription::STATUS_ACTIVE
            && $subscription->hasUnpaidCycles()) {
            return response()->json([
                'success' => false,
                'message' => 'Todavia tenes ciclos sin pagar.',
                'errors' => ['status' => ['Regulariza el cobro pendiente antes de reactivar la suscripcion.']],
            ], 422);
        }

        $allowed = CustomerSubscription::CLIENT_TRANSITIONS[$subscription->status] ?? [];

        if (! in_array($data['status'], $allowed, true)) {
            return response()->json([
                'success' => false,
                'message' => 'La suscripcion no puede pasar de "'.$subscription->status.'" a "'.$data['status'].'".',
                'errors' => ['status' => ['El estado indicado no es valido para esta suscripcion.']],
            ], 422);
        }

        $updates = ['status' => $data['status']];

        // Al reanudar, una fecha vencida dejaria la suscripcion sin generar
        // pedidos: se reagenda a partir de hoy.
        if ($data['status'] === CustomerSubscription::STATUS_ACTIVE
            && $subscription->next_delivery_date->isBefore(today())) {
            $updates['next_delivery_date'] = today();
        }

        $subscription->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado.',
            'subscription' => $this->subscriptionPayload($subscription->fresh()),
        ]);
    }

    /**
     * Sabores de la proxima entrega.
     *
     * El cliente no puede agrandar el plan: reparte la misma cantidad total
     * entre los productos que el plan incluye. Asi cambiar de sabor no le
     * cuesta ni un peso extra, y tampoco le abre la puerta a pedir de mas.
     */
    public function updateItems(Request $request, CustomerSubscription $subscription)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ((int) $subscription->user_id !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tenes acceso a esta suscripcion.',
            ], 403);
        }

        if (! in_array($subscription->status, [
            CustomerSubscription::STATUS_ACTIVE,
            CustomerSubscription::STATUS_PAUSED,
        ], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta suscripcion no admite cambios de productos.',
            ], 422);
        }

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:'.config('fastdelivery.max_items_per_order')],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('fastdelivery.max_quantity_per_item')],
        ]);

        $subscription->loadMissing('plan.items');

        DB::transaction(function () use ($subscription, $data) {
            $this->replaceItems($subscription, $data['items']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Productos de la proxima entrega actualizados.',
            'subscription' => $this->subscriptionPayload($subscription->fresh()->load('items.product')),
        ]);
    }

    /**
     * Caja fija del comercio: cuanto entra por mes con las suscripciones
     * activas y cuanto se cobraro realmente.
     */
    public function revenue(Request $request)
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

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
            'currency' => 'ARS',
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
     * Confirmacion manual de un ciclo: el comercio recibio el pago por efectivo
     * o transferencia, o el cobro fallo y queda registrado como tal.
     */
    public function updatePayment(Request $request, SubscriptionPayment $payment, SubscriptionBillingService $billing)
    {
        $provider = $this->currentProvider();

        if ($provider instanceof JsonResponse) {
            return $provider;
        }

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
    private function resolveProducts(Provider $provider, array $ids)
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
     * Deja los items de la suscripcion listos para el proximo pedido.
     *
     * Solo admite productos del plan y mantiene la cantidad total del plan:
     * el cliente elige como se reparten (sabores), no cuanto recibe.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    private function replaceItems(CustomerSubscription $subscription, array $items): void
    {
        $ids = array_column($items, 'product_id');

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'items' => 'No repitas el mismo producto.',
            ]);
        }

        $plan = $subscription->plan;

        $permitidos = $plan->items->pluck('product_id')->all();
        $fueraDePlan = array_values(array_diff($ids, $permitidos));

        if ($fueraDePlan !== []) {
            throw ValidationException::withMessages([
                'items' => array_map(
                    fn ($id) => 'El producto '.$id.' no forma parte de este plan.',
                    $fueraDePlan
                ),
            ]);
        }

        $totalPlan = (int) $plan->items->sum('quantity');
        $totalPedido = 0;

        foreach ($items as $item) {
            $totalPedido += (int) $item['quantity'];
        }

        if ($totalPedido !== $totalPlan) {
            throw ValidationException::withMessages([
                'items' => 'La cantidad total debe sumar '.$totalPlan.', que es lo que incluye el plan.',
            ]);
        }

        $subscription->items()->delete();

        foreach ($items as $item) {
            $subscription->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }
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
            'price' => $plan->price,
            'discount_percentage' => $plan->discount_percentage,
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

    private function subscriptionPayload(CustomerSubscription $subscription): array
    {
        $subscription->loadMissing(['plan', 'provider:id,business_name', 'items.product:id,name,price,image_path']);

        return [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'next_delivery_date' => $subscription->next_delivery_date?->toDateString(),
            'preferred_delivery_day' => $subscription->preferred_delivery_day,
            'preferred_delivery_time' => $subscription->preferred_delivery_time?->format('H:i'),
            'payment_method' => $subscription->payment_method,
            'delivery_address_id' => $subscription->delivery_address_id,
            'provider' => $subscription->provider ? [
                'id' => $subscription->provider->id,
                'business_name' => $subscription->provider->business_name,
            ] : null,
            'plan' => $subscription->plan ? $this->planPayload($subscription->plan) : null,
            'items' => $subscription->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product?->name,
                'image_path' => $item->product?->image_path,
                'quantity' => (int) $item->quantity,
            ])->values(),
        ];
    }

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
}
