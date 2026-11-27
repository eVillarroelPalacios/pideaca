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

    public static function oauthConfigured(): bool
    {
        return (bool) config('services.mercadopago.app_id')
            && (bool) config('services.mercadopago.client_secret');
    }

    /**
     * URL de autorización OAuth (Marketplace): el comercio conecta SU
     * cuenta de Mercado Pago para cobrar directo con split automático.
     */
    public static function authorizeUrl(string $state): string
    {
        $redirect = route('checkout.mp.seller-callback');

        return 'https://auth.mercadopago.com/authorization'
            .'?client_id='.urlencode((string) config('services.mercadopago.app_id'))
            .'&response_type=code'
            .'&platform_id=mp'
            .'&redirect_uri='.urlencode($redirect)
            .'&state='.urlencode($state);
    }

    /**
     * Canjea el code de OAuth por tokens del vendedor. Devuelve el JSON
     * de MP (access_token, refresh_token, public_key, user_id...) o null.
     */
    public function exchangeCode(string $code): ?array
    {
        $response = Http::acceptJson()->asForm()->post($this->base().'/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => (string) config('services.mercadopago.app_id'),
            'client_secret' => (string) config('services.mercadopago.client_secret'),
            'code' => $code,
            'redirect_uri' => route('checkout.mp.seller-callback'),
        ]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Renueva el access token del vendedor con su refresh token.
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        $response = Http::acceptJson()->asForm()->post($this->base().'/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => (string) config('services.mercadopago.app_id'),
            'client_secret' => (string) config('services.mercadopago.client_secret'),
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
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
     * Crea una preferencia de Checkout Pro. Con $token del vendedor y
     * $applicationFee (comisión PideAca) el pago va directo al vendedor
     * y MP descuenta solo la comisión (split automático). Sin ellos,
     * recauda la cuenta de PideAca (requiere liquidación manual).
     *
     * @param  array{title:string, amount:float, currency:string, external_reference:string, back_urls:array{success?:string,failure?:string,pending?:string}, notification_url:string, expires?:bool}  $data
     */
    public function createPreference(array $data, ?string $token = null, ?float $applicationFee = null): array
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

        if ($applicationFee !== null && $applicationFee > 0 && $applicationFee < (float) $data['amount']) {
            $payload['application_fee'] = round($applicationFee, 2);
        }

        if ($data['expires'] ?? true) {
            $payload['expires'] = true;
            $payload['expiration_date_to'] = now()->addDay()->toIso8601String();
        }

        $response = Http::withToken($token ?? $this->token())
            ->acceptJson()
            ->post($this->base().'/checkout/preferences', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Mercado Pago rechazó la preferencia: '.$response->status()
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
     * Con token del vendedor para pagos con split (el pago vive en su
     * cuenta); sin token usa el de la app recaudadora.
     */
    public function getPayment(string|int $paymentId, ?string $token = null): ?array
    {
        $response = Http::withToken($token ?? $this->token())
            ->acceptJson()
            ->get($this->base().'/v1/payments/'.$paymentId);

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
