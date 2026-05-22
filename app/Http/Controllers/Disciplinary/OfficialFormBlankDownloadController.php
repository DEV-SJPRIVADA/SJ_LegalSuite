<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Disciplinary\OfficialFormHtmlBlankPdfFactory;
use App\Support\Disciplinary\OfficialFormsCatalog;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OfficialFormBlankDownloadController
{
    public function __invoke(string $code): Response|BinaryFileResponse
    {
        Gate::authorize('viewOfficialForms', DisciplinaryCase::class);

        $normalized = strtoupper($code);

        if (OfficialFormsCatalog::isHtmlBlankPdf($normalized)) {
            return OfficialFormHtmlBlankPdfFactory::toResponse($normalized, false);
        }

        $path = OfficialFormsCatalog::staticBlankPdfAbsolutePath($normalized);
        if ($path !== null) {
            return response()->download($path, basename($path));
        }

        abort(404);
    }
}
