<?php

namespace App\Enums\Disciplinary;

enum InformeSubmissionStatus: string
{
    case PENDIENTE_REVISION = 'pendiente_revision';
    case AUTORIZADO = 'autorizado';
    case RECHAZADO = 'rechazado';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE_REVISION => 'Pendiente de revisión (dirección)',
            self::AUTORIZADO => 'Autorizado — expediente creado',
            self::RECHAZADO => 'Rechazado / cancelado',
        };
    }
}
