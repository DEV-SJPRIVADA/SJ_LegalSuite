<?php

namespace App\Enums;

enum EmployeeDocumentType: string
{
    // Codigos historicos del sistema (3.126 registros ya los usan).
    case Cc = 'CC';
    case Ce = 'CE';
    case Pa = 'PA';
    case Ppt = 'PPT';

    // Codigos que llegan desde SJ StatFlow.
    case Cedula = 'CEDULA';
    case C = 'C';
    case Nit = 'NIT';
    case N = 'N';
    case Ti = 'TI';
    case Pt = 'PT';

    public function label(): string
    {
        return match ($this) {
            self::Cc => 'Cédula de Ciudadanía',
            self::Ce => 'Cédula de Extranjería',
            self::Pa => 'Pasaporte',
            self::Ppt => 'PPT',
            self::Cedula => 'Cédula',
            self::C => 'Cédula (código C)',
            self::Nit => 'NIT',
            self::N => 'NIT (código N)',
            self::Ti => 'Tarjeta de identidad',
            self::Pt => 'Permiso temporal',
        };
    }

    /**
     * Mapea los codigos de documento de SJ StatFlow.
     *
     * `employees.document_type` es varchar(8): los dos codigos largos que usa
     * StatFlow ("CEDULA EXTRANJERIA" y "TARJETA EXTRANJERIA") no caben y se
     * colapsan a CE. Si en el futuro se amplia la columna, podrian distinguirse
     * como casos propios sin romper este mapeo.
     */
    public static function fromStatflowCode(?string $code): self
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', mb_strtoupper((string) $code)) ?? '');

        return match ($normalized) {
            'CEDULA' => self::Cedula,
            'C' => self::C,
            'CEDULA EXTRANJERIA', 'TARJETA EXTRANJERIA', 'CE' => self::Ce,
            'NIT' => self::Nit,
            'N' => self::N,
            'TI' => self::Ti,
            'PT' => self::Pt,
            'CC' => self::Cc,
            'PA' => self::Pa,
            'PPT' => self::Ppt,
            default => self::Cedula,
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
