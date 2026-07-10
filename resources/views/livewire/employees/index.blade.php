@php
    $field = 'w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-white/15 dark:bg-dash-lift dark:text-slate-100 dark:placeholder:text-slate-500';
    $label = 'block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5 dark:text-slate-400';
    $section = 'rounded-xl border border-slate-200 bg-slate-50/80 p-5 dark:border-white/10 dark:bg-white/[0.03]';
    $sectionTitle = 'text-sm font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2';
    $listTargets = 'search,status,perPage,scopeFilter,contractFilter,setStatusFilter,applyKpiFilter,clearFilters,gotoPage,nextPage,previousPage';
    $hasFilters = $search !== '' || $status !== '' || $scopeFilter !== '' || $contractFilter !== '';
    $showingFrom = $employees->firstItem() ?? 0;
    $showingTo = $employees->lastItem() ?? 0;
    $showingTotal = $employees->total();
    $statusPills = [
        '' => 'Todos',
        'activos' => 'Activos',
        'inactivos' => 'Inactivos',
        'incompletos' => 'Incompletos',
    ];
    $kpiTotalActive = $status === '' && $scopeFilter === '' && $contractFilter === '';
    $kpiActiveOn = $status === 'activos' && $scopeFilter === '' && $contractFilter === '';
    $kpiIncompleteOn = $status === 'incompletos';
    $kpiOperativoOn = $scopeFilter === 'operativo' && $status === '';
    $kpiAdminOn = $scopeFilter === 'administrativo' && $status === '';
    $fieldCompact = 'h-8 rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-white/15 dark:bg-dash-lift dark:text-slate-100 dark:placeholder:text-slate-500';
    $labelInline = 'text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
