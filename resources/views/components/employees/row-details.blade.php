@props(['employee'])

@php
    $row = $employee;
@endphp

<div class="grid gap-3 px-3 py-2.5 text-xs sm:grid-cols-2 lg:grid-cols-4">
    <div>
        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Territorio labor</p>
        <p class="mt-0.5 font-medium text-slate-700 dark:text-slate-200">{{ $row->workTerritoryLabel() }}</p>
        @if ($row->hasResidenceTerritory())
            <p class="mt-1 text-[10px] text-slate-400">Residencia: {{ $row->residenceTerritoryLabel() }}</p>
        @endif
    </div>
    <div>
        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Contrato</p>
        <p class="mt-0.5 font-medium text-slate-700 dark:text-slate-200">{{ $row->contract_type?->label() ?? '—' }}</p>
        @if ($row->hired_at)
            <p class="mt-1 text-[10px] text-slate-400">Ingreso {{ $row->hired_at->format('d/m/Y') }}</p>
        @endif
    </div>
    <div>
        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Rol</p>
        <p class="mt-0.5 font-medium text-slate-700 dark:text-slate-200">{{ $row->employee_scope?->label() ?? '—' }}</p>
        @if ($row->email || $row->phone)
            <p class="mt-1 truncate text-[10px] text-slate-400">
                @if ($row->email){{ $row->email }}@endif
                @if ($row->email && $row->phone) · @endif
                @if ($row->phone){{ $row->phone }}@endif
            </p>
        @endif
    </div>
    <div class="flex items-end justify-start sm:justify-end">
        @can('update', $row)
            <button type="button"
                wire:click="openEdit({{ $row->id }})"
                class="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Editar empleado
            </button>
        @endcan
    </div>
</div>
