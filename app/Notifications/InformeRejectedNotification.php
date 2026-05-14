<?php

namespace App\Notifications;

use App\Models\Disciplinary\InformeSubmission;
use App\Support\Notifications\BroadcastsInAppDatabaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;

/** Notifica al usuario que elaboró/envió el informe. */
class InformeRejectedNotification extends Notification implements ShouldBroadcastNow
{
    use BroadcastsInAppDatabaseNotification, Queueable;

    public function __construct(
        public InformeSubmission $submission,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $suffix = '';
        $notes = trim((string) ($this->notes ?? ''));
        if ($notes !== '') {
            $trunc = mb_strlen($notes) > 200 ? mb_substr($notes, 0, 200).'…' : $notes;
            $suffix = ' Motivo: '.$trunc.'.';
        }

        return [
            'title' => 'Informe disciplinario no autorizado',
            'body' => 'El informe FO-GJ-51 enviado fue rechazado en revisión.'.($suffix ?: ' Puede generar uno nuevo cuando corresponda.'),
            'action_url' => route('disciplinary.forms.informe-fo-gj-51'),
            'submission_id' => $this->submission->getKey(),
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
