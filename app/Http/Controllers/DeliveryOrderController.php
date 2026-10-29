<?php

namespace App\Http\Controllers;

use App\Models\DriverWallet;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeliveryOrderController extends Controller
{
    /**
     * Estadísticas del panel del repartidor. Compartida entre el acceso
     * directo (/dashboard/repartidor) y la vista incluida dentro del
     * dashboard, que arma las secciones sin pasar variables extra.
     */
    public static function driverStats($user): array
    {
        // Ganancias del día: créditos acreditados en la billetera hoy.
        // Se desglosan en tarifa base y propinas para el KPI del panel.
        $todayCredits = WalletTransaction::where('user_id', $user->id)
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->whereDate('created_at', now()->toDateString())
            ->get();

        $earningsToday = (float) $todayCredits->sum('amount');
        $tipsToday = (float) $todayCredits
            ->filter(fn ($t) => preg_match('/propina|tip/i', (string) $t->description))
            ->sum('amount');
        $baseToday = $earningsToday - $tipsToday;

        // Entregas completadas por este repartidor.
        $completed = (int) Order::where('driver_id', $user->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->count();

        // Entregas realizadas durante la jornada de hoy (meta del día).
        $deliveredToday = (int) Order::where('driver_id', $user->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereDate('updated_at', now()->toDateString())
            ->count();

        // Aceptación: pedidos asignados que terminaron entregados.
        $assigned = (int) Order::where('driver_id', $user->id)->count();
        $acceptance = $assigned > 0 ? round(($completed / $assigned) * 100) : null;

        // Calificación promedio recibida como repartidor.
        $ratingRow = DB::table('order_ratings')
            ->where('ratee_id', $user->id)
            ->selectRaw('AVG(stars) as avg_stars, COUNT(*) as total')
            ->first();
        $rating = $ratingRow && $ratingRow->total > 0 ? round((float) $ratingRow->avg_stars, 1) : null;

        $wallet = DriverWallet::where('user_id', $user->id)->first();

        return [
            'earningsToday' => $earningsToday,
            'baseToday' => $baseToday,
            'tipsToday' => $tipsToday,
            'completed' => $completed,
            'deliveredToday' => $deliveredToday,
            'acceptance' => $acceptance,
            'rating' => $rating,
            'ratingCount' => $ratingRow->total ?? 0,
            'walletBalance' => (float) ($wallet->balance ?? 0),
        ];
    }

    /**
     * Vista principal para la sección de repartidores dentro del dashboard.
     * Prepara las estadísticas del panel (ganancias, entregas, aceptación y
     * calificación) junto al usuario logueado.
     *
     * La sección vive dentro del dashboard unificado (mismo patrón que
     * "Ordenes y Gestion"), así que la URL directa deja ese dashboard abierto
     * en la sección del repartidor en vez de servir la plantilla a medias.
     */
    public function index()
    {
        if (! Auth::check()) {
            // La ruta ya va con middleware "auth"; si igual llega un invitado,
            // se manda al home (no existe GET /login en esta app).
            return redirect('/');
        }

        if (! Auth::user()->hasPageUrl('repartidor')) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('dashboard', ['section' => 'repartidor']);
    }

    /**
     * Actualiza el tipo de vehiculo (y opcionalmente la patente) del
     * repartidor autenticado. Se usa tanto desde la Terminal Central de
     * Repartos como desde "Datos del Vehiculo" en el perfil.
     */
    public function updateVehicle(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user()->loadMissing('typeUser');

        if (! $user->isDriver()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Solo los repartidores pueden actualizar su vehículo.',
            ], 403);
        }

        $data = $request->validate([
            'vehicle_type' => ['required', 'string', Rule::in(array_keys(User::VEHICLE_TYPES))],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
        ]);

        $attributes = [
            'vehicle_type' => $data['vehicle_type'],
        ];

        // La patente es opcional: solo se pisa si el formulario la envio.
        if ($request->exists('vehicle_plate')) {
            $plate = trim((string) $request->input('vehicle_plate'));

            $attributes['vehicle_plate'] = $plate !== '' ? mb_strtoupper($plate) : null;
        }

        $user->update($attributes);

        return response()->json([
            'status' => 'success',
            'vehicle_type' => $user->vehicle_type,
            'vehicle_plate' => $user->vehicle_plate,
            'label' => $user->vehicleTypeLabel(),
            'message' => 'Vehículo actualizado correctamente.',
        ]);
    }

    /**
     * Pedidos ya asignados al repartidor logueado (pestana "En curso").
     * Trae comercio, direccion de retiro, cliente y su contacto para poder
     * armar la tarjeta con el itinerario completo.
     */
    public function myOrders(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $orders = Order::with([
            // user_id hace falta en el select: sin él provider.user (y por lo
            // tanto la direccion de retiro del comercio) llega siempre null.
            'provider:id,user_id,business_name,phone,zone',
            'provider.user:id,name,phone',
            'provider.user.primaryAddress',
            'provider.user.primaryAddress.region',
            'provider.user.primaryAddress.province',
            'address',
            'address.region',
            'address.province',
            'address.department',
            'address.country',
            'user:id,name,phone',
        ])
            ->where('driver_id', Auth::id())
            ->whereIn('delivery_status', ['assigned', 'picked_up', 'on_the_way'])
            ->latest()
            ->get();

        // El PIN solo sirve para que el repartidor lo verifique con el cliente:
        // no viaja en la respuesta, solo un flag para mostrar/ocultar el campo.
        $orders->each(function (Order $order) {
            $order->setAttribute('has_delivery_pin', (bool) $order->delivery_pin);
            $order->makeHidden(['delivery_pin']);
        });

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * Marca el pedido como retirado en el comercio: paso 1 -> paso 2 del
     * indicador "En retiro / En entrega / Entregado".
     */
    public function markPickedUp(Request $request, Order $order)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

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
            'order' => $order->fresh(),
        ]);
    }

    /**
     * Lista los pedidos disponibles para el repartidor.
     * Debe mostrar pedidos con envío por repartidor que aún no tengan conductor asignado.
     */
    public function availableOrders(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $orders = Order::with([
            'provider:id,business_name,phone,zone',
            'address:id,street,number,floor_apartment,postal_code,notes',
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
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * Permite al repartidor logueado tomar/asignarse un pedido disponible.
     */
    public function takeOrder(Request $request, Order $order)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

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

        DB::transaction(function () use ($order, $user) {
            $order->update([
                'driver_id' => $user->id,
                'delivery_status' => 'assigned',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pedido asignado correctamente.',
            'order' => $order->load(['provider:id,business_name,phone,zone', 'address', 'driver:id,name,phone']),
        ]);
    }

    /**
     * Confirma la entrega exitosa del pedido validando el PIN de entrega.
     * Actualiza la orden a 'delivered' y acredita la tarifa en la billetera del repartidor.
     */
    public function confirmDelivery(Request $request, Order $order)
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $user = Auth::user();

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

        DB::transaction(function () use ($order, $user) {
            $order->update([
                'delivery_status' => 'delivered',
                'status' => Order::STATUS_DELIVERED,
            ]);

            $fee = (float) ($order->delivery_fee > 0 ? $order->delivery_fee : 500.00); // Tarifa base por defecto

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
                'description' => 'Pago por entrega de pedido #' . $order->order_number,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => '¡Entrega confirmada con éxito! Se han acreditado los fondos en tu billetera.',
            'order' => $order->fresh(['driver:id,name,phone']),
            'wallet' => DriverWallet::where('user_id', $user->id)->first(),
        ]);
    }
}
