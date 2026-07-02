<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Comité: omitir plazo de justificación (solo desarrollo)
    |--------------------------------------------------------------------------
    |
    | Cuando es true, el botón «Comité» en justificación pendiente se muestra
    | sin esperar los 2 días calendario. En producción debe ser false.
    |
    */
    'comite_bypass_justification_deadline' => (bool) env('DISCIPLINARY_COMITE_BYPASS_DEADLINE', false),

];
