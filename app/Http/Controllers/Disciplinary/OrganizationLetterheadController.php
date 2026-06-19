<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Settings\OrganizationLetterheadService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrganizationLetterheadController
{
    public function show(OrganizationLetterheadService $letterheads): BinaryFileResponse
    {
        Gate::authorize('viewOfficialForms', DisciplinaryCase::class);

        $path = $letterheads->imageAbsolutePath();
        if ($path === null) {
            abort(404);
        }

        $mime = $letterheads->imageMime() ?? @mime_content_type($path) ?: 'image/png';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
