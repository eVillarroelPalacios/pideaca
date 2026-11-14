<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Costo de envio
    |--------------------------------------------------------------------------
    |
    | Se define en el backend a proposito: el cliente nunca envia el costo de
    | envio en el carrito. Cuando exista el gasto por zona o por prestador,
    | este valor se reemplaza por una consulta al catalogo de tarifas.
    |
    */

    'delivery_fee' => env('FAST_DELIVERY_FEE', 3500.00),

    /*
    |--------------------------------------------------------------------------
    | Retiro de saldo del repartidor
    |--------------------------------------------------------------------------
    |
    | Monto minimo que puede solicitarse por retiro. El saldo queda congelado
    | al solicitar y solo se paga cuando el admin lo aprueba.
    |
    */

    'min_withdrawal' => env('DRIVER_MIN_WITHDRAWAL', 1000.00),

    /*
    |--------------------------------------------------------------------------
    | Limites del pedido
    |--------------------------------------------------------------------------
    */

    'max_items_per_order' => 30,

    'max_quantity_per_item' => 20,

    'order_number_prefix' => 'PIDE',

];
