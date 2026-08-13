<?php

namespace App\Jobs\Licitaciones;

use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use App\Notifications\Licitaciones\SolicitudDocumentacionInvitacionNotification;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendLicitacionInvitacionJob
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $invitadoId,
        public bool $reenvio = false,
    ) {}

    public function handle(): void
    {
        $invitado = LicitacionSolicitudInvitado::query()
            ->with(['solicitud.licitacion', 'solicitud.creador'])
            ->find($this->invitadoId);

        if (! $invitado) {
            return;
        }

        try {
            Notification::route('mail', $invitado->email)
                ->notify(new SolicitudDocumentacionInvitacionNotification($invitado, $this->reenvio));

            $invitado->update(['notificado_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('licitaciones.invitacion_mail_failed', [
                'invitado_id' => $this->invitadoId,
                'email' => $invitado->email,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
