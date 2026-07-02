@php
    $field = 'w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-900 shadow-sm dark:border-white/15 dark:bg-dash-lift dark:text-slate-100';
    $label = 'block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5 dark:text-slate-400';
@endphp
<div>
    @push('module-nav')
        <x-licitaciones.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Jurídico</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Informes</h1>
            <p class="text-sm text-slate-600 dark:text-slate-300">Reportes de licitaciones y documentos adjuntos</p>
        </div>
    </div>

    <div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex gap-2 border-b border-slate-200 dark:border-white/10">
            <button type="button" wire:click="$set('tab', 'licitaciones')"
                class="px-4 py-2 text-sm font-semibold border-b-2 {{ $tab === 'licitaciones' ? 'border-indigo-600 text-indigo-700 dark:border-cyan-400 dark:text-cyan-300' : 'border-transparent text-slate-500' }}">
                Reportes de licitaciones
            </button>
            <button type="button" wire:click="$set('tab', 'documentos')"
                class="px-4 py-2 text-sm font-semibold border-b-2 {{ $tab === 'documentos' ? 'border-indigo-600 text-indigo-700 dark:border-cyan-400 dark:text-cyan-300' : 'border-transparent text-slate-500' }}">
                Documentos adjuntos
            </button>
        </div>

        <div class="rounded-xl bg-white ring-1 ring-slate-200 p-4 dark:bg-white/[0.04] dark:ring-white/10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div><label class="{{ $label }}">Desde</label><input type="date" wire:model.live="fecha_desde" class="{{ $field }}"></div>
            <div><label class="{{ $label }}">Hasta</label><input type="date" wire:model.live="fecha_hasta" class="{{ $field }}"></div>
            @if ($tab === 'licitaciones')
                <div><label class="{{ $label }}">Estado proceso</label>
                    <select wire:model.live="estado_proceso" class="{{ $field }}">
                        <option value="all">Todos</option>
                        @foreach ($estadosProceso as $estado)
                            <option value="{{ $estado }}">{{ $estado }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="{{ $label }}">Cumplimos</label>
                    <select wire:model.live="cumplimos" class="{{ $field }}">
                        <option value="all">Todos</option>
                        <option value="SI">SI</option>
                        <option value="NO">NO</option>
                    </select>
                </div>
            @else
                <div><label class="{{ $label }}">Licitación</label>
                    <select wire:model.live="licitacion_id" class="{{ $field }}">
                        <option value="">Todas</option>
                        @foreach ($licitacionesSelect as $lic)
                            <option value="{{ $lic->id }}">{{ $lic->numero_proceso ?: 'Sin número' }} — {{ Str::limit($lic->entidad_contratante, 30) }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="{{ $label }}">Buscar archivo</label><input type="search" wire:model.live.debounce.350ms="busqueda" class="{{ $field }}" placeholder="Nombre del archivo…"></div>
            @endif
        </div>

        @if ($tab === 'licitaciones')
            <div class="flex justify-end">
                <a href="{{ route('licitaciones.informes.export').($exportQuery ? '?'.$exportQuery : '') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Exportar Excel
                </a>
            </div>

            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Proceso</th>
                            <th class="px-4 py-3">Entidad</th>
                            <th class="px-4 py-3">Responsable</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Adjudicado</th>
                            <th class="px-4 py-3">Cierre</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                        @forelse ($licitaciones as $row)
                            <tr wire:key="rep-{{ $row->id }}">
                                <td class="px-4 py-3 font-medium">{{ $row->numero_proceso ?: '—' }}</td>
                                <td class="px-4 py-3">{{ Str::limit($row->entidad_contratante ?? '—', 35) }}</td>
                                <td class="px-4 py-3">{{ $row->responsablePrincipal?->name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $row->estado_proceso ?: '—' }}</td>
                                <td class="px-4 py-3">{{ $row->adjudicado ?: '—' }}</td>
                                <td class="px-4 py-3">{{ $row->fecha_cierre_oferta?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Sin resultados con los filtros actuales.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Archivo</th>
                            <th class="px-4 py-3">Licitación</th>
                            <th class="px-4 py-3">Solicitud</th>
                            <th class="px-4 py-3">Subido por</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3 text-right">Descargar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                        @forelse ($documentos as $doc)
                            @php
                                $lic = $doc->licitacion ?? $doc->solicitud?->licitacion;
                            @endphp
                            <tr wire:key="doc-{{ $doc->id }}">
                                <td class="px-4 py-3">{{ $doc->nombre_archivo }}</td>
                                <td class="px-4 py-3">{{ $lic?->numero_proceso ?: ($lic?->entidad_contratante ?? '—') }}</td>
                                <td class="px-4 py-3">{{ $doc->solicitud?->numero_radicado ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $doc->usuario?->name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $doc->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('licitaciones.adjuntos.file', $doc) }}" target="_blank" class="text-indigo-600 font-semibold dark:text-cyan-400">Abrir</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Sin documentos con los filtros actuales.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
