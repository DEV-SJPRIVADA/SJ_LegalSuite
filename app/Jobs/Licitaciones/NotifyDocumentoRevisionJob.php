<?php

namespace App\Jobs\Licitaciones;

use App\Models\Licitaciones\LicitacionAdjunto;
use App\Notifications\Licitaciones\DocumentoRevisionResultadoNotification;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifyDocumentoRevisionJob
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $adjuntoId,
    ) {}

    public function handle(): void
    {
        $adjunto = LicitacionAdjunto::query()
            ->with(['solicitud.licitacion', 'invitado'])
            ->find($this->adjuntoId);

        if (! $adjunto?->invitado?->email) {
            return;
        }

        try {
            Notification::route('mail', $adjunto->invitado->email)
                ->notify(new DocumentoRevisionResultadoNotification($adjunto));
        } catch (\Throwable $e) {
            Log::error('licitaciones.notify_revision_failed', [
                'adjunto_id' => $this->adjuntoId,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
