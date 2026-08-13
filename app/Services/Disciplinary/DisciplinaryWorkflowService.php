<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Enums\Disciplinary\StageStatus;
use App\Enums\Disciplinary\StageType;
use App\Exceptions\Disciplinary\InvalidStateTransitionException;
use App\Models\Disciplinary\DisciplinaryAction;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryStage;
use App\Models\User;
use App\Support\Disciplinary\SpanishDateParts;
use App\Workflow\Disciplinary\TransitionMap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
    public function __construct(
        private readonly DisciplinaryCitationWorkflowService $citationWorkflow,
    ) {}

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

        if ($from === CaseStatus::CITACION_PROGRAMADA) {
            $this->citationWorkflow->assertCanLeaveCitacionStage($case);
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
     * Actualiza únicamente las fechas de una etapa (citación / plazo). Usado por Planeación.
     */
    public function updateStageSchedule(
        DisciplinaryCase $case,
        DisciplinaryStage $stage,
        User $actor,
        ?Carbon $scheduledAt,
        ?Carbon $deadlineAt,
        ?string $note = null,
    ): DisciplinaryStage {
        if ($stage->disciplinary_case_id !== $case->id) {
            throw new \InvalidArgumentException('La etapa no pertenece al caso indicado.');
        }

        return DB::transaction(function () use ($case, $stage, $actor, $scheduledAt, $deadlineAt, $note) {
            $stage->forceFill([
                'scheduled_at' => $scheduledAt,
                'deadline_at' => $deadlineAt,
            ])->save();

            DisciplinaryAction::create([
                'disciplinary_case_id' => $case->id,
                'disciplinary_stage_id' => $stage->id,
                'user_id' => $actor->id,
                'action_type' => ActionType::FECHA_ETAPA_ACTUALIZADA,
                'description' => $note ?? 'Fechas de etapa actualizadas',
                'metadata' => [
                    'scheduled_at' => $scheduledAt?->toIso8601String(),
                    'deadline_at' => $deadlineAt?->toIso8601String(),
                ],
                'performed_at' => now(),
            ]);

            return $stage->fresh();
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
     * Marca inasistencia a la citación. Abre ventana de 2 días calendario para justificar.
     */
    public function markCitationNoShow(DisciplinaryCase $case, User $actor, ?string $note = null): DisciplinaryCase
    {
        $case = $this->transition($case, CaseStatus::CITACION_NO_ASISTIO, $actor, $note);

        return $this->transition(
            $case,
            CaseStatus::JUSTIFICACION_PENDIENTE,
            $actor,
            'Apertura ventana de 2 días calendario para justificar inasistencia.',
            stageType: StageType::JUSTIFICACION,
            deadlineAt: now()->addDays(2),
        );
    }

    /**
     * Tras FO-GJ-44 en diligencia: abre ventana de 2 días calendario para justificar inasistencia.
     */
    public function openDiligenceNoShowJustification(DisciplinaryCase $case, User $actor, ?string $note = null): DisciplinaryCase
    {
        return $this->transition(
            $case,
            CaseStatus::JUSTIFICACION_PENDIENTE,
            $actor,
            $note ?? 'Apertura ventana de 2 días calendario para justificar inasistencia a diligencia.',
            stageType: StageType::JUSTIFICACION,
            deadlineAt: now()->addDays(2),
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

    /**
     * Reprogramación operativa desde diligencia (fuerza mayor / necesidad de la compañía).
     * Conserva FO-GJ-03 y su evidencia (citación única). Queda en REPROGRAMADO hasta
     * cargar evidencia de recibido del FO-GJ-54 y volver a diligencia.
     *
     * @param  array{
     *     reason: string,
     *     new_hearing_date?: string|null,
     *     new_hearing_time?: string|null,
     *     new_hearing_place?: string|null,
     *     defer_date_to_planning?: bool,
     * }  $payload
     */
    public function rescheduleDiligenceOperational(DisciplinaryCase $case, User $actor, array $payload, ?string $note = null): DisciplinaryCase
    {
        if ($case->current_status !== CaseStatus::DILIGENCIA
            && ! ($case->current_status === CaseStatus::REPROGRAMADO && $this->isOperationalRescheduleCase($case))) {
            throw new InvalidStateTransitionException('La reprogramación operativa solo aplica desde diligencia o reprogramación operativa en curso.');
        }

        if ($case->diligence_attendance !== null) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'No es posible reprogramar: la asistencia ya fue registrada.',
            ]);
        }

        $reason = trim((string) ($payload['reason'] ?? ''));
        if ($reason === '') {
            throw ValidationException::withMessages([
                'foGj54RescheduleReason' => 'Indique el motivo de la reprogramación.',
            ]);
        }

        $deferToPlanning = (bool) ($payload['defer_date_to_planning'] ?? false);
        $newHearingDate = trim((string) ($payload['new_hearing_date'] ?? ''));
        $newHearingTime = trim((string) ($payload['new_hearing_time'] ?? ''));
        $newHearingPlace = trim((string) ($payload['new_hearing_place'] ?? ''));

        $this->logAction(
            $case,
            $actor,
            ActionType::REPROGRAMADO,
            $note ?? 'Reprogramación operativa de diligencia (FO-GJ-54).',
            [
                'reason' => $reason,
                'defer_date_to_planning' => $deferToPlanning,
                'new_hearing_date' => $newHearingDate,
                'new_hearing_time' => $newHearingTime,
                'new_hearing_place' => $newHearingPlace,
                'preserves_fo_gj_03' => true,
            ],
        );

        if ($case->current_status === CaseStatus::DILIGENCIA) {
            $case = $this->transition(
                $case,
                CaseStatus::REPROGRAMADO,
                $actor,
                $note ?? 'Reprogramación operativa de diligencia.',
                [
                    'reason' => $reason,
                    'operational' => true,
                ],
                StageType::REPROGRAMACION,
            );
        }

        $case = $case->fresh();

        $updates = [
            'fo_gj_54_evidence_uploaded_at' => null,
        ];

        if ($deferToPlanning) {
            $updates['citation_confirmed_date'] = null;
            $updates['citation_confirmed_time'] = null;
            $updates['citation_confirmed_by'] = null;
            $updates['citation_selected_message_id'] = null;
        } elseif ($newHearingDate !== '') {
            $updates['citation_confirmed_date'] = $newHearingDate;
            $updates['citation_confirmed_time'] = SpanishDateParts::normalizeTimeForStorage($newHearingTime);
            $updates['citation_confirmed_by'] = $actor->id;
        }

        $case->forceFill($updates)->save();

        return $case->fresh();
    }

    /**
     * Tras evidencia de recibido del FO-GJ-54 operativo: vuelve a Etapa C (diligencia).
     */
    public function returnToDiligenceAfterFoGj54Evidence(DisciplinaryCase $case, User $actor, ?string $note = null): DisciplinaryCase
    {
        if ($case->current_status !== CaseStatus::REPROGRAMADO) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'El expediente debe estar en reprogramación para volver a diligencia.',
            ]);
        }

        if (! $this->isOperationalRescheduleCase($case)) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'Solo aplica a reprogramación operativa con FO-GJ-54.',
            ]);
        }

        if ($case->fo_gj_54_generated_at === null) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'Genere el FO-GJ-54 antes de cargar la evidencia y volver a diligencia.',
            ]);
        }

        if ($case->fo_gj_54_evidence_uploaded_at === null) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'Cargue la evidencia de recibido del FO-GJ-54 firmado.',
            ]);
        }

        if ($case->citation_confirmed_date === null) {
            throw ValidationException::withMessages([
                'fo_gj_54' => 'Confirme la nueva fecha de diligencia (abogado o planeación) antes de volver a Etapa C.',
            ]);
        }

        return $this->transition(
            $case,
            CaseStatus::DILIGENCIA,
            $actor,
            $note ?? 'Retorno a diligencia tras reprogramación operativa (FO-GJ-54 notificado).',
            ['operational_reschedule' => true],
            StageType::DILIGENCIA,
        );
    }

    private function isOperationalRescheduleCase(DisciplinaryCase $case): bool
    {
        $payload = $case->fo_gj_54_payload ?? [];

        return ($payload['mode'] ?? null) === 'operational';
    }

    public function rejectJustification(DisciplinaryCase $case, User $actor, ?string $note = null): DisciplinaryCase
    {
        $this->logAction($case, $actor, ActionType::JUSTIFICACION_RECHAZADA, $note);

        return $this->transition($case, CaseStatus::COMITE_DISCIPLINARIO, $actor, $note);
    }

    public function recordDecision(
        DisciplinaryCase $case,
        User $actor,
        Decision $decision,
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
