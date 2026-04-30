<?php

namespace App\Enums\Disciplinary;

enum StageStatus: string
{
    case PENDIENTE = 'pendiente';
    case EN_CURSO = 'en_curso';
    case COMPLETADA = 'completada';
    case CANCELADA = 'cancelada';
    case OMITIDA = 'omitida';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::EN_CURSO => 'En curso',
            self::COMPLETADA => 'Completada',
            self::CANCELADA => 'Cancelada',
            self::OMITIDA => 'Omitida',
        };
    }
}
