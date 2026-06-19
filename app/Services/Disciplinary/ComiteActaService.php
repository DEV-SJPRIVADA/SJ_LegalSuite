<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Settings\OrganizationLetterheadService;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComiteActaService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
        private readonly DisciplinaryDocumentService $documents,
        private readonly ComiteDraftService $drafts,
        private readonly OrganizationLetterheadService $letterheads,
    ) {}

    public function canGenerate(DisciplinaryCase $case): bool
    {
        return $case->current_status === CaseStatus::COMITE_DISCIPLINARIO
          && $case->assigned_lawyer_id !== null
          && $case->comite_generated_at === null
          && $this->drafts->isReadyForPdf($case);
    }

    /** @return array<string, mixed> */
    public function buildViewData(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'assignedLawyer']);
        $payload = $this->drafts->payloadForPdf($case);
        $meetingAt = now()->timezone('America/Bogota');

        $workerName = trim(($case->employee?->first_name ?? '').' '.($case->employee?->last_name ?? ''));

        return [
            'caseNumber' => $case->case_number,
            'actaNumber' => $case->case_number,
            'actaSubject' => 'Comité para toma de decisión.',
            'companyLegalName' => 'SJ SEGURIDAD PRIVADA LTDA',
            'workerName' => $workerName,
            'workerDocument' => (string) ($case->employee?->document_number ?? ''),
            'workerPosition' => (string) ($case->employee?->job_title ?? ''),
            'lawyerName' => $case->assignedLawyer?->name ?? '',
            'meetingDate' => $meetingAt->format('d/m/Y'),
            'meetingDateLong' => $meetingAt->translatedFormat('j \d\e F \d\e Y'),
            'meetingPlaceLine' => 'Santiago de Cali, '.$meetingAt->translatedFormat('j \d\e F \d\e Y'),
            'decisionNarrative' => (string) ($payload['decision_narrative'] ?? ''),
            'attendees' => is_array($payload['attendees'] ?? null) ? $payload['attendees'] : [],
        ];
    }

    public function downloadPdf(DisciplinaryCase $case, User $actor): string
    {
        $this->drafts->payloadForPdf($case);

        $letterheadBackgroundSrc = $this->letterheads->imageDataUri();

        return HtmlLetterPdfGenerator::fromView(
            'disciplinary.forms.comite-acta-filled-download',
            array_merge(
                $this->buildViewData($case),
                [
                    'embeddedLogoSrc' => $letterheadBackgroundSrc !== null
                        ? null
                        : EmbeddedPublicAsset::disciplinaryLogoDataUri(),
                    'letterheadBackgroundSrc' => $letterheadBackgroundSrc,
                ],
            ),
            zeroPageMargins: $letterheadBackgroundSrc !== null,
        );
    }

    public function generateAndStore(DisciplinaryCase $case, User $actor): DisciplinaryCase
    {
        if (! $this->canGenerate($case)) {
            $missing = $this->drafts->missingDraftRequirements($case);
            throw ValidationException::withMessages([
                'comiteDecisionNarrative' => $missing !== []
                  ? 'No es posible generar el acta de comité. Falta: '.implode(', ', $missing)
                  : 'Complete el diligenciamiento del acta de comité antes de generar el documento.',
            ]);
        }

        return DB::transaction(function () use ($case, $actor) {
            $binary = $this->downloadPdf($case, $actor);
            $path = tempnam(sys_get_temp_dir(), 'comite_');
            file_put_contents($path, $binary);

            try {
                $uploaded = new UploadedFile(
                    $path,
                    'Acta-comite-'.$case->case_number.'.pdf',
                    'application/pdf',
                    UPLOAD_ERR_OK,
                    true,
                );

                $stage = $case->stages()
                    ->where('stage_type', StageType::COMITE)
                    ->orderByDesc('sequence')
                    ->first();

                $this->documents->upload(
                    $case,
                    $uploaded,
                    DocumentType::ACTA_COMITE,
                    $actor,
                    $stage,
                    DisciplinaryCase::NOTE_COMITE_ACTA_GENERATED,
                );
            } finally {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $case->forceFill([
                'comite_generated_at' => now(),
                'comite_generated_by' => $actor->id,
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::COMITE_ACTA_GENERADO,
                'Acta de comité disciplinario generada y almacenada en el expediente.',
            );

            return $case->fresh(['employee', 'assignedLawyer', 'documents']);
        });
    }
}
