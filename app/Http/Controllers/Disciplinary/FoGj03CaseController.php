<?php

namespace App\Http\Controllers\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Services\Disciplinary\FoGj03CitationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FoGj03CaseController
{
    public function download(DisciplinaryCase $case, FoGj03CitationService $service): Response
    {
        Gate::authorize('previewFoGj03', $case);

        $binary = $service->downloadPdf($case, auth()->user());

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="FO-GJ-03-'.$case->case_number.'.pdf"',
        ]);
    }

    public function generate(DisciplinaryCase $case, FoGj03CitationService $service)
    {
        Gate::authorize('generateFoGj03', $case);
        $service->generateAndStore($case->fresh(), auth()->user());

        return redirect()
            ->route('disciplinary.cases.show', $case)
            ->with('success', 'FO-GJ-03 generado y guardado en el expediente.');
    }
}
