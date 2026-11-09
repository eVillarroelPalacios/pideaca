<?php

namespace App\Observers;

use App\Models\ServiceQuote;
use App\Models\UserNotification;
use App\Services\PushNotifier;

/** Cuando un profesional oferta, el cliente se entera al instante. */
class ServiceQuoteObserver
{
    public function __construct(private readonly PushNotifier $notifier) {}

    public function created(ServiceQuote $quote): void
    {
        if (! $quote->provider_id || (float) $quote->proposed_price <= 0) {
            return;
        }

        $order = $quote->serviceOrder;

        $this->notifier->notify(
            $order?->customer,
            UserNotification::TYPE_QUOTE_PROPOSAL,
            'Nueva propuesta recibida',
            ($quote->provider?->business_name ?? 'Un profesional').' te envió una propuesta.',
            [
                'order_id' => $quote->service_order_id,
                'order_type' => $order?->order_type,
                'quote_id' => $quote->id,
                'url' => '/request/'.$quote->service_order_id,
            ],
        );
    }
}
