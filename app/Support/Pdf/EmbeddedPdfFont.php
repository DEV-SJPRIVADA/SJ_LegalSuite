<?php

namespace App\Support\Pdf;

/**
 * Tipografías Liberation embebidas para PDF (Browsershot).
 * Evita depender de Arial/Times del sistema (ausentes en Hostinger / chrome-headless-shell).
 */
final class EmbeddedPdfFont
{
    public const FAMILY_SANS = 'SjPdfSans';

    public const FAMILY_SERIF = 'SjPdfSerif';

    /** @var array<string, string> */
    private static array $dataUriCache = [];

    public static function directory(): string
    {
        return resource_path('fonts/pdf');
    }

    /**
     * CSS @font-face para cartas FO-GJ (métricas tipo Arial).
     */
    public static function sansFontFaceCss(): string
    {
        return self::fontFaceCss(self::FAMILY_SANS, [
            ['LiberationSans-Regular.ttf', 400, 'normal'],
            ['LiberationSans-Bold.ttf', 700, 'normal'],
            ['LiberationSans-Italic.ttf', 400, 'italic'],
            ['LiberationSans-BoldItalic.ttf', 700, 'italic'],
        ]);
    }

    /**
     * CSS @font-face para acta de comité (métricas tipo Times).
     */
    public static function serifFontFaceCss(): string
    {
        return self::fontFaceCss(self::FAMILY_SERIF, [
            ['LiberationSerif-Regular.ttf', 400, 'normal'],
            ['LiberationSerif-Bold.ttf', 700, 'normal'],
            ['LiberationSerif-Italic.ttf', 400, 'italic'],
            ['LiberationSerif-BoldItalic.ttf', 700, 'italic'],
        ]);
    }

    public static function allFontFaceCss(): string
    {
        return self::sansFontFaceCss().self::serifFontFaceCss();
    }

    /**
     * @return list<string>
     */
    public static function requiredFiles(): array
    {
        return [
            'LiberationSans-Regular.ttf',
            'LiberationSans-Bold.ttf',
            'LiberationSans-Italic.ttf',
            'LiberationSans-BoldItalic.ttf',
            'LiberationSerif-Regular.ttf',
            'LiberationSerif-Bold.ttf',
            'LiberationSerif-Italic.ttf',
            'LiberationSerif-BoldItalic.ttf',
        ];
    }

    /**
     * @return list<string> rutas faltantes (vacío si todo OK)
     */
    public static function missingFiles(): array
    {
        $missing = [];
        $dir = self::directory();

        foreach (self::requiredFiles() as $file) {
            if (! is_file($dir.DIRECTORY_SEPARATOR.$file)) {
                $missing[] = $file;
            }
        }

        return $missing;
    }

    /**
     * @param  list<array{0: string, 1: int, 2: string}>  $faces
     */
    private static function fontFaceCss(string $family, array $faces): string
    {
        $blocks = [];

        foreach ($faces as [$file, $weight, $style]) {
            $uri = self::dataUriForFile($file);
            if ($uri === null) {
                continue;
            }

            $blocks[] = sprintf(
                "@font-face{font-family:%s;font-style:%s;font-weight:%d;font-display:block;src:url('%s') format('truetype');}",
                $family,
                $style,
                $weight,
                $uri
            );
        }

        return implode('', $blocks);
    }

    private static function dataUriForFile(string $filename): ?string
    {
        if (isset(self::$dataUriCache[$filename])) {
            return self::$dataUriCache[$filename];
        }

        $full = self::directory().DIRECTORY_SEPARATOR.$filename;
        if (! is_file($full)) {
            return null;
        }

        $uri = 'data:font/ttf;base64,'.base64_encode((string) file_get_contents($full));
        self::$dataUriCache[$filename] = $uri;

        return $uri;
    }
}
