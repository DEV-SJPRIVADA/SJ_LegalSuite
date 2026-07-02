<?php

namespace App\Enums\Licitaciones;

enum PetitionType: string
{
    case Informacion = 'informacion';
    case Documentacion = 'documentacion';

    public function label(): string
    {
        return match ($this) {
            self::Informacion => 'Información',
            self::Documentacion => 'Documentación',
        };
    }
}
