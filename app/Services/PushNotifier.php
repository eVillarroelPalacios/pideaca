<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;

/**
 * Punto único para avisarle algo a un usuario: guarda la fila en
 * user_notifications (el centro de notificaciones de la app) y empuja el
 * push a sus dispositivos. Nunca lanza excepciones: una notificación no
 * puede romper el flujo que la originó (ni la web ni el API).
 */
class PushNotifier
{
    public function __construct(private readonly ExpoPushService $push) {}

    /**
     * @param array<string, mixed> $data
     */
    public function notify(?User $user, string $type, string $title, ?string $body = null, array $data = []): void
    {
        if (! $user) {
            return;
        }

        try {
            UserNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data === [] ? null : $data,
            ]);

            $tokens = $user->pushTokens()->pluck('token')->all();

            // En consola (tests, comandos) se manda sincrónico; en HTTP se
            // manda después de responder para no demorar a nadie.
            $send = fn () => $this->push->send($tokens, $title, $body, $data);

            if (app()->runningInConsole()) {
                $send();
            } else {
                dispatch($send)->afterResponse();
            }
        } catch (\Throwable $e) {
            Log::warning('Push: no se pudo notificar al usuario #'.$user->id.': '.$e->getMessage());
        }
    }
}
