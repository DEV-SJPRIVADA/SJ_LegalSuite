<?php

namespace App\Notifications\Licitaciones;

use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use App\Support\Notifications\BroadcastsInAppDatabaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;

/**
 * Aviso interno (campanita) cuando un aportante está en la última hora
 * sin haber subido documentos.
 */
class AportacionDeadlineUrgentStaffNotification extends Notification implements ShouldBroadcastNow
{
    use BroadcastsInAppDatabaseNotification, Queueable;

    public function __construct(
        public LicitacionSolicitudInvitado $invitado,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->invitado->loadMissing('solicitud');
        $solicitud = $this->invitado->solicitud;
        $deadline = $solicitud?->aportacionDeadline();
        $vencido = $deadline !== null && $deadline->isPast();

        return [
            'title' => $vencido
                ? 'Aportación vencida · archivos pendientes'
                : 'Última hora · aportación pendiente',
            'body' => ($this->invitado->nombre ?: $this->invitado->email)
                .' aún no ha subido documentos en '
                .($solicitud?->numero_radicado ?? 'la solicitud')
                .($deadline
                    ? ' · límite '.$deadline->timezone(config('app.timezone'))->format('d/m/Y H:i')
                    : '')
                .'.',
            'action_url' => $solicitud
                ? route('licitaciones.solicitudes.show', $solicitud)
                : null,
            'licitacion_solicitud_id' => $solicitud?->getKey(),
            'licitacion_invitado_id' => $this->invitado->getKey(),
            'urgent' => true,
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
