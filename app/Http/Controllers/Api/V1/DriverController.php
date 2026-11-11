<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DeliveryOrderController;
use App\Models\Address;
use App\Models\DriverWallet;
use App\Models\Order;
use App\Models\ProviderDriver;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Terminal Central de Repartos (Driver Pro) para la app movil.
 *
 * Espeja la logica de la seccion web `repartidor.blade.php` y de
 * DeliveryOrderController, pero resuelve al usuario desde Sanctum. La
 * conexion online/offline, la zona y la ultima posicion GPS no tienen tabla
 * en la web (viven en JS/localStorage y navigator.geolocation), asi que aca
 * se guardan en cache por usuario para que sobrevivan a recargas de la app.
 */
class DriverController extends Controller
{
    private const STATUS_CACHE_MINUTES = 30;

    /**
     * GET /api/mobile/v1/driver/stats
     *
     * KPIs del dia + perfil del repartidor + catalogo de vehiculos, en una
     * sola llamada para pintar cabecera y tarjetas sin esperar al polling.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $this->driverOrFail($request);
        $stats = DeliveryOrderController::driverStats($user);

        return response()->json([
            'success' => true,
            'stats' => [
                'earnings_today' => $stats['earningsToday'],
                'base_today' => $stats['baseToday'],
                'tips_today' => $stats['tipsToday'],
                'completed' => $stats['completed'],
                'delivered_today' => $stats['deliveredToday'],
                'acceptance' => $stats['acceptance'],
                'rating' => $stats['rating'],
                'rating_count' => $stats['ratingCount'],
                'wallet_balance' => $stats['walletBalance'],
            ],
            'driver' => $this->driverPayload($user),
            'vehicle_types' => collect(User::VEHICLE_TYPES)
                ->map(fn (string $label, string $value) => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values(),
        ]);
    }

    /**
     * GET /api/mobile/v1/driver/status
     *
     * Estado de conexion, zona de cobertura y ultima posicion GPS del
     * repartidor. La web solo lo guarda en el navegador; aca se persiste en
     * cache para restaurar la terminal al abrir la app.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $this->driverOrFail($request);

        return response()->json([
            'success' => true,
            'status' => $this->readStatus($user),
        ]);
    }

    /**
     * PUT /api/mobile/v1/driver/status
     *
     * Guarda el estado que reporta la app. No toca tablas ni el flujo de
     * pedidos: es solo telemetria del panel.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $user = $this->driverOrFail($request);

        $data = $request->validate([
            'online' => ['required', 'boolean'],
            'zone' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $hasPosition = isset($data['latitude'], $data['longitude']);

        $status = [
            'online' => (bool) $data['online'],
            'zone' => isset($data['zone']) && trim((string) $data['zone']) !== ''
                ? trim((string) $data['zone'])
                : null,
            'latitude' => $hasPosition ? (float) $data['latitude'] : null,
            'longitude' => $hasPosition ? (float) $data['longitude'] : null,
            'accuracy' => $hasPosition && isset($data['accuracy'])
                ? (float) $data['accuracy']
                : null,
            'updated_at' => now()->toIso8601String(),
        ];

        Cache::put($this->statusKey($user), $status, now()->addMinutes(self::STATUS_CACHE_MINUTES));

        return response()->json([
            'success' => true,
            'message' => 'Estado del repartidor actualizado.',
            'status' => $status,
        ]);
    }

    /**
     * GET /api/mobile/v1/driver/orders/available
     *
     * Ofertas de viaje. Copia los filtros de DeliveryOrderController@availableOrders:
     * envio por repartidor, sin conductor y con la modalidad de confianza
     * respetada (o el pedido liberado manualmente al pool).
     */
    public function availableOrders(Request $request): JsonResponse
    {
        $user = $this->driverOrFail($request);

        $orders = Order::with([
            'provider:id,business_name,phone,zone',
            'address:id,street,number,floor_apartment,postal_code,notes,latitude,longitude,region_id,department_id,province_id',
            'address.region:id,name',
            'address.department:id,name',
            'address.province:id,name',
        ])
            ->where(function ($q) {
                $q->where('delivery_type', 'platform')
                    ->orWhere('delivery_type', 'delivery')
                    ->orWhereNull('delivery_type');
            })
            ->where(function ($q) {
                $q->whereNull('driver_id')
                    ->orWhere('driver_id', 0);
            })
            ->where(function ($q) {
                $q->whereNull('delivery_status')
                    ->orWhereIn('delivery_status', ['pending', 'assigned']);
            })
            ->where(function ($q) {
                $q->whereIn('status', [
                    'in_preparation',
                    'preparing',
                    'preparation',
                    'on_the_way',
                    'ready',
                    'ready_for_delivery',
                    'pending',
                    'confirmed',
                ])
                    ->orWhereRaw('LOWER(status) LIKE ?', ['%prepar%'])
                    ->orWhereRaw('LOWER(status) LIKE ?', ['%ready%']);
            })
            ->where(function ($q) use ($user) {
                $q->whereNotNull('orders.released_to_pool_at')
                    ->orWhereDoesntHave('provider', fn ($provider) => $provider
                        ->where('trusted_drivers_enabled', true))
                    ->orWhereHas('provider', function ($provider) use ($user) {
                        $provider->where('trusted_drivers_enabled', true)
                            ->whereHas('providerDrivers', fn ($trusted) => $trusted
                                ->where('user_id', $user->id)
                                ->where('status', 'active'));
                    });
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
                ->map(fn (Order $order) => $this->offerPayload($order))
                ->values(),
        ]);
    }

