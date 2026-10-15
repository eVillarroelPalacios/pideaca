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

    /*
    |---------------------------------------------------------------------------
    | Comisiones de plataforma
    |---------------------------------------------------------------------------
    | Se usan en el reporte de ahorro vs. apps tradicionales: PideAca cobra
    | una comision fija y el comparativo asume 25%-30% en el mercado.
    */
    'pideaca_commission_percent' => (float) env('FINANCIALS_PIDEACA_COMMISSION_PERCENT', 10),

    'traditional_app_min_percent' => (float) env('FINANCIALS_TRADITIONAL_APP_MIN_PERCENT', 25),

    'traditional_app_max_percent' => (float) env('FINANCIALS_TRADITIONAL_APP_MAX_PERCENT', 30),

];
