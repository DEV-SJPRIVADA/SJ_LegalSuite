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
}
