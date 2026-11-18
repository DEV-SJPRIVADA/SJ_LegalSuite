<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Integracion entrante desde SJ StatFlow
    |--------------------------------------------------------------------------
    |
    | StatFlow (servidor interno) empuja lotes de empleados por HTTPS. El
    | transporte se autentica con token estatico + HMAC-SHA256 del body crudo
    | mas un timestamp con tolerancia configurable.
    |
    */

    'statflow' => [
        'key' => env('INTEGRATION_STATFLOW_KEY'),
        'secret' => env('INTEGRATION_STATFLOW_SECRET'),
        'timestamp_tolerance' => (int) env('INTEGRATION_TIMESTAMP_TOLERANCE', 300),
    ],

];
