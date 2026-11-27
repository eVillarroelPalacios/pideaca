<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\MpSellerAccount;
use App\Models\Order;
use App\Models\Provider;
use App\Models\ServiceOrder;
use App\Models\SettlementItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Checkout Mercado Pago (Fase 3, cuenta única recaudadora + split).
 *
 * Flujo: se crea/actualiza el charge del pedido con medio 'mercadopago',
 * se genera la preferencia de Checkout Pro y el webhook confirma el pago.
 * Todo es idempotente por charge y por payment id de MP.
 *
 * Split automático (Marketplace): si el vendedor conectó su cuenta, la
 * preferencia se crea con su token y application_fee = comisión vigente;
 * el dinero va directo al vendedor y la liquidación se marca pagada sola.
 * Sin cuenta conectada se recauda en PideAca y liquida manual (fallback).
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
            ? optional(ServiceOrder::find($orderId))->user_id
            : optional(Order::find($orderId))->user_id;

        if (! $ownerId) {
            throw ValidationException::withMessages([
                'order' => 'El pedido no existe.',
            ]);
        }

        $total = $orderType === 'service_order'
            ? (float) (optional(ServiceOrder::find($orderId))->total ?? 0)
            : (float) (optional(Order::find($orderId))->total ?? 0);

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

            // Split automático: si el vendedor conectó su MP, la comisión
            // vigente viaja como application_fee. Si la comisión cubre el
            // total (mínimo alto en ticket chico) no hay split posible.
            $sellerId = $this->sellerUserId($orderType, $orderId);
            $sellerToken = $sellerId ? $this->sellerToken($sellerId) : null;
            $fee = (float) $charge->fee;
            $amount = (float) $charge->amount;
            $split = $sellerToken !== null && $fee > 0 && $fee < $amount;
            $splitFee = $split ? $fee : null;

            if (! $split) {
                $sellerToken = null;
            }

            $pref = $this->gateway->createPreference([
                'title' => 'Pedido #'.$orderId.' - PideAca',
                'amount' => (float) $charge->amount,
                'currency' => $charge->currency,
                'external_reference' => 'charge:'.$charge->id,
                'back_urls' => [
                    'success' => rtrim($returnBaseUrl, '/').'?status=success&charge_id='.$charge->id,
                    'failure' => rtrim($returnBaseUrl, '/').'?status=failure&charge_id='.$charge->id,
                    'pending' => rtrim($returnBaseUrl, '/').'?status=pending&charge_id='.$charge->id,
                ],
                'notification_url' => $notificationUrl,
            ], $sellerToken, $splitFee);

            $charge->update([
                'transaction_reference' => $pref['id'],
                'split_marketplace' => $split,
            ]);

            return [
                'charge' => $charge->fresh(),
                'init_point' => $pref['init_point'],
                'preference_id' => $pref['id'],
                'split' => $split,
            ];
        });
    }

    /**
     * Vendedor del pedido (dueño del comercio/prestador que cobra).
     */
    public function sellerUserId(string $orderType, int $orderId): ?int
    {
        if ($orderType === 'service_order') {
            $merchantUserId = ServiceOrder::whereKey($orderId)->value('merchant_id');
            $merchant = $merchantUserId ? Provider::find($merchantUserId) : null;

            return $merchant?->user_id;
        }

        $providerId = Order::whereKey($orderId)->value('provider_id');

        return $providerId ? Provider::whereKey($providerId)->value('user_id') : null;
    }

    /**
     * Token MP vigente del vendedor (renueva si venció). Null si el
     * vendedor no conectó su cuenta: ahí se recauda en PideAca.
     */
    public function sellerToken(int $userId): ?string
    {
        $account = MpSellerAccount::where('user_id', $userId)->first();

        if (! $account) {
            return null;
        }

        if (! $account->isExpired()) {
            return $account->access_token;
        }

        if (! $account->refresh_token) {
            return null;
        }

        $fresh = $this->gateway->refreshAccessToken($account->refresh_token);

        if (! is_array($fresh) || empty($fresh['access_token'])) {
            return null;
        }

        $account->update([
            'access_token' => (string) $fresh['access_token'],
            'refresh_token' => isset($fresh['refresh_token']) ? (string) $fresh['refresh_token'] : $account->refresh_token,
            'expires_at' => isset($fresh['expires_in']) ? now()->addSeconds((int) $fresh['expires_in']) : null,
        ]);

        return $account->fresh()->access_token;
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

            if (! $charge) {
                return 'ignored';
            }

            if ($charge->status === Charge::STATUS_VERIFIED) {
                return 'verified';
            }

            // Pagos con split viven en la cuenta del vendedor: se confirma
            // el estado con su token (con fallback al de la app).
            if ($charge->split_marketplace) {
                $splitSellerId = $this->sellerUserId($charge->order_type, $charge->order_id);
                $splitToken = $splitSellerId ? $this->sellerToken($splitSellerId) : null;

                if ($splitToken) {
                    $payment = $this->gateway->getPayment($dataId, $splitToken) ?? $payment;
                }
            }

            if (($payment['status'] ?? null) !== 'approved') {
                $charge->update(['transaction_reference' => (string) $dataId]);

                return 'pending';
            }

            $charge->update(['status' => Charge::STATUS_VERIFIED, 'transaction_reference' => (string) $dataId]);

            // El asiento de comisión ya existe desde el registro del cobro.
            if ($charge->split_marketplace) {
                $this->settleSplitCharge($charge->fresh(), (string) $dataId);
            }

            return 'verified';
        });
    }

    /**
     * Liquidación automática de un cobro con split: como el dinero ya fue
     * directo al vendedor, se crea la liquidación y se marca pagada con la
     * referencia del pago de MP. Idempotente por item de liquidación.
     */
    private function settleSplitCharge(Charge $charge, string $paymentId): void
    {
        $sellerId = $this->sellerUserId($charge->order_type, $charge->order_id);

        if (! $sellerId) {
            return;
        }

        $already = SettlementItem::where('reference_type', 'charge')
            ->where('reference_id', $charge->id)
            ->exists();

        if ($already) {
            return;
        }

        $seller = User::find($sellerId);

        if (! $seller) {
            return;
        }

        $settlement = $this->billing->createSettlement(
            $seller,
            (string) ($charge->currency ?: 'ARS'),
            [$charge->id],
            null,
            'Automática MP split'
        );
        $this->billing->paySettlement($settlement, 'MP:'.$paymentId);
    }
}
