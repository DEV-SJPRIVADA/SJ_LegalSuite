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
        if (self::shouldDelegateToArtisanCli()) {
            return HtmlLetterPdfArtisanCliRenderer::render($html, $zeroPageMargins);
        }

        return self::renderDirect($html, $zeroPageMargins);
    }

    /**
     * En LiteSpeed/CageFS el PHP web no lanza Chrome. Con PDF_VIA_ARTISAN_CLI o PDF_USE_QUEUE
     * (perfil Hostinger), las vistas FO-GJ-03/04/… delegan a artisan CLI (igual que pdf-smoke).
     * FO-GJ-51 con cola no pasa por aquí desde la web: usa ProcessFoGj51PdfJob.
     *
     * @param  bool|null  $runningInConsole  Override para tests (null = app()->runningInConsole()).
     */
    public static function shouldDelegateToArtisanCli(?bool $runningInConsole = null): bool
    {
        if ($runningInConsole ?? app()->runningInConsole()) {
            return false;
        }

        if ((bool) config('services.pdf.via_artisan_cli')) {
            return true;
        }

        return (bool) config('services.pdf.use_queue');
    }

    /**
     * Generación directa vía Browsershot (CLI local o comando render-pdf en hosting).
     */
    public static function renderDirect(string $html, bool $zeroPageMargins = false): string
    {
        $tmpDir = storage_path('app/browsershot/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $previousTemp = config('services.pdf.no_sandbox')
            ? self::overrideTempDir($tmpDir)
            : null;

        try {
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
        } finally {
            if ($previousTemp !== null) {
                self::restoreTempDir($previousTemp);
            }
        }
    }

    /**
     * @return array{TMPDIR: ?string, TEMP: ?string, TMP: ?string}
     */
    public static function overrideTempDirForHosting(string $dir): array
    {
        return self::overrideTempDir($dir);
    }

    /**
     * @param  array{TMPDIR: ?string, TEMP: ?string, TMP: ?string}  $previous
     */
    public static function restoreTempDirForHosting(array $previous): void
    {
        self::restoreTempDir($previous);
    }

    /**
     * @return array{TMPDIR: ?string, TEMP: ?string, TMP: ?string}
     */
    private static function overrideTempDir(string $dir): array
    {
        $previous = [
            'TMPDIR' => getenv('TMPDIR') ?: null,
            'TEMP' => getenv('TEMP') ?: null,
            'TMP' => getenv('TMP') ?: null,
        ];

        foreach (array_keys($previous) as $key) {
            putenv($key.'='.$dir);
            $_ENV[$key] = $dir;
            $_SERVER[$key] = $dir;
        }

        return $previous;
    }

    /**
     * @param  array{TMPDIR: ?string, TEMP: ?string, TMP: ?string}  $previous
     */
    private static function restoreTempDir(array $previous): void
    {
        foreach ($previous as $key => $value) {
            if ($value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv($key.'='.$value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
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
