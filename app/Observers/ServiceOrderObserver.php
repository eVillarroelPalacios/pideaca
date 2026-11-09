<?php

namespace App\Observers;

use App\Models\ServiceOrder;
use App\Models\UserNotification;
use App\Services\PushNotifier;

/**
 * Avisos automáticos del ciclo de vida de una solicitud.
 *
 * Se dispara tanto desde la web como desde el API móvil, porque observa al
 * modelo y no a los controladores. Nunca interrumpe el flujo: PushNotifier
 * traga sus propios errores.
 */
class ServiceOrderObserver
{
    public function __construct(private readonly PushNotifier $notifier) {}

    public function created(ServiceOrder $order): void
    {
        // Solicitud directa a un prestador: avisarle que tiene trabajo.
        if ($order->merchant_id) {
            $merchant = $order->merchant;

            $this->notifier->notify(
                $merchant?->user,
                UserNotification::TYPE_ORDER_ASSIGNED,
                'Nueva solicitud asignada',
                ($order->subGroup?->description ?? 'Un servicio').': tenés un trabajo nuevo para revisar.',
                $this->payload($order),
            );
        }
    }

    public function updated(ServiceOrder $order): void
    {
        if (! $order->wasChanged('status') && ! $order->wasChanged('merchant_id')) {
            return;
        }

        $previousStatus = $order->getOriginal('status');
        $customer = $order->customer;

        match ($order->status) {
            ServiceOrder::STATUS_ACCEPTED => $this->onAccepted($order),
            ServiceOrder::STATUS_PROVIDER_DISPATCHED => $this->notifier->notify(
                $customer,
                UserNotification::TYPE_ORDER_DISPATCHED,
                '¡Tu prestador va en camino!',
                'Te avisamos cuando llegue.',
                $this->payload($order),
            ),
            ServiceOrder::STATUS_COMPLETED => $this->onCompleted($order),
            ServiceOrder::STATUS_CANCELLED => $this->onCancelled($order, $previousStatus),
            default => null,
        };

        // La orden salió del pool porque alguien la tomó (sin cambio de
        // estado todavía): avisarle al prestador.
        if ($order->wasChanged('merchant_id') && $order->merchant_id && $order->status === ServiceOrder::STATUS_REQUESTED) {
            $this->notifier->notify(
                $order->merchant?->user,
                UserNotification::TYPE_ORDER_ASSIGNED,
                'Tomaste una solicitud',
                ($order->subGroup?->description ?? 'Un servicio').': la solicitud quedó a tu nombre.',
                $this->payload($order),
            );
        }
    }

    private function onAccepted(ServiceOrder $order): void
    {
        $payload = $this->payload($order);

        $this->notifier->notify(
            $order->customer,
            UserNotification::TYPE_ORDER_CONFIRMED,
            'Trabajo confirmado',
            'El prestador aceptó y el trabajo quedó confirmado.',
            $payload,
        );

        $this->notifier->notify(
            $order->merchant?->user,
            UserNotification::TYPE_ORDER_CONFIRMED,
            'Aceptaron tu propuesta',
            'El cliente confirmó el trabajo. ¡Manos a la obra!',
            $payload,
        );
    }

    private function onCompleted(ServiceOrder $order): void
    {
        $payload = $this->payload($order);

        $this->notifier->notify(
            $order->customer,
            UserNotification::TYPE_ORDER_COMPLETED,
            'Trabajo completado',
            'Contanos cómo te fue calificando al prestador.',
            $payload,
        );

        $this->notifier->notify(
            $order->merchant?->user,
            UserNotification::TYPE_ORDER_COMPLETED,
            'Trabajo completado',
            'El trabajo se marcó como completado.',
            $payload,
        );
    }

    private function onCancelled(ServiceOrder $order, mixed $previousStatus): void
    {
        // Solo avisamos cancelaciones de trabajos que estaban en curso
        // (no las que nunca salieron del pool sin prestador).
        if ($previousStatus === ServiceOrder::STATUS_REQUESTED && ! $order->merchant_id) {
            return;
        }

        $payload = $this->payload($order);
        $message = 'La solicitud fue cancelada.';

        $this->notifier->notify(
            $order->customer,
            UserNotification::TYPE_ORDER_CANCELLED,
            'Solicitud cancelada',
            $message,
            $payload,
        );

        $this->notifier->notify(
            $order->merchant?->user,
            UserNotification::TYPE_ORDER_CANCELLED,
            'Solicitud cancelada',
            $message,
            $payload,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ServiceOrder $order): array
    {
        return [
            'order_id' => $order->id,
            'order_type' => $order->order_type,
            'url' => '/request/'.$order->id,
        ];
    }
}
