<?php

namespace App\Enums;

enum EmployeeGender: string
{
    case Masculino = 'masculino';
    case Femenino = 'femenino';
    case Otro = 'otro';
    case NoIndica = 'no_indica';

    public function label(): string
    {
        return match ($this) {
            self::Masculino => 'Masculino',
            self::Femenino => 'Femenino',
            self::Otro => 'Otro',
            self::NoIndica => 'Prefiero no indicar',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
