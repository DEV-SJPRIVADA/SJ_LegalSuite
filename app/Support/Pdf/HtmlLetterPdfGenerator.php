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
    public static function fromView(string $view, array $data = [], bool $zeroPageMargins = false): string
    {
        return self::fromHtml(View::make($view, $data)->render(), $zeroPageMargins);
    }

    public static function fromHtml(string $html, bool $zeroPageMargins = false): string
    {
        if (config('services.pdf.via_artisan_cli') && ! app()->runningInConsole()) {
            return HtmlLetterPdfArtisanCliRenderer::render($html, $zeroPageMargins);
        }

        return self::renderDirect($html, $zeroPageMargins);
    }

    /**
     * Generación directa vía Browsershot (CLI local o comando render-pdf en hosting).
     */
    public static function renderDirect(string $html, bool $zeroPageMargins = false): string
    {
        $shot = Browsershot::html($html)
            ->format('Letter')
            ->showBackground()
            ->emulateMedia('print')
            ->timeout((int) config('services.pdf.timeout', 120))
            ->windowSize(
                (int) config('services.pdf.viewport_width', 1280),
                (int) config('services.pdf.viewport_height', 1650),
            );

        if (! config('services.pdf.no_sandbox')) {
            $shot->newHeadless();
        }

        if ($zeroPageMargins) {
            $shot->margins(0, 0, 0, 0, 'in');
        }

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

        if (config('services.pdf.no_sandbox')) {
            $shot = self::applySharedHostingChromeOptions($shot);
        }

        return $shot->pdf();
    }

    private static function applySharedHostingChromeOptions(Browsershot $shot): Browsershot
    {
        $runtimeDir = storage_path('app/browsershot/runtime');
        $configDir = $runtimeDir.'/config';
        $cacheDir = $runtimeDir.'/cache';
        $chromeProfile = $runtimeDir.'/chrome-profile';
        $tmpDir = storage_path('app/browsershot/tmp');

        foreach ([$runtimeDir, $configDir, $cacheDir, $chromeProfile, $tmpDir] as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        return $shot->noSandbox()
            ->setEnvironmentOptions([
                'HOME' => $runtimeDir,
                'XDG_CONFIG_HOME' => $configDir,
                'XDG_CACHE_HOME' => $cacheDir,
                'TMPDIR' => $tmpDir,
                'TEMP' => $tmpDir,
                'TMP' => $tmpDir,
            ])
            ->addChromiumArguments([
                'headless=old',
                'disable-dev-shm-usage',
                'disable-gpu',
                'disable-setuid-sandbox',
                'disable-crash-reporter',
                'disable-breakpad',
                'disable-features=Crashpad',
                'single-process',
                'user-data-dir='.$chromeProfile,
            ]);
    }
}
