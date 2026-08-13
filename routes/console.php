<?php

use App\Support\Disciplinary\DisciplinaryAssets;
use App\Support\Pdf\BrowsershotBinaryResolver;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\PdfCliPhpBinaryResolver;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('disciplinary:pdf-check', function () {
    $this->info('Comprobación PDF disciplinarios (HTML → Letter).');

    $driver = \App\Support\Pdf\LetterPdfDriver::current();
    $this->line('PDF_DRIVER: '.$driver.($driver === 'dompdf'
        ? ' (PHP puro; inmediato en web Hostinger)'
        : ' (Chrome/Browsershot)'));

    $logoOk = EmbeddedPublicAsset::disciplinaryLogoDataUri() !== null;
    $logoOk
        ? $this->line('Logo PDF: OK (prioridad: images/logo solo.png)')
        : $this->error('Logo PDF: falta images/logo solo.png (u otros candidatos en EmbeddedPublicAsset).');

    $missingFonts = \App\Support\Pdf\EmbeddedPdfFont::missingFiles();
    $fontsOk = $missingFonts === [];
    $fontsOk
        ? $this->line('Fuentes PDF: OK (Liberation en resources/fonts/pdf)')
        : $this->error('Fuentes PDF: faltan '.implode(', ', $missingFonts).' en resources/fonts/pdf');

    $node = true;
    $puppeteerOk = true;

    if ($driver === 'browsershot') {
        $nodeBin = BrowsershotBinaryResolver::nodeBinary();
        $npm = BrowsershotBinaryResolver::npmBinary();
        $chrome = BrowsershotBinaryResolver::chromeBinary();

        $node = (bool) $nodeBin;
        $node
            ? $this->line('Node: '.$nodeBin)
            : $this->error('Node: no encontrado. Instale Node o defina NODE_BINARY en .env');

        $npm
            ? $this->line('npm: '.$npm)
            : $this->warn('npm: no encontrado (Browsershot puede seguir si Puppeteer no lo exige).');

        $chrome
            ? $this->line('Chrome: '.$chrome)
            : $this->line('Chrome: no fijado; Puppeteer usará su Chromium tras npm install.');

        $puppeteerOk = is_file(base_path('node_modules/puppeteer/package.json'));
        $puppeteerOk
            ? $this->line('puppeteer: node_modules presente')
            : $this->error('puppeteer: falta. Ejecute `npm install` en la raíz del proyecto.');

        $noSandbox = (bool) config('services.pdf.no_sandbox');
        $noSandbox
            ? $this->line('PDF_NO_SANDBOX: activo')
            : $this->line('PDF_NO_SANDBOX: inactivo');

        $viaCli = (bool) config('services.pdf.via_artisan_cli');
        $this->line($viaCli
            ? 'PDF_VIA_ARTISAN_CLI: activo (suele fallar en LiteSpeed; preferir Dompdf o cola)'
            : 'PDF_VIA_ARTISAN_CLI: inactivo');

        $useQueue = (bool) config('services.pdf.use_queue');
        $this->line($useQueue
            ? 'PDF_USE_QUEUE: activo (FO-GJ-51/03 → cola `pdf` + cron)'
            : 'PDF_USE_QUEUE: inactivo');

        if ($useQueue) {
            $this->line('Cron: schedule:run + disciplinary:process-pdf-queue cada minuto.');
        }
    } else {
        $this->line('PDF_USE_QUEUE: no aplica (Dompdf síncrono; vista previa inmediata).');
        $this->line('Chrome/Node: no requeridos con PDF_DRIVER=dompdf.');
    }

    return ($logoOk && $fontsOk && $node && $puppeteerOk) ? 0 : 1;
})->purpose('Verifica driver PDF, logo y fuentes disciplinarias');

Artisan::command('disciplinary:render-pdf {--input=} {--output=} {--zero-margins}', function () {
    $input = (string) $this->option('input');
    $output = (string) $this->option('output');

    if ($input === '' || $output === '') {
        $this->error('Indique --input y --output.');

        return 1;
    }

    $allowedDir = realpath(storage_path('app/browsershot/tmp'));
    $inputReal = realpath($input);

    if ($allowedDir === false || $inputReal === false
        || ! str_starts_with($inputReal, $allowedDir.DIRECTORY_SEPARATOR)) {
        $this->error('Ruta de entrada inválida.');

        return 1;
    }

    $outputNormalized = str_replace('\\', '/', $output);
    $allowedNormalized = str_replace('\\', '/', $allowedDir);
    if (! str_starts_with($outputNormalized, $allowedNormalized.'/')) {
        $this->error('Ruta de salida inválida.');

        return 1;
    }

    if (! is_readable($inputReal)) {
        $this->error('No se puede leer el HTML de entrada.');

        return 1;
    }

    $html = file_get_contents($inputReal);
    if ($html === false) {
        $this->error('HTML de entrada vacío o ilegible.');

        return 1;
    }

    try {
        $pdf = \App\Support\Pdf\HtmlLetterPdfGenerator::renderDirect(
            $html,
            (bool) $this->option('zero-margins'),
        );
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return 1;
    }

    if (file_put_contents($output, $pdf) === false) {
        $this->error('No se pudo escribir el PDF de salida.');

        return 1;
    }

    return 0;
})->purpose('Renderiza HTML a PDF (uso interno; hosting delega desde PHP web)');

Artisan::command('disciplinary:pdf-smoke', function () {
    $this->info('Generando PDF de prueba (HTML mínimo)...');

    try {
        $binary = \App\Support\Pdf\HtmlLetterPdfGenerator::fromHtml(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><p>Smoke test SJ LegalSuite</p></body></html>',
        );
    } catch (\Throwable $e) {
        $this->error('Falló: '.$e->getMessage());

        return 1;
    }

    $this->line('OK: PDF generado ('.strlen($binary).' bytes).');

    return 0;
})->purpose('Prueba real de generación PDF vía Browsershot');

if (! function_exists('pathIsWithinProject')) {
    function pathIsWithinProject(string $path): bool
    {
        $base = realpath(base_path()) ?: base_path();
        $resolved = realpath($path);

        if ($resolved === false) {
            return str_starts_with($path, $base);
        }

        return str_starts_with($resolved, $base.DIRECTORY_SEPARATOR)
            || $resolved === $base;
    }
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
