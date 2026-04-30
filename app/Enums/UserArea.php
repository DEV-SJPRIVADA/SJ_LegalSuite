<?php

namespace App\Enums;

enum UserArea: string
{
    case JURIDICA = 'juridica';
    case OPERACIONES = 'operaciones';
    case PLANEACION = 'planeacion';
    case ADMINISTRATIVA = 'administrativa';
    case GERENCIA = 'gerencia';

    public function label(): string
    {
        return match ($this) {
            self::JURIDICA => 'Jurídica',
            self::OPERACIONES => 'Operaciones',
            self::PLANEACION => 'Planeación',
            self::ADMINISTRATIVA => 'Administrativa',
            self::GERENCIA => 'Gerencia',
        };
    }

    /**
     * @return array<string,string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $a) => [$a->value => $a->label()])
            ->toArray();
    }
}
