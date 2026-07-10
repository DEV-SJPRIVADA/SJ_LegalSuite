<?php

namespace App\Enums;

enum EmployeeContractType: string
{
    case TerminoFijo = 'termino_fijo';
    case TerminoIndefinido = 'termino_indefinido';
    case ObraOLabor = 'obra_labor';
    case AprendizajeLectiva = 'aprendizaje_lectiva';
    case AprendizajePractica = 'aprendizaje_practica';

    public function label(): string
    {
        return match ($this) {
            self::TerminoFijo => 'Término fijo',
            self::TerminoIndefinido => 'Término indefinido',
            self::ObraOLabor => 'Obra o labor',
            self::AprendizajeLectiva => 'Aprendizaje fase lectiva',
            self::AprendizajePractica => 'Aprendizaje fase práctica',
        };
    }

    public static function tryFromImportLabel(string $raw): ?self
    {
        if (trim($raw) === '') {
            return null;
        }

        $n = self::normalize($raw);

        if (str_contains($n, 'no defin')) {
            return null;
        }

        if (str_contains($n, 'indefin')) {
            return self::TerminoIndefinido;
        }

        if (str_contains($n, 'obra') || $n === 'obra labor') {
            return self::ObraOLabor;
        }

        if (str_contains($n, 'lectiv')) {
            return self::AprendizajeLectiva;
        }

        if (str_contains($n, 'practic') || str_contains($n, 'productiv')) {
            return self::AprendizajePractica;
        }

        if (str_contains($n, 'aprendiz')) {
            return self::AprendizajeLectiva;
        }

        if (str_contains($n, 'fijo')) {
            return self::TerminoFijo;
        }

        return self::tryFrom($n);
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

    private static function normalize(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $v);

        return preg_replace('/\s+/u', ' ', $v) ?? $v;
    }
}
