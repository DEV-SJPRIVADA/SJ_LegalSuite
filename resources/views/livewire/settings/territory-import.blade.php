@php
    $fieldCompact = 'h-8 rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-white/15 dark:bg-dash-lift dark:text-slate-100 dark:placeholder:text-slate-500';
    $listTargets = 'search,departmentFilter,perPage,gotoPage,nextPage,previousPage,import';
    $hasExplorerFilters = $search !== '' || $departmentFilter !== '';
    $showingFrom = $municipalities->firstItem() ?? 0;
    $showingTo = $municipalities->lastItem() ?? 0;
    $showingTotal = $municipalities->total();
    $coordPct = $kpiMunicipalities > 0 ? (int) round(($kpiWithCoordinates / $kpiMunicipalities) * 100) : 0;
    $lastUpdatedLabel = $kpiLastUpdated?->format('d/m/Y H:i') ?? '—';
@endphp
<div class="territory-settings mx-auto flex h-[calc(100dvh-3.25rem)] max-h-[calc(100dvh-3.25rem)] w-full max-w-[1600px] flex-col overflow-hidden px-3 py-2 sm:px-5 sm:py-3 lg:px-6">
    <header class="mb-2 flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2 dark:border-white/10">
        <h1 class="truncate text-lg font-bold text-slate-900 dark:text-white">Territorio (Colombia)</h1>
        <x-dashboard.button href="{{ route('dashboard') }}" variant="ghost" class="!h-8 shrink-0 text-xs">← Inicio</x-dashboard.button>
    </header>

    @if (session('success'))
        <div class="mb-2 shrink-0 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-500/30">{{ session('success') }}</div>
    @endif

    @if ($lastImportResult)
        <div class="mb-2 flex shrink-0 flex-wrap items-center justify-between gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-100 dark:ring-emerald-500/30">
            <p>
                <span class="font-semibold">Importación completada</span>
                · {{ number_format($lastImportResult['inserted']) }} nuevos
                · {{ number_format($lastImportResult['updated']) }} actualizados
            </p>
            <button type="button" wire:click="dismissImportResult" class="font-semibold underline hover:text-emerald-950 dark:hover:text-white">Cerrar</button>
        </div>
    @endif

    <section aria-label="Indicadores DIVIPOLA" class="mb-2 grid shrink-0 grid-cols-2 gap-2 lg:grid-cols-4">
        <x-settings.territory-kpi label="Municipios" :value="number_format($kpiMunicipalities)" accent="cyan" :status="$catalogComplete ? 'ok' : 'warn'" />
        <x-settings.territory-kpi label="Departamentos" :value="number_format($kpiDepartments)" accent="indigo" />
        <x-settings.territory-kpi label="Con coordenadas" :value="number_format($kpiWithCoordinates)" accent="emerald" :hint="$coordPct.'% del catálogo'" />
        <x-settings.territory-kpi label="Última actualización" :value="$lastUpdatedLabel" accent="amber" class="col-span-2 lg:col-span-1" />
    </section>

    <div class="grid min-h-0 flex-1 gap-3 lg:grid-cols-12">
        {{-- Panel importación --}}
        <aside class="flex min-h-0 flex-col overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 lg:col-span-4">
            <div class="shrink-0 border-b border-slate-100 px-3 py-2 dark:border-white/10">
                <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Importar DIVIPOLA</h2>
                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Listado oficial DANE · upsert por código municipio</p>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-3 space-y-3">
                <form wire:submit="import" class="space-y-3">
                    <x-settings.territory-dropzone :file="$file">
                        <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="file,import"
                            @disabled(! $file)
                            class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-600 text-xs font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <span wire:loading.remove wire:target="import">Importar archivo</span>
                            <span wire:loading wire:target="import" class="inline-flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Importando…
                            </span>
                        </button>
                    </x-settings.territory-dropzone>
                </form>
                <x-settings.territory-format-help />
            </div>
        </aside>

        {{-- Explorador --}}
        <section class="flex min-h-0 flex-col overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 lg:col-span-8">
            <div class="shrink-0 border-b border-slate-100 px-3 py-2 dark:border-white/10">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative min-w-0 flex-1">
                        <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar municipio, departamento o código…" class="{{ $fieldCompact }} w-full pl-8" aria-label="Buscar municipios">
                    </div>
                    <select wire:model.live="departmentFilter" class="{{ $fieldCompact }} min-w-[10rem]" aria-label="Filtrar por departamento">
                        <option value="">Todos los departamentos</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept['code'] }}">{{ $dept['name'] }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="perPage" class="{{ $fieldCompact }} w-16" aria-label="Por página">
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="mt-1.5 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                    <p wire:loading.remove wire:target="{{ $listTargets }}">
                        @if ($showingTotal > 0)
                            <span class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($showingFrom) }}–{{ number_format($showingTo) }}</span>
                            de {{ number_format($showingTotal) }} municipios
                        @else
                            Sin resultados en el catálogo
                        @endif
                    </p>
                    <p wire:loading wire:target="{{ $listTargets }}" class="text-indigo-600 dark:text-indigo-400">Actualizando…</p>
                    @if ($hasExplorerFilters)
                        <button type="button" wire:click="clearExplorerFilters" class="font-semibold text-indigo-600 dark:text-indigo-400">Limpiar</button>
                    @endif
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-auto">
                <table class="min-w-full text-xs">
                    <thead class="sticky top-0 z-10 bg-slate-50/95 text-left text-[10px] font-bold uppercase tracking-[0.1em] text-slate-500 backdrop-blur-sm dark:bg-dash-ink/95 dark:text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Código</th>
                            <th class="px-3 py-2">Municipio</th>
                            <th class="hidden px-3 py-2 sm:table-cell">Departamento</th>
                            <th class="px-3 py-2 text-center">Coord.</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.remove wire:target="{{ $listTargets }}" class="divide-y divide-slate-100 dark:divide-white/10">
                        @forelse ($municipalities as $mun)
                            <tr wire:key="mun-{{ $mun->id }}" class="hover:bg-slate-50/80 dark:hover:bg-white/[0.03]">
                                <td class="px-3 py-1.5 font-mono text-[11px] text-slate-600 dark:text-slate-300">{{ $mun->municipality_code }}</td>
                                <td class="max-w-[12rem] truncate px-3 py-1.5 font-medium text-slate-900 dark:text-white" title="{{ $mun->municipality_name }}">{{ $mun->municipality_name }}</td>
                                <td class="hidden max-w-[10rem] truncate px-3 py-1.5 text-slate-600 dark:text-slate-300 sm:table-cell" title="{{ $mun->department_name }}">{{ $mun->department_name ?? $mun->department_code }}</td>
                                <td class="px-3 py-1.5 text-center">
                                    @if ($mun->hasCoordinates())
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300" title="Con lat/long">✓</span>
                                    @else
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-white/10" title="Sin coordenadas">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center">
                                    @if ($kpiMunicipalities === 0)
                                        <p class="text-sm font-semibold text-slate-800 dark:text-white">Catálogo vacío</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Importe el archivo DIVIPOLA oficial para habilitar municipios en empleados y disciplinarios.</p>
                                    @elseif ($hasExplorerFilters)
                                        <p class="text-sm text-slate-600 dark:text-slate-300">Sin coincidencias para la búsqueda actual.</p>
                                        <button type="button" wire:click="clearExplorerFilters" class="mt-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400">Limpiar filtros</button>
                                    @else
                                        <p class="text-sm text-slate-500">Sin registros.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tbody wire:loading wire:target="{{ $listTargets }}">
                        @for ($i = 0; $i < 8; $i++)
                            <tr wire:key="mun-skel-{{ $i }}" class="animate-pulse">
                                <td class="px-3 py-2"><div class="h-3 w-12 rounded bg-slate-200 dark:bg-white/10"></div></td>
                                <td class="px-3 py-2"><div class="h-3 w-32 rounded bg-slate-200 dark:bg-white/10"></div></td>
                                <td class="hidden px-3 py-2 sm:table-cell"><div class="h-3 w-24 rounded bg-slate-200 dark:bg-white/10"></div></td>
                                <td class="px-3 py-2"><div class="mx-auto h-4 w-4 rounded-full bg-slate-200 dark:bg-white/10"></div></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            @if ($municipalities->hasPages())
                <div class="shrink-0 border-t border-slate-100 px-3 py-1.5 dark:border-white/10 [&_.pagination]:mb-0 [&_.pagination]:text-xs">{{ $municipalities->links() }}</div>
            @endif
        </section>
    </div>
</div>
