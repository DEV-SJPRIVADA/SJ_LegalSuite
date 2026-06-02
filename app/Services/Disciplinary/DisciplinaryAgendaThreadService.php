<?php

namespace App\Services\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\AgendaMessageKind;
use App\Enums\Disciplinary\StageType;
use App\Events\Disciplinary\AgendaThreadMessagePosted;
use App\Models\Disciplinary\DisciplinaryAgendaAttachment;
use App\Models\Disciplinary\DisciplinaryAgendaMessage;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Notifications\DisciplinaryAgendaLawyerMessageNotification;
use App\Notifications\DisciplinaryAgendaPlanningMessageNotification;
use App\Notifications\DisciplinaryCoordinationStartedNotification;
use App\Support\Broadcasting\PusherBroadcasting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class DisciplinaryAgendaThreadService
{
    private const AGENDA_ATTACHMENT_DIR = 'disciplinary/agenda-attachments';

    public function __construct(
        private readonly DisciplinaryAuditService $audit,
    ) {}

    public function userIsPlanningSide(User $user, DisciplinaryCase $case): bool
    {
        if ($user->read_only) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('planeacion');
    }

    public function userCanCloseCoordination(User $user, DisciplinaryCase $case): bool
    {
        if ($user->read_only) {
            return false;
        }

        if ((int) $case->assigned_lawyer_id === (int) $user->id) {
            return true;
        }

        return $user->hasRole('admin') || $user->hasPermissionTo('disciplinary.assign');
    }

    public function userIsCaseLawyer(User $user, DisciplinaryCase $case): bool
    {
        return ! $user->read_only && (int) $case->assigned_lawyer_id === (int) $user->id;
    }

    public function canUseAgendaThread(DisciplinaryCase $case): bool
    {
        return $case->allowsAgendaThread();
    }

    public function startCoordination(DisciplinaryCase $case, User $lawyer): DisciplinaryCase
    {
        if (! $this->userIsCaseLawyer($lawyer, $case)) {
            throw new \InvalidArgumentException('Sólo el abogado titular puede iniciar la coordinación.');
        }

        if (! $case->canStartCoordination()) {
            throw new \RuntimeException('La coordinación ya fue iniciada o el caso no está en etapa de citación.');
        }

        return DB::transaction(function () use ($case, $lawyer) {
            $now = now();

            DisciplinaryAgendaThread::create([
                'disciplinary_case_id' => $case->id,
                'organizational_area_id' => null,
                'opened_by' => $lawyer->id,
                'coordination_started_at' => $now,
                'coordination_status' => 'open',
            ]);

            $case->forceFill(['coordination_started_at' => $now])->save();

            $this->audit->logCase(
                $case->fresh(),
                $lawyer,
                ActionType::COORDINACION_INICIADA,
                'Coordinación de citación (FO-GJ-03) iniciada con planeación.',
            );

            $recipients = User::query()
                ->where('is_active', true)
                ->where('read_only', false)
                ->role('planeacion')
                ->get();

            if ($recipients->isNotEmpty()) {
                Notification::send(
                    $recipients,
                    new DisciplinaryCoordinationStartedNotification($case->fresh(['employee', 'agendaThread']), $lawyer),
                );
            }

            $caseKey = (int) $case->getKey();
            DB::afterCommit(fn () => $this->broadcastCaseAgendaIfEnabled($caseKey));

            return $case->fresh(['agendaThread']);
        });
    }

    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function postLawyerMessage(
        DisciplinaryCase $case,
        User $lawyer,
        string $body,
        array $attachments = [],
    ): DisciplinaryAgendaMessage {
        if (! $this->userIsCaseLawyer($lawyer, $case)) {
            throw new \InvalidArgumentException('Sólo el abogado asignado puede escribir en este hilo como titular.');
        }

        if (! $this->canUseAgendaThread($case)) {
            throw new \RuntimeException('Inicie la coordinación antes de enviar mensajes a planeación.');
        }

        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Escriba el contenido del mensaje.');
        }

        return DB::transaction(function () use ($case, $lawyer, $body, $attachments) {
            $thread = $case->agendaThread;
            if ($thread === null) {
                throw new \RuntimeException('No existe hilo de coordinación.');
            }

            $message = DisciplinaryAgendaMessage::create([
                'thread_id' => $thread->id,
                'user_id' => $lawyer->id,
                'message_kind' => AgendaMessageKind::LAWYER_REQUEST,
                'body' => $body,
            ]);

            foreach ($attachments as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $this->storeAttachment($message, $file);
                }
            }

            $this->notifyPlanningUsersOfLawyerMessage(
                $case->fresh(['employee']),
                $thread,
                $message,
                $lawyer,
            );

            $caseKey = (int) $case->getKey();
            DB::afterCommit(fn () => $this->broadcastCaseAgendaIfEnabled($caseKey));

            return $message;
        });
    }

    /**
     * Solicitud formal de programación de fechas de diligencia (Etapa B.1).
     */
    public function requestDiligenceDateProgramming(
        DisciplinaryCase $case,
        User $lawyer,
        ?string $additionalNotes = null,
    ): DisciplinaryAgendaMessage {
        if ($case->hasLawyerDiligenceDateRequest()) {
            throw new \InvalidArgumentException('Ya existe una solicitud de fechas pendiente o registrada en este hilo.');
        }

        $body = 'Solicitud de programación de fechas para la diligencia disciplinaria (citación FO-GJ-03). '
            .'Planeación debe proponer opciones de fecha y hora disponibles para la diligencia.';

        $notes = trim((string) $additionalNotes);
        if ($notes !== '') {
            $body .= "\n\nObservaciones del abogado:\n".$notes;
        }

        return $this->postLawyerMessage($case, $lawyer, $body, []);
    }

    /**
     * @param  list<array{date: string, time?: string|null, notes?: string|null}>  $proposedSlots
     * @param  list<UploadedFile>  $attachments
     */
    public function postPlanningMessage(
        DisciplinaryCase $case,
        User $actor,
        string $body,
        array $proposedSlots = [],
        array $attachments = [],
    ): DisciplinaryAgendaMessage {
        if (! $this->userIsPlanningSide($actor, $case)) {
            throw new \InvalidArgumentException('No tiene permiso para responder como planeación en este caso.');
        }

        if (! $this->canUseAgendaThread($case)) {
            throw new \RuntimeException('La coordinación no está activa para este expediente.');
        }

        $thread = $case->agendaThread;
        if ($thread === null) {
            throw new \RuntimeException('Aún no se ha iniciado la coordinación.');
        }
        if (! $thread->isOpen()) {
            throw new \RuntimeException('La coordinación ya está cerrada.');
        }

        $body = trim($body);
        $slots = $this->normalizeSlots($proposedSlots);

        if ($body === '' && $slots === [] && $attachments === []) {
            throw new \InvalidArgumentException('Escriba un mensaje, proponga fechas o adjunte archivos.');
        }

        return DB::transaction(function () use ($case, $actor, $body, $slots, $attachments, $thread) {
            $message = DisciplinaryAgendaMessage::create([
                'thread_id' => $thread->id,
                'user_id' => $actor->id,
                'message_kind' => AgendaMessageKind::PLANNING_RESPONSE,
                'body' => $body !== '' ? $body : 'Propuesta de fechas de citación.',
                'proposed_slots' => $slots !== [] ? $slots : null,
            ]);

            foreach ($attachments as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $this->storeAttachment($message, $file);
                }
            }

            if (! $thread->hasPlanningReply()) {
                $thread->forceFill(['planning_replied_at' => now()])->save();
            }

            $message = $message->fresh(['attachments']);

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::PLANEACION_RESPONDIO,
                'Planeación respondió en el hilo de coordinación de citación.',
                ['message_id' => $message->id, 'slots' => $slots],
            );

            $this->notifyLawyerOfPlanningMessage($case->fresh(['employee']), $message, $actor);

            $caseKey = (int) $case->getKey();
            DB::afterCommit(fn () => $this->broadcastCaseAgendaIfEnabled($caseKey));

            return $message;
        });
    }

    public function confirmCitationSlot(
        DisciplinaryCase $case,
        User $lawyer,
        int $messageId,
        int $slotIndex,
    ): DisciplinaryCase {
        if (! $this->userIsCaseLawyer($lawyer, $case)) {
            throw new \InvalidArgumentException('Sólo el abogado titular puede confirmar la fecha.');
        }

        $message = DisciplinaryAgendaMessage::query()
            ->whereKey($messageId)
            ->whereHas('thread', fn ($q) => $q->where('disciplinary_case_id', $case->id))
            ->firstOrFail();

        $slots = $message->normalizedProposedSlots();
        if (! isset($slots[$slotIndex])) {
            throw new \InvalidArgumentException('Propuesta de fecha no válida.');
        }

        $slot = $slots[$slotIndex];
        $date = $slot['date'] ?? null;
        if (! is_string($date) || $date === '') {
            throw new \InvalidArgumentException('La propuesta no incluye fecha válida.');
        }

        return DB::transaction(function () use ($case, $lawyer, $message, $slot, $date, $slotIndex) {
            $case->forceFill([
                'citation_confirmed_date' => $date,
                'citation_confirmed_time' => isset($slot['time']) && $slot['time'] !== '' ? $slot['time'] : null,
                'citation_confirmed_by' => $lawyer->id,
                'citation_selected_message_id' => $message->id,
            ])->save();

            $stage = $case->stages()
                ->where('stage_type', StageType::CITACION)
                ->orderByDesc('sequence')
                ->first();

            if ($stage) {
                $scheduled = Carbon::parse($date.' '.($slot['time'] ?? '09:00'));
                $stage->forceFill([
                    'scheduled_at' => $scheduled,
                    'deadline_at' => $scheduled->copy()->subDay(),
                ])->save();
            }

            $this->audit->logCase(
                $case->fresh(),
                $lawyer,
                ActionType::FECHA_CITACION_SELECCIONADA,
                'Fecha definitiva de citación seleccionada.',
                ['message_id' => $message->id, 'slot_index' => $slotIndex, 'slot' => $slot],
            );

            return $case->fresh();
        });
    }

    public function closeCoordination(DisciplinaryCase $case, User $actor): DisciplinaryCase
    {
        if (! $this->userCanCloseCoordination($actor, $case)) {
            throw new \InvalidArgumentException('No tiene permisos para cerrar esta coordinación.');
        }

        $thread = $case->agendaThread;
        if (! $thread instanceof DisciplinaryAgendaThread) {
            throw new \RuntimeException('No existe una coordinación activa para este expediente.');
        }
        if ($thread->isClosed()) {
            throw new \RuntimeException('La coordinación ya se encuentra cerrada.');
        }

        return DB::transaction(function () use ($case, $actor, $thread) {
            $thread->forceFill([
                'coordination_status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $actor->id,
            ])->save();

            $this->audit->logCase(
                $case->fresh(),
                $actor,
                ActionType::COORDINACION_CERRADA,
                'Coordinación de citación cerrada.',
                ['agenda_thread_id' => $thread->id],
            );

            $caseKey = (int) $case->getKey();
            DB::afterCommit(fn () => $this->broadcastCaseAgendaIfEnabled($caseKey));

            return $case->fresh(['agendaThread']);
        });
    }

    /**
     * @param  list<array{date?: mixed, time?: mixed, notes?: mixed}>  $raw
     * @return list<array{date: string, time?: string|null, notes?: string|null}>
     */
    private function normalizeSlots(array $raw): array
    {
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $date = trim((string) ($row['date'] ?? ''));
            if ($date === '') {
                continue;
            }
            $out[] = [
                'date' => $date,
                'time' => isset($row['time']) && $row['time'] !== '' ? substr((string) $row['time'], 0, 5) : null,
                'notes' => isset($row['notes']) && $row['notes'] !== '' ? (string) $row['notes'] : null,
            ];
        }

        return $out;
    }

    private function notifyPlanningUsersOfLawyerMessage(
        DisciplinaryCase $case,
        DisciplinaryAgendaThread $thread,
        DisciplinaryAgendaMessage $message,
        User $lawyer,
    ): void {
        $recipients = User::query()
            ->where('is_active', true)
            ->where('read_only', false)
            ->role('planeacion')
            ->whereKeyNot($lawyer->id)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new DisciplinaryAgendaLawyerMessageNotification($case, $message)
        );
    }

    private function notifyLawyerOfPlanningMessage(
        DisciplinaryCase $case,
        DisciplinaryAgendaMessage $message,
        User $actor,
    ): void {
        $lawyerId = $case->assigned_lawyer_id;
        if (! $lawyerId || (int) $lawyerId === (int) $actor->id) {
            return;
        }

        $lawyerUser = User::query()
            ->whereKey($lawyerId)
            ->where('is_active', true)
            ->where('read_only', false)
            ->first();

        if (! $lawyerUser instanceof User) {
            return;
        }

        Notification::send(
            $lawyerUser,
            new DisciplinaryAgendaPlanningMessageNotification($case, $message)
        );
    }

    private function broadcastCaseAgendaIfEnabled(int $disciplinaryCaseId): void
    {
        if (! PusherBroadcasting::isEnabled()) {
            return;
        }

        try {
            broadcast(new AgendaThreadMessagePosted($disciplinaryCaseId));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function storeAttachment(DisciplinaryAgendaMessage $message, UploadedFile $file): void
    {
        $dir = self::AGENDA_ATTACHMENT_DIR.'/'.$message->thread_id;
        $path = Storage::disk('local')->putFile($dir, $file);

        $clientMime = $file->getClientMimeType();
        $guessedMime = $file->getMimeType();

        DisciplinaryAgendaAttachment::create([
            'agenda_message_id' => $message->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => ($clientMime && $clientMime !== 'application/octet-stream')
                ? $clientMime
                : $guessedMime,
            'size_bytes' => $file->getSize(),
        ]);
    }
}
