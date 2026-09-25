<?php

namespace App\Services\Licitaciones;

use App\Enums\Licitaciones\RequestStatus;
use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\Licitaciones\LicitacionSolicitudInvitado;
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

    public function upcomingExpiries(?User $actor = null, int $limit = 8): array
    {
        return LicitacionSolicitud::query()
            ->when($actor, fn ($q) => $q->forActor($actor))
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('aportacion_limite_at')
                        ->where('aportacion_limite_at', '>=', now());
                })->orWhere(function ($inner) {
                    $inner->whereNull('aportacion_limite_at')
                        ->whereDate('fecha_limite', '>=', now()->toDateString());
                });
            })
            ->get(['id', 'numero_radicado', 'nombre', 'fecha_limite', 'aportacion_limite_at', 'estado'])
            ->sortBy(fn (LicitacionSolicitud $sol) => $sol->aportacionDeadline()?->timestamp ?? PHP_INT_MAX)
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Datos para ApexCharts del dashboard de licitaciones.
     *
     * @return array{
     *     vencimientos: array{labels: list<string>, series: list<int>},
     *     solicitudesEstado: array{labels: list<string>, series: list<int>, colors: list<string>},
     *     licitacionesEstado: array{labels: list<string>, series: list<int>},
     *     aportacionesUrgentes: list<array<string, mixed>>
     * }
     */
    public function charts(?User $actor = null): array
    {
        return [
            'vencimientos' => $this->vencimientosSeries($actor),
            'solicitudesEstado' => $this->solicitudesPorEstado($actor),
            'licitacionesEstado' => $this->licitacionesPorEstado(),
            'aportacionesUrgentes' => $this->aportacionesUrgentes($actor),
        ];
    }

    /**
     * @return array{labels: list<string>, series: list<int>}
     */
    private function vencimientosSeries(?User $actor, int $days = 14): array
    {
        $labels = [];
        $counts = [];
        $start = now()->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $labels[] = $day->format('d/m');
            $counts[$day->toDateString()] = 0;
        }

        $rows = LicitacionSolicitud::query()
            ->when($actor, fn ($q) => $q->forActor($actor))
            ->where(function ($q) use ($start, $days) {
                $end = $start->copy()->addDays($days)->endOfDay();
                $q->whereBetween('aportacion_limite_at', [$start, $end])
                    ->orWhere(function ($inner) use ($start, $days) {
                        $inner->whereNull('aportacion_limite_at')
                            ->whereBetween('fecha_limite', [
                                $start->toDateString(),
                                $start->copy()->addDays($days - 1)->toDateString(),
                            ]);
                    });
            })
            ->get(['id', 'fecha_limite', 'aportacion_limite_at', 'estado']);

        foreach ($rows as $row) {
            $deadline = $row->aportacionDeadline();
            if (! $deadline) {
                continue;
            }
            $key = $deadline->toDateString();
            if (array_key_exists($key, $counts)) {
                $counts[$key]++;
            }
        }

        return [
            'labels' => $labels,
            'series' => array_values($counts),
        ];
    }

    /**
     * @return array{labels: list<string>, series: list<int>, colors: list<string>}
     */
    private function solicitudesPorEstado(?User $actor): array
    {
        $colors = [
            'recibido' => '#1e2743',
            'en_tramite' => '#f7a823',
            'respondido' => '#059669',
            'enviado' => '#0d9488',
            'vencido' => '#dc2626',
            'rechazado' => '#e11d48',
        ];

        $raw = LicitacionSolicitud::query()
            ->when($actor, fn ($q) => $q->forActor($actor))
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $labels = [];
        $series = [];
        $palette = [];

        foreach (RequestStatus::cases() as $status) {
            $count = (int) ($raw[$status->value] ?? 0);
            if ($count === 0 && ! in_array($status, [RequestStatus::Recibido, RequestStatus::EnTramite], true)) {
                continue;
            }
            $labels[] = $status->label();
            $series[] = $count;
            $palette[] = $colors[$status->value] ?? '#64748b';
        }

        if ($labels === []) {
            $labels = ['Sin datos'];
            $series = [0];
            $palette = ['#cbd5e1'];
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'colors' => $palette,
        ];
    }

    /**
     * @return array{labels: list<string>, series: list<int>}
     */
    private function licitacionesPorEstado(): array
    {
        $raw = Licitacion::query()
            ->selectRaw("COALESCE(NULLIF(TRIM(estado_proceso), ''), 'Sin estado') as estado_label, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(TRIM(estado_proceso), ''), 'Sin estado')")
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'estado_label');

        if ($raw->isEmpty()) {
            return [
                'labels' => ['Sin procesos'],
                'series' => [0],
            ];
        }

        return [
            'labels' => $raw->keys()->values()->all(),
            'series' => $raw->values()->map(fn ($n) => (int) $n)->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function aportacionesUrgentes(?User $actor, int $limit = 8): array
    {
        $invitados = LicitacionSolicitudInvitado::query()
            ->with(['solicitud:id,numero_radicado,nombre,aportacion_limite_at,fecha_limite,usuario_responsable_id,created_by_id'])
            ->whereNotNull('notificado_at')
            ->whereDoesntHave('adjuntos')
            ->whereHas('solicitud', function ($q) use ($actor) {
                $q->when($actor, fn ($inner) => $inner->forActor($actor))
                    ->where(function ($deadline) {
                        $deadline->whereNotNull('aportacion_limite_at')
                            ->orWhereNotNull('fecha_limite');
                    });
            })
            ->orderBy('id')
            ->get()
            ->filter(fn (LicitacionSolicitudInvitado $inv) => $inv->isInFinalHourWindow())
            ->sortBy(fn (LicitacionSolicitudInvitado $inv) => $inv->solicitud?->aportacionDeadline()?->timestamp ?? PHP_INT_MAX)
            ->take($limit)
            ->values();

        return $invitados->map(function (LicitacionSolicitudInvitado $inv) {
            $deadline = $inv->solicitud?->aportacionDeadline();

            return [
                'email' => $inv->email,
                'nombre' => $inv->nombre,
                'radicado' => $inv->solicitud?->numero_radicado,
                'solicitud_id' => $inv->solicitud_id,
                'deadline' => $deadline?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'vencido' => $deadline?->isPast() ?? false,
                'url' => $inv->solicitud
                    ? route('licitaciones.solicitudes.show', $inv->solicitud)
                    : null,
            ];
        })->all();
    }
}
