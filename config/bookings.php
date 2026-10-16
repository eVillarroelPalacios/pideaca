<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Citas programadas y presupuestos (Modulo B - Servicios del hogar)
    |---------------------------------------------------------------------------
    | slot_minutes      : duracion fija de cada franja de la agenda. El
    |                     solape se calcula contra esta ventana.
    | deposit_percent   : seña que cobra la reserva cuando el cliente elige
    |                     DEPOSIT; con FULL se cobra el total del servicio.
    | max_quote_photos  : fotos que puede adjuntar el cliente al pedir
    |                     presupuesto (ademas de un video opcional).
    */
    'slot_minutes' => (int) env('BOOKINGS_SLOT_MINUTES', 60),

    'deposit_percent' => (float) env('BOOKINGS_DEPOSIT_PERCENT', 50),

    'max_quote_photos' => (int) env('BOOKINGS_MAX_QUOTE_PHOTOS', 5),

];
