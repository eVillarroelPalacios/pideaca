<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Mercado Pago sin SDK (HTTP directo + HMAC manual).
 *
 * Fase 3: Checkout Pro con cuenta única recaudadora. El pago entra a la
 * cuenta de PideAca y la liquidación al comercio sigue siendo manual.
 * El split automático (OAuth por comercio) queda para una etapa posterior
 * sin cambiar el modelo de cobros.
 */
class MercadoPagoGateway
{
    public function configured(): bool
    {
        return (bool) config('services.mercadopago.access_token');
    }

    protected function base(): string
    {
        return rtrim((string) config('services.mercadopago.base_url', 'https://api.mercadopago.com'), '/');
    }

    protected function token(): string
    {
        return (string) config('services.mercadopago.access_token');
    }

    /**
     * Crea una preferencia de Checkout Pro. Devuelve init_point y id.
     *
     * @param array{title:string, amount:float, currency:string, external_reference:string, back_urls:array{success?:string,failure?:string,pending?:string}, notification_url:string, expires?:bool} $data
     */
    public function createPreference(array $data): array
    {
        $payload = [
            'items' => [[
                'title' => mb_substr($data['title'], 0, 255),
                'quantity' => 1,
                'currency_id' => $data['currency'],
                'unit_price' => round((float) $data['amount'], 2),
            ]],
            'external_reference' => $data['external_reference'],
            'back_urls' => array_filter([
                'success' => $data['back_urls']['success'] ?? null,
                'failure' => $data['back_urls']['failure'] ?? null,
                'pending' => $data['back_urls']['pending'] ?? null,
            ]),
            'auto_return' => 'approved',
            'notification_url' => $data['notification_url'],
        ];

        if ($data['expires'] ?? true) {
            $payload['expires'] = true;
            $payload['expiration_date_to'] = now()->addDay()->toIso8601String();
        }

        $response = Http::withToken($this->token())
            ->acceptJson()
            ->post($this->base() . '/checkout/preferences', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Mercado Pago rechazó la preferencia: ' . $response->status()
            );
        }

        $json = $response->json();

        return [
            'id' => $json['id'] ?? null,
            'init_point' => $json['init_point'] ?? $json['sandbox_init_point'] ?? null,
        ];
    }

    /**
     * Trae un pago por id (para confirmar por webhook o por retorno).
     */
    public function getPayment(string|int $paymentId): ?array
    {
        $response = Http::withToken($this->token())
            ->acceptJson()
            ->get($this->base() . '/v1/payments/' . $paymentId);

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Verifica la firma HMAC del webhook de Mercado Pago.
     *
     * x-signature: "ts=...,v1=..." ; x-request-id: <uuid>.
     * Mensaje: "id:<data.id>;request-id:<x-request-id>;ts:<ts>;".
     */
    public static function validSignature(?string $xSignature, ?string $xRequestId, string $dataId, ?string $secret = null): bool
    {
        $secret ??= (string) config('services.mercadopago.webhook_secret');

        if (! $xSignature || ! $xRequestId || $secret === '') {
            return false;
        }

        $ts = null;
        $hash = null;

        foreach (explode(',', $xSignature) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, null);

            if ($k === 'ts') {
                $ts = $v;
            } elseif ($k === 'v1') {
                $hash = $v;
            }
        }

        if (! $ts || ! $hash) {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals(strtolower($expected), strtolower(trim($hash)));
    }
}
