<?php

namespace App\Support\Pdf;

use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

/**
 * Convierte HTML a PDF tamaño carta (Letter) usando Chrome headless (Spatie Browsershot).
 * Todas las plantillas disciplinarias que generemos desde HTML deben usar este generador para mantener el mismo tamaño de página.
 */
final class HtmlLetterPdfGenerator
{
    public static function fromView(string $view, array $data = []): string
    {
        return self::fromHtml(View::make($view, $data)->render());
    }

    public static function fromHtml(string $html): string
    {
        $shot = Browsershot::html($html)
            ->format('Letter')
            ->showBackground()
            ->emulateMedia('print')
            ->newHeadless()
            ->timeout((int) config('services.pdf.timeout', 120))
            ->windowSize(
                (int) config('services.pdf.viewport_width', 1280),
                (int) config('services.pdf.viewport_height', 1650),
            );

        $chromePath = BrowsershotBinaryResolver::chromeBinary();
        if ($chromePath !== null) {
            $shot->setChromePath($chromePath);
        }

        $nodeBinary = BrowsershotBinaryResolver::nodeBinary();
        if ($nodeBinary !== null) {
            $shot->setNodeBinary($nodeBinary);
        }

        $npmBinary = BrowsershotBinaryResolver::npmBinary();
        if ($npmBinary !== null) {
            $shot->setNpmBinary($npmBinary);
        }

        return $shot->pdf();
    }
}
