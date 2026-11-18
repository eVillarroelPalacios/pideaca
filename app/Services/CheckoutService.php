<?php

namespace App\Services;

use App\Models\Charge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Checkout Mercado Pago (Fase 3, cuenta única recaudadora).
 *
 * Flujo: se crea/actualiza el charge del pedido con medio 'mercadopago',
 * se genera la preferencia de Checkout Pro y el webhook confirma el pago.
 * Todo es idempotente por charge y por payment id de MP.
 */
class CheckoutService
{
    public function __construct(
        protected BillingService $billing,
        protected MercadoPagoGateway $gateway
    ) {}

    /**
     * Inicia el pago MP de un pedido. Devuelve init_point + charge.
     */
    public function startMercadoPago(string $orderType, int $orderId, string $returnBaseUrl, string $notificationUrl): array
    {
        if (! $this->gateway->configured()) {
            throw ValidationException::withMessages([
                'mercadopago' => 'El pago con Mercado Pago no está disponible por el momento.',
            ]);
        }

        $module = $orderType === 'service_order' ? 'hogar' : 'mercado';

        $ownerId = $orderType === 'service_order'
            ? optional(\App\Models\ServiceOrder::find($orderId))->user_id
            : optional(\App\Models\Order::find($orderId))->user_id;

        if (! $ownerId) {
            throw ValidationException::withMessages([
                'order' => 'El pedido no existe.',
            ]);
        }

        $total = $orderType === 'service_order'
            ? (float) (optional(\App\Models\ServiceOrder::find($orderId))->total ?? 0)
            : (float) (optional(\App\Models\Order::find($orderId))->total ?? 0);

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'order' => 'El pedido no tiene monto a cobrar.',
            ]);
        }

        return DB::transaction(function () use ($module, $orderType, $orderId, $total, $returnBaseUrl, $notificationUrl) {
            $charge = Charge::where('order_type', $orderType)->where('order_id', $orderId)->first();

            if ($charge && $charge->status === Charge::STATUS_VERIFIED) {
                throw ValidationException::withMessages([
                    'order' => 'Ese pedido ya está pagado.',
                ]);
            }

            if (! $charge) {
                $charge = $this->billing->registerManualCharge(
                    $module,
                    $orderType,
                    $orderId,
                    $total,
                    Charge::METHOD_MERCADOPAGO,
                    null,
                    'ARS'
                );
            }

            $pref = $this->gateway->createPreference([
                'title' => 'Pedido #' . $orderId . ' - PideAca',
                'amount' => (float) $charge->amount,
                'currency' => $charge->currency,
                'external_reference' => 'charge:' . $charge->id,
                'back_urls' => [
                    'success' => rtrim($returnBaseUrl, '/') . '?status=success&charge_id=' . $charge->id,
                    'failure' => rtrim($returnBaseUrl, '/') . '?status=failure&charge_id=' . $charge->id,
                    'pending' => rtrim($returnBaseUrl, '/') . '?status=pending&charge_id=' . $charge->id,
                ],
                'notification_url' => $notificationUrl,
            ]);

            $charge->update(['transaction_reference' => $pref['id']]);

            return ['charge' => $charge->fresh(), 'init_point' => $pref['init_point'], 'preference_id' => $pref['id']];
        });
    }

    /**
     * Procesa un webhook de MP. Devuelve el estado resultante ('verified', 'pending', 'ignored').
     */
    public function handleWebhook(array $payload, ?string $xSignature, ?string $xRequestId): string
    {
        $type = $payload['type'] ?? null;
        $dataId = (string) ($payload['data']['id'] ?? '');

        if ($type !== 'payment' || $dataId === '') {
            return 'ignored';
        }

        if (! MercadoPagoGateway::validSignature($xSignature, $xRequestId, $dataId)) {
            Log::warning('MP webhook con firma inválida', ['data_id' => $dataId]);

            throw ValidationException::withMessages(['webhook' => 'Firma inválida.']);
        }

        return DB::transaction(function () use ($dataId) {
            $existing = Charge::where('transaction_reference', $dataId)
                ->where('status', Charge::STATUS_VERIFIED)
                ->first();

            if ($existing) {
                return 'verified';
            }

            $payment = $this->gateway->getPayment($dataId);

            if (! $payment) {
                return 'ignored';
            }

            $external = (string) ($payment['external_reference'] ?? '');

            if (! str_starts_with($external, 'charge:')) {
                return 'ignored';
            }

            $charge = Charge::whereKey((int) substr($external, strlen('charge:')))->lockForUpdate()->first();

            if (! $charge || $charge->status === Charge::STATUS_VERIFIED) {
                return $charge ? 'verified' : 'ignored';
            }

            if (($payment['status'] ?? null) !== 'approved') {
                $charge->update(['transaction_reference' => (string) $dataId]);

                return 'pending';
            }

            $charge->update(['status' => Charge::STATUS_VERIFIED, 'transaction_reference' => (string) $dataId]);

            // El asiento de comisión ya existe desde el registro del cobro.
            return 'verified';
        });
    }
}
