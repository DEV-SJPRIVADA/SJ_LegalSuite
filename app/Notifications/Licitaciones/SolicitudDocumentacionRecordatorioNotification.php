<?php

namespace App\Notifications\Licitaciones;

use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudDocumentacionRecordatorioNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LicitacionSolicitudInvitado $invitado,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $solicitud = $this->invitado->solicitud;
        $deadline = $solicitud?->aportacionDeadline();
        $vencido = $deadline !== null && $deadline->isPast();
        $ultimaHora = ! $vencido
            && $deadline !== null
            && now()->gte($deadline->copy()->subHour());

        $subjectPrefix = match (true) {
            $vencido => 'Vencido: ',
            $ultimaHora => 'URGENTE · última hora: ',
            default => 'Recordatorio: ',
        };

        $mail = (new MailMessage)
            ->subject($subjectPrefix.'documentos pendientes · '.$solicitud?->numero_radicado)
            ->greeting($this->invitado->nombre ? 'Hola '.$this->invitado->nombre.',' : 'Hola,')
            ->line(
                match (true) {
                    $vencido => 'La fecha límite para entregar la documentación ya venció y aún no hemos recibido sus archivos.',
                    $ultimaHora => 'Queda menos de una hora para el cierre. Aún no hemos recibido sus archivos; le enviaremos un recordatorio cada 10 minutos hasta que los cargue.',
                    default => 'Le recordamos que tiene documentación pendiente por enviar a la plataforma.',
                }
            )
            ->line('**Solicitud:** '.$solicitud?->numero_radicado.' — '.$solicitud?->nombre);

        if ($solicitud?->descripcion) {
            $mail->line('**Detalle:** '.$solicitud->descripcion);
        }

        if ($this->invitado->mensaje) {
            $mail->line('**Indicaciones:** '.$this->invitado->mensaje);
        }

        if ($deadline) {
            $mail->line(
                '**Fecha y hora de límite de entrega:** '.$deadline->timezone(config('app.timezone'))->format('d/m/Y H:i')
                .($vencido ? ' (vencido)' : '')
            );
        }

        return $mail
            ->action('Anexar documentos ahora', $this->invitado->portalUrl())
            ->line('Use el enlace para subir los archivos. No necesita crear una cuenta.')
            ->salutation('SJ LegalSuite');
    }
}
