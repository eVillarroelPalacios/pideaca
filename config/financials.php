<?php

return [

    /*
    |---------------------------------------------------------------------------
    | Umbral de margen bruto
    |---------------------------------------------------------------------------
    | Por debajo de este porcentaje el producto entra en alerta y la interfaz
    | recomienda ajustar el precio de venta.
    */
    'min_gross_margin_percent' => (float) env('FINANCIALS_MIN_MARGIN_PERCENT', 30),

    'per_page' => (int) env('FINANCIALS_PER_PAGE', 50),

];
