@props([
    'inputId' => 'territory-file',
    'accept' => '.xlsx,.csv',
    'file' => null,
])

@php
    $shell = 'relative flex min-h-[9rem] flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-5 text-center transition';
    $idle = 'border-slate-300 bg-slate-50/80 dark:border-white/15 dark:bg-white/[0.03]';
    $drag = 'border-indigo-400 bg-indigo-50/80 dark:border-indigo-400/50 dark:bg-indigo-500/10';
@endphp

<div
    x-data="{ dragging: false }"
    x-on:dragover.prevent="dragging = true"
    x-on:dragleave.prevent="dragging = false"
    x-on:drop.prevent="
        dragging = false;
        if ($event.dataTransfer.files.length) {
            $refs.fileInput.files = $event.dataTransfer.files;
            $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    "
    {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <label
        for="{{ $inputId }}"
        :class="dragging ? '{{ $shell }} {{ $drag }}' : '{{ $shell }} {{ $idle }}'"
        class="cursor-pointer hover:border-indigo-300 dark:hover:border-indigo-400/40">
        <svg class="mx-auto h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
        </svg>
        <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">Arrastre el archivo DIVIPOLA aquí</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">o haga clic para seleccionar</p>
        <div class="mt-3 flex flex-wrap justify-center gap-1.5">
            <span class="rounded-md bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-white/10 dark:text-slate-300 dark:ring-white/10">.xlsx</span>
            <span class="rounded-md bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-white/10 dark:text-slate-300 dark:ring-white/10">.csv UTF-8</span>
            <span class="rounded-md bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-white/10 dark:text-slate-300 dark:ring-white/10">máx. 15 MB</span>
        </div>
        <input
            id="{{ $inputId }}"
            type="file"
            x-ref="fileInput"
            wire:model="file"
            accept="{{ $accept }}"
            class="sr-only">
    </label>

    @if ($file)
        <div class="flex items-center justify-between gap-2 rounded-lg bg-slate-100 px-3 py-2 text-xs dark:bg-white/[0.06]">
            <div class="min-w-0 truncate">
                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $file->getClientOriginalName() }}</span>
                <span class="text-slate-500 dark:text-slate-400"> · {{ number_format($file->getSize() / 1024, 1) }} KB</span>
            </div>
            <button type="button" wire:click="clearFile" class="shrink-0 font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">Quitar</button>
        </div>
    @endif

    <div wire:loading wire:target="file" class="text-center text-xs text-slate-500 dark:text-slate-400">Preparando archivo…</div>

    @error('file')
        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    {{ $slot }}
</div>
