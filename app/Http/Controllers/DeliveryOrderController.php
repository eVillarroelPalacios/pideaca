<?php

namespace App\Http\Controllers;

use App\Models\DriverWallet;
use App\Models\Order;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeliveryOrderController extends Controller
{
    /**
     * Lista los pedidos disponibles para la plataforma de delivery:
     * delivery_type = 'platform' y delivery_status = 'pending'.
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
            ->where('delivery_type', 'platform')
            ->where('delivery_status', 'pending')
            ->where('status', 'on_the_way') // O listos para entregar
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

        if ($order->delivery_type !== 'platform' || $order->delivery_status !== 'pending') {
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
