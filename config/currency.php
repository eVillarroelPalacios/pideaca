<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default currency
    |--------------------------------------------------------------------------
    |
    | ISO 4217 code used when the user has no primary address/country or the
    | country has no currency assigned. Existing installations fall back to
    | the Argentine peso.
    |
    */

    'default' => env('APP_CURRENCY', 'ARS'),

];
