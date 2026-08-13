<?php

namespace App\Enums\Disciplinary;

enum Decision: string
{
    case AMONESTACION_ESCRITA = 'amonestacion_escrita';
    case SUSPENSION = 'suspension';
    case TERMINACION_CONTRATO = 'terminacion_contrato';

    public function label(): string
    {
        return match ($this) {
            self::AMONESTACION_ESCRITA => 'Llamado de atención',
            self::SUSPENSION => 'Suspensión',
            self::TERMINACION_CONTRATO => 'Terminación de contrato',
        };
    }
}
