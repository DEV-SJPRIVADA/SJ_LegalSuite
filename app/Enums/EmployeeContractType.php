<?php

namespace App\Enums;

enum EmployeeContractType: string
{
    case TerminoFijo = 'termino_fijo';
    case TerminoIndefinido = 'termino_indefinido';
    case ObraOLabor = 'obra_labor';
    case Aprendizaje = 'aprendizaje';

    public function label(): string
    {
        return match ($this) {
            self::TerminoFijo => 'Término fijo',
            self::TerminoIndefinido => 'Término indefinido',
            self::ObraOLabor => 'Obra o labor',
            self::Aprendizaje => 'Aprendizaje',
        };
    }

    public function requiresTerminationDate(): bool
    {
        return $this === self::TerminoFijo;
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
