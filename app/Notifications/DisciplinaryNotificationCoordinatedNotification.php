<?php

namespace App\Notifications;

use App\Models\Disciplinary\DisciplinaryAgendaMessage;
use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DisciplinaryNotificationCoordinatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DisciplinaryCase $case,
        public DisciplinaryAgendaMessage $message,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Notificación física coordinada',
            'body' => "Planeación registró la información de notificación para el caso {$this->case->case_number}.",
            'url' => route('disciplinary.cases.show', $this->case),
            'case_id' => $this->case->id,
            'message_id' => $this->message->id,
        ];
    }
}
