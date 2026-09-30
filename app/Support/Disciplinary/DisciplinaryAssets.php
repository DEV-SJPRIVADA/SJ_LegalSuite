<?php

namespace App\Support\Disciplinary;

/**
 * Rutas públicas de marca (evitar strings repetidos en vistas).
 */
final class DisciplinaryAssets
{
    /** Logo oficial en pantalla y PDF (PNG en `public/images`). */
    public const LOGO_RELATIVE_PATH = 'images/logo solo.png';

    public static function logoPublicUrl(): string
    {
        return asset(self::LOGO_RELATIVE_PATH);
    }

    public static function logoAbsolutePath(): string
    {
        return public_path(self::LOGO_RELATIVE_PATH);
    }

    public static function logoExists(): bool
    {
        return is_file(self::logoAbsolutePath());
    }
}
