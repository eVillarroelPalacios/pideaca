<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderRating;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Centro de Despacho del comercio para la app movil.
 *
 * Espeja la logica de OrderController@index/updateStatus/rateOrder y
 * ProviderDispatchController@sendOrderToPool, pero resuelve el comercio desde
 * el usuario autenticado con Sanctum. La lista de repartidores de confianza ya
 * vive en ProfileDriversController (/profile/trusted-drivers), asi que aca solo
 * se exponen los pedidos y las acciones sobre ellos.
 */
class ProviderDispatchController extends Controller
{
    use ResolvesProvider;

    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * GET /api/mobile/v1/provider/dispatch/orders
     *
     * Pedidos del comercio con los mismos filtros de la web: pestana
     * (today/history), estado, busqueda libre, rango de fechas y paginacion.
     */
    public function index(Request $request): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', Order::STATUSES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'tab' => ['nullable', 'string', 'in:today,history'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $status = $data['status'] ?? null;
        $tab = $data['tab'] ?? null;
        $search = trim((string) ($data['search'] ?? ''));

        // La seccion "del dia" no usa rango de fechas: solo el historial filtra.
        $dateFrom = $tab !== 'today' && ! empty($data['date_from'])
            ? Carbon::parse($data['date_from'])->startOfDay()
            : null;
        $dateTo = $tab !== 'today' && ! empty($data['date_to'])
            ? Carbon::parse($data['date_to'])->endOfDay()
            : null;

