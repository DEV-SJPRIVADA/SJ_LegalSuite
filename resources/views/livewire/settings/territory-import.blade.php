@php
    $field = 'w-full rounded-md border border-slate-300 bg-white !text-slate-900 shadow-sm text-sm file:mr-3 file:rounded file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:!border-white/15 dark:!bg-dash-lift dark:!text-slate-100 dark:file:bg-white/10 dark:file:text-indigo-200 dark:hover:file:bg-white/15';
    $label = 'block text-xs font-semibold text-slate-600 mb-1 dark:text-slate-400';
@endphp
<div>
    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Ajustes</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Territorio (Colombia)</h1>
            <p class="text-sm text-slate-600 mt-1 dark:text-slate-300">
                Cargue el listado oficial de municipios (DIVIPOLA / DANE). Los registros se identifican por el
                <strong class="font-semibold text-slate-800 dark:text-slate-200">código de municipio de 5 dígitos</strong>
                y se actualizan si ya existían.
            </p>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/35 dark:text-emerald-300 dark:ring-emerald-500/25">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-lg bg-white shadow-sm ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card p-5 sm:p-6">
                <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">
                    Registros en base de datos: <span class="font-mono font-semibold text-slate-900 dark:text-white">{{ number_format($municipalityCount) }}</span>
                </p>

                <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-2 list-disc pl-5 mb-6">
                    <li>Excel: hoja llamada exactamente <strong class="text-slate-800 dark:text-slate-200">Municipios</strong> (se ignoran otras hojas, p. ej. MapInfo).</li>
                    <li>Primera fila de datos: <strong class="text-slate-800 dark:text-slate-200">fila 3</strong> (filas 1–2 pueden ser título / vacías).</li>
                    <li>Columnas A–H: código departamento, nombre departamento, código municipio (5 dígitos), nombre municipio, tipo, longitud, latitud, nota.</li>
                    <li>CSV: codificación <strong class="text-slate-800 dark:text-slate-200">UTF-8 sin BOM</strong>, mismo orden de columnas; datos desde la fila 3 del archivo.</li>
                </ul>

                <form wire:submit="import" class="space-y-4">
                    <div>
                        <label class="{{ $label }}" for="territory-file">Archivo</label>
                        <input id="territory-file" type="file" wire:model="file" accept=".xlsx,.csv"
                            class="{{ $field }}">
                        <div wire:loading wire:target="file" class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cargando archivo…</div>
                        @error('file')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="file,import"
                            class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="import">Importar</span>
                            <span wire:loading wire:target="import">Importando…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
