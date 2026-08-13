<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\AgendaMessageKind;
use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryAgendaMessage;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\DecisionBranch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Coordinación Etapa D: opciones múltiples → confirmación del abogado → re-negociación.
 * Fuente de verdad canónica: columnas decision_notification_* del caso.
 */
class DecisionCoordinationService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
        private readonly DisciplinaryAgendaThreadService $agenda,
        private readonly SupervisionZoneService $supervisionZones,
    ) {}

    public function hasConfirmedNotification(DisciplinaryCase $case): bool
    {
        return $case->decision_notification_completed_at !== null
            && $case->decision_notification_supervision_zone_id !== null
            && $case->decision_notification_date !== null;
    }

    public function hasOpenOptions(DisciplinaryCase $case): bool
    {
        return $this->latestOptionsMessage($case) !== null;
    }

    public function latestOptionsMessage(DisciplinaryCase $case): ?DisciplinaryAgendaMessage
    {
        $case->loadMissing('agendaThread.messages');

        $messages = $case->agendaThread?->messages;
        if ($messages === null) {
            return null;
        }

        foreach ($messages->sortByDesc('id') as $message) {
            if ($message->message_kind !== AgendaMessageKind::DECISION_PLANNING_RESPONSE) {
                continue;
            }
            if ($message->normalizedProposedSlots() !== []) {
                return $message;
            }
        }

        return null;
    }

    public function canLawyerConfirm(DisciplinaryCase $case, User $lawyer): bool
    {
        if ($case->current_status !== CaseStatus::DECISION) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $lawyer->id) {
            return false;
        }

        if ($this->hasConfirmedNotification($case)) {
            return false;
        }

        if ($case->agendaThread?->isClosed()) {
            return false;
        }

        return $this->hasOpenOptions($case) && ! $this->deliveryLocked($case);
    }

    public function canLawyerRequestNewOptions(DisciplinaryCase $case, User $lawyer): bool
    {
        if ($case->current_status !== CaseStatus::DECISION) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id !== (int) $lawyer->id) {
            return false;
        }

        if ($case->agendaThread?->isClosed()) {
            return false;
        }

        if ($this->deliveryLocked($case)) {
            return false;
        }

        return $this->hasConfirmedNotification($case) || $this->hasOpenOptions($case);
    }

    public function canPlanningPublishOptions(DisciplinaryCase $case): bool
    {
        if ($case->current_status !== CaseStatus::DECISION) {
            return false;
        }

        if ($case->decision_coordination_started_at === null) {
            return false;
        }

        if ($case->agendaThread?->isClosed()) {
            return false;
        }

        return ! $this->deliveryLocked($case);
    }

    /** Evidencia 46/47 o paquete 45 ya en expediente: no reabrir fechas. */
    public function deliveryLocked(DisciplinaryCase $case): bool
    {
        if ($case->decision_evidence_uploaded_at !== null) {
            return true;
        }

        $branch = DecisionBranch::forDecision($case->decision);
        if ($branch !== null && DecisionBranch::requiresLawyerTerminationPackage($branch)) {
            return $case->hasDecisionHrAnnex();
        }

        return false;
    }

    public function confirmOption(
        DisciplinaryCase $case,
        User $lawyer,
        int $messageId,
        int $slotIndex,
    ): DisciplinaryCase {
        if ((int) $case->assigned_lawyer_id !== (int) $lawyer->id) {
            throw ValidationException::withMessages([
                'selectedDecisionSlotKey' => 'Solo el abogado titular puede confirmar la opción de notificación.',
            ]);
        }

        if ($case->current_status !== CaseStatus::DECISION) {
            throw ValidationException::withMessages([
                'selectedDecisionSlotKey' => 'El expediente no está en etapa de decisión.',
            ]);
        }

        if ($this->deliveryLocked($case)) {
            throw ValidationException::withMessages([
                'selectedDecisionSlotKey' => 'No se puede cambiar la programación: la entrega ya está registrada.',
            ]);
        }

        $message = DisciplinaryAgendaMessage::query()
            ->whereKey($messageId)
            ->whereHas('thread', fn ($q) => $q->where('disciplinary_case_id', $case->id))
            ->first();

        if (! $message instanceof DisciplinaryAgendaMessage
            || $message->message_kind !== AgendaMessageKind::DECISION_PLANNING_RESPONSE) {
            throw ValidationException::withMessages([
                'selectedDecisionSlotKey' => 'Seleccione una opción publicada por planeación.',
            ]);
        }

        $slots = $message->normalizedProposedSlots();
        if (! isset($slots[$slotIndex])) {
            throw ValidationException::withMessages([
                'selectedDecisionSlotKey' => 'La opción seleccionada no es válida.',
            ]);
        }

        $slot = $slots[$slotIndex];
        $date = trim((string) ($slot['date'] ?? ''));
        $shift = trim((string) ($slot['notes'] ?? ''));
        $place = trim((string) ($slot['zone'] ?? ''));
        $supervisionZoneId = isset($slot['supervision_zone_id'])
            ? (int) $slot['supervision_zone_id']
            : $this->resolveLegacySupervisionZoneId($slot);

        if ($date === '' || $shift === '' || $place === '' || $supervisionZoneId <= 0) {
            throw ValidationException::withMessages([
                'selectedDecisionSlotKey' => 'La opción debe incluir fecha, turno, lugar y zona de supervisión.',
            ]);
        }

        try {
            $supervisionZone = $this->supervisionZones->assertActiveZone($supervisionZoneId);
        } catch (ValidationException) {
            throw ValidationException::withMessages([
                'selectedDecisionSlotKey' => 'La zona de supervisión de la opción no es válida.',
            ]);
        }

        $planningPayload = is_array($message->notification_payload) ? $message->notification_payload : [];

        return DB::transaction(function () use ($case, $lawyer, $message, $slot, $date, $shift, $place, $supervisionZone, $slotIndex, $planningPayload) {
            $this->invalidateGeneratedComunicadoIfNeeded($case, $lawyer);

            $case->forceFill([
                'decision_notification_completed_at' => now(),
                'decision_notification_message_id' => $message->id,
                'decision_notification_date' => $date,
                'decision_notification_shift' => $shift,
                'decision_notification_zone' => $place,
                'decision_notification_supervision_zone_id' => $supervisionZone->id,
                'decision_notification_supervision_zone_name' => $supervisionZone->name,
                'decision_notification_notes' => isset($slot['time']) && $slot['time'] !== ''
                    ? 'Hora propuesta: '.(string) $slot['time']
                    : null,
                'decision_notification_supervisor_assigned_at' => now(),
                'decision_notification_supervisor_assigned_by' => $lawyer->id,
            ])->save();

            $this->mergePlanningExtrasIntoPayload($case->fresh(), $planningPayload);

            $this->audit->logCase(
                $case->fresh(),
                $lawyer,
                ActionType::DECISION_NOTIFICACION_COORDINADA,
                'Abogado confirmó opción de notificación de decisión.',
                [
                    'message_id' => $message->id,
                    'slot_index' => $slotIndex,
                    'slot' => $slot,
                ],
            );

            return $case->fresh(['agendaThread']);
        });
    }

    public function requestNewOptions(DisciplinaryCase $case, User $lawyer, ?string $note = null): DisciplinaryCase
    {
        if (! $this->canLawyerRequestNewOptions($case, $lawyer)) {
            throw ValidationException::withMessages([
                'decisionCoordination' => 'No es posible solicitar nuevas fechas en este momento.',
            ]);
        }

        if ($this->deliveryLocked($case)) {
            throw ValidationException::withMessages([
                'decisionCoordination' => 'No se puede reabrir la programación: la entrega ya está registrada.',
            ]);
        }

        return DB::transaction(function () use ($case, $lawyer, $note) {
            $this->clearConfirmation($case, $lawyer, 'Abogado solicitó nuevas opciones de notificación.');
            $this->invalidateGeneratedComunicadoIfNeeded($case->fresh(), $lawyer);

            $body = trim((string) $note);
            if ($body === '') {
                $body = 'Solicito nuevas opciones de fecha/turno/lugar/zona de supervisión para notificar la decisión.';
            }

            $this->agenda->postLawyerMessage(
                $case->fresh(['agendaThread']),
                $lawyer,
                $body,
            );

            return $case->fresh(['agendaThread']);
        });
    }

    /**
     * Llamado al republicar opciones desde planeación: limpia confirmación previa.
     */
    public function clearConfirmationOnRepublish(DisciplinaryCase $case, User $planner): void
    {
        if (! $this->hasConfirmedNotification($case) && $case->decision_notification_completed_at === null) {
            return;
        }

        if ($this->deliveryLocked($case)) {
            throw ValidationException::withMessages([
                'decisionPlanningModal' => 'No se pueden reproponer opciones: la entrega ya está registrada.',
            ]);
        }

        $this->clearConfirmation($case, $planner, 'Planeación republó opciones; se invalidó la confirmación previa.');
        $this->invalidateGeneratedComunicadoIfNeeded($case->fresh(), $planner);
    }

    private function clearConfirmation(DisciplinaryCase $case, User $actor, string $auditNote): void
    {
        $case->forceFill([
            'decision_notification_completed_at' => null,
            'decision_notification_message_id' => null,
            'decision_notification_date' => null,
            'decision_notification_shift' => null,
            'decision_notification_zone' => null,
            'decision_notification_supervision_zone_id' => null,
            'decision_notification_supervision_zone_name' => null,
            'decision_notification_notes' => null,
            'decision_notification_supervisor_assigned_at' => null,
            'decision_notification_supervisor_assigned_by' => null,
        ])->save();

        $this->audit->logCase(
            $case->fresh(),
            $actor,
            ActionType::DECISION_COORDINACION_INICIADA,
            $auditNote,
        );
    }

    private function invalidateGeneratedComunicadoIfNeeded(DisciplinaryCase $case, User $actor): void
    {
        if ($case->decision_comunicado_generated_at === null) {
            return;
        }

        $case->forceFill([
            'decision_comunicado_generated_at' => null,
            'decision_comunicado_generated_by' => null,
            'decision_draft_completed_at' => null,
        ])->save();

        $this->audit->logCase(
            $case->fresh(),
            $actor,
            ActionType::DECISION_COMUNICADO_GENERADO,
            'Comunicado de decisión invalidado por cambio de programación; debe regenerarse.',
        );
    }

    /** @param array<string, mixed> $slot */
    private function resolveLegacySupervisionZoneId(array $slot): int
    {
        $legacySupervisorId = isset($slot['supervisor_user_id'])
            ? (int) $slot['supervisor_user_id']
            : 0;

        if ($legacySupervisorId <= 0) {
            return 0;
        }

        $legacySupervisor = User::query()->whereKey($legacySupervisorId)->first();

        return (int) ($legacySupervisor?->currentSupervisionZone()?->id ?? 0);
    }

    /** @param  array<string, mixed>  $planningPayload */
    private function mergePlanningExtrasIntoPayload(DisciplinaryCase $case, array $planningPayload): void
    {
        $extras = array_filter([
            'suspension_start' => $planningPayload['suspension_start'] ?? null,
            'suspension_end' => $planningPayload['suspension_end'] ?? null,
            'relief_notes' => $planningPayload['relief_notes'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($extras === []) {
            return;
        }

        $payload = is_array($case->decision_payload) ? $case->decision_payload : [];
        $case->forceFill([
            'decision_payload' => array_merge($payload, $extras),
        ])->save();
    }
}
