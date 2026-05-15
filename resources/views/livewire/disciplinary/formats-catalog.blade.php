<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Disciplinarios · Formatos</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Formatos oficiales del proceso</h1>
            <p class="mt-2 text-sm text-slate-600 max-w-3xl dark:text-slate-300">
                Referencia de plantillas alineadas con las etapas <strong>A–F</strong> del proceso disciplinario SJ (informe, citación, reprogramación, diligencia, decisión, apelación y segunda instancia).
                Use <strong>Plantilla</strong> para abrir el <strong>PDF en un modal</strong> (misma plantilla en blanco que la descarga).
                En <strong>Descarga</strong> guarda el archivo PDF en su equipo.
                @can('generateFo51Inform', \App\Models\Disciplinary\DisciplinaryCase::class)
                    Para diligenciar el informe en pantalla y generar PDF en tamaño carta:
                    <a href="{{ route('disciplinary.cases.index', ['informe_modal' => 1]) }}" class="font-semibold text-indigo-700 underline decoration-dotted underline-offset-2 hover:text-indigo-900 dark:text-cyan-400 dark:hover:text-cyan-300">Abrir FO-GJ-51 para diligenciar</a>.
                @endcan
                Las plantillas <strong class="text-slate-800 dark:text-slate-200">FO-GJ-03, FO-GJ-54 y FO-GJ-42</strong> tienen por ahora <strong>PDF en blanco</strong> desde HTML (misma mecánica que la plantilla FO-GJ-51); el diligenciamiento en pantalla y el vínculo al expediente se irán conectando por formato.
                Los PDF opcionales van en
                <code class="text-xs bg-slate-100 px-1 py-0.5 rounded dark:bg-white/10 dark:text-slate-200">public/formatos/disciplinarios/</code>.
            </p>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 overflow-hidden dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-white/10">
                        <thead class="bg-slate-50 dark:bg-white/[0.06]">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">#</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Código</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Formato</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Fase</th>
                                <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-700 hidden md:table-cell dark:text-slate-200">Descripción</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-200">Plantilla</th>
                                <th scope="col" class="px-4 py-3 text-center font-semibold text-slate-700 w-[5rem] dark:text-slate-200">Descarga</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-white/10 dark:bg-transparent">
                            @foreach ($forms as $i => $row)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-white/[0.04]">
                                    <td class="px-4 py-3 text-slate-500 tabular-nums dark:text-slate-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        @if ($row['code'])
                                            <span class="font-mono text-xs font-semibold text-indigo-700">{{ $row['code'] }}</span>
                                        @else
                                            <span class="text-xs text-slate-400">Pendiente</span>
                                        @endif
                                    </td>
                                    @php
                                        $canPlantillaPdf = \App\Support\Disciplinary\OfficialFormsCatalog::hasBlankPdf($row['code'] ?? null);
                                    @endphp
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100 {{ $canPlantillaPdf ? 'cursor-pointer hover:text-indigo-700 underline decoration-dotted decoration-slate-400 underline-offset-2 dark:hover:text-cyan-300 dark:decoration-slate-500' : '' }}"
                                        @if ($canPlantillaPdf)
                                            wire:click="openFormPreview('{{ $row['code'] }}')"
                                            title="Abrir plantilla PDF en modal"
                                        @endif>
                                        {{ $row['title'] }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $row['phase'] }}</td>
                                    <td class="px-4 py-3 text-slate-600 hidden md:table-cell max-w-md dark:text-slate-400">{{ $row['summary'] }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                            @if ($canPlantillaPdf)
                                                <button type="button" wire:click="openFormPreview('{{ $row['code'] }}')"
                                                    class="inline-flex items-center gap-1.5 rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 dark:bg-dash-lift dark:ring-1 dark:ring-white/15 dark:hover:bg-dash-lift/90 dark:hover:text-white">
                                                    Ver plantilla PDF
                                                </button>
                                            @else
                                                <span class="text-xs text-slate-400 dark:text-slate-500">Sin archivo</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap align-middle">
                                        @if ($canPlantillaPdf)
                                            <a href="{{ route('disciplinary.formats.download-blank', ['code' => $row['code']]) }}"
                                               class="inline-flex items-center justify-center rounded-lg p-2 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-800 dark:text-indigo-400 dark:hover:bg-white/10 dark:hover:text-indigo-300"
                                               title="Descargar plantilla en blanco (diligenciar manualmente)"
                                               aria-label="Descargar {{ $row['code'] }} en blanco">
                                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                </svg>
                                            </a>
                                        @else
                                            <span class="inline-flex justify-center text-slate-300 dark:text-slate-600" title="Sin plantilla descargable">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: PDF plantilla en blanco (misma fuente que descarga/preview-auth) --}}
    @if (filled($activeFormPreview))
        @php
            $previewLabels = collect($forms)->keyBy(fn ($r) => (string) ($r['code'] ?? ''));
            $previewRow = $previewLabels[$activeFormPreview] ?? null;
            $previewIframeSrc = route('disciplinary.formats.preview', ['code' => $activeFormPreview]);
        @endphp

        {{-- Margen lateral real: el ancho NO debe usar 100vw (ignora el padding). w-full dentro del padding del overlay. --}}
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 px-5 py-6 sm:px-10 sm:py-8 md:px-14 md:py-10 dark:bg-black/65"
             wire:click="closeFormPreview"
             x-on:keydown.escape.window="$wire.closeFormPreview()">
            <div class="mx-auto flex w-full max-w-6xl flex-shrink-0 flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/10"
                 style="height: calc(100vh - 5rem); height: calc(100dvh - 5rem); max-height: calc(100dvh - 5rem);"
                 wire:key="pdf-modal-{{ $activeFormPreview }}"
                 x-on:click.stop
                 x-data="{ pdfReady: false }">
                <header class="flex flex-none items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-dash-muted">Plantilla PDF · {{ $activeFormPreview }}</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $previewRow['title'] ?? $activeFormPreview }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ $previewIframeSrc }}"
                           target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-white/15 dark:bg-white/10 dark:text-slate-200 dark:hover:bg-white/15"
                           title="Abrir PDF en nueva pestaña (si el visor del modal falla)">
                            Abrir en pestaña
                        </a>
                        <a href="{{ route('disciplinary.formats.download-blank', ['code' => $activeFormPreview]) }}"
                           class="hidden sm:inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-white/15 dark:bg-white/10 dark:text-slate-200 dark:hover:bg-white/15"
                           title="Descargar PDF">
                            Descargar
                        </a>
                        <button type="button" wire:click="closeFormPreview"
                            class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white"
                            title="Cerrar">
                            <span class="sr-only">Cerrar</span>
                            ✕
                        </button>
                    </div>
                </header>
                <div class="relative flex min-h-0 flex-1 flex-col overflow-hidden bg-slate-900 dark:bg-black">
                    <div class="relative min-h-0 w-full flex-1 overflow-hidden">
                        <div x-show="!pdfReady"
                             x-transition.opacity.duration.200ms
                             class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-slate-900/95 px-4 text-center dark:bg-black/95">
                            <svg class="h-10 w-10 animate-spin text-indigo-600 dark:text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-sm font-medium text-slate-200">Cargando vista previa del PDF…</p>
                            <p class="max-w-sm text-xs text-slate-400">La primera vez puede tardar unos segundos mientras se genera el documento; las siguientes suelen ser más rápidas.</p>
                        </div>
                        <iframe wire:key="fmt-preview-{{ $activeFormPreview }}"
                            title="Vista previa PDF {{ $activeFormPreview }}"
                            src="{{ $previewIframeSrc }}"
                            x-on:load="pdfReady = true"
                            class="absolute inset-0 box-border h-full w-full border-0 bg-slate-900"></iframe>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
