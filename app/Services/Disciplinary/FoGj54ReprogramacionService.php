<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Users\UserSignatureService;
use App\Support\Disciplinary\SpanishDateParts;
use App\Support\Disciplinary\WorkerLegalPhrasing;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FoGj54ReprogramacionService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
        private readonly DisciplinaryDocumentService $documents,
        private readonly DisciplinaryWorkflowService $workflow,
        private readonly FoGj54DraftService $drafts,
        private readonly FoGj04DraftService $foGj04Drafts,
        private readonly UserSignatureService $signatures,
    ) {}

    public function canGenerate(DisciplinaryCase $case): bool
    {
        return $case->current_status === CaseStatus::JUSTIFICACION_PENDIENTE
            && $case->assigned_lawyer_id !== null
            && $case->fo_gj_54_generated_at === null
            && $this->drafts->isReadyForPdf($case);
    }

    /** @return array<string, mixed> */
    public function buildViewData(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'assignedLawyer.jobPosition']);
        $payload = $this->drafts->payloadForPdf($case);
        $lawyer = $case->assignedLawyer;
        $defaults = $this->drafts->defaultsForCase($case);
        $newParts = SpanishDateParts::fromDate(
            \Illuminate\Support\Carbon::parse((string) $payload['new_hearing_date'])->timezone('America/Bogota')
        );

        $workerName = trim(($case->employee?->first_name ?? '').' '.($case->employee?->last_name ?? ''));
        $legalPhrasing = WorkerLegalPhrasing::fromEmployee($case->employee);

        return [
            'fecha' => now()->timezone('America/Bogota')->format('d/m/Y'),
            'workerName' => $workerName,
            'workerDocument' => (string) ($case->employee?->document_number ?? ''),
            'workerPosition' => (string) ($case->employee?->job_title ?? ''),
            'legalPhrasing' => $legalPhrasing,
            'originalHearingDay' => (string) ($defaults['original_hearing_day'] ?? ''),
            'originalHearingMonth' => (string) ($defaults['original_hearing_month'] ?? ''),
            'originalHearingYear' => (string) ($defaults['original_hearing_year'] ?? ''),
            'originalHearingTime' => (string) ($defaults['original_hearing_time'] ?? ''),
            'factsDay' => (string) ($defaults['facts_day'] ?? ''),
            'factsMonth' => (string) ($defaults['facts_month'] ?? ''),
            'clientSite' => (string) ($payload['client_site'] ?? ''),
            'shiftStart' => (string) ($payload['shift_start'] ?? ''),
            'shiftEnd' => (string) ($payload['shift_end'] ?? ''),
            'newHearingDay' => $newParts['day'],
            'newHearingMonth' => $newParts['month'],
            'newHearingYear' => $newParts['year'],
            'newHearingTime' => (string) ($payload['new_hearing_time'] ?? ''),
            'newHearingPlace' => (string) ($payload['new_hearing_place'] ?? ''),
            'employerName' => $lawyer?->name ?? '',
            'signatureDataUri' => $lawyer ? $this->signatures->dataUriForPdf($lawyer) : null,
        ];
    }

    public function downloadPdf(DisciplinaryCase $case, User $actor): string
    {
        $this->drafts->payloadForPdf($case);

        return HtmlLetterPdfGenerator::fromView('disciplinary.forms.fo-gj-54-filled-download', array_merge(
            $this->buildViewData($case),
            ['embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri()],
        ));
    }

    public function generateAcceptJustificationAndStore(DisciplinaryCase $case, User $actor, ?string $note = null): DisciplinaryCase
    {
        if (! $this->canGenerate($case)) {
            $missing = $this->drafts->missingDraftRequirements($case);
            throw ValidationException::withMessages([
                'fo_gj_54' => $missing !== []
                    ? 'No es posible generar FO-GJ-54. Falta: '.implode(', ', $missing)
                    : 'Complete el diligenciamiento del FO-GJ-54 antes de generar el documento.',
            ]);
        }

        return DB::transaction(function () use ($case, $actor, $note) {
            $binary = $this->downloadPdf($case, $actor);
            $path = tempnam(sys_get_temp_dir(), 'fo54_');
            file_put_contents($path, $binary);

            try {
                $uploaded = new UploadedFile(
                    $path,
                    'FO-GJ-54-reprogramacion-'.$case->case_number.'.pdf',
                    'application/pdf',
                    UPLOAD_ERR_OK,
                    true,
                );

                $stage = $case->stages()
                    ->where('stage_type', StageType::JUSTIFICACION)
                    ->orderByDesc('sequence')
                    ->first();

                $this->documents->upload(
                    $case,
                    $uploaded,
                    DocumentType::REPROGRAMACION,
                    $actor,
                    $stage,
                    DisciplinaryCase::NOTE_FO_GJ_54_GENERATED,
                );
            } finally {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $case->forceFill([
                'fo_gj_54_generated_at' => now(),
                'fo_gj_54_generated_by' => $actor->id,
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::FO_GJ_54_GENERADO,
                'FO-GJ-54 generado y almacenado en el expediente.',
            );

            $newCitationAt = $this->drafts->newCitationAt($case->fresh());

            return $this->workflow->acceptJustification(
                $case->fresh(['employee', 'assignedLawyer']),
                $actor,
                $newCitationAt,
                $note ?? 'Justificación aceptada; diligencia reprogramada.',
            );
        });
    }
}
