@props(['user'])

@php
    $row = $user;
    $citiesCount = $row->authorized_municipalities_count
        ?? ($row->relationLoaded('authorizedMunicipalities') ? $row->authorizedMunicipalities->count() : null);
@endphp

<div class="grid gap-3 px-3 py-2.5 text-xs sm:grid-cols-2 lg:grid-cols-4">
    <div>
        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Contacto</p>
        <p class="mt-0.5 font-medium text-slate-700 dark:text-slate-200">
            {{ $row->document_number ?: '—' }}
            @if ($row->phone)
                <span class="text-slate-400"> · </span>{{ $row->phone }}
            @endif
        </p>
        @if ($row->primaryRoleLabel())
            <p class="mt-1 text-[10px] text-slate-400">{{ $row->primaryRoleLabel() }}</p>
        @endif
    </div>
    <div>
        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Casos disciplinarios</p>
        <p class="mt-0.5 font-medium text-slate-700 dark:text-slate-200">
            {{ number_format($row->assigned_cases_count ?? 0) }} asignados
            · {{ number_format($row->reported_cases_count ?? 0) }} reportados
        </p>
        @if ($citiesCount !== null && $citiesCount > 0)
            <p class="mt-1 text-[10px] text-slate-400">{{ $citiesCount }} {{ $citiesCount === 1 ? 'ciudad autorizada' : 'ciudades autorizadas' }}</p>
        @endif
    </div>
    <div>
        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Acceso</p>
        <p class="mt-0.5 font-medium text-slate-700 dark:text-slate-200">
            @if ($row->read_only)
                Solo lectura
            @else
                Puede realizar cambios
            @endif
        </p>
        <a href="{{ route('users.show', $row) }}" wire:navigate class="mt-1 inline-block text-[10px] font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
            Ver ficha completa →
        </a>
    </div>
    <div class="flex flex-wrap items-end justify-start gap-1.5 sm:justify-end">
        @can('update', $row)
            <button type="button" wire:click="openEdit({{ $row->id }})"
                class="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                Editar
            </button>
        @endcan
        @can('changePassword', $row)
            <button type="button" wire:click="openPasswordModal({{ $row->id }})"
                class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-white/15 dark:bg-white/10 dark:text-slate-200">
                Contraseña
            </button>
        @endcan
        @can('toggleActive', $row)
            <button type="button" wire:click="toggleActive({{ $row->id }})"
                class="inline-flex items-center gap-1 rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-white/15 dark:bg-white/10 dark:text-slate-200">
                {{ $row->is_active ? 'Desactivar' : 'Activar' }}
            </button>
        @endcan
        @can('delete', $row)
            <button type="button" wire:click="deleteUser({{ $row->id }})"
                wire:confirm="¿Eliminar al usuario {{ $row->name }}? Esta acción no se puede deshacer."
                class="inline-flex items-center gap-1 rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                Eliminar
            </button>
        @endcan
    </div>
</div>
