<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\AgendaMessageKind;
use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryAgendaMessage;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Support\Disciplinary\FieldDisciplinaryScopeService;
use App\Support\Disciplinary\WorkerLegalPhrasing;
use App\Notifications\DisciplinaryFoGj03EvidenceEnabledNotification;
use App\Notifications\DisciplinaryNotificationCoordinatedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class DisciplinaryCitationNotificationService
{
    public function __construct(
        private readonly DisciplinaryAuditService $audit,
        private readonly FoGj03DraftService $foGj03Drafts,
    ) {}

    /**
     * Planeación puede registrar o actualizar notificación (ingreso, turno, zona, supervisor).
     * Se habilita al iniciar coordinación en Etapa B.
     */
    public function canPlanningRegisterNotification(DisciplinaryCase $case): bool
    {
        return $this->canPlanningManageNotification($case);
    }

    public function canPlanningManageNotification(DisciplinaryCase $case): bool
    {
        if (! $case->hasCoordinationStarted()) {
            return false;
        }

        $case->loadMissing('agendaThread');

        if ($case->agendaThread?->isClosed()) {
            return false;
        }

        return $case->current_status === CaseStatus::CITACION_PROGRAMADA;
    }

    public function canPlanningProposeDiligenceSlots(DisciplinaryCase $case): bool
    {
        return $this->canPlanningManageNotification($case)
            && $this->hasNotificationInformationCompleted($case);
    }

    public function hasPendingNotificationRequest(DisciplinaryCase $case): bool
    {
        if ($this->hasNotificationInformationCompleted($case)) {
            return false;
        }

        if ($case->notification_requested_at !== null) {
            return true;
        }

        return $this->canPlanningManageNotification($case);
    }

    public function hasNotificationInformationCompleted(DisciplinaryCase $case): bool
    {
        return $case->notification_information_completed_at !== null
            && $case->notification_supervisor_user_id !== null;
    }

    /** @return Collection<string, bool> */
    public function foGj03GenerationChecklist(DisciplinaryCase $case): Collection
    {
        $case->loadMissing('employee', 'assignedLawyer');

        return collect([
            'definitive_date' => $case->citation_confirmed_date !== null,
            'notification_completed' => $case->notification_information_completed_at !== null,
            'notification_shift' => filled($case->notification_shift),
            'notification_zone' => filled($case->notification_zone),
            'notification_supervisor' => $case->notification_supervisor_user_id !== null,
            'employee_gender' => WorkerLegalPhrasing::fromEmployee($case->employee)->hasDefiniteGender(),
            'fo_gj_03_draft' => $this->foGj03Drafts->hasDraftCompleted($case),
            'lawyer_signature' => $case->assignedLawyer?->hasSignature() ?? false,
        ]);
    }

    /** @return list<string> */
    public function missingFoGj03GenerationRequirements(DisciplinaryCase $case): array
    {
        $missing = [];
        $checklist = $this->foGj03GenerationChecklist($case);

        if (! $checklist['definitive_date']) {
            $missing[] = 'Fecha de diligencia confirmada';
        }
        if (! $checklist['notification_completed']) {
            $missing[] = 'Información de notificación completada por Planeación';
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
        if (! $checklist['employee_gender']) {
            $missing[] = 'Género del trabajador en catálogo de empleados';
        }
        if (! $checklist['fo_gj_03_draft']) {
            $missing[] = 'Diligenciamiento del FO-GJ-03';
        }
        if (! $checklist['lawyer_signature']) {
            $missing[] = 'Firma digital del abogado en Mi perfil';
        }

        return $missing;
    }

    public function assertCanGenerateFoGj03(DisciplinaryCase $case): void
    {
        $missing = $this->missingFoGj03GenerationRequirements($case);
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'fo_gj_03' => 'No es posible generar FO-GJ-03. Falta: '.implode(', ', $missing),
            ]);
        }
    }

    public function canGenerateFoGj03(DisciplinaryCase $case): bool
    {
        return $this->missingFoGj03GenerationRequirements($case) === [];
    }

    /** @return array<string, string> */
    public static function foGj03GenerationLabels(): array
    {
        return [
            'definitive_date' => 'Fecha de diligencia confirmada',
            'notification_completed' => 'Información de notificación completada',
            'notification_shift' => 'Turno del trabajador',
            'notification_zone' => 'Zona',
            'notification_supervisor' => 'Supervisor asignado',
            'employee_gender' => 'Género del trabajador (Masculino o Femenino)',
            'fo_gj_03_draft' => 'FO-GJ-03 diligenciado',
            'lawyer_signature' => 'Firma digital en Mi perfil',
        ];
    }

    public function requestNotificationInformation(DisciplinaryCase $case, User $lawyer): DisciplinaryAgendaMessage
    {
        if ($this->canPlanningManageNotification($case)) {
            throw new \InvalidArgumentException('Planeación ya puede registrar la notificación desde Coordinaciones.');
        }

        if ($case->notification_requested_at !== null) {
            throw new \InvalidArgumentException('Ya existe una solicitud de notificación pendiente de respuesta.');
        }

        if ($this->hasNotificationInformationCompleted($case)) {
            throw new \InvalidArgumentException('La información de notificación ya fue registrada.');
        }

        $thread = $case->agendaThread;
        if ($thread === null) {
            throw new \RuntimeException('Inicie la coordinación con planeación antes de solicitar notificación.');
        }

        return DB::transaction(function () use ($case, $lawyer, $thread) {
            $message = DisciplinaryAgendaMessage::create([
                'thread_id' => $thread->id,
                'user_id' => $lawyer->id,
                'message_kind' => AgendaMessageKind::LAWYER_NOTIFICATION_REQUEST,
                'body' => 'Solicitud de información para notificación física del trabajador (fecha de ingreso, turno, zona y supervisor).',
            ]);

            $case->forceFill([
                'notification_requested_at' => now(),
                'notification_requested_by' => $lawyer->id,
            ])->save();

            return $message;
        });
    }

    /**
     * @param  array{notification_date: string, notification_shift: string, notification_zone: string, notification_supervisor_user_id: int, notification_notes?: string|null}  $data
     */
    public function completeNotificationInformation(
        DisciplinaryCase $case,
        User $planner,
        array $data,
    ): DisciplinaryAgendaMessage {
        if (! $this->canPlanningManageNotification($case)) {
            throw new \InvalidArgumentException('La coordinación no está disponible para registrar notificación.');
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
            $isUpdate = $this->hasNotificationInformationCompleted($case);

            $message = DisciplinaryAgendaMessage::create([
                'thread_id' => $thread->id,
                'user_id' => $planner->id,
                'message_kind' => AgendaMessageKind::NOTIFICATION_COORDINATION,
                'body' => $isUpdate
                    ? 'Información de notificación física actualizada por Planeación.'
                    : 'Información de notificación física registrada por Planeación.',
                'notification_payload' => $payload,
            ]);

            $case->forceFill([
                'notification_information_completed_at' => now(),
                'notification_information_message_id' => $message->id,
                'notification_date' => $payload['notification_date'],
                'notification_shift' => $payload['notification_shift'],
                'notification_zone' => $payload['notification_zone'],
                'notification_supervisor_user_id' => $supervisor->id,
                'notification_supervisor_name' => $supervisor->name,
                'notification_notes' => $payload['notification_notes'],
                'notification_supervisor_assigned_at' => now(),
                'notification_supervisor_assigned_by' => $planner->id,
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $planner,
                ActionType::NOTIFICACION_COORDINADA,
                $isUpdate
                    ? 'Planeación actualizó información para notificación física.'
                    : 'Planeación registró información para notificación física.',
                ['message_id' => $message->id, 'payload' => $payload, 'updated' => $isUpdate],
            );

            $this->audit->logCase(
                $case->fresh(),
                $planner,
                ActionType::SUPERVISOR_NOTIFICADOR_ASIGNADO,
                'Supervisor asignado para notificación física.',
                [
                    'supervisor_user_id' => $supervisor->id,
                    'supervisor_name' => $supervisor->name,
                    'zone' => $payload['notification_zone'],
                ],
            );

            if ($case->assignedLawyer instanceof User) {
                Notification::send(
                    $case->assignedLawyer,
                    new DisciplinaryNotificationCoordinatedNotification($case->fresh(['employee']), $message),
                );
            }

            return $message;
        });
    }

    public function reassignNotificationSupervisor(
        DisciplinaryCase $case,
        User $actor,
        int $newSupervisorUserId,
        string $reason,
    ): DisciplinaryCase {
        if (! $this->hasNotificationInformationCompleted($case)) {
            throw new \InvalidArgumentException('Aún no hay supervisor de notificación asignado.');
        }

        $newSupervisor = User::query()
            ->whereKey($newSupervisorUserId)
            ->where('is_active', true)
            ->role('nivel7')
            ->first();

        if (! $newSupervisor instanceof User) {
            throw new \InvalidArgumentException('Seleccione un supervisor activo válido.');
        }

        $case->loadMissing('employee');
        app(FieldDisciplinaryScopeService::class)->assertSupervisorCoversCase($newSupervisor, $case);

        $previousId = (int) $case->notification_supervisor_user_id;
        $previousName = (string) $case->notification_supervisor_name;
        $reason = trim($reason);

        if ($reason === '') {
            throw new \InvalidArgumentException('Indique el motivo de la reasignación.');
        }

        if ($previousId === (int) $newSupervisor->id) {
            throw new \InvalidArgumentException('Seleccione un supervisor distinto al actual.');
        }

        return DB::transaction(function () use ($case, $actor, $newSupervisor, $previousId, $previousName, $reason) {
            $case->forceFill([
                'notification_supervisor_user_id' => $newSupervisor->id,
                'notification_supervisor_name' => $newSupervisor->name,
                'notification_supervisor_assigned_at' => now(),
                'notification_supervisor_assigned_by' => $actor->id,
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::SUPERVISOR_NOTIFICADOR_REASIGNADO,
                'Supervisor de notificación reasignado.',
                [
                    'previous_supervisor_user_id' => $previousId,
                    'previous_supervisor_name' => $previousName,
                    'new_supervisor_user_id' => $newSupervisor->id,
                    'new_supervisor_name' => $newSupervisor->name,
                    'reason' => $reason,
                ],
            );

            return $case->fresh();
        });
    }

    public function notifyEvidenceUploadEnabled(DisciplinaryCase $case): void
    {
        $case = $case->fresh(['employee', 'assignedLawyer', 'informeSubmission']);
        $recipients = collect();

        if ($case->notification_supervisor_user_id) {
            $supervisor = User::query()
                ->whereKey($case->notification_supervisor_user_id)
                ->where('is_active', true)
                ->where('read_only', false)
                ->first();
            if ($supervisor instanceof User) {
                $recipients->push($supervisor);
            }
        }

        $reviewerId = $case->informeSubmission?->reviewed_by;
        if ($reviewerId) {
            $reviewer = User::query()->whereKey($reviewerId)->where('is_active', true)->where('read_only', false)->first();
            if ($reviewer instanceof User) {
                $recipients->push($reviewer);
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
            new DisciplinaryFoGj03EvidenceEnabledNotification($case),
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
