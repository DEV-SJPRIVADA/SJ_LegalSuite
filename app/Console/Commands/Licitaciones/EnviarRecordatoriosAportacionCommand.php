<?php

namespace App\Console\Commands\Licitaciones;

use App\Jobs\Licitaciones\SendLicitacionAportacionRecordatorioJob;
use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use Illuminate\Console\Command;

class EnviarRecordatoriosAportacionCommand extends Command
{
    protected $signature = 'licitaciones:enviar-recordatorios-aportacion
                            {--dry-run : Lista destinatarios sin enviar correo}';

    protected $description = 'Envía recordatorios a aportantes sin documentos (diario desde 48h; cada 10 min en la última hora).';

    public function handle(): int
    {
        $invitados = LicitacionSolicitudInvitado::query()
            ->with(['solicitud.licitacion', 'solicitud.creador'])
            ->whereNotNull('notificado_at')
            ->whereDoesntHave('adjuntos')
            ->whereHas('solicitud', function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('aportacion_limite_at')
                        ->orWhereNotNull('fecha_limite');
                });
            })
            ->orderBy('id')
            ->get()
            ->filter(fn (LicitacionSolicitudInvitado $invitado) => $invitado->needsAportacionReminder());

        if ($invitados->isEmpty()) {
            $this->info('Sin recordatorios pendientes.');

            return self::SUCCESS;
        }

        $this->info('Recordatorios a enviar: '.$invitados->count());

        foreach ($invitados as $invitado) {
            $deadline = $invitado->solicitud?->aportacionDeadlineLabel() ?? '—';
            $this->line("- {$invitado->email} · {$invitado->solicitud?->numero_radicado} · límite {$deadline}");

            if ($this->option('dry-run')) {
                continue;
            }

            SendLicitacionAportacionRecordatorioJob::dispatchSync($invitado->id);
        }

        return self::SUCCESS;
    }
}
