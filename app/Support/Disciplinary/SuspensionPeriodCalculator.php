<?php

namespace App\Support\Disciplinary;

use Illuminate\Support\Carbon;

/**
 * Cálculo de periodo de suspensión (días calendario) y redacción en español.
 */
final class SuspensionPeriodCalculator
{
    /**
     * @return array{
     *     days: int,
     *     start: Carbon,
     *     end: Carbon,
     *     return_date: Carbon,
     *     days_phrase: string,
     *     start_long: string,
     *     end_long: string,
     *     return_long: string
     * }
     */
    public static function fromStartAndDays(Carbon $start, int $days): array
    {
        if ($days < 1) {
            throw new \InvalidArgumentException('La suspensión debe ser de al menos 1 día.');
        }

        $start = $start->copy()->startOfDay();
        $end = $start->copy()->addDays($days - 1);
        $return = $end->copy()->addDay();

        return [
            'days' => $days,
            'start' => $start,
            'end' => $end,
            'return_date' => $return,
            'days_phrase' => self::daysPhrase($days),
            'start_long' => self::longDate($start),
            'end_long' => self::longDate($end),
            'return_long' => self::longDate($return),
        ];
    }

    public static function daysPhrase(int $days): string
    {
        $word = self::cardinalWord($days);
        $unit = $days === 1 ? 'DÍA' : 'DÍAS';

        return $word.' ('.$days.') '.$unit;
    }

    public static function longDate(Carbon $date): string
    {
        $parts = SpanishDateParts::fromDate($date->timezone('America/Bogota'));

        return $parts['day'].' de '.$parts['month'].' de '.$parts['year'];
    }

    private static function cardinalWord(int $n): string
    {
        $map = [
            1 => 'UN', 2 => 'DOS', 3 => 'TRES', 4 => 'CUATRO', 5 => 'CINCO',
            6 => 'SEIS', 7 => 'SIETE', 8 => 'OCHO', 9 => 'NUEVE', 10 => 'DIEZ',
            11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE',
            16 => 'DIECISÉIS', 17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE', 20 => 'VEINTE',
            21 => 'VEINTIUNO', 22 => 'VEINTIDÓS', 23 => 'VEINTITRÉS', 24 => 'VEINTICUATRO', 25 => 'VEINTICINCO',
            26 => 'VEINTISÉIS', 27 => 'VEINTISIETE', 28 => 'VEINTIOCHO', 29 => 'VEINTINUEVE', 30 => 'TREINTA',
        ];

        return $map[$n] ?? (string) $n;
    }
}
