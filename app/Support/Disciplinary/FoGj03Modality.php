<?php

namespace App\Support\Disciplinary;

enum FoGj03Modality: string
{
    case Presencial = 'presencial';
    case Virtual = 'virtual';

    public function label(): string
    {
        return match ($this) {
            self::Presencial => 'Presencial',
            self::Virtual => 'Virtual',
        };
    }
}
