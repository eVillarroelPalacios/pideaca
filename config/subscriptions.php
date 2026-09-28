<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Cobro de suscripciones
    |---------------------------------------------------------------------------
    | manual  : el cobro queda PENDING y el comercio lo confirma cuando recibe
    |           el pago (efectivo, transferencia). Es el default porque no
    |           requiere credenciales de ningun proveedor.
    | webhook : POST al gateway configurado (Mercado Pago, Stripe, pasarela
    |           propia) y el estado se resuelve solo segun la respuesta.
    */
    'billing' => [
        'gateway' => env('SUBSCRIPTIONS_BILLING_GATEWAY', 'manual'),

        'webhook' => [
            'url' => env('SUBSCRIPTIONS_BILLING_URL'),
            'token' => env('SUBSCRIPTIONS_BILLING_TOKEN'),
            'timeout' => (int) env('SUBSCRIPTIONS_BILLING_TIMEOUT', 15),
        ],

        // Al fallar un cobro la suscripcion pasa a PAYMENT_FAILED y deja de
        // generar pedidos: cobrar un pedido que no se puede pagar no sirve.
        'pause_on_failure' => (bool) env('SUBSCRIPTIONS_PAUSE_ON_FAILURE', true),
    ],

];
