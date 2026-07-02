<?php

namespace App\Enums\Licitaciones;

enum Periodicity: string
{
    case Quincenal = 'quincenal';
    case Mensual = 'mensual';

    public function label(): string
    {
        return match ($this) {
            self::Quincenal => 'Quincenal',
            self::Mensual => 'Mensual',
        };
    }
}
