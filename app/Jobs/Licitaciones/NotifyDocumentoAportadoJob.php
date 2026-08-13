<?php

namespace App\Jobs\Licitaciones;

use App\Models\Licitaciones\LicitacionAdjunto;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Services\Licitaciones\LicitacionDocumentService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Notificación de documento aportado (se ejecuta tras enviar la respuesta HTTP).
 */
class NotifyDocumentoAportadoJob
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $solicitudId,
        public int $adjuntoId,
    ) {}

    public function handle(LicitacionDocumentService $documents): void
    {
        $solicitud = LicitacionSolicitud::query()
            ->with(['creador', 'usuarioResponsable'])
            ->find($this->solicitudId);

        $adjunto = LicitacionAdjunto::query()
            ->with(['solicitud', 'invitado'])
            ->find($this->adjuntoId);

        if (! $solicitud || ! $adjunto) {
            return;
        }

        try {
            $documents->notifyDocumentoAportado($solicitud, $adjunto);
        } catch (\Throwable $e) {
            Log::error('licitaciones.notify_documento_aportado_failed', [
                'solicitud_id' => $this->solicitudId,
                'adjunto_id' => $this->adjuntoId,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
