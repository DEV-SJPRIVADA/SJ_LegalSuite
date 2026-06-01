<?php

namespace App\Notifications;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DisciplinaryCoordinationStartedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DisciplinaryCase $case,
        public User $lawyer,
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
            'title' => 'Nueva coordinación de citación (FO-GJ-03)',
            'body' => "El abogado {$this->lawyer->name} inició coordinación para el caso {$this->case->case_number}.",
            'url' => route('disciplinary.cases.show', $this->case),
            'case_id' => $this->case->id,
        ];
    }
}
