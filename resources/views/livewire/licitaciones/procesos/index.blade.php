@php
    $field = 'w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-900 shadow-sm dark:border-white/15 dark:bg-dash-lift dark:text-slate-100';
    $label = 'block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5 dark:text-slate-400';
@endphp
<div>
    @push('module-nav')
        <x-licitaciones.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Jurídico</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Licitaciones</h1>
            </div>
            @can('create', \App\Models\Licitaciones\Licitacion::class)
                <button type="button" wire:click="openCreate" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Nueva licitación</button>
            @endcan
        </div>
    </div>

    <div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 dark:bg-red-950/40 dark:text-red-200">{{ session('error') }}</div>@endif

        <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4 dark:bg-white/[0.04] dark:ring-white/10">
            <label class="{{ $label }}">Buscar</label>
            <div class="relative">
                <x-ui.search-field-icon />
                <input type="text" inputmode="search" autocomplete="off" wire:model.live.debounce.350ms="search" placeholder="Proceso, entidad, objeto…" class="{{ $field }} pl-8" aria-label="Buscar licitaciones">
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3">Proceso</th>
                        <th class="px-4 py-3">Entidad</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Responsable</th>
                        <th class="px-4 py-3">Cierre oferta</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                    @forelse ($licitaciones as $row)
                        <tr wire:key="lic-{{ $row->id }}" class="hover:bg-slate-50/80 dark:hover:bg-white/[0.03]">
                            <td class="px-4 py-3 font-medium">{{ $row->numero_proceso ?: '—' }}</td>
                            <td class="px-4 py-3">{{ Str::limit($row->entidad_contratante ?? '—', 40) }}</td>
                            <td class="px-4 py-3">{{ $row->estado_proceso ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $row->responsablePrincipal?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $row->fecha_cierre_oferta?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <a href="{{ route('licitaciones.procesos.show', $row) }}" wire:navigate class="text-indigo-600 font-semibold dark:text-cyan-400">Ver</a>
                                @can('update', $row)
                                    <button type="button" wire:click="openEdit({{ $row->id }})" class="text-slate-600 font-semibold dark:text-slate-300">Editar</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Sin licitaciones.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-slate-100 dark:border-white/10">{{ $licitaciones->links() }}</div>
        </div>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4">
            <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl dark:bg-dash-ink dark:ring-1 dark:ring-white/15 my-8">
                <div class="flex items-center justify-between border-b px-6 py-4 dark:border-white/10">
                    <h2 class="text-lg font-bold dark:text-white">{{ $editingId ? 'Editar licitación' : 'Nueva licitación' }}</h2>
                    <button type="button" wire:click="closeForm">✕</button>
                </div>
                <form wire:submit="save" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[70vh] overflow-y-auto">
                    <div class="md:col-span-2"><label class="{{ $label }}">Responsable principal</label>
                        <select wire:model="responsable_principal_id" class="{{ $field }}">@foreach($abogados as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                    <div><label class="{{ $label }}">Número proceso</label><input wire:model="numero_proceso" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Entidad contratante</label><input wire:model="entidad_contratante" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Modalidad</label><input wire:model="modalidad_contratacion" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Estado proceso</label><input wire:model="estado_proceso" class="{{ $field }}"></div>
                    <div class="md:col-span-2"><label class="{{ $label }}">Objeto</label><textarea wire:model="objeto" rows="3" class="{{ $field }}"></textarea></div>
                    <div><label class="{{ $label }}">Cuantía</label><input wire:model="cuantia" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Plazo ejecución</label><input wire:model="plazo_ejecucion" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Lugar ejecución</label><input wire:model="lugar_ejecucion" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Medio presentación</label><input wire:model="medio_presentacion" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Participación</label>
                        <select wire:model="participacion_tipo" class="{{ $field }}">
                            <option value="">—</option>
                            <option value="IND">IND</option>
                            <option value="UT">UT</option>
                        </select>
                    </div>
                    <div class="md:col-span-2"><label class="{{ $label }}">Integrantes participación</label><textarea wire:model="integrantes_participacion" rows="2" class="{{ $field }}"></textarea></div>
                    <div><label class="{{ $label }}">Fecha cierre oferta</label><input type="date" wire:model="fecha_cierre_oferta" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Hora cierre</label><input type="time" wire:model="hora_cierre_oferta" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Fecha observaciones evaluación</label><input type="date" wire:model="fecha_observaciones_evaluacion" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Fecha adjudicación</label><input type="date" wire:model="fecha_adjudicacion" class="{{ $field }}"></div>
                    <div class="md:col-span-2"><label class="{{ $label }}">Enlace proceso</label><input wire:model="enlace_proceso" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Cumplimos</label>
                        <select wire:model="cumplimos" class="{{ $field }}">
                            <option value="">—</option>
                            <option value="SI">SI</option>
                            <option value="NO">NO</option>
                        </select>
                    </div>
                    <div><label class="{{ $label }}">Adjudicado</label>
                        <select wire:model.live="adjudicado" class="{{ $field }}">
                            <option value="">—</option>
                            <option value="Si">Sí</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    @if ($adjudicado === 'No')
                        <div class="md:col-span-2"><label class="{{ $label }}">Motivo de pérdida</label><textarea wire:model="motivo_perdida" rows="2" class="{{ $field }}"></textarea>@error('motivo_perdida')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    @endif
                    @if ($cumplimos === 'NO')
                        <div class="md:col-span-2"><label class="{{ $label }}">Motivo no cumplir</label><textarea wire:model="motivo_no_cumplir" rows="2" class="{{ $field }}"></textarea></div>
                    @endif
                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeForm" class="px-4 py-2 text-sm rounded-lg border">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
