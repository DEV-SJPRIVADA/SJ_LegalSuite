@php
    $chartDark = ($uiTheme ?? 'light') === 'dark';
    $chartConfig = [
        'chartDark' => $chartDark,
        'vencimientos' => $charts['vencimientos'] ?? ['labels' => [], 'series' => []],
        'solicitudesEstado' => $charts['solicitudesEstado'] ?? ['labels' => [], 'series' => [], 'colors' => []],
        'licitacionesEstado' => $charts['licitacionesEstado'] ?? ['labels' => [], 'series' => []],
    ];
@endphp

<div
    x-data="licitacionesDashboard(@js($chartConfig))"
    x-init="init()"
    @destroy.window="destroy()"
>
    @push('module-nav')
        <x-licitaciones.nav />
    @endpush

    <div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-sj-orange">Licitaciones · Dashboard</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Vencimientos, progreso y alertas de aportación.</p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ([
                ['Licitaciones', $stats['licitaciones_total'], 'text-sj-blue dark:text-white'],
                ['Solicitudes', $stats['solicitudes_total'], 'text-sj-blue dark:text-white'],
                ['Pendientes', $stats['solicitudes_pendientes'], 'text-sj-orange'],
                ['Vencidas', $stats['solicitudes_vencidas'], 'text-red-600 dark:text-red-400'],
            ] as [$label, $value, $valueClass])
                <div class="rounded-xl bg-white ring-1 ring-slate-200 p-4 dark:bg-white/[0.04] dark:ring-white/10">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums {{ $valueClass }}">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="rounded-xl bg-white ring-1 ring-slate-200 p-5 dark:bg-white/[0.04] dark:ring-white/10 lg:col-span-2" wire:ignore>
                <div class="mb-3 flex items-center justify-between gap-2">
                    <div>
                        <h2 class="font-semibold text-sj-blue dark:text-white">Vencimientos próximos</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Solicitudes con límite en los próximos 14 días</p>
                    </div>
                </div>
                <div x-ref="chartVencimientos" data-chart-key="vencimientos" data-apex-chart-root class="min-h-[260px]"></div>
            </div>

            <div class="rounded-xl bg-white ring-1 ring-slate-200 p-5 dark:bg-white/[0.04] dark:ring-white/10" wire:ignore>
                <div class="mb-3">
                    <h2 class="font-semibold text-sj-blue dark:text-white">Solicitudes en progreso</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Distribución por estado</p>
                </div>
                <div x-ref="chartSolicitudes" data-chart-key="solicitudes" data-apex-chart-root class="min-h-[260px]"></div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="rounded-xl bg-white ring-1 ring-slate-200 p-5 dark:bg-white/[0.04] dark:ring-white/10" wire:ignore>
                <div class="mb-3">
                    <h2 class="font-semibold text-sj-blue dark:text-white">Licitaciones por estado</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Procesos en curso</p>
                </div>
                <div x-ref="chartLicitaciones" data-chart-key="licitaciones" data-apex-chart-root class="min-h-[260px]"></div>
            </div>

            <div class="rounded-xl bg-white ring-1 ring-slate-200 p-5 dark:bg-white/[0.04] dark:ring-white/10 lg:col-span-2">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <div>
                        <h2 class="font-semibold text-sj-blue dark:text-white">Última hora · aportaciones pendientes</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Recordatorio cada 10 min hasta que suban archivos</p>
                    </div>
                    @if (count($aportacionesUrgentes) > 0)
                        <span class="rounded-md bg-sj-orange/15 px-2 py-0.5 text-[11px] font-bold tabular-nums text-sj-blue ring-1 ring-sj-orange/40 dark:text-sj-orange">
                            {{ count($aportacionesUrgentes) }}
                        </span>
                    @endif
                </div>
                <ul class="divide-y divide-slate-100 dark:divide-white/10">
                    @forelse ($aportacionesUrgentes as $row)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900 dark:text-white truncate">
                                    {{ $row['nombre'] ?: $row['email'] }}
                                    <span class="font-normal text-slate-500">· {{ $row['radicado'] }}</span>
                                </p>
                                <p class="text-xs text-slate-500">{{ $row['email'] }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span @class([
                                    'rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1',
                                    'bg-red-50 text-red-700 ring-red-200 dark:bg-red-500/15 dark:text-red-200 dark:ring-red-500/30' => $row['vencido'],
                                    'bg-sj-orange/15 text-sj-blue ring-sj-orange/40 dark:text-sj-orange' => ! $row['vencido'],
                                ])>
                                    {{ $row['vencido'] ? 'Vencido' : 'Cierra' }} {{ $row['deadline'] }}
                                </span>
                                @if ($row['url'])
                                    <a href="{{ $row['url'] }}" wire:navigate class="text-xs font-semibold text-sj-blue underline dark:text-sj-orange">Ver</a>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-sm text-slate-500 text-center">Ningún aportante en la última hora con archivos pendientes.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="rounded-xl bg-white ring-1 ring-slate-200 p-5 dark:bg-white/[0.04] dark:ring-white/10">
                <h2 class="font-semibold text-sj-blue dark:text-white mb-3">Licitaciones recientes</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($recentLicitaciones as $lic)
                        <li>
                            <a href="{{ route('licitaciones.procesos.show', $lic) }}" wire:navigate class="text-sj-blue hover:underline dark:text-sj-orange">
                                {{ $lic->numero_proceso ?: 'Sin número' }} — {{ Str::limit($lic->entidad_contratante ?? $lic->objeto, 50) }}
                            </a>
                        </li>
                    @empty
                        <li class="text-slate-500">Sin licitaciones registradas.</li>
                    @endforelse
                </ul>
            </div>
            <div class="rounded-xl bg-white ring-1 ring-slate-200 p-5 dark:bg-white/[0.04] dark:ring-white/10">
                <h2 class="font-semibold text-sj-blue dark:text-white mb-3">Próximos vencimientos</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($upcomingExpiries as $sol)
                        <li class="flex justify-between gap-2">
                            <a href="{{ route('licitaciones.solicitudes.show', $sol) }}" wire:navigate class="text-sj-blue hover:underline dark:text-sj-orange truncate">
                                {{ $sol->numero_radicado }} — {{ Str::limit($sol->nombre, 40) }}
                            </a>
                            <span class="text-slate-500 shrink-0 tabular-nums">
                                {{ $sol->aportacionDeadline()?->format('d/m/Y H:i') ?? $sol->fecha_limite?->format('d/m/Y') }}
                            </span>
                        </li>
                    @empty
                        <li class="text-slate-500">Sin vencimientos próximos.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
