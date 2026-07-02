<?php

namespace App\Services\Licitaciones;

use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\User;
use Illuminate\Support\Carbon;

class LicitacionDashboardService
{
    public function stats(?User $actor = null): array
    {
        $licitacionesQuery = Licitacion::query();
        $solicitudesQuery = LicitacionSolicitud::query()->when($actor, fn ($q) => $q->forActor($actor));

        return [
            'licitaciones_total' => (clone $licitacionesQuery)->count(),
            'solicitudes_total' => (clone $solicitudesQuery)->count(),
            'solicitudes_pendientes' => (clone $solicitudesQuery)->whereIn('estado', ['recibido', 'en_tramite'])->count(),
            'solicitudes_vencidas' => (clone $solicitudesQuery)->where('estado', '!=', 'respondido')
                ->whereDate('fecha_limite', '<', now()->toDateString())->count(),
        ];
    }

    public function recentLicitaciones(int $limit = 5): array
    {
        return Licitacion::query()
            ->with('responsablePrincipal:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function recentSolicitudes(?User $actor = null, int $limit = 5): array
    {
        return LicitacionSolicitud::query()
            ->when($actor, fn ($q) => $q->forActor($actor))
            ->with(['licitacion:id,numero_proceso,entidad_contratante', 'usuarioResponsable:id,name'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function upcomingExpiries(?User $actor = null, int $limit = 5): array
    {
        return LicitacionSolicitud::query()
            ->when($actor, fn ($q) => $q->forActor($actor))
            ->whereDate('fecha_limite', '>=', now()->toDateString())
            ->orderBy('fecha_limite')
            ->limit($limit)
            ->get(['id', 'numero_radicado', 'nombre', 'fecha_limite', 'estado'])
            ->all();
    }
}
