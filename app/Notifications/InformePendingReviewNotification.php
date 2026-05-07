<?php

namespace App\Notifications;

use App\Models\Disciplinary\InformeSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Quienes tienen permiso de revisión de informes FO-GJ-51 (salvo exclusión explícita del remitente).
 */
class InformePendingReviewNotification extends Notification
{
    use Queueable;

    public function __construct(
        public InformeSubmission $submission,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->submission->loadMissing('personnel');

        return [
            'title' => 'Informe pendiente de revisión',
            'body' => 'Se envió un FO-GJ-51 a revisión.'.($this->submission->personnel
                ? ' Disciplinado: '.$this->submission->personnel->first_name.' '.$this->submission->personnel->last_name.'.'
                : ''),
            'action_url' => route('disciplinary.informes-pendientes.index'),
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
