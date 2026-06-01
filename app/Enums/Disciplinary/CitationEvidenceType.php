<?php

namespace App\Enums\Disciplinary;

enum CitationEvidenceType: string
{
    case SIGNED = 'signed';
    case REFUSED_WITNESSES = 'refused_witnesses';

    public function label(): string
    {
        return match ($this) {
            self::SIGNED => 'Citación firmada por el trabajador',
            self::REFUSED_WITNESSES => 'Rechazo de firma (jefe inmediato y dos testigos)',
        };
    }
}
