<?php

namespace App\Enums\Licitaciones;

enum RequestType: string
{
    case Esporadica = 'esporadica';
    case Fija = 'fija';

    public function label(): string
    {
        return match ($this) {
            self::Esporadica => 'Esporádica',
            self::Fija => 'Fija',
        };
    }
}