@endphp
<div class="employees-index mx-auto flex h-[calc(100dvh-3.25rem)] max-h-[calc(100dvh-3.25rem)] w-full max-w-[1600px] flex-col overflow-hidden px-3 py-2 sm:px-5 sm:py-3 lg:px-6">
    <header class="mb-2 flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2 dark:border-white/10">
        <h1 class="truncate text-lg font-bold text-slate-900 dark:text-white">Empleados SJ</h1>
        @can('create', \App\Models\Employee::class)
            <div class="flex shrink-0 flex-wrap gap-2">
                <button type="button" wire:click="openBulk"
                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-white/15 dark:bg-white/10 dark:text-white dark:hover:bg-white/15">
                    <svg class="h-3.5 w-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Carga masiva
                </button>
                <button type="button" wire:click="openCreate"
                    class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white transition hover:bg-indigo-700">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Crear empleado
                </button>
            </div>
        @endcan
    </header>

    @if (session('success'))
        <div class="mb-2 shrink-0 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-500/30">{{ session('success') }}</div>
    @endif

    {{-- KPIs compactos --}}
    <section aria-label="Indicadores de empleados" class="mb-2 grid shrink-0 grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
        <x-employees.kpi-stat wire:click="applyKpiFilter('total')" label="Total" :value="$kpiTotal" accent="cyan" :active="$kpiTotalActive" compact />
        <x-employees.kpi-stat wire:click="applyKpiFilter('activos')" label="Activos" :value="$kpiActive" accent="emerald" :active="$kpiActiveOn" compact />
        <x-employees.kpi-stat wire:click="applyKpiFilter('incompletos')" label="Incompletos" :value="$incompleteProfilesCount" accent="amber" :active="$kpiIncompleteOn" compact />
        <x-employees.kpi-stat wire:click="applyKpiFilter('operativos')" label="Operativos" :value="$kpiOperativo" accent="indigo" :active="$kpiOperativoOn" compact />
        <x-employees.kpi-stat wire:click="applyKpiFilter('administrativos')" label="Administrativos" :value="$kpiAdministrativo" accent="fuchsia" :active="$kpiAdminOn" compact class="col-span-2 sm:col-span-1" />
    </section>

    @if (($incompleteProfilesCount ?? 0) > 0 && $status !== 'incompletos')
        <div class="mb-2 flex shrink-0 flex-wrap items-center justify-between gap-2 rounded-lg bg-amber-50 px-3 py-1.5 text-xs text-amber-900 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-100 dark:ring-amber-500/30">
            <p>
                <span class="font-semibold tabular-nums">{{ number_format($incompleteProfilesCount) }}</span>
                {{ $incompleteProfilesCount === 1 ? 'perfil incompleto' : 'perfiles incompletos' }}
            </p>
            <button type="button" wire:click="applyKpiFilter('incompletos')" class="shrink-0 font-semibold underline hover:text-amber-950 dark:text-amber-200">
                Ver
            </button>
        </div>
    @endif

    {{-- Panel tabla --}}
    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
        <div class="shrink-0 border-b border-slate-100 px-3 py-2 dark:border-white/10 sm:px-4">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:gap-3">
                <div class="relative min-w-0 flex-1">
                    <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar nombre, documento, email…" class="{{ $fieldCompact }} w-full pl-8" aria-label="Buscar empleados">
                </div>
                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach ($statusPills as $value => $pillLabel)
                        <button type="button" wire:click="setStatusFilter('{{ $value }}')"
                            @class([
                                'rounded-full px-2.5 py-1 text-[11px] font-semibold transition ring-1 ring-inset',
                                'bg-indigo-600 text-white ring-indigo-600 dark:bg-indigo-500 dark:ring-indigo-400/50' => $status === $value,
                                'bg-slate-100 text-slate-700 ring-slate-200 hover:bg-slate-200 dark:bg-white/5 dark:text-slate-300 dark:ring-white/10 dark:hover:bg-white/10' => $status !== $value,
                            ])>{{ $pillLabel }}</button>
                    @endforeach
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select wire:model.live="scopeFilter" class="{{ $fieldCompact }}" aria-label="Filtrar por rol">
                        <option value="">Rol: todos</option>
                        @foreach ($employeeScopes as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="contractFilter" class="{{ $fieldCompact }}" aria-label="Filtrar por contrato">
                        <option value="">Contrato: todos</option>
                        @foreach ($contractTypes as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="perPage" class="{{ $fieldCompact }} w-16" aria-label="Registros por página">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
            <div class="mt-1.5 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                <p wire:loading.remove wire:target="{{ $listTargets }}">
                    @if ($showingTotal > 0)
                        <span class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($showingFrom) }}–{{ number_format($showingTo) }}</span>
                        de {{ number_format($showingTotal) }}
                    @else
                        Sin resultados
                    @endif
                </p>
                <p wire:loading wire:target="{{ $listTargets }}" class="inline-flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400">
                    <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Actualizando…
                </p>
                @if ($hasFilters)
                    <button type="button" wire:click="clearFilters" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Limpiar</button>
                @endif
            </div>
        </div>

        {{-- Tabla escritorio --}}
        <div class="hidden min-h-0 flex-1 overflow-auto md:block"
            x-data="{ expandedId: null }"
            wire:key="emp-table-{{ $employees->currentPage() }}-{{ md5($search.$status.$scopeFilter.$contractFilter) }}">
            <table class="min-w-full text-xs">
                <thead class="sticky top-0 z-10 bg-slate-50/95 text-left text-[10px] font-bold uppercase tracking-[0.1em] text-slate-500 backdrop-blur-sm dark:bg-dash-ink/95 dark:text-slate-400">
                    <tr>
                        <th class="w-8 px-2 py-2" aria-label="Expandir"></th>
                        <th class="px-3 py-2">Empleado</th>
                        <th class="hidden px-3 py-2 md:table-cell">Cargo</th>
                        <th class="px-3 py-2">Estado</th>
                        <th class="w-16 px-3 py-2 text-right"></th>
                    </tr>
                </thead>
                <tbody wire:loading.remove wire:target="{{ $listTargets }}" class="divide-y divide-slate-100 dark:divide-white/10">
                    @forelse ($employees as $row)
                        @php $profileIncomplete = ! $row->isProfileComplete(); @endphp
                        <tr wire:key="emp-{{ $row->id }}-main"
                            @class([
                                'group transition hover:bg-slate-50/80 dark:hover:bg-white/[0.03]',
                                'border-l-2 border-l-amber-400 dark:border-l-amber-500/80' => $profileIncomplete,
                                'border-l-2 border-l-transparent' => ! $profileIncomplete,
                            ])>
                            <td class="px-2 py-1.5 align-middle">
                                <button type="button"
                                    @click="expandedId = expandedId === {{ $row->id }} ? null : {{ $row->id }}"
                                    :aria-expanded="expandedId === {{ $row->id }}"
                                    class="flex h-6 w-6 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-white/10 dark:hover:text-slate-200"
                                    aria-label="Ver detalle">
                                    <svg class="h-3.5 w-3.5 transition-transform" :class="expandedId === {{ $row->id }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </td>
                            <td class="px-3 py-1.5">
                                <div class="flex min-w-0 items-center gap-2">
                                    <div @class([
                                        'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold ring-1',
                                        'bg-indigo-100 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-200 dark:ring-indigo-400/30' => ! $profileIncomplete,
                                        'bg-amber-100 text-amber-800 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-400/30' => $profileIncomplete,
                                    ])>{{ $row->initials() }}</div>
                                    <p class="min-w-0 truncate">
                                        <span class="font-semibold text-slate-900 dark:text-white">{{ $row->displayName() }}</span>
                                        <span class="text-slate-400"> · </span>
                                        <span class="font-mono text-[10px] text-slate-500 dark:text-slate-400">{{ $row->document_type?->value }} {{ $row->document_number }}</span>
                                    </p>
                                </div>
                            </td>
                            <td class="hidden max-w-[14rem] truncate px-3 py-1.5 text-slate-700 dark:text-slate-200 md:table-cell" title="{{ $row->employeeJobPosition?->name ?? $row->job_title ?? '—' }}">
                                {{ $row->employeeJobPosition?->name ?? $row->job_title ?? '—' }}
                            </td>
                            <td class="px-3 py-1.5">
                                <div class="flex flex-wrap items-center gap-1">
                                    @if ($row->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">Activo</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-400">Inactivo</span>
                                    @endif
                                    @if ($profileIncomplete)
                                        <span class="inline-flex rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-500/15 dark:text-amber-200">Inc.</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-1.5 text-right">
                                @can('update', $row)
                                    <button type="button" wire:click="openEdit({{ $row->id }})"
                                        class="inline-flex h-6 w-6 items-center justify-center rounded text-slate-400 opacity-0 transition group-hover:opacity-100 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-500/10 dark:hover:text-indigo-400"
                                        title="Editar">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                        <tr wire:key="emp-{{ $row->id }}-detail" x-show="expandedId === {{ $row->id }}" x-cloak class="bg-slate-50/80 dark:bg-white/[0.02]">
                            <td colspan="5" class="border-t border-slate-100 px-2 py-0 dark:border-white/5">
                                <x-employees.row-details :employee="$row" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                @if ($hasFilters || $search !== '')
                                    <p class="text-sm font-semibold text-slate-800 dark:text-white">Sin resultados</p>
                                    <button type="button" wire:click="clearFilters" class="mt-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400">Limpiar filtros</button>
                                @else
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Sin empleados registrados.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tbody wire:loading wire:target="{{ $listTargets }}" class="divide-y divide-slate-100 dark:divide-white/10">
                    <x-employees.table-skeleton :rows="min($perPage, 12)" />
                </tbody>
            </table>
        </div>

        {{-- Vista móvil --}}
        <div class="min-h-0 flex-1 overflow-auto md:hidden"
            x-data="{ expandedId: null }"
            wire:key="emp-cards-{{ $employees->currentPage() }}-{{ md5($search.$status.$scopeFilter.$contractFilter) }}">
            <div wire:loading.remove wire:target="{{ $listTargets }}" class="divide-y divide-slate-100 dark:divide-white/10">
                @forelse ($employees as $row)
                    @php $profileIncomplete = ! $row->isProfileComplete(); @endphp
                    <article wire:key="emp-card-{{ $row->id }}" @class(['border-l-2 border-l-amber-400 dark:border-l-amber-500/80' => $profileIncomplete, 'border-l-2 border-l-transparent' => ! $profileIncomplete])>
                        <div class="flex items-center gap-1 px-2 py-2">
                            <button type="button"
                                @click="expandedId = expandedId === {{ $row->id }} ? null : {{ $row->id }}"
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded text-slate-400">
                                <svg class="h-3.5 w-3.5 transition-transform" :class="expandedId === {{ $row->id }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200">{{ $row->initials() }}</div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-slate-900 dark:text-white">{{ $row->displayName() }}</p>
                                    <p class="truncate font-mono text-[10px] text-slate-500">{{ $row->document_type?->value }} {{ $row->document_number }}</p>
                                </div>
                                @if ($row->is_active)
                                    <span class="shrink-0 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">Activo</span>
                                @endif
                            </div>
                        </div>
                        <p class="truncate px-3 pb-1.5 text-[11px] text-slate-600 dark:text-slate-300">{{ $row->employeeJobPosition?->name ?? $row->job_title ?? '—' }}</p>
                        <div x-show="expandedId === {{ $row->id }}" x-cloak class="border-t border-slate-100 dark:border-white/10">
                            <x-employees.row-details :employee="$row" />
                        </div>
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-xs text-slate-500">Sin resultados.</p>
                @endforelse
            </div>
            <div wire:loading wire:target="{{ $listTargets }}" class="space-y-2 p-3">
                @for ($i = 0; $i < 4; $i++)
                    <div wire:key="emp-card-skel-{{ $i }}" class="animate-pulse rounded-lg border border-slate-100 p-2 dark:border-white/10">
                        <div class="h-3 w-40 rounded bg-slate-200 dark:bg-white/10"></div>
                    </div>
                @endfor
            </div>
        </div>

        @if ($employees->hasPages())
            <div class="shrink-0 border-t border-slate-100 px-3 py-1.5 dark:border-white/10 [&_.pagination]:mb-0 [&_.pagination]:text-xs">{{ $employees->links() }}</div>
        @endif
    </div>

    {{-- Formulario crear/editar --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-8" wire:keydown.escape="closeForm">
            <div class="w-full max-w-4xl rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15" @click.outside="closeForm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-white/10">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $editingId ? 'Editar empleado' : 'Nuevo empleado' }}</h2>
                    <button type="button" wire:click="closeForm" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form wire:submit="save" class="p-6 space-y-6 max-h-[calc(100vh-8rem)] overflow-y-auto">
                    <div class="{{ $section }}">
                        <h3 class="{{ $sectionTitle }}">1. Datos personales e identificación</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2"><label class="{{ $label }}">Nombre completo</label><input type="text" wire:model="fullName" class="{{ $field }}" placeholder="Ej. Juan Carlos Pérez López" autocomplete="name">@error('fullName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">Tipo de documento</label><select wire:model="documentType" class="{{ $field }}">@foreach ($documentTypes as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach</select></div>
                            <div><label class="{{ $label }}">Número de documento</label><input type="text" wire:model.live="documentNumber" class="{{ $field }}" inputmode="numeric" pattern="[0-9]*" autocomplete="off" placeholder="Solo números">@error('documentNumber')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">Fecha de nacimiento</label><input type="date" wire:model="birthDate" class="{{ $field }}"></div>
                            <div><label class="{{ $label }}">Género</label><select wire:model="gender" class="{{ $field }}"><option value="">—</option>@foreach ($genders as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach</select></div>
                        </div>
                    </div>
                    <div class="{{ $section }}">
                        <h3 class="{{ $sectionTitle }}">2. Datos de contacto</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2"><label class="{{ $label }}">Dirección de residencia</label><input type="text" wire:model="address" class="{{ $field }}"></div>
                            <div>
                                <label class="{{ $label }}">Departamento de residencia</label>
                                <select wire:model.live="residenceDepartmentCode" class="{{ $field }}">
                                    <option value="">— Seleccionar —</option>
                                    @foreach ($this->departments as $dept)
                                        <option value="{{ $dept['code'] }}">{{ $dept['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('residenceDepartmentCode')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $label }}">Municipio de residencia</label>
                                <select wire:model="residenceMunicipalityCode" class="{{ $field }}">
                                    <option value="">— Opcional si hay departamento —</option>
                                    @foreach ($this->municipalitiesGrouped as $dept => $rows)
                                        <optgroup label="{{ $dept }}">
                                            @foreach ($rows as $mun)
                                                @if ($residenceDepartmentCode === '' || str_starts_with($mun['code'], $residenceDepartmentCode))
                                                    <option value="{{ $mun['code'] }}">{{ $mun['name'] }}</option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('residenceMunicipalityCode')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $label }}">Departamento de labor</label>
                                <select wire:model.live="workDepartmentCode" class="{{ $field }}">
                                    <option value="">— Seleccionar —</option>
                                    @foreach ($this->departments as $dept)
                                        <option value="{{ $dept['code'] }}">{{ $dept['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('workDepartmentCode')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $label }}">Municipio de labor</label>
                                <select wire:model="municipalityCode" class="{{ $field }}">
                                    <option value="">— Opcional si hay departamento —</option>
                                    @foreach ($this->municipalitiesGrouped as $dept => $rows)
                                        <optgroup label="{{ $dept }}">
                                            @foreach ($rows as $mun)
                                                @if ($workDepartmentCode === '' || str_starts_with($mun['code'], $workDepartmentCode))
                                                    <option value="{{ $mun['code'] }}">{{ $mun['name'] }}</option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('municipalityCode')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <div><label class="{{ $label }}">Teléfono celular</label><input type="tel" wire:model="phone" class="{{ $field }}"></div>
                            <div class="sm:col-span-2"><label class="{{ $label }}">Correo electrónico</label><input type="email" wire:model="email" class="{{ $field }}">@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        </div>
                    </div>
                    <div class="{{ $section }}">
                        <h3 class="{{ $sectionTitle }}">3. Información laboral</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><label class="{{ $label }}">Fecha de ingreso *</label><input type="date" wire:model="hiredAt" class="{{ $field }}">@error('hiredAt')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">Tipo de contrato *</label><select wire:model.live="contractType" class="{{ $field }}"><option value="">—</option>@foreach ($contractTypes as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach</select>@error('contractType')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="{{ $label }}">Cargo / puesto *</label>
                                <select wire:model="employeeJobPositionId" class="{{ $field }}">
                                    <option value="">— Seleccione cargo —</option>
                                    @foreach ($this->employeeJobPositions as $position)
                                        <option value="{{ $position->id }}">{{ $position->name }}</option>
                                    @endforeach
                                </select>
                                @error('employeeJobPositionId') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div><label class="{{ $label }}">Rol empleado *</label>
                                <select wire:model="employeeScope" class="{{ $field }}">
                                    <option value="">— Seleccionar —</option>
                                    @foreach ($employeeScopes as $val => $lbl)
                                        <option value="{{ $val }}">{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                @error('employeeScope') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                            </div>
                            <div><label class="{{ $label }}">Salario base</label><input type="number" step="0.01" min="0" wire:model="baseSalary" class="{{ $field }}"></div>
                            <div class="sm:col-span-2 flex items-center gap-2 pt-1">
                                <input type="checkbox" wire:model="isActive" id="emp-active" class="rounded border-slate-300 text-indigo-600">
                                <label for="emp-active" class="text-sm font-medium text-slate-700 dark:text-slate-200">Empleado activo</label>
                            </div>
                        </div>
                    </div>
                    <div class="{{ $section }}">
                        <h3 class="{{ $sectionTitle }}">4. Emergencias</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><label class="{{ $label }}">Nombre contacto</label><input type="text" wire:model="emergencyContactName" class="{{ $field }}"></div>
                            <div><label class="{{ $label }}">Teléfono contacto</label><input type="tel" wire:model="emergencyContactPhone" class="{{ $field }}"></div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-white/10">
                        <button type="button" wire:click="closeForm" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-white/15 dark:text-slate-200">Cancelar</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700" wire:loading.attr="disabled">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Carga masiva --}}
    @if ($showBulkModal && $bulkImportRunning)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div
                wire:ignore.self
                class="w-full max-w-sm"
                x-data="bulkImportProgressDisplay(@entangle('bulkImportProgress'))"
                x-init="startPolling($wire)"
                @bulk-import-finished.window="destroy()">
                <x-employees.bulk-import-loader class="shadow-2xl" />
            </div>
        </div>
    @elseif ($showBulkModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 shadow-xl dark:bg-dash-ink dark:ring-1 dark:ring-white/15">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Carga masiva de empleados</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Suba un archivo Excel (.xlsx) con la plantilla oficial. La primera fila debe contener los encabezados.</p>
                <p class="mt-2">
                    <a href="{{ route('employees.template') }}" class="text-sm font-semibold text-indigo-600 underline dark:text-indigo-400">Descargar plantilla</a>
                </p>
                <form wire:submit="importBulk" class="mt-4 space-y-4">
                    <input
                        type="file"
                        wire:model="bulkFile"
                        accept=".xlsx"
                        class="block w-full text-sm"
                        wire:loading.attr="disabled"
                        wire:target="importBulk,bulkFile">
                    @error('bulkFile')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    @if ($bulkImportErrors !== [])
                        <ul class="max-h-40 overflow-y-auto text-xs text-red-700 dark:text-red-300 space-y-1">
                            @foreach ($bulkImportErrors as $row => $msg)
                                <li>Fila {{ $row }}: {{ $msg }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div wire:loading wire:target="bulkFile" class="text-xs text-slate-500 dark:text-slate-400">Preparando archivo…</div>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            wire:click="closeBulk"
                            wire:loading.attr="disabled"
                            wire:target="importBulk"
                            class="rounded-lg border px-4 py-2 text-sm font-semibold dark:border-white/15">
                            Cerrar
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="importBulk"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="importBulk">Importar</span>
                            <span wire:loading wire:target="importBulk">Iniciando…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
