<?php

namespace App\Services;

use App\Models\CustomerSubscription;
use App\Models\Order;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cobro de los ciclos de una suscripcion.
 *
 * El cron genera el pedido y registra el cobro en subscription_payments. El
 * canal de cobro se elige con config('subscriptions.billing.gateway'):
 * 'manual' deja el cobro PENDING para que el comercio lo confirme, y 'webhook'
 * lo delega a una pasarela. El reporte de ingresos recurrentes se apoya en
 * estas filas, no en estimar sobre los pedidos.
 */
class SubscriptionBillingService
{
    /**
     * Registra el cobro del ciclo que se acaba de generar.
     */
    public function registerCycle(
        CustomerSubscription $subscription,
        Order $order,
        Carbon $periodStart,
        Carbon $periodEnd
    ): SubscriptionPayment {
        $intentoPrevio = SubscriptionPayment::where('customer_subscription_id', $subscription->id)
            ->where('period_start', $periodStart->toDateString())
            ->orderByDesc('attempt')
            ->first();

        return SubscriptionPayment::create([
            'customer_subscription_id' => $subscription->id,
            'order_id' => $order->id,
            'provider_id' => $subscription->provider_id,
            'user_id' => $subscription->user_id,
            'gateway' => (string) config('subscriptions.billing.gateway', 'manual'),
            'status' => SubscriptionPayment::STATUS_PENDING,
            'attempt' => $intentoPrevio ? (int) $intentoPrevio->attempt + 1 : 1,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount,
            'delivery_fee' => (float) $order->delivery_fee,
            'total' => (float) $order->total,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
        ]);
    }

    /**
     * Intenta cobrar el ciclo. Con el gateway manual no hace nada: el pago se
     * confirma despues desde el panel del comercio.
     */
    public function charge(SubscriptionPayment $payment): SubscriptionPayment
    {
        $gateway = (string) config('subscriptions.billing.gateway', 'manual');

        if ($payment->status !== SubscriptionPayment::STATUS_PENDING) {
            return $payment;
        }

        if ($gateway === 'manual') {
            Log::info('Suscripcion #'.$payment->customer_subscription_id.': cobro de $'
                .number_format((float) $payment->total, 2, ',', '.').' pendiente de confirmacion (gateway manual).');

            return $payment;
        }

        if ($gateway !== 'webhook') {
            return $this->markFailed($payment, 'Gateway de cobro desconocido: '.$gateway);
        }

        $url = config('subscriptions.billing.webhook.url');

        if (! is_string($url) || $url === '') {
            return $this->markFailed($payment, 'SUBSCRIPTIONS_BILLING_URL vacio');
        }

        $peticion = Http::timeout((int) config('subscriptions.billing.webhook.timeout', 15))->acceptJson();
        $token = config('subscriptions.billing.webhook.token');

        if (is_string($token) && $token !== '') {
            $peticion = $peticion->withToken($token);
        }

        try {
            $respuesta = $peticion->post($url, [
                'payment_id' => $payment->id,
                'attempt' => $payment->attempt,
                'amount' => (float) $payment->total,
                'currency' => $payment->currency,
                'period' => [
                    'start' => $payment->period_start->toDateString(),
                    'end' => $payment->period_end->toDateString(),
                ],
                'customer' => [
                    'id' => $payment->user_id,
                    'email' => $payment->user?->email,
                    'name' => $payment->user?->name,
                ],
                'subscription' => [
                    'id' => $payment->customer_subscription_id,
                    'plan' => $payment->subscription?->plan?->title,
                ],
                'order_id' => $payment->order_id,
            ]);
        } catch (\Throwable $e) {
            return $this->markFailed($payment, $e->getMessage());
        }

        if (! $respuesta->successful()) {
            return $this->markFailed($payment, 'HTTP '.$respuesta->status().': '.substr($respuesta->body(), 0, 200));
        }

        $idExterno = $respuesta->json('id') ?? $respuesta->json('payment_id');

        return $this->markPaid($payment, is_string($idExterno) ? $idExterno : null);
    }

    public function markPaid(SubscriptionPayment $payment, ?string $gatewayPaymentId = null): SubscriptionPayment
    {
        $payment->update([
            'status' => SubscriptionPayment::STATUS_PAID,
            'paid_at' => now(),
            'failure_reason' => null,
            'gateway_payment_id' => $gatewayPaymentId ?? $payment->gateway_payment_id,
        ]);

        return $payment->fresh();
    }

    public function markFailed(SubscriptionPayment $payment, string $motivo): SubscriptionPayment
    {
        $payment->update([
            'status' => SubscriptionPayment::STATUS_FAILED,
            'failure_reason' => substr($motivo, 0, 250),
        ]);

        $subscription = $payment->subscription;

        if ($subscription && config('subscriptions.billing.pause_on_failure', true)) {
            $subscription->update(['status' => CustomerSubscription::STATUS_PAYMENT_FAILED]);

            Log::warning('Suscripcion #'.$subscription->id.' paso a PAYMENT_FAILED: '.$motivo);
        }

        return $payment->fresh();
    }
}
