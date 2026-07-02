<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\DecisionBranch;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecisionComunicadoService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
        private readonly DisciplinaryDocumentService $documents,
        private readonly DecisionDraftService $drafts,
    ) {}

    public function canGenerate(DisciplinaryCase $case): bool
    {
        return $case->current_status === CaseStatus::DECISION
            && $case->assigned_lawyer_id !== null
            && $case->decision_comunicado_generated_at === null
            && $this->drafts->isReadyForPdf($case);
    }

    /** @return array<string, mixed> */
    public function buildViewData(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'assignedLawyer', 'notificationSupervisor']);
        $payload = $case->decision_comunicado_generated_at !== null
            ? ($case->decision_payload ?? [])
            : $this->drafts->payloadForPdf($case);
        $issuedAt = now()->timezone('America/Bogota');
        $branch = DecisionBranch::forDecision($case->decision);

        $workerName = trim(($case->employee?->first_name ?? '').' '.($case->employee?->last_name ?? ''));

        return [
            'caseNumber' => $case->case_number,
            'decisionLabel' => $case->decision?->label() ?? 'Decisión disciplinaria',
            'subject' => (string) ($payload['subject'] ?? ''),
            'bodyNarrative' => (string) ($payload['body_narrative'] ?? ''),
            'companyLegalName' => 'SJ SEGURIDAD PRIVADA LTDA',
            'workerName' => $workerName,
            'workerDocument' => (string) ($case->employee?->document_number ?? ''),
            'workerPosition' => (string) ($case->employee?->job_title ?? ''),
            'lawyerName' => $case->assignedLawyer?->name ?? '',
            'issuedDate' => $issuedAt->format('d/m/Y'),
            'issuedDateLong' => $issuedAt->translatedFormat('j \d\e F \d\e Y'),
            'placeLine' => 'Santiago de Cali, '.$issuedAt->translatedFormat('j \d\e F \d\e Y'),
            'suspensionStart' => (string) ($payload['suspension_start'] ?? ''),
            'suspensionEnd' => (string) ($payload['suspension_end'] ?? ''),
            'reliefNotes' => (string) ($payload['relief_notes'] ?? ''),
            'showSuspensionDates' => $branch !== null && DecisionBranch::requiresSuspensionDates($branch),
            'showRelief' => $branch === DecisionBranch::TERMINATION,
            'notificationDate' => $case->decision_notification_date?->format('d/m/Y') ?? '',
            'notificationShift' => (string) ($case->decision_notification_shift ?? ''),
            'notificationZone' => (string) ($case->decision_notification_zone ?? ''),
            'supervisorName' => (string) ($case->decision_notification_supervisor_name ?? ''),
        ];
    }

    public function downloadPdf(DisciplinaryCase $case, User $actor): string
    {
        $this->drafts->payloadForPdf($case);

        return HtmlLetterPdfGenerator::fromView(
            'disciplinary.forms.decision-comunicado-filled-download',
            array_merge(
                $this->buildViewData($case),
                ['embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri()],
            ),
        );
    }

    public function generateAndStore(DisciplinaryCase $case, User $actor): DisciplinaryCase
    {
        if (! $this->canGenerate($case)) {
            $missing = $this->drafts->missingDraftRequirements($case);
            throw ValidationException::withMessages([
                'decisionBodyNarrative' => $missing !== []
                    ? 'No es posible generar el comunicado. Falta: '.implode(', ', $missing)
                    : 'Complete el diligenciamiento del comunicado antes de generar el documento.',
            ]);
        }

        return DB::transaction(function () use ($case, $actor) {
            $binary = $this->downloadPdf($case, $actor);
            $path = tempnam(sys_get_temp_dir(), 'decision_');
            file_put_contents($path, $binary);

            try {
                $uploaded = new UploadedFile(
                    $path,
                    'Comunicado-decision-'.$case->case_number.'.pdf',
                    'application/pdf',
                    UPLOAD_ERR_OK,
                    true,
                );

                $stage = $case->stages()
                    ->where('stage_type', StageType::DECISION)
                    ->orderByDesc('sequence')
                    ->first();

                $this->documents->upload(
                    $case,
                    $uploaded,
                    DocumentType::DECISION,
                    $actor,
                    $stage,
                    DisciplinaryCase::NOTE_DECISION_COMUNICADO_GENERATED,
                );
            } finally {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $case->forceFill([
                'decision_comunicado_generated_at' => now(),
                'decision_comunicado_generated_by' => $actor->id,
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::DECISION_COMUNICADO_GENERADO,
                'Comunicado de decisión generado y almacenado en el expediente.',
            );

            app(DisciplinaryDecisionNotificationService::class)
                ->notifyEvidenceUploadEnabled($case->fresh(['employee', 'assignedLawyer']));

            return $case->fresh(['employee', 'assignedLawyer', 'documents']);
        });
    }
}
