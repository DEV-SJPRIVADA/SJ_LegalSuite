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
                Use <strong>Ver formato</strong> para abrir la plantilla oficial en modal (<span class="whitespace-nowrap">listo para diligenciar</span>).
                En <strong>Descarga</strong> obtiene la misma plantilla en blanco para imprimir o diligenciar manualmente (HTML editable para FO-GJ-51; PDF adjunto cuando exista archivo en servidor).
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
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100 {{ ($row['code'] ?? '') === 'FO-GJ-51' ? 'cursor-pointer hover:text-indigo-700 underline decoration-dotted decoration-slate-400 underline-offset-2 dark:hover:text-cyan-300 dark:decoration-slate-500' : '' }}"
                                        @if (($row['code'] ?? '') === 'FO-GJ-51')
                                            wire:click="openFormPreview('FO-GJ-51')"
                                            title="Abrir vista previa del formato"
                                        @endif>
                                        {{ $row['title'] }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $row['phase'] }}</td>
                                    <td class="px-4 py-3 text-slate-600 hidden md:table-cell max-w-md dark:text-slate-400">{{ $row['summary'] }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                            @if (($row['code'] ?? '') === 'FO-GJ-51')
                                                <button type="button" wire:click="openFormPreview('FO-GJ-51')"
                                                    class="inline-flex items-center gap-1 rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                                    Ver formato
                                                </button>
                                            @endif
                                            @if ($row['pdf'])
                                                <a href="{{ asset('formatos/disciplinarios/'.$row['pdf']) }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 font-medium text-xs">
                                                    PDF
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V10.5m-18 6.75h16.5m0 0-3.75-3.75M21 17.25l-3.75-3.75m0 0L12.75 21" />
                                                    </svg>
                                                </a>
                                            @elseif (($row['code'] ?? '') !== 'FO-GJ-51')
                                                <span class="text-xs text-slate-400">Sin archivo</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap align-middle">
                                        @if (filled($row['code']) && (($row['pdf'] ?? null) || ($row['code'] === 'FO-GJ-51')))
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

    {{-- Modal: hojas carta centradas sobre fondo tipo escritorio --}}
    @if ($activeFormPreview === 'FO-GJ-51')
        <div class="fixed inset-0 z-[70] flex items-start justify-center bg-neutral-900/55 p-2 sm:p-4 pt-6 pb-10 overflow-y-auto"
             wire:click="closeFormPreview"
             x-data
             x-on:keydown.escape.window="$wire.closeFormPreview()">
            <div class="relative w-full max-w-[calc(8.5in+5rem)] mx-auto rounded-lg overflow-hidden shadow-2xl ring-1 ring-neutral-500/80 bg-neutral-500"
                 wire:click.stop>
                <div class="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-neutral-400 bg-white px-3 py-2.5 sm:px-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-neutral-500">Plantilla oficial · FO-GJ-51</p>
                        <p class="text-sm font-semibold text-neutral-900">FO-GJ-51 · Informe disciplinario</p>
                    </div>
                    <button type="button" wire:click="closeFormPreview"
                        class="rounded-md p-2 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 shrink-0"
                        title="Cerrar">
                        <span class="sr-only">Cerrar</span>
                        ✕
                    </button>
                </div>
                <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-5rem)] px-3 py-6 sm:px-10 sm:py-8 bg-[#bfbfbf]">
                    <div class="inline-block mx-auto min-w-[8.5in] max-w-full align-top">
                        <x-disciplinary.forms.fo-gj-51-preview />
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
