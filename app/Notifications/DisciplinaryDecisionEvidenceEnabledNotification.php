<?php

namespace App\Notifications;

use App\Models\Disciplinary\DisciplinaryCase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DisciplinaryDecisionEvidenceEnabledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DisciplinaryCase $case,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $url = $notifiable->hasRole('supervisor')
            ? route('disciplinary.evidences-pending.index')
            : route('disciplinary.cases.show', $this->case);

        return [
            'title' => 'Evidencia de decisión habilitada',
            'body' => "Comunicado de decisión generado para {$this->case->case_number}. Ya puede cargar la evidencia de notificación.",
            'url' => $url,
            'case_id' => $this->case->id,
        ];
    }
}
