<?php

use App\Support\Disciplinary\DisciplinaryAssets;
use App\Support\Pdf\BrowsershotBinaryResolver;
use App\Support\Pdf\EmbeddedPublicAsset;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('disciplinary:pdf-check', function () {
    $this->info('Comprobación PDF disciplinarios (HTML → Letter, Browsershot).');

    $node = BrowsershotBinaryResolver::nodeBinary();
    $npm = BrowsershotBinaryResolver::npmBinary();
    $chrome = BrowsershotBinaryResolver::chromeBinary();

    $node
        ? $this->line('Node: '.$node)
        : $this->error('Node: no encontrado. Instale Node en Laragon/PATH o defina NODE_BINARY en .env');

    $npm
        ? $this->line('npm: '.$npm)
        : $this->warn('npm: no encontrado (Browsershot puede seguir funcionando si Puppeteer no lo exige en su instalación).');

    $chrome
        ? $this->line('Chrome: '.$chrome.' (se usará para rasterizar el PDF)')
        : $this->line('Chrome: no fijado; se usará el Chromium gestionado por Puppeteer tras npm install.');

    $puppeteerOk = is_file(base_path('node_modules/puppeteer/package.json'));
    $puppeteerOk
        ? $this->line('puppeteer: node_modules presente')
        : $this->error('puppeteer: falta. Ejecute `npm install` en la raíz del proyecto.');

    $logoOk = EmbeddedPublicAsset::disciplinaryLogoDataUri() !== null;
    $logoOk
        ? $this->line('Logo PDF: OK (prioridad: images/logo solo.png)')
        : $this->error('Logo PDF: falta images/logo solo.png (u otros candidatos en EmbeddedPublicAsset).');

    return ($node && $puppeteerOk && $logoOk) ? 0 : 1;
})->purpose('Verifica Node/npm/Chrome/logo para generar PDF disciplinarios');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
