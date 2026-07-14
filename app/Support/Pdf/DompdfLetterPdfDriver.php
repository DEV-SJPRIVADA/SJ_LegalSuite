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
        $html = self::prepareHtml($html, $zeroPageMargins);

        $wrapper = Pdf::loadHTML($html)
            ->setPaper('letter', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('chroot', self::chrootPaths());

        self::registerLiberationFonts($wrapper->getDomPDF());

        $binary = $wrapper->output();

        if (! is_string($binary) || $binary === '') {
            throw new \RuntimeException('Dompdf generó un PDF vacío.');
        }

        return $binary;
    }

    /**
     * @return list<string>
     */
    private static function chrootPaths(): array
    {
        return array_values(array_filter([
            base_path(),
            public_path(),
            storage_path('app'),
            resource_path('fonts/pdf'),
            resource_path('fonts'),
        ], fn (string $path): bool => is_dir($path)));
    }

    private static function prepareHtml(string $html, bool $zeroPageMargins): string
    {
        $css = self::fontOverrideCss();
        if ($zeroPageMargins) {
            $css .= '@page{margin:0;}html,body{margin:0;padding:0;}';
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
