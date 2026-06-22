<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\DecisionComunicadoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DecisionComunicadoCaseController
{
    public function download(DisciplinaryCase $case, DecisionComunicadoService $service, Request $request): Response
    {
        Gate::authorize('previewDecisionComunicado', $case);

        $binary = $service->downloadPdf($case, auth()->user());
        $filename = 'Comunicado-decision-'.str_replace(['/', '\\', '"'], '-', $case->case_number).'.pdf';
        $inline = $request->boolean('inline');

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
        ]);
    }
}
