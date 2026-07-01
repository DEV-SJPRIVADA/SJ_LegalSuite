<?php

namespace App\Support\Pdf;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * En hosting compartido (LiteSpeed), PHP web no puede lanzar Chrome aunque PHP CLI sí.
 * Delega la generación a `php artisan disciplinary:render-pdf` (mismo entorno que pdf-smoke).
 */
final class HtmlLetterPdfArtisanCliRenderer
{
    public static function render(string $html, bool $zeroPageMargins = false): string
    {
        $tmpDir = self::tmpDir();
        $token = Str::uuid()->toString();
        $inputPath = $tmpDir.DIRECTORY_SEPARATOR.$token.'.html';
        $outputPath = $tmpDir.DIRECTORY_SEPARATOR.$token.'.pdf';

        file_put_contents($inputPath, $html);

        try {
            $command = [
                PdfCliPhpBinaryResolver::resolve(),
                base_path('artisan'),
                'disciplinary:render-pdf',
                '--input='.$inputPath,
                '--output='.$outputPath,
            ];

            if ($zeroPageMargins) {
                $command[] = '--zero-margins';
            }

            $process = new Process(
                $command,
                base_path(),
                self::subprocessEnvironment(),
                null,
                (int) config('services.pdf.timeout', 120) + 15,
            );

            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'Falló artisan disciplinary:render-pdf.');
            }

            if (! is_file($outputPath)) {
                throw new \RuntimeException('No se generó el archivo PDF de salida.');
            }

            $binary = file_get_contents($outputPath);

            if ($binary === false || $binary === '') {
                throw new \RuntimeException('El PDF generado está vacío.');
            }

            return $binary;
        } finally {
            if (is_file($inputPath)) {
                @unlink($inputPath);
            }
            if (is_file($outputPath)) {
                @unlink($outputPath);
            }
        }
    }

    private static function tmpDir(): string
    {
        $dir = storage_path('app/browsershot/tmp');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * @return array<string, string>
     */
    private static function subprocessEnvironment(): array
    {
        $runtimeDir = storage_path('app/browsershot/runtime');

        return array_filter([
            'HOME' => $runtimeDir,
            'TMPDIR' => self::tmpDir(),
            'TEMP' => self::tmpDir(),
            'TMP' => self::tmpDir(),
        ]);
    }
}
