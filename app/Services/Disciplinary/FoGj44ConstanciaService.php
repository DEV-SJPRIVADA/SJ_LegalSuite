<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Users\UserSignatureService;
use App\Support\Disciplinary\SpanishDateParts;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FoGj44ConstanciaService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
        private readonly DisciplinaryDocumentService $documents,
        private readonly DisciplinaryWorkflowService $workflow,
        private readonly FoGj44DraftService $drafts,
        private readonly FoGj04DraftService $foGj04Drafts,
        private readonly UserSignatureService $signatures,
    ) {}

    public function canGenerate(DisciplinaryCase $case): bool
    {
        return $case->current_status === CaseStatus::DILIGENCIA
            && $case->diligence_attendance === DiligenceAttendance::ABSENT
            && $case->assigned_lawyer_id !== null
            && $case->fo_gj_44_generated_at === null
            && $this->drafts->isReadyForPdf($case);
    }

    /** @return array<string, mixed> */
    public function buildViewData(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'assignedLawyer.jobPosition']);
        $payload = $this->drafts->payloadForPdf($case);
        $lawyer = $case->assignedLawyer;
        $citation = $this->foGj04Drafts->citationDataFromFo03($case);
        $since = $this->drafts->citationSinceParts($case);
        $hearing = $case->citation_confirmed_date;
        $hearingParts = SpanishDateParts::fromDate($hearing?->timezone('America/Bogota'));
        $hearingTime = $case->resolvedDiligenceHearingTimeLabel() ?? '';

        $workerName = trim(($case->employee?->first_name ?? '').' '.($case->employee?->last_name ?? ''));

        return [
            'fecha' => now()->timezone('America/Bogota')->format('d/m/Y'),
            'workerName' => $workerName,
            'workerPosition' => (string) ($case->employee?->job_title ?? ''),
            'citationSinceDay' => $since['day'],
            'citationSinceMonth' => $since['month'],
            'citationSinceYearSuffix' => $since['year_suffix'],
            'hearingDay' => $hearingParts['day'],
            'hearingMonth' => $hearingParts['month'],
            'hearingYearSuffix' => $hearingParts['year_suffix'],
            'hearingTime' => $hearingTime,
            'allegedOmission' => $citation['charges_description'],
            'signDay' => (string) ($payload['sign_day'] ?? ''),
            'signMonth' => (string) ($payload['sign_month'] ?? ''),
            'signYearSuffix' => (string) ($payload['sign_year_suffix'] ?? ''),
            'signTime' => (string) ($payload['sign_time'] ?? ''),
            'employerName' => $lawyer?->name ?? '',
            'signatureDataUri' => $lawyer ? $this->signatures->dataUriForPdf($lawyer) : null,
            'witness1Name' => (string) ($payload['witness1_name'] ?? ''),
            'witness1Cargo' => (string) ($payload['witness1_cargo'] ?? ''),
            'witness1Date' => (string) ($payload['witness1_date'] ?? ''),
            'witness2Name' => (string) ($payload['witness2_name'] ?? ''),
            'witness2Cargo' => (string) ($payload['witness2_cargo'] ?? ''),
            'witness2Date' => (string) ($payload['witness2_date'] ?? ''),
        ];
    }

    public function downloadPdf(DisciplinaryCase $case, User $actor): string
    {
        $this->drafts->payloadForPdf($case);

        return HtmlLetterPdfGenerator::fromView('disciplinary.forms.fo-gj-44-filled-download', array_merge(
            $this->buildViewData($case),
            ['embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri()],
        ));
    }

    public function generateAndStore(DisciplinaryCase $case, User $actor): DisciplinaryCase
    {
        if (! $this->canGenerate($case)) {
            $missing = $this->drafts->missingDraftRequirements($case);
            throw ValidationException::withMessages([
                'fo_gj_44' => $missing !== []
                    ? 'No es posible generar FO-GJ-44. Falta: '.implode(', ', $missing)
                    : 'Complete el diligenciamiento del FO-GJ-44 antes de generar el documento.',
            ]);
        }

        return DB::transaction(function () use ($case, $actor) {
            $binary = $this->downloadPdf($case, $actor);
            $path = tempnam(sys_get_temp_dir(), 'fo44_');
            file_put_contents($path, $binary);

            try {
                $uploaded = new UploadedFile(
                    $path,
                    'FO-GJ-44-constancia-'.$case->case_number.'.pdf',
                    'application/pdf',
                    UPLOAD_ERR_OK,
                    true,
                );

                $stage = $case->stages()
                    ->where('stage_type', StageType::DILIGENCIA)
                    ->orderByDesc('sequence')
                    ->first();

                $this->documents->upload(
                    $case,
                    $uploaded,
                    DocumentType::CONSTANCIA_INASISTENCIA,
                    $actor,
                    $stage,
                    DisciplinaryCase::NOTE_FO_GJ_44_GENERATED,
                );
            } finally {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $case->forceFill([
                'fo_gj_44_generated_at' => now(),
                'fo_gj_44_generated_by' => $actor->id,
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::FO_GJ_44_GENERADO,
                'FO-GJ-44 generado y almacenado en el expediente.',
            );

            $fresh = $case->fresh(['employee', 'assignedLawyer']);

            return $this->workflow->openDiligenceNoShowJustification(
                $fresh,
                $actor,
                'Ventana de 2 días calendario para justificar inasistencia a diligencia.',
            );
        });
    }
}
