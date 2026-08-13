<?php

namespace App\Notifications\Licitaciones;

use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudDocumentacionInvitacionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LicitacionSolicitudInvitado $invitado,
        public bool $reenvio = false,
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
        $solicitud = $this->invitado->solicitud;
        $licitacion = $solicitud?->licitacion;
        $proceso = $licitacion
            ? trim(($licitacion->numero_proceso ?: '').' '.($licitacion->entidad_contratante ?: ''))
            : null;

        $mail = (new MailMessage)
            ->subject(($this->reenvio ? 'Recordatorio: ' : '').'Documentación requerida · '.$solicitud?->numero_radicado)
            ->greeting($this->invitado->nombre ? 'Hola '.$this->invitado->nombre.',' : 'Hola,')
            ->line('Se le solicita aportar documentación para una solicitud de licitación.')
            ->line('**Solicitud:** '.$solicitud?->numero_radicado.' — '.$solicitud?->nombre);

        if ($proceso) {
            $mail->line('**Proceso:** '.$proceso);
        }

        if ($solicitud?->descripcion) {
            $mail->line('**Detalle:** '.$solicitud->descripcion);
        }

        if ($this->invitado->mensaje) {
            $mail->line('**Indicaciones:** '.$this->invitado->mensaje);
        }

        if ($solicitud?->fecha_limite) {
            $mail->line('**Fecha límite:** '.$solicitud->fecha_limite->format('d/m/Y'));
        }

        return $mail
            ->action('Anexar documentos', $this->invitado->portalUrl())
            ->line('Use el enlace anterior para subir los archivos solicitados. No necesita crear una cuenta.')
            ->salutation('SJ LegalSuite');
    }
}
