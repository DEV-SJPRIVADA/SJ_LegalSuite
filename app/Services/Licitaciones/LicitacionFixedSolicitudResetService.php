<?php

namespace App\Services\Licitaciones;

use App\Enums\Licitaciones\RequestStatus;
use App\Enums\Licitaciones\RequestType;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LicitacionFixedSolicitudResetService
{
    public function __construct(
        private readonly LicitacionDocumentService $documents,
        private readonly LicitacionHistorialService $historial,
    ) {}

    /**
     * @return array{processed: int, ids: list<int>}
     */
    public function resetExpired(?User $actor = null): array
    {
        $processed = [];
        $now = now();

        $candidates = LicitacionSolicitud::query()
            ->where('tipo_solicitud', RequestType::Fija->value)
            ->where('estado', RequestStatus::Respondido->value)
            ->whereNotNull('periodicidad')
            ->whereDate('fecha_limite', '<=', $now->toDateString())
            ->get();

        foreach ($candidates as $solicitud) {
            $deadline = $solicitud->fecha_limite?->copy()->endOfDay();
            if ($deadline === null || $deadline->greaterThan($now)) {
                continue;
            }

            DB::transaction(function () use ($solicitud, $actor, &$processed): void {
                foreach ($solicitud->adjuntos()->get() as $adjunto) {
                    $this->documents->delete($adjunto);
                }

                $solicitud->historial()->delete();

                $nuevaFecha = $this->nextFechaLimite($solicitud);

                $solicitud->update([
                    'estado' => RequestStatus::Recibido->value,
                    'fecha_limite' => $nuevaFecha->toDateString(),
                    'archivo_adjunto' => null,
                ]);

                if ($actor !== null) {
                    $this->historial->log($solicitud, $actor, 'solicitud_fija_reiniciada', [
                        'nueva_fecha_limite' => $nuevaFecha->toDateString(),
                    ]);
                }

                $processed[] = $solicitud->id;
            });
        }

        return [
            'processed' => count($processed),
            'ids' => $processed,
        ];
    }

    private function nextFechaLimite(LicitacionSolicitud $solicitud): Carbon
    {
        $base = $solicitud->fecha_limite?->copy() ?? now();

        return match ($solicitud->periodicidad?->value) {
            'quincenal' => $base->addDays(15),
            'mensual' => $base->addDays(30),
            default => $base,
        };
    }
}
