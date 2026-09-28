<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Canal de envio
    |---------------------------------------------------------------------------
    | log     : el mensaje se registra en el log de Laravel (por defecto, para
    |           poder probar el motor sin credenciales ni mensajes reales).
    | webhook : POST HTTP a la URL indicada. Sirve para un gateway de WhatsApp
    |           Business API, Twilio, MessageBird o cualquier proveedor propio.
    */
    'driver' => env('MARKETING_DRIVER', 'log'),

    // Etiqueta que viaja en el payload para que el gateway sepa el canal.
    'channel' => env('MARKETING_CHANNEL', 'whatsapp'),

    'log_channel' => env('MARKETING_LOG_CHANNEL'),

    'webhook' => [
        'url' => env('MARKETING_WEBHOOK_URL'),
        'token' => env('MARKETING_WEBHOOK_TOKEN'),
        'timeout' => (int) env('MARKETING_WEBHOOK_TIMEOUT', 10),
    ],

    /*
    |---------------------------------------------------------------------------
    | Motor de retencion
    |---------------------------------------------------------------------------
    | Tope de envios por comercio y por corrida, para que una campana mal
    | configurada no dispare cientos de mensajes de un dia para otro.
    */
    'max_sends_per_provider' => (int) env('MARKETING_MAX_SENDS_PER_PROVIDER', 500),

    // Dias que se tolera de atraso antes de considerar que el cliente ya paso
    // la ventana: si el cron dejo de correr, la campana vuelve a evaluarlo.
    'inactive_grace_days' => (int) env('MARKETING_INACTIVE_GRACE_DAYS', 0),

];
