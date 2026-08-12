<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\CitationEvidenceType;
use App\Enums\Disciplinary\Decision;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\StageType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\DecisionBranch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisciplinaryDecisionWorkflowService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
        private readonly DisciplinaryAgendaThreadService $agenda,
        private readonly DisciplinaryWorkflowService $workflow,
        private readonly DisciplinaryDocumentService $documents,
    ) {}

    public function assertCanSelectDecisionType(DisciplinaryCase $case, User $lawyer): void
    {
        if ($case->current_status !== CaseStatus::DECISION) {
            throw ValidationException::withMessages([
                'decisionType' => 'El expediente no está en etapa de decisión.',
            ]);
        }

        if ((int) $case->assigned_lawyer_id !== (int) $lawyer->id) {
            throw ValidationException::withMessages([
                'decisionType' => 'Solo el abogado titular puede registrar el tipo de decisión.',
            ]);
        }

        if ($case->decision_coordination_started_at !== null) {
            throw ValidationException::withMessages([
                'decisionType' => 'El tipo de decisión ya fue registrado.',
            ]);
        }
    }

    public function selectDecisionType(DisciplinaryCase $case, User $lawyer, Decision $decision): DisciplinaryCase
    {
        $this->assertCanSelectDecisionType($case, $lawyer);

        return DB::transaction(function () use ($case, $lawyer, $decision) {
            $now = now();

            $case->forceFill([
                'decision' => $decision,
                'decided_at' => now()->toDateString(),
                'decision_coordination_started_at' => $now,
                'decision_coordination_started_by' => $lawyer->id,
            ])->save();

            $this->agenda->reopenCoordinationForDecision($case->fresh(), $lawyer);

            $this->audit->logCase(
                $case->fresh(),
                $lawyer,
                ActionType::DECISION_TOMADA,
                'Tipo de decisión registrado: '.$decision->label(),
                ['decision' => $decision->value],
            );

            $this->audit->logCase(
                $case->fresh(),
                $lawyer,
                ActionType::DECISION_COORDINACION_INICIADA,
                'Coordinación de decisión iniciada con planeación.',
            );

            return $case->fresh(['agendaThread']);
        });
    }

    /** @return list<string> */
    public function missingFinalizeRequirements(DisciplinaryCase $case): array
    {
        $missing = [];

        if ($case->current_status !== CaseStatus::DECISION) {
            return ['expediente en etapa de decisión'];
        }

        if ($case->decision_comunicado_generated_at === null && $case->latestDecisionComunicadoDocument() === null) {
            $missing[] = 'documento FO-GJ de decisión generado';
        }

        $branch = DecisionBranch::forDecision($case->decision);

        if ($branch !== null && DecisionBranch::requiresLawyerTerminationPackage($branch)) {
            if (! $case->hasDecisionHrAnnex()) {
                $missing[] = 'paquete PDF de terminación (anexos firmados) cargado por el abogado';
            }
        } elseif ($case->decision_evidence_uploaded_at === null) {
            $missing[] = 'evidencia de notificación cargada';
        }

        return $missing;
    }

    public function assertCanFinalize(DisciplinaryCase $case, User $lawyer): void
    {
        if ((int) $case->assigned_lawyer_id !== (int) $lawyer->id) {
            throw ValidationException::withMessages([
                'decisionFinalizeConclusion' => 'Solo el abogado titular puede finalizar el proceso.',
            ]);
        }

        $missing = $this->missingFinalizeRequirements($case);
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'decisionFinalizeConclusion' => 'No es posible finalizar. Falta: '.implode(', ', $missing),
            ]);
        }
    }

    public function finalizeCase(DisciplinaryCase $case, User $lawyer, ?string $note = null): DisciplinaryCase
    {
        $this->assertCanFinalize($case, $lawyer);

        $conclusion = trim((string) $note);
        if ($conclusion === '') {
            throw ValidationException::withMessages([
                'decisionFinalizeConclusion' => 'Indique una breve conclusión antes de cerrar el caso.',
            ]);
        }

        $case = $case->fresh();
        $case->forceFill([
            'decision_notes' => $conclusion,
        ])->save();

        return $this->workflow->finalize($case->fresh(), $lawyer, $conclusion);
    }

    public function markEvidenceUploaded(DisciplinaryCase $case, CitationEvidenceType $type): DisciplinaryCase
    {
        $case->forceFill([
            'decision_evidence_type' => $type->value,
            'decision_evidence_uploaded_at' => now(),
        ])->save();

        return $case->fresh();
    }

    public function assertDecisionEvidenceUploadAllowed(DisciplinaryCase $case, User $user): void
    {
        if (! $case->canUserUploadDecisionEvidence($user)) {
            throw ValidationException::withMessages([
                'decisionEvidence' => 'No tiene permisos para cargar evidencia de decisión en este expediente.',
            ]);
        }

        if ($case->decision_comunicado_generated_at === null && $case->latestDecisionComunicadoDocument() === null) {
            throw ValidationException::withMessages([
                'decisionEvidence' => 'Genere el comunicado de decisión antes de cargar evidencia.',
            ]);
        }

        if ($case->decision_evidence_uploaded_at !== null) {
            throw ValidationException::withMessages([
                'decisionEvidence' => 'La evidencia de decisión ya fue registrada.',
            ]);
        }
    }

    public function completeHrReview(DisciplinaryCase $case, User $actor): DisciplinaryCase
    {
        if ($case->current_status !== CaseStatus::DECISION) {
            throw new \InvalidArgumentException('El expediente no está en etapa de decisión.');
        }

        $branch = DecisionBranch::forDecision($case->decision);
        if ($branch === null || ! DecisionBranch::requiresHrReview($branch)) {
            throw new \InvalidArgumentException('Este tipo de decisión no requiere gestión humana.');
        }

        if (! $this->userCanCompleteHrReview($actor)) {
            throw new \InvalidArgumentException('No tiene permisos para completar la gestión humana.');
        }

        if ($case->decision_hr_review_completed_at !== null) {
            throw new \InvalidArgumentException('La gestión humana ya fue completada.');
        }

        if (! $case->hasDecisionHrAnnex()) {
            throw new \InvalidArgumentException('Debe cargar al menos un anexo laboral antes de completar la gestión.');
        }

        return DB::transaction(function () use ($case, $actor) {
            $case->forceFill([
                'decision_hr_review_completed_at' => now(),
                'decision_hr_review_completed_by' => $actor->id,
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::DECISION_RRHH_COMPLETADA,
                'Gestión humana completada (anexos laborales).',
            );

            return $case->fresh();
        });
    }

    public function userCanCompleteHrReview(User $user): bool
    {
        if ($user->read_only) {
            return false;
        }

        return $user->hasRole('nivel4') || $user->hasRole('nivel1');
    }

    public function uploadHrAnnex(DisciplinaryCase $case, User $actor, UploadedFile $file): DisciplinaryCase
    {
        return $this->uploadTerminationPackage($case, $actor, $file);
    }

    /**
     * Paquete PDF único de terminación (certificado, egreso, SS, liquidación, etc.) — lo carga el abogado titular.
     */
    public function uploadTerminationPackage(DisciplinaryCase $case, User $actor, UploadedFile $file): DisciplinaryCase
    {
        if ($case->current_status !== CaseStatus::DECISION) {
            throw ValidationException::withMessages([
                'terminationPackageFile' => 'El expediente no está en etapa de decisión.',
            ]);
        }

        $branch = DecisionBranch::forDecision($case->decision);
        if ($branch === null || ! DecisionBranch::requiresLawyerTerminationPackage($branch)) {
            throw ValidationException::withMessages([
                'terminationPackageFile' => 'Este tipo de decisión no requiere paquete de terminación.',
            ]);
        }

        if ((int) $case->assigned_lawyer_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'terminationPackageFile' => 'Solo el abogado titular puede cargar el paquete de terminación.',
            ]);
        }

        $stage = $case->stages()
            ->where('stage_type', StageType::DECISION)
            ->orderByDesc('sequence')
            ->first();

        $this->documents->upload(
            $case,
            $file,
            DocumentType::EVIDENCIA,
            $actor,
            $stage,
            DisciplinaryCase::NOTE_DECISION_TERMINATION_PACKAGE_PREFIX.' — '.$file->getClientOriginalName(),
        );

        $case->forceFill([
            'decision_hr_review_completed_at' => now(),
            'decision_hr_review_completed_by' => $actor->id,
        ])->save();

        $this->audit->logCase(
            $case->fresh(),
            $actor,
            ActionType::DECISION_RRHH_COMPLETADA,
            'Paquete PDF de terminación cargado por el abogado titular.',
        );

        return $case->fresh(['documents']);
    }
}
