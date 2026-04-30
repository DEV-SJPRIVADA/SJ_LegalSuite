<?php

namespace App\Enums\Disciplinary;

enum Decision: string
{
    case ABSUELTO = 'absuelto';
    case AMONESTACION_VERBAL = 'amonestacion_verbal';
    case AMONESTACION_ESCRITA = 'amonestacion_escrita';
    case SUSPENSION = 'suspension';
    case TERMINACION_CONTRATO = 'terminacion_contrato';
    case ARCHIVADO = 'archivado';

    public function label(): string
    {
        return match ($this) {
            self::ABSUELTO => 'Absuelto',
            self::AMONESTACION_VERBAL => 'Amonestación verbal',
            self::AMONESTACION_ESCRITA => 'Amonestación escrita',
            self::SUSPENSION => 'Suspensión',
            self::TERMINACION_CONTRATO => 'Terminación de contrato',
            self::ARCHIVADO => 'Archivado',
        };
    }
}