    /**
     * GET /api/mobile/v1/driver/orders/mine
     *
     * Pedidos ya asignados al repartidor (pestana "En Curso"). Incluye el
     * retiro en el comercio, la direccion del cliente y su telefono.
     */
    public function myOrders(Request $request): JsonResponse
    {
        $user = $this->driverOrFail($request);

        $orders = $this->assignedOrdersQuery($user)
            ->whereIn('delivery_status', ['assigned', 'picked_up', 'on_the_way'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
                ->map(fn (Order $order) => $this->activePayload($order))
                ->values(),
        ]);
    }

    /**
     * POST /api/mobile/v1/driver/orders/{order}/take
     *
     * Asigna el pedido al repartidor autenticado con bloqueo pesimista, igual
     * que la web, y revalida la restriccion de repartidores de confianza.
     */
    public function takeOrder(Request $request, Order $order): JsonResponse
    {
        $user = $this->driverOrFail($request);

        $hasDriver = ! empty($order->driver_id);
        $deliveryType = $order->delivery_type;
        $deliveryOk = $deliveryType === 'platform' || $deliveryType === 'delivery' || $deliveryType === null;
        $statusOk = true;

        if (! is_null($order->delivery_status)) {
            $statusOk = in_array($order->delivery_status, ['pending', 'assigned'], true);
        }

        if (! $deliveryOk || $hasDriver || ! $statusOk) {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido ya no está disponible para tomar.',
            ], 422);
        }

        if (! $this->driverCanTakeTrustedOrder($order, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido está reservado para los repartidores de confianza del comercio.',
            ], 403);
        }

        $assigned = DB::transaction(function () use ($order, $user) {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if (! $locked || ! empty($locked->driver_id)) {
                return false;
            }

            if (! is_null($locked->delivery_status)
                && ! in_array($locked->delivery_status, ['pending', 'assigned'], true)) {
                return false;
            }

            if (! $this->driverCanTakeTrustedOrder($locked, $user)) {
                return false;
            }

            $locked->update([
                'driver_id' => $user->id,
                'delivery_status' => 'assigned',
            ]);

            return true;
        });

        if (! $assigned) {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido ya no está disponible para tomar.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pedido asignado correctamente.',
            'order' => $this->activePayload($this->loadAssignedOrder($order->getKey())),
        ]);
    }

    /**
     * POST /api/mobile/v1/driver/orders/{order}/pickup
     *
     * Paso 1 -> 2 del stepper: el repartidor confirma que retiro el pedido.
     */
    public function markPickedUp(Request $request, Order $order): JsonResponse
    {
        $user = $this->driverOrFail($request);

        if ((int) $order->driver_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés asignado este pedido.',
            ], 403);
        }

        if ($order->delivery_status !== 'assigned') {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido ya fue retirado.',
            ], 422);
        }

        DB::transaction(function () use ($order) {
            $order->update(['delivery_status' => 'picked_up']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pedido retirado. Ahora entregalo al cliente.',
            'order' => $this->activePayload($this->loadAssignedOrder($order->getKey())),
        ]);
    }

    /**
     * POST /api/mobile/v1/driver/orders/{order}/confirm
     *
     * Confirma la entrega validando el PIN y acredita la tarifa en la
     * billetera del repartidor, igual que la web.
     */
    public function confirmDelivery(Request $request, Order $order): JsonResponse
    {
        $user = $this->driverOrFail($request);

        if ((int) $order->driver_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tenés asignado este pedido.',
            ], 403);
        }

        if ($order->delivery_status === 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido ya fue entregado.',
            ], 422);
        }

        $data = $request->validate([
            'delivery_pin' => ['required', 'string'],
        ]);

        if ($order->delivery_pin && trim($order->delivery_pin) !== trim($data['delivery_pin'])) {
            return response()->json([
                'success' => false,
                'message' => 'El PIN de entrega es incorrecto.',
            ], 422);
        }

        $wallet = DB::transaction(function () use ($order, $user) {
            $order->update([
                'delivery_status' => 'delivered',
                'status' => Order::STATUS_DELIVERED,
            ]);

            $fee = (float) ($order->delivery_fee > 0 ? $order->delivery_fee : 500.00);

            $wallet = DriverWallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'pending_balance' => 0]
            );

            $wallet->balance += $fee;
            $wallet->save();

            WalletTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'amount' => $fee,
                'type' => 'credit',
                'status' => 'completed',
                'description' => 'Pago por entrega de pedido #'.$order->order_number,
            ]);

            return $wallet;
        });

        return response()->json([
            'success' => true,
            'message' => '¡Entrega confirmada con éxito! Se han acreditado los fondos en tu billetera.',
            'order' => $this->activePayload($this->loadAssignedOrder($order->getKey())),
            'wallet' => [
                'balance' => (float) $wallet->balance,
            ],
        ]);
    }

    /**
     * Restriccion de despacho por repartidores de confianza, sin fallback
     * por tiempo: solo se libera cuando el comercio lo manda al pool.
     */
    private function driverCanTakeTrustedOrder(Order $order, User $user): bool
    {
        if ($order->isReleasedToPool()) {
            return true;
        }

        $provider = $order->relationLoaded('provider')
            ? $order->provider
            : $order->provider()->first();

        if (! $provider || ! $provider->trusted_drivers_enabled) {
            return true;
        }

        return ProviderDriver::query()
            ->where('provider_id', $provider->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    private function assignedOrdersQuery(User $user): Builder
    {
        return Order::with([
            'provider:id,user_id,business_name,phone,zone',
            'provider.user:id,name,phone',
            'provider.user.primaryAddress',
            'provider.user.primaryAddress.region',
            'provider.user.primaryAddress.department',
            'provider.user.primaryAddress.province',
            'address',
            'address.region',
            'address.department',
            'address.province',
            'address.country',
            'user:id,name,phone',
        ])->where('driver_id', $user->id);
    }

    private function loadAssignedOrder(int $orderId): Order
    {
        return Order::with([
            'provider:id,user_id,business_name,phone,zone',
            'provider.user:id,name,phone',
            'provider.user.primaryAddress',
            'provider.user.primaryAddress.region',
            'provider.user.primaryAddress.department',
            'provider.user.primaryAddress.province',
            'address',
            'address.region',
            'address.department',
            'address.province',
            'address.country',
            'user:id,name,phone',
        ])->findOrFail($orderId);
    }

    /**
     * Payload de una oferta disponible: lo justo para la tarjeta de la
     * pestana "Pedidos Disponibles".
     */
    private function offerPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'delivery_status' => $order->delivery_status,
            'delivery_type' => $order->delivery_type,
            'delivery_fee' => (float) $order->delivery_fee,
            'total' => (float) $order->total,
            'payment_method' => $order->payment_method,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toIso8601String(),
            'provider' => [
                'id' => $order->provider?->id,
                'business_name' => $order->provider?->business_name,
                'phone' => $order->provider?->phone,
                'zone' => $order->provider?->zone,
            ],
            'address' => $this->addressPayload($order->address),
        ];
    }

    /**
     * Payload de un pedido en curso: incluye retiro en el comercio, cliente
     * y bandera de PIN (el PIN real nunca viaja al dispositivo).
     */
    private function activePayload(Order $order): array
    {
        $pickup = $order->provider?->user?->primaryAddress;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'delivery_status' => $order->delivery_status,
            'delivery_type' => $order->delivery_type,
            'delivery_fee' => (float) $order->delivery_fee,
            'total' => (float) $order->total,
            'payment_method' => $order->payment_method,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toIso8601String(),
            'has_delivery_pin' => (bool) $order->delivery_pin,
            'provider' => [
                'id' => $order->provider?->id,
                'business_name' => $order->provider?->business_name,
                'phone' => $order->provider?->phone,
                'zone' => $order->provider?->zone,
                'pickup_address' => $this->addressPayload($pickup),
            ],
            'customer' => [
                'id' => $order->user?->id,
                'name' => $order->user?->name,
                'phone' => $order->user?->phone,
            ],
            'address' => $this->addressPayload($order->address),
        ];
    }

    private function addressPayload(?Address $address): ?array
    {
        if (! $address) {
            return null;
        }

        $locality = collect([
            $address->region?->name,
            $address->department?->name,
            $address->province?->name,
        ])->filter()->unique()->implode(', ');

        $line = trim(implode(' ', array_filter([$address->street, $address->number])));

        if ($address->floor_apartment) {
            $line = trim($line.', '.$address->floor_apartment);
        }

        $full = trim(implode(', ', array_filter([$line, $locality])));

        return [
            'id' => $address->id,
            'street' => $address->street,
            'number' => $address->number,
            'floor_apartment' => $address->floor_apartment,
            'postal_code' => $address->postal_code,
            'notes' => $address->notes,
            'locality' => $locality !== '' ? $locality : null,
            'latitude' => $address->latitude !== null ? (float) $address->latitude : null,
            'longitude' => $address->longitude !== null ? (float) $address->longitude : null,
            'full_line' => $full !== '' ? $full : 'Sin dirección cargada',
        ];
    }

    private function driverPayload(User $user): array
    {
        $parts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
        $initials = strtoupper(substr($parts[0] ?? '', 0, 1))
            .(isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'initials' => $initials,
            'code' => 'DRV-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            'vehicle_type' => $user->vehicle_type,
            'vehicle_label' => $user->vehicleTypeLabel(),
            'vehicle_plate' => $user->vehicle_plate,
            'has_vehicle' => filled($user->vehicle_type),
        ];
    }

    /**
     * El panel es exclusivo del rol Repartidor (el admin conserva el bypass
     * que ya tiene en la web).
     */
    private function driverOrFail(Request $request): User
    {
        $user = $request->user();
        $user?->loadMissing('typeUser');

        if (! $user || (! $user->isDriver() && ! $user->isAdmin())) {
            abort(response()->json([
                'success' => false,
                'message' => 'Esta terminal es exclusiva para repartidores.',
                'code' => 'NOT_A_DRIVER',
            ], 403));
        }

        return $user;
    }

    private function statusKey(User $user): string
    {
        return 'driver:status:'.$user->id;
    }

    private function readStatus(User $user): array
    {
        return Cache::get($this->statusKey($user), [
            'online' => true,
            'zone' => null,
            'latitude' => null,
            'longitude' => null,
            'accuracy' => null,
            'updated_at' => null,
        ]);
    }
}
