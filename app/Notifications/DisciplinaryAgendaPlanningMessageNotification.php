<?php

namespace App\Notifications;

use App\Models\Disciplinary\DisciplinaryAgendaMessage;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Notifications\BroadcastsInAppDatabaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Planeación (o admin con permiso de lado planeación) respondió en el hilo de agenda. */
class DisciplinaryAgendaPlanningMessageNotification extends Notification implements ShouldBroadcastNow
{
    use BroadcastsInAppDatabaseNotification, Queueable;

    public function __construct(
        public DisciplinaryCase $case,
        public DisciplinaryAgendaMessage $message,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->case->loadMissing('personnel');
        $snippet = Str::limit(trim((string) $this->message->body), 140);

        $who = $this->case->personnel
            ? ' · '.$this->case->personnel->first_name.' '.$this->case->personnel->last_name
            : '';

        return [
            'title' => 'Planeación respondió en el hilo de agenda',
            'body' => 'Hay una nueva respuesta en la solicitud de agenda'.$who.'. '.$snippet,
            'action_url' => route('disciplinary.cases.show', $this->case),
            'disciplinary_case_id' => $this->case->getKey(),
            'agenda_message_id' => $this->message->getKey(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
