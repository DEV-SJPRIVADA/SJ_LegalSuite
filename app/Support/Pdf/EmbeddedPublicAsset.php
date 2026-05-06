<?php

namespace App\Support\Pdf;

final class EmbeddedPublicAsset
{
    /**
     * Logo disciplinario para PDF (data URI). Prueba varios nombres por compatibilidad con despliegues anteriores.
     */
    public static function disciplinaryLogoDataUri(): ?string
    {
        foreach (['images/logo solo.png', 'images/logo-solo.svg', 'images/logo.png'] as $relative) {
            $uri = self::dataUriFromPublicPath($relative);
            if ($uri !== null) {
                return $uri;
            }
        }

        return null;
    }

    /**
     * Data URI para embeber en HTML/PDF sin depender de APP_URL (Browsershot abre HTML desde archivo temporal).
     */
    public static function dataUriFromPublicPath(string $pathUnderPublic): ?string
    {
        $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($pathUnderPublic, '/\\'));
        $full = public_path($relative);

        if (! is_file($full)) {
            return null;
        }

        $mime = @mime_content_type($full) ?: 'application/octet-stream';

        return sprintf('data:%s;base64,%s', $mime, base64_encode((string) file_get_contents($full)));
    }
}
