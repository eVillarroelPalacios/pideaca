<?php

namespace App\Services;

use App\Models\MarketingCampaignRule;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Salida del motor de retencion.
 *
 * El transporte se elige con config('marketing.driver'): 'log' (por defecto,
 * no envia nada real) o 'webhook' (POST HTTP hacia un gateway de WhatsApp,
 * SMS o un servicio propio).
 */
class MarketingMessageSender
{
    /**
     * @return array{sent: bool, driver: string, detail: ?string}
     */
    public function send(
        MarketingCampaignRule $rule,
        User $user,
        Provider $provider,
        string $message
    ): array {
        $driver = (string) config('marketing.driver', 'log');

        return match ($driver) {
            'log' => $this->sendToLog($rule, $user, $provider, $message),
            'webhook' => $this->sendToWebhook($rule, $user, $provider, $message),
            default => throw new RuntimeException('Canal de marketing desconocido: '.$driver),
        };
    }

    /**
     * @return array{sent: bool, driver: string, detail: ?string}
     */
    private function sendToLog(
        MarketingCampaignRule $rule,
        User $user,
        Provider $provider,
        string $message
    ): array {
        $linea = sprintf(
            '[marketing] regla %d (%s) comercio %d -> usuario %d (%s): %s',
            $rule->id,
            $rule->rule_type,
            $provider->id,
            $user->id,
            $user->phone ?? 'sin telefono',
            $message
        );

        $canal = config('marketing.log_channel');

        $canal ? Log::channel($canal)->info($linea) : Log::info($linea);

        return ['sent' => true, 'driver' => 'log', 'detail' => null];
    }

    /**
     * @return array{sent: bool, driver: string, detail: ?string}
     */
    private function sendToWebhook(
        MarketingCampaignRule $rule,
        User $user,
        Provider $provider,
        string $message
    ): array {
        $url = config('marketing.webhook.url');

        if (! is_string($url) || $url === '') {
            return ['sent' => false, 'driver' => 'webhook', 'detail' => 'MARKETING_WEBHOOK_URL vacio'];
        }

        $peticion = Http::timeout((int) config('marketing.webhook.timeout', 10))
            ->acceptJson();

        $token = config('marketing.webhook.token');

        if (is_string($token) && $token !== '') {
            $peticion = $peticion->withToken($token);
        }

        try {
            $respuesta = $peticion->post($url, [
                'channel' => config('marketing.channel', 'whatsapp'),
                'to' => $user->phone,
                'message' => $message,
                'customer' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'campaign' => [
                    'rule_id' => $rule->id,
                    'rule_type' => $rule->rule_type,
                    'days_inactive' => $rule->days_inactive,
                    'discount_code' => $rule->discount_code,
                ],
                'provider' => [
                    'id' => $provider->id,
                    'business_name' => $provider->business_name,
                ],
            ]);
        } catch (\Throwable $e) {
            return ['sent' => false, 'driver' => 'webhook', 'detail' => $e->getMessage()];
        }

        if (! $respuesta->successful()) {
            return [
                'sent' => false,
                'driver' => 'webhook',
                'detail' => 'HTTP '.$respuesta->status().': '.substr($respuesta->body(), 0, 200),
            ];
        }

        return ['sent' => true, 'driver' => 'webhook', 'detail' => null];
    }
}
