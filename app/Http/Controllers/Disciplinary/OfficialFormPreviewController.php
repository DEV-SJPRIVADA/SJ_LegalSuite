<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Disciplinary\OfficialFormsCatalog;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sirve el mismo PDF en blanco que la descarga, pero con cabecera inline para verlo en navegador / iframe.
 */
class OfficialFormPreviewController
{
    private const INLINE_CACHE_CONTROL = ['Cache-Control' => 'private, max-age=600'];

    public function __invoke(string $code): Response
    {
        Gate::authorize('viewOfficialForms', DisciplinaryCase::class);

        $normalized = strtoupper($code);

        $path = OfficialFormsCatalog::staticBlankPdfAbsolutePath($normalized);
        if ($path !== null) {
            return response()->file($path, array_merge([
                'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            ], self::INLINE_CACHE_CONTROL));
        }

        if (OfficialFormsCatalog::isHtmlBlankPdf($normalized)) {
            return match ($normalized) {
                'FO-GJ-51' => $this->foGj51BlankPdfInline(),
                default => abort(404),
            };
        }

        abort(404);
    }

    private function foGj51BlankPdfInline(): Response
    {
        $binary = HtmlLetterPdfGenerator::fromView('disciplinary.forms.fo-gj-51-blank-download', [
            'embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri(),
        ]);

        return response($binary, 200, array_merge([
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="FO-GJ-51-informe-disciplinario-en-blanco.pdf"',
        ], self::INLINE_CACHE_CONTROL));
    }
}
