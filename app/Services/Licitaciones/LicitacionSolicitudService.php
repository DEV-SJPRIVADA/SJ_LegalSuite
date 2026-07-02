<?php

namespace App\Services\Licitaciones;

use App\Enums\Licitaciones\RequestStatus;
use App\Enums\Licitaciones\RequestType;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LicitacionSolicitudService
{
    public function __construct(
        private readonly LicitacionHistorialService $historial,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): LicitacionSolicitud
    {
        return DB::transaction(function () use ($data, $actor) {
            if (($data['tipo_solicitud'] ?? null) === RequestType::Esporadica->value && empty($data['licitacion_id'])) {
                throw new \InvalidArgumentException('Las solicitudes esporádicas deben estar asociadas a una licitación.');
            }

            $solicitud = LicitacionSolicitud::create([
                ...$data,
                'created_by_id' => $actor->id,
                'fecha_creacion' => $data['fecha_creacion'] ?? now()->toDateString(),
                'estado' => $data['estado'] ?? RequestStatus::Recibido->value,
            ]);

            $this->historial->log($solicitud, $actor, 'solicitud_creada', [
                'solicitud_id' => $solicitud->id,
            ]);

            return $solicitud->fresh(['licitacion', 'usuarioResponsable', 'creador']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LicitacionSolicitud $solicitud, array $data, User $actor, ?string $comentario = null): LicitacionSolicitud
    {
        return DB::transaction(function () use ($solicitud, $data, $actor, $comentario) {
            $estadoAnterior = $solicitud->estado;

            $solicitud->update($data);

            $this->historial->log($solicitud, $actor, 'solicitud_actualizada', array_filter([
                'estado_anterior' => $estadoAnterior?->value,
                'estado_nuevo' => $solicitud->estado?->value,
                'comentario' => $comentario,
            ]));

            return $solicitud->fresh(['licitacion', 'usuarioResponsable', 'creador']);
        });
    }

    public function delete(LicitacionSolicitud $solicitud, User $actor): void
    {
        DB::transaction(function () use ($solicitud, $actor) {
            if ($solicitud->adjuntos()->exists()) {
                throw new \InvalidArgumentException('No se puede eliminar una solicitud con adjuntos.');
            }

            $this->historial->log($solicitud, $actor, 'solicitud_eliminada', [
                'solicitud_id' => $solicitud->id,
            ]);

            $solicitud->delete();
        });
    }
}
