<?php

namespace App\Notifications;

use App\Models\Disciplinary\DisciplinaryAgendaMessage;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Support\Notifications\BroadcastsInAppDatabaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/** Planeación respondió en el hilo de coordinación citación (FO-GJ-03). */
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
        $this->case->loadMissing('employee');
        $snippet = Str::limit(trim((string) $this->message->body), 140);

        $who = $this->case->employee
            ? ' · '.$this->case->employee->first_name.' '.$this->case->employee->last_name
            : '';

        return [
            'title' => 'Planeación respondió (citación FO-GJ-03)',
            'body' => 'Hay una nueva respuesta en el hilo con planeación'.$who.'. '.$snippet,
            'action_url' => route('disciplinary.cases.show', $this->case),
            'disciplinary_case_id' => $this->case->getKey(),
            'agenda_message_id' => $this->message->getKey(),
            'agenda_thread_id' => $this->message->thread_id,
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
