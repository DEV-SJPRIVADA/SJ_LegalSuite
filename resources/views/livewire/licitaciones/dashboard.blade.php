<div>
    @push('module-nav')
        <x-licitaciones.nav />
    @endpush

    <div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-cyan-400">Licitaciones · Dashboard</p>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ([
                ['Licitaciones', $stats['licitaciones_total']],
                ['Solicitudes', $stats['solicitudes_total']],
                ['Pendientes', $stats['solicitudes_pendientes']],
                ['Vencidas', $stats['solicitudes_vencidas']],
            ] as [$label, $value])
                <div class="rounded-xl bg-white ring-1 ring-slate-200 p-4 dark:bg-white/[0.04] dark:ring-white/10">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <div class="rounded-xl bg-white ring-1 ring-slate-200 p-5 dark:bg-white/[0.04] dark:ring-white/10">
                <h2 class="font-semibold text-slate-900 dark:text-white mb-3">Licitaciones recientes</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($recentLicitaciones as $lic)
                        <li>
                            <a href="{{ route('licitaciones.procesos.show', $lic) }}" wire:navigate class="text-indigo-600 hover:underline dark:text-cyan-400">
                                {{ $lic->numero_proceso ?: 'Sin número' }} — {{ Str::limit($lic->entidad_contratante ?? $lic->objeto, 50) }}
                            </a>
                        </li>
                    @empty
                        <li class="text-slate-500">Sin licitaciones registradas.</li>
                    @endforelse
                </ul>
            </div>
            <div class="rounded-xl bg-white ring-1 ring-slate-200 p-5 dark:bg-white/[0.04] dark:ring-white/10">
                <h2 class="font-semibold text-slate-900 dark:text-white mb-3">Próximos vencimientos</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($upcomingExpiries as $sol)
                        <li class="flex justify-between gap-2">
                            <span>{{ $sol->numero_radicado }} — {{ Str::limit($sol->nombre, 40) }}</span>
                            <span class="text-slate-500 shrink-0">{{ $sol->fecha_limite?->format('d/m/Y') }}</span>
                        </li>
                    @empty
                        <li class="text-slate-500">Sin vencimientos próximos.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
