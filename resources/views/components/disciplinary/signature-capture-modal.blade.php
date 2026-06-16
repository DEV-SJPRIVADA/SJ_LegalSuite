@props([
    'show' => false,
    'title' => 'Capturar firma',
    'wireKey' => 'signature-pad',
    'closeAction' => 'closeWorkerSignaturePad',
    'saveAction' => 'saveCapturedSignature',
    /** @var 'indigo'|'teal' */
    'variant' => 'indigo',
])

@php
    $saveButtonClass = $variant === 'teal'
        ? 'bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-400'
        : 'bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400';
@endphp

@if ($show)
    <div class="fixed inset-0 z-[90] flex items-center justify-center p-3 sm:p-4"
        x-data="window.sjWorkerSignaturePad()"
        x-init="init()"
        x-on:keydown.escape.window="$wire.{{ $closeAction }}()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sj-signature-title-{{ $wireKey }}"
        wire:key="{{ $wireKey }}">
        <div class="absolute inset-0 bg-black/60" wire:click="{{ $closeAction }}" aria-hidden="true"></div>

        <div class="relative w-[calc(100vw-1.5rem)] max-w-none overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 sm:w-[calc(100vw-2rem)] dark:bg-dash-ink dark:ring-white/15">
            <div class="border-b border-slate-200 px-4 py-3 sm:px-6 dark:border-white/10">
                <h2 id="sj-signature-title-{{ $wireKey }}" class="text-base font-bold text-slate-900 dark:text-white">
                    {{ $title }}
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 sm:hidden">
                    Firme con el dedo en el recuadro horizontal.
                </p>
                <p class="mt-1 hidden text-xs text-slate-500 dark:text-slate-400 sm:block">
                    Firme con el lápiz de la mesa digitalizadora en el recuadro horizontal.
                </p>
            </div>

            <div class="flex flex-col items-center justify-center bg-slate-50 px-3 py-6 sm:px-4 sm:py-8 dark:bg-black/20">
                <div class="sj-signature-strip-wrap w-full">
                    <canvas
                        x-ref="canvas"
                        class="sj-signature-strip touch-none"
                        aria-label="Área de firma"
                        x-on:pointerdown="start($event)"
                        x-on:pointermove="draw($event)"
                        x-on:pointerup="end($event)"
                        x-on:pointercancel="end($event)"
                        x-on:lostpointercapture="end($event)"></canvas>
                </div>
            </div>

            @if (trim($slot) !== '')
                <div class="border-t border-slate-200 px-4 py-2 dark:border-white/10">
                    {{ $slot }}
                </div>
            @endif

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-6 dark:border-white/10 dark:bg-dash-ink/80">
                <button type="button" x-on:click="clear()"
                    class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                    Limpiar
                </button>
                <button type="button" wire:click="{{ $closeAction }}"
                    class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                    Cancelar
                </button>
                <button type="button"
                    x-on:click="(() => { const uri = exportDataUri(); if (!uri) { alert('Dibuje la firma antes de guardar.'); return; } $wire.{{ $saveAction }}(uri); })()"
                    class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold text-white {{ $saveButtonClass }}">
                    Guardar firma
                </button>
            </div>
        </div>
    </div>
@endif
