<?php

namespace App\Notifications\Licitaciones;

use App\Enums\Licitaciones\DocumentRevisionStatus;
use App\Models\Licitaciones\LicitacionAdjunto;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentoRevisionResultadoNotification extends Notification
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->adjunto->loadMissing(['solicitud.licitacion', 'invitado']);
        $solicitud = $this->adjunto->solicitud;
        $aprobado = $this->adjunto->revision_estado === DocumentRevisionStatus::Aprobado;
        $portal = $this->adjunto->invitado?->portalUrl();
        if (! $aprobado && $portal && $this->adjunto->id) {
            $portal .= (str_contains($portal, '?') ? '&' : '?').'corregir='.$this->adjunto->id;
        }

        $mail = (new MailMessage)
            ->subject(($aprobado ? 'Documento aprobado' : 'Documento por corregir').' · '.$solicitud?->numero_radicado)
            ->greeting($this->adjunto->invitado?->nombre
                ? 'Hola '.$this->adjunto->invitado->nombre.','
                : 'Hola,')
            ->line('Se revisó el documento «'.$this->adjunto->nombre_archivo.'» de la solicitud '.$solicitud?->numero_radicado.'.');

        if ($aprobado) {
            $mail->line('**Resultado:** el documento fue aprobado. Gracias por su aporte.');
        } else {
            $mail->line('**Resultado:** se requiere corrección.')
                ->line('**Observación:** '.$this->adjunto->revision_comentario)
                ->line('Por favor cargue una nueva versión del documento.');
        }

        if ($portal) {
            $mail->action($aprobado ? 'Ver portal' : 'Corregir documento', $portal);
        }

        return $mail->salutation('SJ LegalSuite');
    }
}
