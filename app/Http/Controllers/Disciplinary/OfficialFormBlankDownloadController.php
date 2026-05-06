<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Disciplinary\OfficialFormsCatalog;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OfficialFormBlankDownloadController
{
    public function __invoke(string $code): Response|BinaryFileResponse
    {
        Gate::authorize('viewOfficialForms', DisciplinaryCase::class);

        $normalized = strtoupper($code);

        $path = OfficialFormsCatalog::staticBlankPdfAbsolutePath($normalized);
        if ($path !== null) {
            return response()->download($path, basename($path));
        }

        if (OfficialFormsCatalog::isHtmlBlankPdf($normalized)) {
            return match ($normalized) {
                'FO-GJ-51' => $this->foGj51BlankPdfDownload(),
                default => abort(404),
            };
        }

        abort(404);
    }

    private function foGj51BlankPdfDownload(): Response
    {
        $binary = HtmlLetterPdfGenerator::fromView('disciplinary.forms.fo-gj-51-blank-download', [
            'embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri(),
        ]);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="FO-GJ-51-informe-disciplinario-en-blanco.pdf"',
        ]);
    }
}
