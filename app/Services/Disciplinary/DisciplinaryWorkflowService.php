<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\StageStatus;
use App\Enums\Disciplinary\StageType;
use App\Exceptions\Disciplinary\InvalidStateTransitionException;
use App\Models\Disciplinary\DisciplinaryAction;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryStage;
use App\Models\User;
use App\Workflow\Disciplinary\TransitionMap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Único punto de entrada para mover un caso entre estados.
 *
 * Garantiza atómicamente:
 *   1. Que la transición sea legal (TransitionMap)
 *   2. Que se cree la stage correspondiente al nuevo estado (cuando aplica)
 *   3. Que se registre la actuación en el audit log
 *   4. Que se actualice current_status / current_stage_type del caso
 */
class DisciplinaryWorkflowService
{
    /**
     * Aplica una transición de estado al caso.
     *
     * @param  array<string,mixed>  $context  metadata libre que se persiste en stage y action
     */
    public function transition(
        DisciplinaryCase $case,
        CaseStatus $to,
        User $actor,
        ?string $note = null,
        array $context = [],
        ?StageType $stageType = null,
        ?Carbon $scheduledAt = null,
        ?Carbon $deadlineAt = null,
    ): DisciplinaryCase {
        $from = $case->current_status;

        if ($from === $to) {
            return $case;
        }

        if (! TransitionMap::canTransition($from, $to)) {
            throw InvalidStateTransitionException::notAllowed($from, $to);
        }

        return DB::transaction(function () use (
            $case, $from, $to, $actor, $note, $context, $stageType, $scheduledAt, $deadlineAt,
        ) {
            $stage = $this->openStageFor($case, $to, $actor, $stageType, $scheduledAt, $deadlineAt, $context);

            $case->forceFill([
                'current_status' => $to,
                'current_stage_type' => $stage?->stage_type ?? $case->current_stage_type,
                'closed_at' => $to->isTerminal() ? now()->toDateString() : $case->closed_at,
            ])->save();

            DisciplinaryAction::create([
                'disciplinary_case_id' => $case->id,
                'disciplinary_stage_id' => $stage?->id,
                'user_id' => $actor->id,
                'action_type' => ActionType::ESTADO_TRANSICIONADO,
                'from_status' => $from,
                'to_status' => $to,
                'description' => $note,
                'metadata' => $context ?: null,
                'performed_at' => now(),
            ]);

            return $case->fresh(['stages', 'currentStage']);
        });
    }

    /**
     * Crea automáticamente la stage asociada al nuevo estado, cuando hay relación 1:1.
     */
    private function openStageFor(
        DisciplinaryCase $case,
        CaseStatus $to,
        User $actor,
        ?StageType $explicitType,
        ?Carbon $scheduledAt,
        ?Carbon $deadlineAt,
        array $context,
    ): ?DisciplinaryStage {
        $type = $explicitType ?? $this->stageTypeForStatus($to);
        if (! $type) {
            return null;
        }

        $sequence = ((int) $case->stages()->max('sequence')) + 1;

        return DisciplinaryStage::create([
            'disciplinary_case_id' => $case->id,
            'stage_type' => $type,
            'form_code' => $type->formCode(),
            'status' => StageStatus::EN_CURSO,
            'scheduled_at' => $scheduledAt,
            'performed_at' => now(),
            'deadline_at' => $deadlineAt,
            'performed_by' => $actor->id,
            'metadata' => $context ?: null,
            'sequence' => $sequence,
        ]);
    }

    /**
     * Mapeo de estado del caso → tipo de etapa generada automáticamente.
     */
    private function stageTypeForStatus(CaseStatus $status): ?StageType
    {
        return match ($status) {
            CaseStatus::INFORME => StageType::INFORME,
            CaseStatus::CITACION_PROGRAMADA => StageType::CITACION,
            CaseStatus::REPROGRAMADO => StageType::REPROGRAMACION,
            CaseStatus::JUSTIFICACION_PENDIENTE => StageType::JUSTIFICACION,
            CaseStatus::COMITE_DISCIPLINARIO => StageType::COMITE,
            CaseStatus::DILIGENCIA => StageType::DILIGENCIA,
            CaseStatus::DECISION => StageType::DECISION,
            CaseStatus::APELACION => StageType::APELACION,
            CaseStatus::SEGUNDA_INSTANCIA => StageType::SEGUNDA_INSTANCIA,
            default => null,
        };
    }

