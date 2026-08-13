<?php

namespace App\Services\Licitaciones;

use App\Models\Licitaciones\LicitacionHistorialActividad;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\User;

class LicitacionHistorialService
{
    /**
     * @param  array<string, mixed>|null  $detalles
     */
    public function log(LicitacionSolicitud $solicitud, ?User $actor, string $accion, ?array $detalles = null): LicitacionHistorialActividad
    {
        return LicitacionHistorialActividad::create([
            'solicitud_id' => $solicitud->id,
            'user_id' => $actor?->id,
            'accion' => $accion,
            'detalles' => $detalles,
            'created_at' => now(),
        ]);
    }
}
