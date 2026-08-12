<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Users\UserSignatureService;
use App\Support\Disciplinary\DecisionBranch;
use App\Support\Disciplinary\FoGj46HearingLead;
use App\Support\Disciplinary\WorkerLegalPhrasing;
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
        private readonly FoGj46DraftService $foGj46Drafts,
        private readonly FoGj47DraftService $foGj47Drafts,
        private readonly UserSignatureService $signatures,
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
        if ($this->foGj46Drafts->appliesTo($case)) {
            return $this->buildFoGj46ViewData($case);
        }

        if ($this->foGj47Drafts->appliesTo($case)) {
            return $this->buildFoGj47ViewData($case);
        }

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

    /** @return array<string, mixed> */
    private function buildFoGj46ViewData(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'assignedLawyer']);
        $payload = $case->decision_comunicado_generated_at !== null
            ? ($case->decision_payload ?? [])
            : $this->foGj46Drafts->payloadForPdf($case);

        $defaults = $this->foGj46Drafts->defaultsForCase($case);
        $issuedAt = now()->timezone('America/Bogota');
        $lead = FoGj46HearingLead::tryFrom((string) ($payload['hearing_lead'] ?? ''));
        $workerName = trim(($case->employee?->first_name ?? '').' '.($case->employee?->last_name ?? ''));
        $lawyer = $case->assignedLawyer;
        $modality = (string) ($payload['modality'] ?? $defaults['modality'] ?? 'presencial');
        $legalPhrasing = WorkerLegalPhrasing::fromEmployee($case->employee);

        return [
            'issuedDateLong' => $issuedAt->translatedFormat('j \d\e F \d\e\l Y'),
            'caseNumber' => (string) $case->case_number,
            'workerName' => $workerName,
            'workerDocument' => (string) ($case->employee?->document_number ?? ''),
            'workerPosition' => (string) ($case->employee?->job_title ?? ''),
            'hearingLeadPhrase' => $lead !== null ? $legalPhrasing->foGj46HearingLeadPhrase($lead) : '',
            'postHearingBridge' => $legalPhrasing->foGj46PostHearingBridge($lead),
            'legalPhrasing' => $legalPhrasing,
            'hearingLead' => $lead?->value ?? '',
            'modalityLabel' => $modality === 'virtual' ? 'virtual' : 'presencial',
            'hearingDay' => (string) ($payload['hearing_day'] ?? $defaults['hearing_day']),
            'hearingMonth' => (string) ($payload['hearing_month'] ?? $defaults['hearing_month']),
            'hearingYear' => (string) ($payload['hearing_year'] ?? $defaults['hearing_year']),
            'factsNarrative' => (string) ($payload['facts_narrative'] ?? ''),
            'breachDay' => (string) ($payload['breach_day'] ?? $defaults['breach_day']),
            'breachMonth' => (string) ($payload['breach_month'] ?? $defaults['breach_month']),
            'breachYear' => (string) ($payload['breach_year'] ?? $defaults['breach_year']),
            'articles55' => (string) ($payload['articles_55'] ?? ''),
            'articles57' => (string) ($payload['articles_57'] ?? ''),
            'articles60' => (string) ($payload['articles_60'] ?? ''),
            'signerName' => (string) ($payload['signer_name'] ?? ''),
            'signerTitle' => (string) ($payload['signer_title'] ?? 'DIRECTORA DE GESTIÓN HUMANA'),
            'signatureDataUri' => $lawyer ? $this->signatures->dataUriForPdf($lawyer) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function buildFoGj47ViewData(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'assignedLawyer']);
        $payload = $case->decision_comunicado_generated_at !== null
            ? ($case->decision_payload ?? [])
            : $this->foGj47Drafts->payloadForPdf($case);

        $defaults = $this->foGj47Drafts->defaultsForCase($case);
        $issuedAt = now()->timezone('America/Bogota');
        $legalPhrasing = WorkerLegalPhrasing::fromEmployee($case->employee);
        $workerName = trim(($case->employee?->first_name ?? '').' '.($case->employee?->last_name ?? ''));
        $lawyer = $case->assignedLawyer;

        return [
            'issuedDateLong' => $issuedAt->translatedFormat('j \d\e F \d\e\l Y'),
            'caseNumber' => (string) $case->case_number,
            'workerName' => $workerName,
            'workerDocument' => (string) ($case->employee?->document_number ?? ''),
            'workerPosition' => (string) ($case->employee?->job_title ?? ''),
            'openingSalutation' => $legalPhrasing->foGj47OpeningSalutation(),
            'openingNarrative' => (string) ($payload['opening_narrative'] ?? ''),
            'daysPhrase' => (string) ($payload['days_phrase'] ?? $defaults['days_phrase'] ?? ''),
            'notifyWorkerPhrase' => $legalPhrasing->foGj47NotifyWorkerPhrase(),
            'startLong' => (string) ($payload['start_long'] ?? $defaults['start_long'] ?? ''),
            'endLong' => (string) ($payload['end_long'] ?? $defaults['end_long'] ?? ''),
            'returnLong' => (string) ($payload['return_long'] ?? $defaults['return_long'] ?? ''),
            'articles55' => (string) ($payload['articles_55'] ?? ''),
            'articles57' => (string) ($payload['articles_57'] ?? ''),
            'articles60' => (string) ($payload['articles_60'] ?? ''),
            'signerName' => (string) ($payload['signer_name'] ?? ''),
            'signerTitle' => (string) ($payload['signer_title'] ?? 'DIRECTORA DE GESTIÓN HUMANA'),
            'signatureDataUri' => $lawyer ? $this->signatures->dataUriForPdf($lawyer) : null,
            'legalPhrasing' => $legalPhrasing,
        ];
    }

    public function downloadPdf(DisciplinaryCase $case, User $actor): string
    {
        $this->drafts->payloadForPdf($case);

        if ($this->foGj46Drafts->appliesTo($case)) {
            return HtmlLetterPdfGenerator::fromView(
                'disciplinary.forms.fo-gj-46-filled-download',
                array_merge(
                    $this->buildFoGj46ViewData($case),
                    ['embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri()],
                ),
            );
        }

        if ($this->foGj47Drafts->appliesTo($case)) {
            return HtmlLetterPdfGenerator::fromView(
                'disciplinary.forms.fo-gj-47-filled-download',
                array_merge(
                    $this->buildFoGj47ViewData($case),
                    ['embeddedLogoSrc' => EmbeddedPublicAsset::disciplinaryLogoDataUri()],
                ),
            );
        }

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
            $field = $this->foGj47Drafts->appliesTo($case)
                ? 'foGj47OpeningNarrative'
                : ($this->foGj46Drafts->appliesTo($case) ? 'foGj46HearingLead' : 'decisionBodyNarrative');
            throw ValidationException::withMessages([
                $field => $missing !== []
                    ? 'No es posible generar el documento. Falta: '.implode(', ', $missing)
                    : 'Complete el diligenciamiento antes de generar el documento.',
            ]);
        }

        $isFo46 = $this->foGj46Drafts->appliesTo($case);
        $isFo47 = $this->foGj47Drafts->appliesTo($case);

        return DB::transaction(function () use ($case, $actor, $isFo46, $isFo47) {
            $binary = $this->downloadPdf($case, $actor);
            $prefix = $isFo47 ? 'fo47_' : ($isFo46 ? 'fo46_' : 'decision_');
            $path = tempnam(sys_get_temp_dir(), $prefix);
            file_put_contents($path, $binary);

            try {
                $filename = match (true) {
                    $isFo47 => 'FO-GJ-47-suspension-'.$case->case_number.'.pdf',
                    $isFo46 => 'FO-GJ-46-llamado-atencion-'.$case->case_number.'.pdf',
                    default => 'Comunicado-decision-'.$case->case_number.'.pdf',
                };

                $uploaded = new UploadedFile(
                    $path,
                    $filename,
                    'application/pdf',
                    UPLOAD_ERR_OK,
                    true,
                );

                $stage = $case->stages()
                    ->where('stage_type', StageType::DECISION)
                    ->orderByDesc('sequence')
                    ->first();

                $note = match (true) {
                    $isFo47 => DisciplinaryCase::NOTE_FO_GJ_47_GENERATED,
                    $isFo46 => DisciplinaryCase::NOTE_FO_GJ_46_GENERATED,
                    default => DisciplinaryCase::NOTE_DECISION_COMUNICADO_GENERATED,
                };

                $this->documents->upload(
                    $case,
                    $uploaded,
                    DocumentType::DECISION,
                    $actor,
                    $stage,
                    $note,
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

            $auditMessage = match (true) {
                $isFo47 => 'FO-GJ-47 (Suspensión) generado y almacenado en el expediente.',
                $isFo46 => 'FO-GJ-46 (Llamado de atención) generado y almacenado en el expediente.',
                default => 'Comunicado de decisión generado y almacenado en el expediente.',
            };

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::DECISION_COMUNICADO_GENERADO,
                $auditMessage,
            );

            app(DisciplinaryDecisionNotificationService::class)
                ->notifyEvidenceUploadEnabled($case->fresh(['employee', 'assignedLawyer']));

            return $case->fresh(['employee', 'assignedLawyer', 'documents']);
        });
    }
}
