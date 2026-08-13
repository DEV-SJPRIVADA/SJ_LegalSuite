@php
    $field = 'w-full rounded-lg border border-slate-300 bg-white text-sm dark:border-white/15 dark:bg-dash-lift dark:text-slate-100';
    $label = 'block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5 dark:text-slate-400';
@endphp
<div>
    @push('module-nav')
        <x-licitaciones.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex justify-between items-end gap-3">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-500">Licitaciones</p>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Solicitudes</h1>
            </div>
            @can('create', \App\Models\Licitaciones\LicitacionSolicitud::class)
                <button type="button" wire:click="openCreate" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Nueva solicitud</button>
            @endcan
        </div>
    </div>

    <div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
        <div class="relative">
            <x-ui.search-field-icon />
            <input type="text" inputmode="search" autocomplete="off" wire:model.live.debounce.350ms="search" placeholder="Buscar radicado, nombre, área…" class="{{ $field }} pl-8" aria-label="Buscar solicitudes">
        </div>

        <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-white/5"><tr>
                    <th class="px-4 py-3 text-left">Radicado</th><th class="px-4 py-3 text-left">Nombre</th><th class="px-4 py-3 text-left">Licitación</th><th class="px-4 py-3 text-left">Estado</th><th class="px-4 py-3 text-left">Vence</th><th class="px-4 py-3 text-right"></th>
                </tr></thead>
                <tbody class="divide-y dark:divide-white/10">
                    @forelse ($solicitudes as $row)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $row->numero_radicado }}</td>
                            <td class="px-4 py-3">{{ $row->nombre }}</td>
                            <td class="px-4 py-3">{{ $row->licitacion?->numero_proceso ?? '—' }}</td>
                            <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full {{ $row->estado?->badgeClass() }}">{{ $row->estado?->label() }}</span></td>
                            <td class="px-4 py-3">{{ $row->fecha_limite?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('licitaciones.solicitudes.show', $row) }}" wire:navigate class="text-indigo-600 font-semibold">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Sin solicitudes.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3">{{ $solicitudes->links() }}</div>
        </div>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-dash-ink p-6 ring-1 dark:ring-white/15">
                <h2 class="text-lg font-bold mb-4 dark:text-white">Nueva solicitud</h2>
                <form wire:submit="save" class="space-y-3">
                    <div><label class="{{ $label }}">Radicado</label><input wire:model="numero_radicado" class="{{ $field }}">@error('numero_radicado')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="{{ $label }}">Nombre</label><input wire:model="nombre" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Descripción / docs requeridos</label><textarea wire:model="descripcion" rows="2" class="{{ $field }}" placeholder="Qué documentación deben aportar…"></textarea></div>
                    <div><label class="{{ $label }}">Tipo</label>
                        <select wire:model.live="tipo_solicitud" class="{{ $field }}">
                            <option value="esporadica">Esporádica</option><option value="fija">Fija</option>
                        </select></div>
                    @if ($tipo_solicitud === 'esporadica')
                        <div><label class="{{ $label }}">Licitación</label>
                            <select wire:model="licitacion_id" class="{{ $field }}"><option value="">—</option>@foreach($licitaciones as $l)<option value="{{ $l->id }}">{{ $l->numero_proceso }} — {{ Str::limit($l->entidad_contratante, 30) }}</option>@endforeach</select>
                            @error('licitacion_id')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    @endif
                    <div><label class="{{ $label }}">Área responsable</label><input wire:model="area_responsable" class="{{ $field }}"></div>
                    <div><label class="{{ $label }}">Usuario responsable</label>
                        <select wire:model="usuario_responsable_id" class="{{ $field }}"><option value="">—</option>@foreach($usuarios as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                    <div>
                        <label class="{{ $label }}">Correo para notificaciones</label>
                        <input type="email" wire:model="email_notificacion" class="{{ $field }}" placeholder="soporte.admin@sjsp.com.co">
                        <p class="mt-1 text-[11px] text-slate-500">Ahí llegará el aviso cuando aporten documentos.</p>
                        @error('email_notificacion')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div><label class="{{ $label }}">Fecha límite</label><input type="date" wire:model="fecha_limite" class="{{ $field }}"></div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeForm">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Crear</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
