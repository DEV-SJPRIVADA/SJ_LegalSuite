@props([
    'imageUrl' => null,
])

@php
    $gavelSrc = $imageUrl ?? asset('images/Mazo_juez.jpg');
@endphp

<div {{ $attributes->merge(['class' => 'absolute inset-0 z-20 flex flex-col items-center justify-center rounded-2xl bg-slate-900/75 backdrop-blur-sm']) }}>
    <div class="flex flex-col items-center gap-4 px-6 text-center">
        <div class="relative h-36 w-36 sm:h-40 sm:w-40">
            <img
                src="{{ $gavelSrc }}"
                alt=""
                class="h-full w-full rounded-full object-cover shadow-lg ring-1 ring-white/20"
                width="160"
                height="160"
                decoding="async">
            <div class="pointer-events-none absolute inset-0 animate-[bulk-orbit-spin_1.15s_linear_infinite]" aria-hidden="true">
                <span class="absolute left-1/2 top-0 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white shadow-[0_0_10px_rgba(255,255,255,0.95)] ring-2 ring-cyan-400/80"></span>
            </div>
        </div>
        <div class="space-y-1">
            <p class="text-base font-semibold tracking-wide text-white">Cargando…</p>
            <p class="text-sm font-medium text-cyan-200" x-text="label">0 s</p>
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
