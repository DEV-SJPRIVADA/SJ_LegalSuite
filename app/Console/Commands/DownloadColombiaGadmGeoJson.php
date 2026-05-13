<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DownloadColombiaGadmGeoJson extends Command
{
    protected $signature = 'geo:download-colombia-gadm
                            {--force : Sobrescribe si los archivos ya existen}';

    protected $description = 'Descarga los GeoJSON GADM (departamentos y municipios de Colombia) a public/geo para el mapa del dashboard.';

    /**
     * @var array<string, string>
     */
    private const SOURCES = [
        'gadm41_COL_1.json' => 'https://geodata.ucdavis.edu/gadm/gadm4.1/json/gadm41_COL_1.json',
        'gadm41_COL_2.json' => 'https://geodata.ucdavis.edu/gadm/gadm4.1/json/gadm41_COL_2.json',
    ];

    public function handle(): int
    {
        $dir = public_path('geo');
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $this->error("No se pudo crear el directorio: {$dir}");

            return self::FAILURE;
        }

        foreach (self::SOURCES as $filename => $url) {
            $path = $dir.DIRECTORY_SEPARATOR.$filename;
            if (is_file($path) && ! $this->option('force')) {
                $this->line("Omitido (ya existe): {$filename} — use --force para volver a descargar.");

                continue;
            }

            $this->info("Descargando {$filename}…");
            try {
                $response = Http::timeout(600)
                    ->connectTimeout(30)
                    ->withHeaders(['User-Agent' => 'SJ-LegalSuite/1.0'])
                    ->get($url);
            } catch (\Throwable $e) {
                $this->error("Fallo la petición a {$url}: {$e->getMessage()}");

                return self::FAILURE;
            }

            if (! $response->successful()) {
                $this->error("HTTP {$response->status()} al descargar {$url}");

                return self::FAILURE;
            }

            $body = $response->body();
            if ($body === '' || strlen($body) < 1000) {
                $this->error("Respuesta vacía o demasiado corta para {$filename}.");

                return self::FAILURE;
            }

            if (file_put_contents($path, $body) === false) {
                $this->error("No se pudo escribir: {$path}");

                return self::FAILURE;
            }

            $this->info('Guardado: '.basename($path).' ('.number_format(strlen($body)).' bytes).');
        }

        $this->newLine();
        $this->info('Listo. Recargue el dashboard disciplinario.');

        return self::SUCCESS;
    }
}
