<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\ComiteActaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ComiteActaCaseController
{
    public function download(DisciplinaryCase $case, ComiteActaService $service, Request $request): Response
    {
        Gate::authorize('previewComite', $case);

        $binary = $service->downloadPdf($case, auth()->user());
        $filename = 'Acta-comite-'.str_replace(['/', '\\', '"'], '-', $case->case_number).'.pdf';
        $inline = $request->boolean('inline');

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
        ]);
    }
}
