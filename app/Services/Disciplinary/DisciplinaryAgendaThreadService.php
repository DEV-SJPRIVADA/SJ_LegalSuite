<?php

namespace App\Services\Disciplinary;

use App\Events\Disciplinary\AgendaThreadMessagePosted;
use App\Models\Disciplinary\DisciplinaryAgendaAttachment;
use App\Models\Disciplinary\DisciplinaryAgendaMessage;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\OrganizationalArea;
use App\Models\User;
use App\Notifications\DisciplinaryAgendaLawyerMessageNotification;
use App\Notifications\DisciplinaryAgendaPlanningMessageNotification;
use App\Support\Broadcasting\PusherBroadcasting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class DisciplinaryAgendaThreadService
{
    private const AGENDA_ATTACHMENT_DIR = 'disciplinary/agenda-attachments';

    /** Roles que pueden intervenir como “lado planeación” en el hilo (sin ser el abogado titular). */
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

    public function userIsCaseLawyer(User $user, DisciplinaryCase $case): bool
    {
        return ! $user->read_only && $case->assigned_lawyer_id === $user->id;
    }

    public function canUseAgendaThread(DisciplinaryCase $case): bool
    {
        return $case->allowsAgendaThread();
    }

    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function postLawyerMessage(
        DisciplinaryCase $case,
        User $lawyer,
        string $body,
        ?int $organizationalAreaId,
        array $attachments = [],
    ): DisciplinaryAgendaMessage {
        if (! $this->userIsCaseLawyer($lawyer, $case)) {
            throw new \InvalidArgumentException('Sólo el abogado asignado puede escribir en este hilo como titular.');
        }

        if (! $this->canUseAgendaThread($case)) {
            throw new \RuntimeException('El hilo con planeación sólo está disponible en citación o reprogramación, con abogado titular asignado.');
        }

        if ($attachments !== []) {
            throw new \InvalidArgumentException('El abogado no puede adjuntar archivos en la solicitud de agenda.');
        }

        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Escriba el contenido del mensaje.');
        }

        return DB::transaction(function () use ($case, $lawyer, $body, $organizationalAreaId) {
            $thread = $case->agendaThread;

            if ($thread === null) {
                if ($organizationalAreaId === null) {
                    throw new \InvalidArgumentException('Seleccione el área de planeación para la primera solicitud.');
                }

                if (! OrganizationalArea::query()->whereKey($organizationalAreaId)->where('is_active', true)->exists()) {
                    throw new \InvalidArgumentException('Área de planeación no válida.');
                }

                $thread = DisciplinaryAgendaThread::create([
                    'disciplinary_case_id' => $case->id,
                    'organizational_area_id' => $organizationalAreaId,
                    'opened_by' => $lawyer->id,
                ]);
            }

            $message = DisciplinaryAgendaMessage::create([
                'thread_id' => $thread->id,
                'user_id' => $lawyer->id,
                'body' => $body,
            ]);

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
     * @param  list<UploadedFile>  $attachments
     */
    public function postPlanningMessage(
        DisciplinaryCase $case,
        User $actor,
        string $body,
        array $attachments = [],
    ): DisciplinaryAgendaMessage {
        if (! $this->userIsPlanningSide($actor, $case)) {
            throw new \InvalidArgumentException('No tiene permiso para responder como planeación en este caso.');
        }

        if (! $this->canUseAgendaThread($case)) {
            throw new \RuntimeException('El hilo con planeación sólo está disponible en citación o reprogramación, con abogado titular asignado.');
        }

        $thread = $case->agendaThread;
        if ($thread === null) {
            throw new \RuntimeException('Aún no hay solicitud del abogado.');
        }

        $body = trim($body);
        if ($body === '' && $attachments === []) {
            throw new \InvalidArgumentException('Escriba un mensaje o adjunte al menos un archivo.');
        }

        return DB::transaction(function () use ($case, $actor, $body, $attachments, $thread) {
            $message = DisciplinaryAgendaMessage::create([
                'thread_id' => $thread->id,
                'user_id' => $actor->id,
                'body' => $body !== '' ? $body : '(Adjuntos)',
            ]);

            foreach ($attachments as $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    continue;
                }
                $this->storeAttachment($message, $file);
            }

            if (! $thread->hasPlanningReply()) {
                $thread->forceFill(['planning_replied_at' => now()])->save();
            }

            $message = $message->fresh(['attachments']);

            $this->notifyLawyerOfPlanningMessage($case->fresh(['employee']), $message, $actor);

            $caseKey = (int) $case->getKey();
            DB::afterCommit(fn () => $this->broadcastCaseAgendaIfEnabled($caseKey));

            return $message;
        });
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
            ->where('organizational_area_id', $thread->organizational_area_id)
            ->whereKeyNot($lawyer->id)
            ->get();

        if ($recipients->isEmpty()) {
            $recipients = User::query()
                ->where('is_active', true)
                ->where('read_only', false)
                ->role('planeacion')
                ->whereKeyNot($lawyer->id)
                ->get();
        }

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
