<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Disciplinary\OfficialFormHtmlBlankPdfFactory;
use App\Support\Disciplinary\OfficialFormsCatalog;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sirve el mismo PDF en blanco que la descarga, pero con cabecera inline para verlo en navegador / iframe.
 */
class OfficialFormPreviewController
{
    public function __invoke(string $code): Response|BinaryFileResponse
    {
        Gate::authorize('viewOfficialForms', DisciplinaryCase::class);

        $normalized = strtoupper($code);

        $path = OfficialFormsCatalog::staticBlankPdfAbsolutePath($normalized);
        if ($path !== null) {
            return response()->file($path, array_merge([
                'Content-Disposition' => 'inline; filename="'.basename($path).'"',
            ], ['Cache-Control' => 'private, max-age=600']));
        }

        if (OfficialFormsCatalog::isHtmlBlankPdf($normalized)) {
            return OfficialFormHtmlBlankPdfFactory::toResponse($normalized, true);
        }

        abort(404);
    }
}
