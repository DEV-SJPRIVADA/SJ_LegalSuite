<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\FoGj54ReprogramacionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FoGj54CaseController
{
    public function download(DisciplinaryCase $case, FoGj54ReprogramacionService $service, Request $request): Response
    {
        Gate::authorize('previewFoGj54', $case);

        $binary = $service->downloadPdf($case, auth()->user());
        $filename = 'FO-GJ-54-'.str_replace(['/', '\\', '"'], '-', $case->case_number).'.pdf';
        $inline = $request->boolean('inline');

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$filename.'"',
        ]);
    }
}
