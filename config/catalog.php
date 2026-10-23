<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IDs de catalogo
    |--------------------------------------------------------------------------
    |
    | IDs de registros que los controladores buscan por id en vez de por
    | descripcion: renombrar un registro desde el panel no rompe las
    | busquedas. Se cargan desde .env y cada entorno declara los suyos.
    |
    */

    // Grupo de servicios del hogar (Solicitudes: urgencias, citas y presupuestos).
    'service_group_id' => env('SERVICE_GROUP_ID'),

    // Estados de los grupos: alta de grupos nuevos y listados del sitio.
    'group_status_active_id' => env('GROUP_STATUS_ACTIVE_ID'),
    'group_status_inactive_id' => env('GROUP_STATUS_INACTIVE_ID'),

    // Tipos de usuario que se pueden elegir al registrarse ('Cliente', 'Prestador').
    'registrable_type_ids' => array_values(array_map(
        'intval',
        array_filter(explode(',', (string) env('REGISTRABLE_TYPE_IDS', '')))
    )),
];