    /**
     * Programa una citación con fecha y lugar.
     */
    public function scheduleCitation(
        DisciplinaryCase $case,
        User $actor,
        Carbon $scheduledAt,
        ?string $location = null,
        ?string $note = null,
    ): DisciplinaryCase {
        return $this->transition(
            $case,
            CaseStatus::CITACION_PROGRAMADA,
            $actor,
            $note,
            ['location' => $location],
            StageType::CITACION,
            $scheduledAt,
        );
    }

    /**
     * Marca inasistencia a la citación. Abre ventana de 2 días para justificar.
     */
    public function markCitationNoShow(DisciplinaryCase $case, User $actor, ?string $note = null): DisciplinaryCase
    {
        $case = $this->transition($case, CaseStatus::CITACION_NO_ASISTIO, $actor, $note);

        return $this->transition(
            $case,
            CaseStatus::JUSTIFICACION_PENDIENTE,
            $actor,
            'Apertura ventana de 2 días hábiles para justificar inasistencia.',
            stageType: StageType::JUSTIFICACION,
            deadlineAt: now()->addWeekdays(2),
        );
    }

    public function acceptJustification(DisciplinaryCase $case, User $actor, ?Carbon $newCitationAt = null, ?string $note = null): DisciplinaryCase
    {
        $this->logAction($case, $actor, ActionType::JUSTIFICACION_ACEPTADA, $note);
        $case = $this->transition($case, CaseStatus::REPROGRAMADO, $actor, $note);

        if ($newCitationAt) {
            $case = $this->scheduleCitation($case, $actor, $newCitationAt, note: 'Reprogramación tras justificación.');
        }

        return $case;
    }

    public function rejectJustification(DisciplinaryCase $case, User $actor, ?string $note = null): DisciplinaryCase
    {
        $this->logAction($case, $actor, ActionType::JUSTIFICACION_RECHAZADA, $note);

        return $this->transition($case, CaseStatus::COMITE_DISCIPLINARIO, $actor, $note);
    }

    public function recordDecision(
        DisciplinaryCase $case,
        User $actor,
        \App\Enums\Disciplinary\Decision $decision,
        ?string $notes = null,
    ): DisciplinaryCase {
        $case = $this->transition(
            $case,
            CaseStatus::DECISION,
            $actor,
            $notes,
            ['decision' => $decision->value],
            StageType::DECISION,
        );

        $case->forceFill([
            'decision' => $decision,
            'decision_notes' => $notes,
            'decided_at' => now()->toDateString(),
        ])->save();

        $this->logAction($case, $actor, ActionType::DECISION_TOMADA, $notes, ['decision' => $decision->value]);

        return $case;
    }

    public function fileAppeal(DisciplinaryCase $case, User $actor, ?string $note = null): DisciplinaryCase
    {
        $this->logAction($case, $actor, ActionType::APELACION_INTERPUESTA, $note);

        return $this->transition($case, CaseStatus::APELACION, $actor, $note);
    }

    public function finalize(DisciplinaryCase $case, User $actor, ?string $note = null): DisciplinaryCase
    {
        $case = $this->transition($case, CaseStatus::FINALIZADO, $actor, $note);
        $this->logAction($case, $actor, ActionType::CASO_FINALIZADO, $note);

        return $case;
    }

    public function archive(DisciplinaryCase $case, User $actor, ?string $note = null): DisciplinaryCase
    {
        $case = $this->transition($case, CaseStatus::ARCHIVADO, $actor, $note);
        $this->logAction($case, $actor, ActionType::CASO_ARCHIVADO, $note);

        return $case;
    }

    private function logAction(
        DisciplinaryCase $case,
        User $actor,
        ActionType $type,
        ?string $description = null,
        array $metadata = [],
    ): void {
        DisciplinaryAction::create([
            'disciplinary_case_id' => $case->id,
            'user_id' => $actor->id,
            'action_type' => $type,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'performed_at' => now(),
        ]);
    }
}
