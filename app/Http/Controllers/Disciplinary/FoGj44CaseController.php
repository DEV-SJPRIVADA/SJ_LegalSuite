<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\FoGj44ConstanciaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FoGj44CaseController
{
    public function download(DisciplinaryCase $case, FoGj44ConstanciaService $service, Request $request): Response
    {
        Gate::authorize('previewFoGj44', $case);

        $binary = $service->downloadPdf($case, auth()->user());
        $filename = 'FO-GJ-44-'.str_replace(['/', '\\', '"'], '-', $case->case_number).'.pdf';
        $inline = $request->boolean('inline');

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
        ]);
    }
}
