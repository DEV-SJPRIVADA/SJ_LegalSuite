<?php

namespace App\Support\Disciplinary;

enum FoGj54RescheduleCause: string
{
    case NovedadOperativa = 'novedad_operativa';
    case SolicitudTrabajador = 'solicitud_trabajador';

    public function label(): string
    {
        return match ($this) {
            self::NovedadOperativa => 'Novedad operativa',
            self::SolicitudTrabajador => 'Solicitud realizada por el trabajador',
        };
    }

    /** Frase insertada tras «debido a» en el FO-GJ-54. */
    public function dueToPhrase(): string
    {
        return match ($this) {
            self::NovedadOperativa => 'a una novedad operativa',
            self::SolicitudTrabajador => 'una solicitud realizada por usted',
        };
    }
}