        $base = $provider->orders()
            ->with([
                // phone alimenta el boton de WhatsApp de la tarjeta.
                'user:id,name,email,phone',
                'items.options',
                'address:id,street,number,floor_apartment,postal_code,notes',
                'payments:id,order_id,provider,status,amount,created_at',
                'ratings',
                'driver:id,name,email,phone',
            ])
            ->when($status, fn ($query) => $query->withStatus($status))
            ->when($dateFrom, fn ($query) => $query->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->where('created_at', '<=', $dateTo))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('order_number', 'like', $like)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like));
                });
            });

        $counts = [
            'today' => (clone $base)->whereDate('created_at', today())->count(),
            'history' => (clone $base)->whereDate('created_at', '<', today())->count(),
            'pending' => (clone $provider->orders())->where('status', Order::STATUS_PENDING)->count(),
            'in_preparation' => (clone $provider->orders())->where('status', Order::STATUS_IN_PREPARATION)->count(),
        ];

        $orders = (clone $base)
            ->when($tab === 'today', fn ($query) => $query->whereDate('created_at', today()))
            ->when($tab === 'history', fn ($query) => $query->whereDate('created_at', '<', today()))
            ->orderByDesc('created_at')
            ->paginate((int) ($data['per_page'] ?? 10))
            ->withQueryString();

        $mappedOrders = collect($orders->items())->map(
            fn (Order $order) => $this->orderPayload($order, (int) $provider->user_id)
        );

        return response()->json([
            'success' => true,
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'is_active' => (bool) $provider->is_active,
                'trusted_drivers_enabled' => (bool) $provider->trusted_drivers_enabled,
            ],
            'statuses' => Order::STATUSES,
            'orders' => $mappedOrders,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
            'counts' => $counts,
        ]);
    }

    /**
     * PATCH /api/mobile/v1/provider/dispatch/orders/{order}/status
     *
     * Recorre la cadena de estados o cancela. Solo pedidos del comercio.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        if ((int) $order->provider_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ese pedido no pertenece a tu comercio.',
            ], 403);
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', Order::STATUSES)],
            'delivery_type' => ['nullable', 'string', 'in:merchant,platform,takeaway'],
            'driver_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (! in_array($data['status'], $order->allowedTransitions(false), true)) {
            return response()->json([
                'success' => false,
                'message' => 'El pedido no puede pasar de "'.$order->status.'" a "'.$data['status'].'".',
                'errors' => ['status' => ['El estado indicado no es valido para este pedido.']],
            ], 422);
        }

        DB::transaction(function () use ($order, $data) {
            if ($data['status'] === Order::STATUS_CANCELLED) {
                $this->inventory->restoreCancelledOrder($order);
            }

            $updateData = ['status' => $data['status']];

            if (isset($data['delivery_type'])) {
                $updateData['delivery_type'] = $data['delivery_type'];
            }

            if (array_key_exists('driver_id', $data)) {
                $updateData['driver_id'] = $data['driver_id'];

                if ($data['driver_id']) {
                    $updateData['delivery_status'] = 'assigned';
                }
            }

            $order->update($updateData);
        });

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado.',
            'status' => $order->status,
            'order' => $this->orderPayload($order->fresh(), (int) $provider->user_id),
        ]);
    }

    /**
     * POST /api/mobile/v1/provider/dispatch/orders/{order}/send-to-pool
     *
     * Liberacion manual al pool general. No existe liberacion automatica.
     */
    public function sendToPool(Request $request, Order $order): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        if ((int) $order->provider_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ese pedido no pertenece a tu comercio.',
            ], 404);
        }

        if (! empty($order->driver_id)) {
            return response()->json([
                'success' => false,
                'message' => 'El pedido ya tiene un repartidor asignado.',
            ], 422);
        }

        if (! $order->isReleasedToPool()) {
            $order->update(['released_to_pool_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pedido enviado al pool general. Ya puede tomarlo cualquier repartidor.',
            'order' => $this->orderPayload($order->fresh(), (int) $provider->user_id),
        ]);
    }

    /**
     * POST /api/mobile/v1/provider/dispatch/orders/{order}/rate
     *
     * El comercio califica al cliente en pedidos entregados. Una sola
     * calificacion por autor y pedido, igual que la web.
     */
    public function rateOrder(Request $request, Order $order): JsonResponse
    {
        $provider = $this->providerOrFail($request);

        if ((int) $order->provider_id !== (int) $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés permisos para calificar este pedido.',
            ], 403);
        }

        if ($order->status !== Order::STATUS_DELIVERED) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden calificar pedidos entregados.',
            ], 422);
        }

        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->loadMissing(['ratings']);
        $reviewerId = (int) $provider->user_id;

        $existing = $order->ratings->first(
            fn ($rating) => (int) $rating->reviewer_id === $reviewerId
        );

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Ya calificaste este pedido.',
            ], 422);
        }

        try {
            $rating = DB::transaction(function () use ($order, $reviewerId, $provider, $data) {
                $rating = OrderRating::create([
                    'order_id' => $order->id,
                    'reviewer_id' => $reviewerId,
                    'ratee_id' => $order->user_id,
                    'stars' => $data['stars'],
                    'comment' => $data['comment'] ?? null,
                ]);

                // Mismo recalculo consolidado que la web.
                $allRatings = OrderRating::where('ratee_id', $provider->user_id);
                $promedio = $allRatings->avg('stars');
                $count = $allRatings->count();

                $provider->rating = round((float) $promedio, 2);
                $provider->rating_count = (int) $count;
                $provider->save();

                return $rating;
            });
        } catch (\Exception $e) {
            Log::error('Calificacion pedido (movil): no se pudo registrar en la orden #'.$order->id.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la calificacion. Intenta nuevamente.',
            ], 500);
        }

        $order->load('ratings');
        $customerRating = $order->ratings->first(
            fn ($value) => (int) $value->reviewer_id === (int) $order->user_id
        );
        $providerRating = $order->ratings->first(
            fn ($value) => (int) $value->reviewer_id === (int) $provider->user_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Gracias por calificar al cliente.',
            'rating' => [
                'id' => $rating->id,
                'order_id' => $rating->order_id,
                'reviewer_id' => $rating->reviewer_id,
                'ratee_id' => $rating->ratee_id,
                'stars' => $rating->stars,
                'comment' => $rating->comment,
                'created_at' => $rating->created_at?->toIso8601String(),
            ],
            'customer_rated' => (bool) $customerRating,
            'customer_rating' => $customerRating ? [
                'stars' => $customerRating->stars,
                'comment' => $customerRating->comment,
            ] : null,
            'provider_rated' => (bool) $providerRating,
            'provider_rating' => $providerRating ? [
                'stars' => $providerRating->stars,
                'comment' => $providerRating->comment,
            ] : null,
            'provider_global_rating' => $provider->fresh()->rating,
        ]);
    }

    /**
     * Payload del pedido con las mismas relaciones y banderas de calificacion
     * que arma OrderController@index en la web.
     */
    private function orderPayload(Order $order, int $providerUserId): array
    {
        $order->loadMissing([
            'user:id,name,email,phone',
            'items.options',
            'address:id,street,number,floor_apartment,postal_code,notes',
            'payments:id,order_id,provider,status,amount,created_at',
            'ratings',
            'driver:id,name,email,phone',
        ]);

        $customerRating = $order->ratings->first(
            fn ($rating) => (int) $rating->reviewer_id === (int) $order->user_id
        );
        $providerRating = $order->ratings->first(
            fn ($rating) => (int) $rating->reviewer_id === $providerUserId
        );

        $arr = $order->toArray();
        $arr['customer_rated'] = (bool) $customerRating;
        $arr['customer_rating'] = $customerRating ? [
            'stars' => $customerRating->stars,
            'comment' => $customerRating->comment,
        ] : null;
        $arr['provider_rated'] = (bool) $providerRating;
        $arr['provider_rating'] = $providerRating ? [
            'stars' => $providerRating->stars,
            'comment' => $providerRating->comment,
        ] : null;

        return $arr;
    }
}
