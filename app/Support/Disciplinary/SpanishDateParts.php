<?php

namespace App\Support\Disciplinary;

use Illuminate\Support\Carbon;

final class SpanishDateParts
{
    /** @return array{day: string, month: string, year_suffix: string, year: string} */
    public static function fromDate(?Carbon $date): array
    {
        if ($date === null) {
            return [
                'day' => '',
                'month' => '',
                'year_suffix' => '',
                'year' => '',
            ];
        }

        $year = (string) $date->year;

        return [
            'day' => (string) $date->day,
            'month' => self::monthName($date),
            'year_suffix' => substr($year, -1),
            'year' => $year,
        ];
    }

    public static function monthName(Carbon $date): string
    {
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return $months[(int) $date->month] ?? $date->format('F');
    }

    /**
     * Normaliza hora libre (p. ej. "08:00 am", "8:00") a HH:MM:SS para columnas TIME de MySQL.
     */
    public static function normalizeTimeForStorage(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /** Formato HH:MM para formularios / PDF. */
    public static function normalizeTimeForForm(?string $raw): ?string
    {
        $stored = self::normalizeTimeForStorage($raw);
        if ($stored === null) {
            return null;
        }

        return substr($stored, 0, 5);
    }
}
