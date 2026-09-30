<?php

namespace App\Support\LegalDocuments;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Interpreta la etiqueta de frecuencia del documento (MENSUAL, 6 MESES…) 
 * para calcular la próxima fecha de renovación.
 */
final class LegalDocumentFrequencyParser
{
    /**
     * @return Carbon|null null si la frecuencia no es reconocible
     */
    public static function nextRenewOn(?string $frequency, ?CarbonInterface $from = null): ?Carbon
    {
        $base = $from ? Carbon::instance($from)->startOfDay() : now()->startOfDay();
        $normalized = self::normalize($frequency);

        if ($normalized === '') {
            return null;
        }

        return match (true) {
            self::matches($normalized, ['mensual', 'mensuales', '1 mes', 'cada mes', 'monthly']) => $base->copy()->addMonthNoOverflow(),
            self::matches($normalized, ['bimestral', '2 meses', 'cada 2 meses']) => $base->copy()->addMonthsNoOverflow(2),
            self::matches($normalized, ['trimestral', '3 meses', 'cada 3 meses', 'quarterly']) => $base->copy()->addMonthsNoOverflow(3),
            self::matches($normalized, ['cuatrimestral', '4 meses', 'cada 4 meses']) => $base->copy()->addMonthsNoOverflow(4),
            self::matches($normalized, ['semestral', '6 meses', 'cada 6 meses', 'bianual']) => $base->copy()->addMonthsNoOverflow(6),
            self::matches($normalized, ['anual', '12 meses', '1 año', 'un año', 'yearly', 'year']) => $base->copy()->addYearNoOverflow(),
            self::matches($normalized, ['bienal', '2 años', 'cada 2 años']) => $base->copy()->addYearsNoOverflow(2),
            default => self::parseNumericMonths($normalized, $base),
        };
    }

    public static function isRecognized(?string $frequency): bool
    {
        return self::nextRenewOn($frequency, now()) !== null;
    }

    private static function normalize(?string $frequency): string
    {
        $raw = mb_strtolower(trim((string) $frequency));
        if ($raw === '') {
            return '';
        }

        $raw = strtr($raw, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
        $raw = preg_replace('/\s+/', ' ', $raw) ?? $raw;

        return $raw;
    }

    /**
     * @param  list<string>  $needles
     */
    private static function matches(string $normalized, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($normalized === $needle || str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function parseNumericMonths(string $normalized, Carbon $base): ?Carbon
    {
        if (preg_match('/\b(\d+)\s*meses?\b/', $normalized, $m)) {
            $months = (int) $m[1];
            if ($months >= 1 && $months <= 120) {
                return $base->copy()->addMonthsNoOverflow($months);
            }
        }

        if (preg_match('/\b(\d+)\s*anos?\b/', $normalized, $m)) {
            $years = (int) $m[1];
            if ($years >= 1 && $years <= 20) {
                return $base->copy()->addYearsNoOverflow($years);
            }
        }

        return null;
    }
}
