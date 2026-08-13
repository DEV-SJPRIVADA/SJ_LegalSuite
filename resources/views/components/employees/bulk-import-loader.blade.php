@props([
    'imageUrl' => null,
    'progress' => [],
])

@php
    $gavelSrc = $imageUrl ?? asset('images/Mazo_juez.jpg');
@endphp

<div {{ $attributes->merge(['class' => 'relative w-full overflow-hidden rounded-xl bg-slate-900/90 ring-1 ring-white/10']) }}>
    <div class="flex flex-col items-center px-5 py-6 text-center">
        <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full">
            <img
                src="{{ $gavelSrc }}"
                alt=""
                class="h-full w-full object-cover shadow-md ring-1 ring-white/20"
                width="80"
                height="80"
                decoding="async">
            <div class="pointer-events-none absolute inset-1 animate-[bulk-orbit-spin_1.15s_linear_infinite]" aria-hidden="true">
                <span class="absolute left-1/2 top-0 h-2 w-2 -translate-x-1/2 rounded-full bg-white shadow-[0_0_8px_rgba(255,255,255,0.9)] ring-1 ring-cyan-400/80"></span>
            </div>
        </div>

        <div class="mt-4 w-full space-y-3">
            <div class="space-y-1">
                <p class="text-sm font-semibold tracking-wide text-white" x-text="phaseLabel"></p>
                <p class="text-sm font-medium text-slate-200" x-show="(progress.total_rows || 0) > 0">
                    <span class="tabular-nums" x-text="displayed.toLocaleString('es-CO')"></span> de
                    <span class="tabular-nums" x-text="Number(progress.total_rows || 0).toLocaleString('es-CO')"></span> registros procesados
                </p>
            </div>

            <div class="space-y-2 pb-1">
                <div class="flex items-center justify-between gap-3 text-[11px] font-semibold uppercase tracking-wide text-cyan-100/90">
                    <span>Progreso</span>
                    <span class="tabular-nums"><span x-text="percent"></span>%</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-white/15 ring-1 ring-white/10">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-indigo-500 transition-[width] duration-150 ease-linear"
                        :style="`width: ${percent}%`"
                        role="progressbar"
                        :aria-valuenow="percent"
                        aria-valuemin="0"
                        aria-valuemax="100"></div>
                </div>
                <p class="text-xs font-medium text-cyan-200/95" x-text="etaLabel"></p>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes bulk-orbit-spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
