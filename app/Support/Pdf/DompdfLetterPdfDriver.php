<?php

namespace App\Support\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;

/**
 * HTML → PDF Letter vía Dompdf (PHP puro: inmediato en Hostinger LiteSpeed).
 */
final class DompdfLetterPdfDriver
{
    public static function render(string $html, bool $zeroPageMargins = false): string
    {
        $fontDir = self::ensureFontCacheDirectory();
        $webRoot = self::resolveWebRoot();

        // Hostinger usa public_html; barryvdh falla si no existe base_path('public').
        config([
            'dompdf.public_path' => $webRoot,
            'dompdf.options.font_dir' => $fontDir,
            'dompdf.options.font_cache' => $fontDir,
        ]);

        $html = self::prepareHtml($html, $zeroPageMargins);

        $wrapper = Pdf::loadHTML($html)
            ->setPaper('letter', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('fontDir', $fontDir)
            ->setOption('fontCache', $fontDir)
            ->setOption('chroot', self::chrootPaths($webRoot, $fontDir));

        self::registerLiberationFonts($wrapper->getDomPDF());

        $binary = $wrapper->output();

        if (! is_string($binary) || $binary === '') {
            throw new \RuntimeException('Dompdf generó un PDF vacío.');
        }

        return $binary;
    }

    public static function ensureFontCacheDirectory(): string
    {
        $dir = storage_path('fonts');

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear storage/fonts para Dompdf.');
        }

        if (! is_writable($dir)) {
            throw new \RuntimeException('storage/fonts no es escribible por PHP.');
        }

        return rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }

    /**
     * Hostinger: public_html (vía usePublicPath o detección). Laragon: public.
     */
    public static function resolveWebRoot(): string
    {
        $candidates = [
            public_path(),
            base_path('public_html'),
            base_path('public'),
        ];

        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);
            if ($resolved !== false && is_dir($resolved)) {
                return $resolved;
            }
        }

        throw new \RuntimeException('Cannot resolve public path (ni public_html ni public).');
    }

    /**
     * @return list<string>
     */
    private static function chrootPaths(string $webRoot, string $fontDir): array
    {
        return array_values(array_unique(array_filter([
            base_path(),
            $webRoot,
            rtrim($fontDir, DIRECTORY_SEPARATOR),
            storage_path('app'),
            resource_path('fonts/pdf'),
            resource_path('fonts'),
        ], fn (string $path): bool => is_dir($path))));
    }

    private static function prepareHtml(string $html, bool $zeroPageMargins): string
    {
        $css = self::fontOverrideCss();
        if ($zeroPageMargins) {
            $css .= '@page{margin:0;}html,body{margin:0;padding:0;}';
        } else {
            // Caja Letter 7.5in + margen 0.5in. Evita width:100%+padding (Dompdf corta la derecha).
            $css .= '@page{size:Letter;margin:0;}'
                .'html,body{margin:0;padding:0;}'
                .'.ogj-page{width:7.5in;margin:0.5in;padding:0;}'
                .'.ogj-wrap{width:auto;margin:0;padding:0;}';
        }

        $block = '<style data-sj-pdf-driver="dompdf">'.$css.'</style>';

        if (stripos($html, '</head>') !== false) {
            return (string) preg_replace('/<\/head>/i', $block.'</head>', $html, 1);
        }

        return $block.$html;
    }

    private static function fontOverrideCss(): string
    {
        return 'html,body,.ogj-wrap{font-family:SjPdfSans,DejaVu Sans,sans-serif!important;}'
            .'.comite-body,.comite-body *{font-family:SjPdfSerif,DejaVu Serif,serif!important;}';
    }

    private static function registerLiberationFonts(Dompdf $dompdf): void
    {
        $dir = EmbeddedPdfFont::directory();
        if (! is_dir($dir)) {
            return;
        }

        $map = [
            ['family' => EmbeddedPdfFont::FAMILY_SANS, 'style' => 'normal', 'weight' => 'normal', 'file' => 'LiberationSans-Regular.ttf'],
            ['family' => EmbeddedPdfFont::FAMILY_SANS, 'style' => 'normal', 'weight' => 'bold', 'file' => 'LiberationSans-Bold.ttf'],
            ['family' => EmbeddedPdfFont::FAMILY_SANS, 'style' => 'italic', 'weight' => 'normal', 'file' => 'LiberationSans-Italic.ttf'],
            ['family' => EmbeddedPdfFont::FAMILY_SANS, 'style' => 'italic', 'weight' => 'bold', 'file' => 'LiberationSans-BoldItalic.ttf'],
            ['family' => EmbeddedPdfFont::FAMILY_SERIF, 'style' => 'normal', 'weight' => 'normal', 'file' => 'LiberationSerif-Regular.ttf'],
            ['family' => EmbeddedPdfFont::FAMILY_SERIF, 'style' => 'normal', 'weight' => 'bold', 'file' => 'LiberationSerif-Bold.ttf'],
            ['family' => EmbeddedPdfFont::FAMILY_SERIF, 'style' => 'italic', 'weight' => 'normal', 'file' => 'LiberationSerif-Italic.ttf'],
            ['family' => EmbeddedPdfFont::FAMILY_SERIF, 'style' => 'italic', 'weight' => 'bold', 'file' => 'LiberationSerif-BoldItalic.ttf'],
        ];

        $metrics = $dompdf->getFontMetrics();

        foreach ($map as $face) {
            $path = $dir.DIRECTORY_SEPARATOR.$face['file'];
            if (! is_readable($path)) {
                continue;
            }

            try {
                $metrics->registerFont(
                    [
                        'family' => $face['family'],
                        'style' => $face['style'],
                        'weight' => $face['weight'],
                    ],
                    $path,
                );
            } catch (\Throwable) {
                // DejaVu queda como respaldo.
            }
        }
    }
}
