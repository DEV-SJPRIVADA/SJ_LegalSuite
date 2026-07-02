<?php

namespace App\Support\Disciplinary;

use App\Services\Settings\OrganizationLetterheadService;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\Response;

/**
 * Genera el PDF en blanco desde HTML (Letter) para códigos registrados en {@see OfficialFormsCatalog::htmlBlankPdfRegistry()}.
 */
final class OfficialFormHtmlBlankPdfFactory
{
    private const INLINE_CACHE_CONTROL = [
        'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
    ];

    public static function binary(string $normalizedCode): string
    {
        $view = OfficialFormsCatalog::htmlBlankPdfView($normalizedCode);
        if ($view === null) {
            abort(404);
        }

        $data = [
            'embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri(),
        ];

        if (strtoupper($normalizedCode) === 'ACTA-COMITE') {
            $letterheadBackgroundSrc = app(OrganizationLetterheadService::class)->imageDataUri();
            $data['letterheadBackgroundSrc'] = $letterheadBackgroundSrc;
            if ($letterheadBackgroundSrc !== null) {
                $data['embeddedLogoSrc'] = null;
            }

            return HtmlLetterPdfGenerator::fromView(
                $view,
                $data,
                zeroPageMargins: $letterheadBackgroundSrc !== null,
            );
        }

        return HtmlLetterPdfGenerator::fromView($view, $data);
    }

    public static function toResponse(string $normalizedCode, bool $inline): Response
    {
        $binary = self::binary($normalizedCode);
        $names = OfficialFormsCatalog::htmlBlankPdfFilenames($normalizedCode);
        if ($names === null) {
            abort(404);
        }

        $filename = $inline ? $names['inline'] : $names['download'];
        $disposition = ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"';

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
        ];

        if ($inline) {
            $headers = array_merge($headers, self::INLINE_CACHE_CONTROL);
        }

        return response($binary, 200, $headers);
    }
}
