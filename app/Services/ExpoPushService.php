<?php

namespace App\Services;

use App\Models\PushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envío de pushes vía el servicio de Expo.
 *
 * La app manda su ExponentPushToken al registrarse y este servicio le
 * entrega título + cuerpo + datos. Los tokens que Expo marca como
 * inexistentes se borran para no reintentarlos jamás.
 *
 * @see https://docs.expo.dev/push-notifications/sending-notifications/
 */
class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /**
     * @param array<int, string> $tokens
     * @param array<string, mixed> $data
     */
    public function send(array $tokens, string $title, ?string $body, array $data = []): void
    {
        $tokens = array_values(array_unique(array_filter($tokens)));

        if ($tokens === []) {
            return;
        }

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->post(self::ENDPOINT, [
                    'to' => $tokens,
                    'sound' => 'default',
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                ]);

            $this->pruneDeadTokens($tokens, $response->json('data'));
        } catch (\Throwable $e) {
            Log::warning('Push: no se pudo contactar a Expo: '.$e->getMessage());
        }
    }

    /**
     * Expo responde por cada token: los DeviceNotRegistered se eliminan.
     */
    private function pruneDeadTokens(array $tokens, mixed $receipts): void
    {
        if (! is_array($receipts)) {
            return;
        }

        foreach ($receipts as $index => $receipt) {
            $error = is_array($receipt) ? ($receipt['details']['error'] ?? null) : null;

            if ($error === 'DeviceNotRegistered' && isset($tokens[$index])) {
                PushToken::where('token', $tokens[$index])->delete();
            }
        }
    }
}
