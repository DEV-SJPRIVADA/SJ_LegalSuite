<?php

namespace App\Support\Pdf;

/**
 * Selector del motor HTML→PDF Letter. Un solo switch de entorno; sin ifs en controladores.
 */
final class LetterPdfDriver
{
    public const BROWSERSHOT = 'browsershot';

    public const DOMPDF = 'dompdf';

    public static function current(): string
    {
        $driver = strtolower((string) config('services.pdf.driver', self::BROWSERSHOT));

        return in_array($driver, [self::BROWSERSHOT, self::DOMPDF], true)
            ? $driver
            : self::BROWSERSHOT;
    }

    public static function usesBrowsershot(): bool
    {
        return self::current() === self::BROWSERSHOT;
    }

    public static function usesDompdf(): bool
    {
        return self::current() === self::DOMPDF;
    }

    /**
     * Cola FO-GJ-51/03 solo aplica con Browsershot en PHP web (Hostinger + Chrome).
     * Con Dompdf la generación es síncrona e inmediata.
     */
    public static function shouldUseQueue(): bool
    {
        return self::usesBrowsershot()
            && (bool) config('services.pdf.use_queue')
            && ! app()->runningInConsole();
    }
}
