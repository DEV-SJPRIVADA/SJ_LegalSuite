<?php

namespace App\Support\Disciplinary;

/**
 * Textos fijos del FO-GJ-51 (faltas listadas). Una sola fuente para Blade y validación POST.
 *
 * Si agrega o cambia textos aquí, actualice el mapeo a códigos F-xxx en {@see FoGj51SnapshotFaultMapper}
 * (y el test `every_catalog_checkbox_label_maps_to_a_fault`).
 */
final class FoGj51Catalog
{
    /**
     * @return list<string>
     */
    public static function faultLeft(): array
    {
        return [
            'Retardo al Servicio',
            'Actitud poco alerta y vigilante (dormido)',
            'No porta uniforme de dotación adecuadamente',
            'Ausencia al servicio',
            'Cambio por solicitud del cliente',
            'Descuido con elementos de puesto y/o dotación',
            'Irrespeto a superiores, compañeros y/o clientes',
            'Incautación o decomiso de arma de dotación',
        ];
    }

    /**
     * @return list<string>
     */
    public static function faultRight(): array
    {
        return [
            'Abandono del puesto',
            'Síntomas de alicoramiento',
            'Incumplimiento de consignas',
            'Daño con elementos de puesto y/o dotación',
            'Mala presentación personal',
            'Incumplimiento de instrucciones',
        ];
    }
}
