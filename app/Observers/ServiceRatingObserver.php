<?php

namespace App\Observers;

use App\Models\ServiceRating;
use App\Models\UserNotification;
use App\Services\PushNotifier;

/** Cuando alguien te califica, te enterás. */
class ServiceRatingObserver
{
    public function __construct(private readonly PushNotifier $notifier) {}

    public function created(ServiceRating $rating): void
    {
        $ratee = $rating->ratee;

        if (! $ratee) {
            return;
        }

        $this->notifier->notify(
            $ratee,
            UserNotification::TYPE_RATING_RECEIVED,
            'Te calificaron con '.$rating->stars.'★',
            $rating->comment ?: 'Recibiste una nueva calificación.',
            [
                'order_id' => $rating->service_order_id,
                'url' => '/request/'.$rating->service_order_id,
            ],
        );
    }
}
