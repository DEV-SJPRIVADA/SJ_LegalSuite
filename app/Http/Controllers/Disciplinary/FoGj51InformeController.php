<?php

namespace App\Http\Controllers\Disciplinary;

use App\Http\Requests\Disciplinary\StoreFoGj51InformePdfRequest;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FoGj51InformeController
{
    public function show(): Response
    {
        Gate::authorize('create', DisciplinaryCase::class);

        return response()->view('disciplinary.forms.fo-gj-51-fill');
    }

    public function pdf(StoreFoGj51InformePdfRequest $request): Response
    {
        $v = $request->validated();

        $embeddedLogo = EmbeddedPublicAsset::disciplinaryLogoDataUri();

        $binary = HtmlLetterPdfGenerator::fromView('disciplinary.forms.fo-gj-51-filled-download', [
            'embeddedLogoSrc' => $embeddedLogo,
            'workerName' => $v['fo51_worker_name'] ?? '',
            'workerDocument' => $v['fo51_worker_document'] ?? '',
            'city' => $v['fo51_city'] ?? '',
            'shift' => $v['fo51_shift'] ?? '',
            'position' => $v['fo51_position'] ?? '',
            'faultOtherDetail' => $v['fo51_fault_other_detail'] ?? '',
            'observations' => $v['fo51_observations'] ?? '',
            'preparerName' => $v['fo51_preparer_name'] ?? '',
            'preparerRole' => $v['fo51_preparer_role'] ?? '',
            'preparerSignature' => $v['fo51_preparer_signature'] ?? '',
            'reportDay' => $v['fo51_report_dd'] ?? null,
            'reportMonth' => $v['fo51_report_mm'] ?? null,
            'reportYear' => $v['fo51_report_yyyy'] ?? null,
            'faultLeftChecked' => $v['fo51_fault_left'] ?? [],
            'faultRightChecked' => $v['fo51_fault_right'] ?? [],
            'faultOtherChecked' => (bool) ($v['fo51_fault_other_chk'] ?? false),
            'jurPd' => $v['fo51_jur_pd'] ?? '',
            'entregaGh' => $v['fo51_entrega_gh'] ?? '',
            'jurDd' => $v['fo51_jur_dd'] ?? '',
            'jurMm' => $v['fo51_jur_mm'] ?? '',
            'jurYyyy' => $v['fo51_jur_yyyy'] ?? '',
        ]);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="FO-GJ-51-informe-disciplinario.pdf"',
        ]);
    }
}
