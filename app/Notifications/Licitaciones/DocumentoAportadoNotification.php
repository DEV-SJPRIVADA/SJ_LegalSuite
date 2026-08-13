<?php

namespace App\Notifications\Licitaciones;

use App\Models\Licitaciones\LicitacionAdjunto;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentoAportadoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LicitacionAdjunto $adjunto,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        // Sin broadcast síncrono: evita que el portal se congele si Pusher no responde.
        if ($notifiable instanceof User) {
            return ['mail', 'database'];
        }

        return ['mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->adjunto->loadMissing(['solicitud', 'invitado']);
        $solicitud = $this->adjunto->solicitud;

        return [
            'title' => 'Documento aportado · pendiente de revisión',
            'body' => ($this->adjunto->uploaderLabel()).' subió «'.$this->adjunto->nombre_archivo.'» en la solicitud '.$solicitud?->numero_radicado.'.',
            'action_url' => $solicitud
                ? route('licitaciones.solicitudes.show', $solicitud)
                : null,
            'licitacion_solicitud_id' => $solicitud?->getKey(),
            'licitacion_adjunto_id' => $this->adjunto->getKey(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->adjunto->loadMissing(['solicitud', 'invitado']);
        $solicitud = $this->adjunto->solicitud;
        $name = $notifiable instanceof User ? (string) $notifiable->name : '';
        $greeting = $name !== '' ? 'Hola '.$name.',' : 'Hola,';

        return (new MailMessage)
            ->subject('Documento por revisar · '.$solicitud?->numero_radicado)
            ->greeting($greeting)
            ->line(($this->adjunto->uploaderLabel()).' aportó un documento en la solicitud '.$solicitud?->numero_radicado.'.')
            ->line('**Archivo:** '.$this->adjunto->nombre_archivo)
            ->line('Revise el documento y apruébelo o solicite corrección.')
            ->action('Revisar solicitud', route('licitaciones.solicitudes.show', $solicitud))
            ->salutation('SJ LegalSuite');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
