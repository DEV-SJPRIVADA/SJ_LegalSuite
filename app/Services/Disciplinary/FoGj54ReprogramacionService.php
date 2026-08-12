<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Users\UserSignatureService;
use App\Support\Disciplinary\FoGj03Modality;
use App\Support\Disciplinary\FoGj54RescheduleCause;
use App\Support\Disciplinary\SpanishDateParts;
use App\Support\Disciplinary\WorkerLegalPhrasing;
use App\Support\Pdf\EmbeddedPublicAsset;
use App\Support\Pdf\HtmlLetterPdfGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FoGj54ReprogramacionService
{
    public const NOTE_FO_GJ_54_EVIDENCE = 'Evidencia de recibido FO-GJ-54 (reprogramación)';

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
        if ($case->assigned_lawyer_id === null || ! $this->drafts->isReadyForPdf($case)) {
            return false;
        }

        if ($this->drafts->isJustificationContext($case)) {
            return $case->fo_gj_54_generated_at === null;
        }

        if ($this->drafts->isOperationalRescheduleContext($case)) {
            $payload = $case->fo_gj_54_payload ?? [];

            return ($payload['mode'] ?? null) === FoGj54DraftService::MODE_OPERATIONAL
                && $case->fo_gj_54_generated_at === null
                && filled($payload['new_hearing_date'] ?? null);
        }

        return false;
    }

    public function canUploadReceiptEvidence(DisciplinaryCase $case): bool
    {
        return $case->current_status === \App\Enums\Disciplinary\CaseStatus::REPROGRAMADO
            && ($case->fo_gj_54_payload['mode'] ?? null) === FoGj54DraftService::MODE_OPERATIONAL
            && $case->fo_gj_54_generated_at !== null
            && $case->fo_gj_54_evidence_uploaded_at === null;
    }

    /** @return array<string, mixed> */
    public function buildViewData(DisciplinaryCase $case): array
    {
        $case->loadMissing(['employee', 'assignedLawyer.jobPosition']);
        $payload = $this->drafts->payloadForPdf($case);
        $lawyer = $case->assignedLawyer;
        $defaults = $this->drafts->defaultsForCase($case);
        $charges = $this->drafts->chargesFromFo03($case);
        $newParts = SpanishDateParts::fromDate(
            \Illuminate\Support\Carbon::parse((string) $payload['new_hearing_date'])->timezone('America/Bogota')
        );

        $cause = FoGj54RescheduleCause::tryFrom((string) ($payload['reschedule_cause'] ?? ''));
        $modality = FoGj03Modality::tryFrom((string) ($payload['modality'] ?? '')) ?? FoGj03Modality::Presencial;
        $modalityLocationText = $modality === FoGj03Modality::Virtual
            ? (string) ($payload['virtual_meeting_link'] ?? '')
            : FoGj03DraftService::PRESENCIAL_LOCATION;

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
            'informeReportDateLong' => $charges['informe_report_date_long'],
            'chargesDescription' => $charges['charges_description'],
            'rescheduleCausePhrase' => $cause?->dueToPhrase() ?? '',
            'newHearingDay' => $newParts['day'],
            'newHearingMonth' => $newParts['month'],
            'newHearingYear' => $newParts['year'],
            'newHearingTime' => (string) ($payload['new_hearing_time'] ?? ''),
            'modality' => $modality->value,
            'modalityLocationText' => $modalityLocationText,
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
        if (! $this->drafts->isJustificationContext($case) || ! $this->canGenerate($case)) {
            $missing = $this->drafts->missingDraftRequirements($case);
            throw ValidationException::withMessages([
                'fo_gj_54' => $missing !== []
                    ? 'No es posible generar FO-GJ-54. Falta: '.implode(', ', $missing)
                    : 'Complete el diligenciamiento del FO-GJ-54 antes de generar el documento.',
            ]);
        }

        return DB::transaction(function () use ($case, $actor, $note) {
            $this->storeGeneratedPdf($case, $actor, StageType::JUSTIFICACION);

            $newCitationAt = $this->drafts->newCitationAt($case->fresh());

            return $this->workflow->acceptJustification(
                $case->fresh(['employee', 'assignedLawyer']),
                $actor,
                $newCitationAt,
                $note ?? 'Justificación aceptada; diligencia reprogramada.',
            );
        });
    }

    /**
     * Inicia reprogramación operativa diferida a planeación (sin generar FO-GJ-54 aún).
     */
    public function beginOperationalRescheduleWithPlanning(DisciplinaryCase $case, User $actor, string $rescheduleCause): DisciplinaryCase
    {
        if (! $this->drafts->isOperationalRescheduleContext($case) || $case->current_status !== \App\Enums\Disciplinary\CaseStatus::DILIGENCIA) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'Solo puede iniciar coordinación de reprogramación desde diligencia, antes de registrar asistencia.',
            ]);
        }

        $cause = FoGj54RescheduleCause::tryFrom(trim($rescheduleCause));
        if ($cause === null) {
            throw ValidationException::withMessages([
                'foGj54RescheduleCause' => 'Seleccione el motivo de la reprogramación.',
            ]);
        }

        return DB::transaction(function () use ($case, $actor, $cause) {
            $originalHearingDate = $case->citation_confirmed_date?->format('Y-m-d') ?? '';
            $originalHearingTime = '';
            if ($case->citation_confirmed_time) {
                try {
                    $originalHearingTime = \Illuminate\Support\Carbon::parse($case->citation_confirmed_time)->format('H:i');
                } catch (\Throwable) {
                    $originalHearingTime = substr((string) $case->citation_confirmed_time, 0, 5);
                }
            }

            $case->forceFill([
                'fo_gj_54_payload' => [
                    'mode' => FoGj54DraftService::MODE_OPERATIONAL,
                    'reschedule_cause' => $cause->value,
                    'defer_date_to_planning' => true,
                    'modality' => FoGj03Modality::Presencial->value,
                    'virtual_meeting_link' => '',
                    'new_hearing_date' => '',
                    'new_hearing_time' => '',
                    'new_hearing_place' => '',
                    'original_hearing_date' => $originalHearingDate,
                    'original_hearing_time' => $originalHearingTime,
                ],
                'fo_gj_54_draft_completed_at' => null,
                'fo_gj_54_draft_completed_by' => null,
                'fo_gj_54_generated_at' => null,
                'fo_gj_54_generated_by' => null,
                'fo_gj_54_evidence_uploaded_at' => null,
            ])->save();

            $fresh = $this->workflow->rescheduleDiligenceOperational(
                $case->fresh(['employee', 'assignedLawyer']),
                $actor,
                [
                    'reason' => $cause->label(),
                    'defer_date_to_planning' => true,
                ],
                'Reprogramación operativa: coordinación de fechas con planeación.',
            );

            return $this->reopenCitationCoordinationIfNeeded($fresh, $actor);
        });
    }

    public function generateOperationalRescheduleAndStore(DisciplinaryCase $case, User $actor, ?string $note = null): DisciplinaryCase
    {
        if (! $this->drafts->isOperationalRescheduleContext($case) || ! $this->canGenerate($case)) {
            $missing = $this->drafts->missingDraftRequirements($case);
            throw ValidationException::withMessages([
                'fo_gj_54' => $missing !== []
                    ? 'No es posible generar FO-GJ-54. Falta: '.implode(', ', $missing)
                    : 'Complete el diligenciamiento del FO-GJ-54 antes de reprogramar la diligencia.',
            ]);
        }

        return DB::transaction(function () use ($case, $actor, $note) {
            $stageType = $case->current_status === \App\Enums\Disciplinary\CaseStatus::REPROGRAMADO
                ? StageType::REPROGRAMACION
                : StageType::DILIGENCIA;

            $this->storeGeneratedPdf($case, $actor, $stageType);

            $payload = $case->fresh()->fo_gj_54_payload ?? [];
            $cause = FoGj54RescheduleCause::tryFrom((string) ($payload['reschedule_cause'] ?? ''));

            $fresh = $this->workflow->rescheduleDiligenceOperational(
                $case->fresh(['employee', 'assignedLawyer']),
                $actor,
                [
                    'reason' => $cause?->label() ?? 'Reprogramación operativa',
                    'new_hearing_date' => (string) ($payload['new_hearing_date'] ?? ''),
                    'new_hearing_time' => (string) ($payload['new_hearing_time'] ?? ''),
                    'new_hearing_place' => (string) ($payload['new_hearing_place'] ?? ''),
                    'defer_date_to_planning' => false,
                ],
                $note ?? 'Reprogramación operativa de diligencia (FO-GJ-54).',
            );

            return $this->reopenCitationCoordinationIfNeeded($fresh, $actor);
        });
    }

    public function uploadReceiptEvidenceAndReturnToDiligence(
        DisciplinaryCase $case,
        User $actor,
        UploadedFile $file,
    ): DisciplinaryCase {
        if (! $this->canUploadReceiptEvidence($case)) {
            throw ValidationException::withMessages([
                'foGj54EvidenceFile' => 'No es posible cargar evidencia FO-GJ-54 en el estado actual.',
            ]);
        }

        return DB::transaction(function () use ($case, $actor, $file) {
            $stage = $case->stages()
                ->where('stage_type', StageType::REPROGRAMACION)
                ->orderByDesc('sequence')
                ->first();

            $doc = $this->documents->upload(
                $case,
                $file,
                DocumentType::EVIDENCIA,
                $actor,
                $stage,
                self::NOTE_FO_GJ_54_EVIDENCE,
            );

            $case->forceFill([
                'fo_gj_54_evidence_uploaded_at' => now(),
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::FO_GJ_54_EVIDENCIA_CARGADA,
                'Evidencia de recibido del FO-GJ-54 cargada.',
                ['document_id' => $doc->id],
            );

            return $this->workflow->returnToDiligenceAfterFoGj54Evidence(
                $case->fresh(['employee', 'assignedLawyer']),
                $actor,
            );
        });
    }

    private function reopenCitationCoordinationIfNeeded(DisciplinaryCase $case, User $actor): DisciplinaryCase
    {
        $case->loadMissing('agendaThread');
        $thread = $case->agendaThread;
        if ($thread === null || $thread->isOpen()) {
            return $case;
        }

        $thread->forceFill([
            'coordination_status' => 'open',
            'closed_at' => null,
            'closed_by' => null,
        ])->save();

        $this->audit->logCase(
            $case->fresh(),
            $actor,
            ActionType::COORDINACION_INICIADA,
            'Coordinación reabierta tras reprogramación operativa de diligencia.',
            ['agenda_thread_id' => $thread->id],
        );

        return $case->fresh(['agendaThread']);
    }

    private function storeGeneratedPdf(DisciplinaryCase $case, User $actor, StageType $preferredStage): void
    {
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
                ->where('stage_type', $preferredStage)
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
            'fo_gj_54_evidence_uploaded_at' => null,
        ])->save();

        $this->audit->logCase(
            $case->fresh(),
            $actor,
            ActionType::FO_GJ_54_GENERADO,
            'FO-GJ-54 generado y almacenado en el expediente.',
        );
    }
}
