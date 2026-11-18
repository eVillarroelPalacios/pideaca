<?php

namespace App\Http\Controllers;

use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Checkout Mercado Pago (Fase 3): crea la preferencia y atiende el webhook.
 * Sirve a web y móvil (mismo controlador, distintas rutas).
 */
class MercadoPagoCheckoutController extends Controller
{
    public function __construct(protected CheckoutService $checkout) {}

    public function start(Request $request)
    {
        $data = $request->validate([
            'order_type' => ['required', 'string', 'in:order,service_order'],
            'order_id' => ['required', 'integer', 'min:1'],
        ]);

        $owner = $data['order_type'] === 'service_order'
            ? \App\Models\ServiceOrder::whereKey((int) $data['order_id'])->value('customer_id')
            : \App\Models\Order::whereKey((int) $data['order_id'])->value('user_id');

        if (! $owner || ((int) $owner !== (int) $request->user()->id && ! $request->user()->isAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Ese pedido no existe o no es tuyo.',
            ], 404);
        }

        try {
            $result = $this->checkout->startMercadoPago(
                $data['order_type'],
                (int) $data['order_id'],
                route('checkout.mp.return'),
                route('checkout.mp.webhook')
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors()->toArray(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No pudimos iniciar el pago. Intentá de nuevo.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'init_point' => $result['init_point'],
            'preference_id' => $result['preference_id'],
            'charge_id' => $result['charge']->id,
        ]);
    }

    public function webhook(Request $request)
    {
        try {
            $status = $this->checkout->handleWebhook(
                $request->all(),
                $request->header('x-signature'),
                $request->header('x-request-id')
            );
        } catch (ValidationException) {
            return response()->json(['success' => false], 403);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false], 500);
        }

        return response()->json(['success' => true, 'status' => $status]);
    }

    public function retorno(Request $request)
    {
        return view('checkout.mp-return', [
            'status' => $request->input('status', 'pending'),
            'chargeId' => $request->input('charge_id'),
        ]);
    }
}
