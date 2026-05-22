<?php

namespace App\Enums;

enum EmployeeDocumentType: string
{
    case Cc = 'CC';
    case Ce = 'CE';
    case Pa = 'PA';
    case Ppt = 'PPT';

    public function label(): string
    {
        return match ($this) {
            self::Cc => 'Cédula de Ciudadanía',
            self::Ce => 'Cédula de Extranjería',
            self::Pa => 'Pasaporte',
            self::Ppt => 'PPT',
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
