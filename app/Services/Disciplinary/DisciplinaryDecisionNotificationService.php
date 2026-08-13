<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\AgendaMessageKind;
use App\Models\Disciplinary\DisciplinaryAgendaMessage;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\FieldDisciplinaryScopeService;
use App\Notifications\DisciplinaryDecisionCoordinatedNotification;
use App\Notifications\DisciplinaryDecisionEvidenceEnabledNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class DisciplinaryDecisionNotificationService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
        private readonly DecisionDraftService $drafts,
    ) {}

    public function canPlanningRegisterNotification(DisciplinaryCase $case): bool
    {
        // Etapa D ya no usa un segundo modal de notificación: el abogado confirma una opción.
        return false;
    }

    public function hasNotificationInformationCompleted(DisciplinaryCase $case): bool
    {
        return app(DecisionCoordinationService::class)->hasConfirmedNotification($case);
    }

    /** @return Collection<string, bool> */
    public function generationChecklist(DisciplinaryCase $case): Collection
    {
        $coordination = app(DecisionCoordinationService::class);

        return collect([
            'decision_type' => $case->decision !== null,
            'coordination_completed' => $coordination->hasOpenOptions($case),
            'notification_completed' => $coordination->hasConfirmedNotification($case),
            'notification_shift' => filled($case->decision_notification_shift),
            'notification_zone' => filled($case->decision_notification_zone),
            'notification_supervisor' => $case->decision_notification_supervisor_user_id !== null,
            'decision_draft' => $this->drafts->hasDraftCompleted($case),
            'lawyer_signature' => $case->assignedLawyer?->hasSignature() ?? false,
        ]);
    }

    /** @return list<string> */
    public function missingGenerationRequirements(DisciplinaryCase $case): array
    {
        $missing = [];
        $checklist = $this->generationChecklist($case);

        if (! $checklist['decision_type']) {
            $missing[] = 'Tipo de decisión registrado';
        }
        if (! $checklist['coordination_completed']) {
            $missing[] = 'Programación completada por planeación';
        }
        if (! $checklist['notification_completed']) {
            $missing[] = 'Información de notificación completada';
        }
        if (! $checklist['notification_shift']) {
            $missing[] = 'Turno del trabajador';
        }
        if (! $checklist['notification_zone']) {
            $missing[] = 'Zona';
        }
        if (! $checklist['notification_supervisor']) {
            $missing[] = 'Supervisor asignado';
        }
        if (! $checklist['decision_draft']) {
            $missing[] = 'Comunicado diligenciado';
        }
        if (! $checklist['lawyer_signature']) {
            $missing[] = 'Firma digital del abogado en Mi perfil';
        }

        return $missing;
    }

    /**
     * @param  array{
     *     notification_date: string,
     *     notification_shift: string,
     *     notification_zone: string,
     *     notification_supervisor_user_id: int,
     *     notification_notes?: string|null,
     * }  $data
     */
    public function completeNotificationInformation(
        DisciplinaryCase $case,
        User $planner,
        array $data,
    ): DisciplinaryAgendaMessage {
        if (! $this->canPlanningRegisterNotification($case)) {
            throw new \InvalidArgumentException('Planeación debe publicar la programación en el hilo antes de registrar la notificación.');
        }

        $supervisor = User::query()
            ->whereKey($data['notification_supervisor_user_id'])
            ->where('is_active', true)
            ->role('nivel7')
            ->first();

        if (! $supervisor instanceof User) {
            throw new \InvalidArgumentException('Seleccione un supervisor activo válido.');
        }

        $case->loadMissing('employee');
        app(FieldDisciplinaryScopeService::class)->assertSupervisorCoversCase($supervisor, $case);

        $thread = $case->agendaThread;
        if ($thread === null) {
            throw new \RuntimeException('No existe hilo de coordinación.');
        }

        $payload = [
            'notification_date' => $data['notification_date'],
            'notification_shift' => $data['notification_shift'],
            'notification_zone' => $data['notification_zone'],
            'notification_supervisor_user_id' => $supervisor->id,
            'notification_supervisor_name' => $supervisor->name,
            'notification_notes' => $data['notification_notes'] ?? null,
        ];

        return DB::transaction(function () use ($case, $planner, $thread, $payload, $supervisor) {
            $message = DisciplinaryAgendaMessage::create([
                'thread_id' => $thread->id,
                'user_id' => $planner->id,
                'message_kind' => AgendaMessageKind::DECISION_NOTIFICATION_COORDINATION,
                'body' => 'Información de notificación de decisión registrada por Planeación.',
                'notification_payload' => $payload,
            ]);

            $case->forceFill([
                'decision_notification_completed_at' => now(),
                'decision_notification_message_id' => $message->id,
                'decision_notification_date' => $payload['notification_date'],
                'decision_notification_shift' => $payload['notification_shift'],
                'decision_notification_zone' => $payload['notification_zone'],
                'decision_notification_supervisor_user_id' => $supervisor->id,
                'decision_notification_supervisor_name' => $supervisor->name,
                'decision_notification_notes' => $payload['notification_notes'],
                'decision_notification_supervisor_assigned_at' => now(),
                'decision_notification_supervisor_assigned_by' => $planner->id,
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $planner,
                ActionType::DECISION_NOTIFICACION_COORDINADA,
                'Planeación registró información para notificación de decisión.',
                ['message_id' => $message->id, 'payload' => $payload],
            );

            if ($case->assignedLawyer instanceof User) {
                Notification::send(
                    $case->assignedLawyer,
                    new DisciplinaryDecisionCoordinatedNotification($case->fresh(['employee']), $message),
                );
            }

            return $message;
        });
    }

    public function notifyEvidenceUploadEnabled(DisciplinaryCase $case): void
    {
        $case = $case->fresh(['employee', 'assignedLawyer']);
        $recipients = collect();

        if ($case->decision_notification_supervisor_user_id) {
            $supervisor = User::query()
                ->whereKey($case->decision_notification_supervisor_user_id)
                ->where('is_active', true)
                ->where('read_only', false)
                ->first();
            if ($supervisor instanceof User) {
                $recipients->push($supervisor);
            }
        }

        $operationsDirectors = User::query()
            ->where('is_active', true)
            ->where('read_only', false)
            ->permission('disciplinary.review-inform-all')
            ->get();

        $recipients = $recipients->merge($operationsDirectors)->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new DisciplinaryDecisionEvidenceEnabledNotification($case),
        );
    }

    public function userCanReassignNotificationSupervisor(User $user, DisciplinaryCase $case): bool
    {
        if ($user->read_only) {
            return false;
        }

        if ($this->hasReviewInformAllPermission($user)) {
            return true;
        }

        $case->loadMissing('informeSubmission');

        return $case->informeSubmission
            && (int) $case->informeSubmission->reviewed_by === (int) $user->id;
    }

    private function hasReviewInformAllPermission(User $user): bool
    {
        try {
            return $user->hasPermissionTo('disciplinary.review-inform-all');
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
