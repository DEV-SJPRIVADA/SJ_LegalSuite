<?php

namespace App\Support\Employees;

final class EmployeeImportValueNormalizer
{
    private const BLANK_MARKERS = [
        's/i', 'si', 'nn', 'na', 'no', 'n/a', 'n/d', 'nd', 'sin dato', 'sin informacion', 'sin información',
    ];

    public static function nullableContact(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $normalized = mb_strtolower($trimmed);
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $normalized);

        if (in_array($normalized, self::BLANK_MARKERS, true)) {
            return null;
        }

        return $trimmed;
    }
}
