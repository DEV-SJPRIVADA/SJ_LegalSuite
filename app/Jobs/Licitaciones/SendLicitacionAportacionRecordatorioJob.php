<?php

namespace App\Jobs\Licitaciones;

use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use App\Models\User;
use App\Notifications\Licitaciones\AportacionDeadlineUrgentStaffNotification;
use App\Notifications\Licitaciones\SolicitudDocumentacionRecordatorioNotification;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendLicitacionAportacionRecordatorioJob
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $invitadoId,
    ) {}

    public function handle(): void
    {
        $invitado = LicitacionSolicitudInvitado::query()
            ->with([
                'solicitud.licitacion.responsablePrincipal',
                'solicitud.creador',
                'solicitud.usuarioResponsable',
            ])
            ->find($this->invitadoId);

        if (! $invitado || ! $invitado->needsAportacionReminder()) {
            return;
        }

        try {
            Notification::route('mail', $invitado->email)
                ->notify(new SolicitudDocumentacionRecordatorioNotification($invitado));

            if ($invitado->isInFinalHourWindow()) {
                $deadline = $invitado->solicitud?->aportacionDeadline();
                $windowStart = $deadline?->copy()->subHour();
                $firstInFinalHour = $invitado->ultimo_recordatorio_at === null
                    || ($windowStart !== null && $invitado->ultimo_recordatorio_at->lt($windowStart));

                // Campanita interna solo al entrar en la última hora (el correo al aportante sigue cada 10 min).
                if ($firstInFinalHour) {
                    $this->notifyStaff($invitado);
                }
            }

            $invitado->update(['ultimo_recordatorio_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('licitaciones.aportacion_recordatorio_failed', [
                'invitado_id' => $this->invitadoId,
                'email' => $invitado->email,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }

    private function notifyStaff(LicitacionSolicitudInvitado $invitado): void
    {
        $solicitud = $invitado->solicitud;
        if (! $solicitud) {
            return;
        }

        $recipients = collect([
            $solicitud->creador,
            $solicitud->usuarioResponsable,
            $solicitud->licitacion?->responsablePrincipal,
        ])
            ->filter(fn ($user) => $user instanceof User)
            ->unique(fn (User $user) => $user->getKey())
            ->values();

        foreach ($recipients as $user) {
            $user->notify(new AportacionDeadlineUrgentStaffNotification($invitado));
        }
    }
}
