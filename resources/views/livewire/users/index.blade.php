@php
    $field = 'w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-white/15 dark:bg-dash-lift dark:text-slate-100 dark:placeholder:text-slate-500';
    $section = 'rounded-xl border border-slate-200 bg-slate-50/80 p-5 dark:border-white/10 dark:bg-white/[0.03]';
    $sectionTitle = 'text-sm font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2';
    $usersReadonlyField = 'w-full rounded-lg border border-slate-300 bg-slate-50 font-mono text-sm px-3 py-2 text-slate-900 dark:border-white/15 dark:bg-dash-lift/80 dark:text-slate-100';
    $usersLabel = 'block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5 dark:text-slate-400';
    $listTargets = 'search,role,organizationalAreaFilter,status,accessFilter,perPage,setStatusFilter,applyKpiFilter,clearFilters,gotoPage,nextPage,previousPage';
    $hasFilters = $search !== '' || $role !== '' || $organizationalAreaFilter !== '' || $status !== '' || $accessFilter !== '';
    $showingFrom = $users->firstItem() ?? 0;
    $showingTo = $users->lastItem() ?? 0;
    $showingTotal = $users->total();
    $statusPills = ['' => 'Todos', 'activos' => 'Activos', 'inactivos' => 'Inactivos'];
    $kpiTotalActive = $status === '' && $role === '' && $organizationalAreaFilter === '' && $accessFilter === '';
    $kpiActiveOn = $status === 'activos' && $role === '' && $organizationalAreaFilter === '' && $accessFilter === '';
    $kpiInactiveOn = $status === 'inactivos' && $role === '' && $organizationalAreaFilter === '' && $accessFilter === '';
    $kpiReadOnlyOn = $accessFilter === 'solo_lectura';
    $kpiAdminsOn = $role === 'nivel1' && $status === '' && $organizationalAreaFilter === '' && $accessFilter === '';
    $fieldCompact = 'h-8 rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-white/15 dark:bg-dash-lift dark:text-slate-100 dark:placeholder:text-slate-500';
@endphp
<div class="users-index mx-auto flex h-[calc(100dvh-6.25rem)] max-h-[calc(100dvh-6.25rem)] w-full max-w-[1600px] flex-col overflow-hidden px-3 py-2 sm:px-5 sm:py-3 lg:px-6">
    @push('module-nav')
        <x-users.nav />
    @endpush

    <header class="mb-2 flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-2 dark:border-white/10">
        <h1 class="truncate text-lg font-bold text-slate-900 dark:text-white">Usuarios</h1>
        @can('create', \App\Models\User::class)
            <div class="flex shrink-0 flex-wrap gap-2">
                <a href="{{ route('users.organization') }}" wire:navigate
                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-white/15 dark:bg-white/10 dark:text-white dark:hover:bg-white/15">
                    Organización
                </a>
                <button type="button" wire:click="openCreate"
                    class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 text-xs font-semibold text-white transition hover:bg-indigo-700">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nuevo usuario
                </button>
            </div>
        @endcan
    </header>

    @if (session('success'))
        <div class="mb-2 shrink-0 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-500/30">{{ session('success') }}</div>
    @endif

    <section aria-label="Indicadores de usuarios" class="mb-2 grid shrink-0 grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
        <x-employees.kpi-stat wire:click="applyKpiFilter('total')" label="Total" :value="$kpiTotal" accent="cyan" :active="$kpiTotalActive" compact />
        <x-employees.kpi-stat wire:click="applyKpiFilter('activos')" label="Activos" :value="$kpiActive" accent="emerald" :active="$kpiActiveOn" compact />
        <x-employees.kpi-stat wire:click="applyKpiFilter('inactivos')" label="Inactivos" :value="$kpiInactive" accent="amber" :active="$kpiInactiveOn" compact />
        <x-employees.kpi-stat wire:click="applyKpiFilter('solo_lectura')" label="Solo lectura" :value="$kpiReadOnly" accent="indigo" :active="$kpiReadOnlyOn" compact />
        <x-employees.kpi-stat wire:click="applyKpiFilter('admins')" label="Admins" :value="$kpiAdmins" accent="fuchsia" :active="$kpiAdminsOn" compact class="col-span-2 sm:col-span-1" />
    </section>

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
        <div class="shrink-0 border-b border-slate-100 px-3 py-2 dark:border-white/10 sm:px-4">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:gap-3">
                <div class="relative min-w-0 flex-1">
                    <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar nombre, email, documento…" class="{{ $fieldCompact }} w-full pl-8" aria-label="Buscar usuarios">
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
                    <select wire:model.live="role" class="{{ $fieldCompact }} max-w-[10rem]" aria-label="Filtrar por nivel">
                        <option value="">Nivel: todos</option>
                        @foreach ($this->rolesListForFilter as $slug => $label)
                            <option value="{{ $slug }}">{{ \App\Enums\PlatformLevel::tryFrom($slug)?->title() ?? $slug }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="organizationalAreaFilter" class="{{ $fieldCompact }}" aria-label="Filtrar por área">
                        <option value="">Área: todas</option>
                        @foreach ($this->organizationalAreasList as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
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
            wire:key="usr-table-{{ $users->currentPage() }}-{{ md5($search.$role.$organizationalAreaFilter.$status.$accessFilter) }}">
            <table class="min-w-full text-xs">
                <thead class="sticky top-0 z-10 bg-slate-50/95 text-left text-[10px] font-bold uppercase tracking-[0.1em] text-slate-500 backdrop-blur-sm dark:bg-dash-ink/95 dark:text-slate-400">
                    <tr>
                        <th class="w-8 px-2 py-2" aria-label="Expandir"></th>
                        <th class="px-3 py-2">Usuario</th>
                        <th class="hidden px-3 py-2 lg:table-cell">Área / cargo</th>
                        <th class="px-3 py-2">Acceso</th>
                        <th class="w-16 px-3 py-2 text-right"></th>
                    </tr>
                </thead>
                <tbody wire:loading.remove wire:target="{{ $listTargets }}" class="divide-y divide-slate-100 dark:divide-white/10">
                    @forelse ($users as $row)
                        <tr wire:key="usr-{{ $row->id }}-main" class="group transition hover:bg-slate-50/80 dark:hover:bg-white/[0.03]">
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
                                        'bg-violet-100 text-violet-800 ring-violet-200 dark:bg-violet-500/15 dark:text-violet-200 dark:ring-violet-400/30' => $row->isPlatformAdmin(),
                                        'bg-indigo-100 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-200 dark:ring-indigo-400/30' => ! $row->isPlatformAdmin(),
                                    ])>{{ $row->initials() }}</div>
                                    <div class="min-w-0">
                                        <a href="{{ route('users.show', $row) }}" wire:navigate class="block truncate font-semibold text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">{{ $row->name }}</a>
                                        <p class="truncate text-[10px] text-slate-500 dark:text-slate-400">{{ $row->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden max-w-[14rem] px-3 py-1.5 lg:table-cell">
                                <p class="truncate font-medium text-slate-700 dark:text-slate-200">{{ $row->organizationalArea?->name ?? $row->areaDisplayLabel() ?? '—' }}</p>
                                <p class="truncate text-[10px] text-slate-500 dark:text-slate-400">
                                    @if ($row->isPlatformAdmin())
                                        <span class="font-semibold text-violet-700 dark:text-violet-300">Admin plataforma</span>
                                    @else
                                        {{ $row->cargoDisplayLabel() }}
                                    @endif
                                </p>
                            </td>
                            <td class="px-3 py-1.5">
                                <div class="flex flex-wrap items-center gap-1">
                                    @if ($row->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">Activo</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-400">Inactivo</span>
                                    @endif
                                    @if ($row->read_only)
                                        <span class="inline-flex rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-900 dark:bg-amber-500/15 dark:text-amber-200">Lectura</span>
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
                        <tr wire:key="usr-{{ $row->id }}-detail" x-show="expandedId === {{ $row->id }}" x-cloak class="bg-slate-50/80 dark:bg-white/[0.02]">
                            <td colspan="5" class="border-t border-slate-100 px-2 py-0 dark:border-white/5">
                                <x-users.row-details :user="$row" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                @if ($hasFilters || $search !== '')
                                    <p class="text-sm font-semibold text-slate-800 dark:text-white">Sin resultados</p>
                                    <button type="button" wire:click="clearFilters" class="mt-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400">Limpiar filtros</button>
                                @else
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Sin usuarios registrados.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tbody wire:loading wire:target="{{ $listTargets }}" class="divide-y divide-slate-100 dark:divide-white/10">
                    <x-users.table-skeleton :rows="min($perPage, 12)" />
                </tbody>
            </table>
        </div>

        {{-- Vista móvil --}}
        <div class="min-h-0 flex-1 overflow-auto md:hidden"
            x-data="{ expandedId: null }"
            wire:key="usr-cards-{{ $users->currentPage() }}-{{ md5($search.$role.$organizationalAreaFilter.$status.$accessFilter) }}">
            <div wire:loading.remove wire:target="{{ $listTargets }}" class="divide-y divide-slate-100 dark:divide-white/10">
                @forelse ($users as $row)
                    <article wire:key="usr-card-{{ $row->id }}">
                        <div class="flex items-center gap-1 px-2 py-2">
                            <button type="button"
                                @click="expandedId = expandedId === {{ $row->id }} ? null : {{ $row->id }}"
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded text-slate-400">
                                <svg class="h-3.5 w-3.5 transition-transform" :class="expandedId === {{ $row->id }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200">{{ $row->initials() }}</div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-slate-900 dark:text-white">{{ $row->name }}</p>
                                    <p class="truncate text-[10px] text-slate-500">{{ $row->email }}</p>
                                </div>
                                @if ($row->is_active)
                                    <span class="shrink-0 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">Activo</span>
                                @endif
                            </div>
                        </div>
                        <p class="truncate px-3 pb-1.5 text-[11px] text-slate-600 dark:text-slate-300">
                            {{ $row->organizationalArea?->name ?? $row->areaDisplayLabel() ?? '—' }} · {{ $row->cargoDisplayLabel() }}
                        </p>
                        <div x-show="expandedId === {{ $row->id }}" x-cloak class="border-t border-slate-100 dark:border-white/10">
                            <x-users.row-details :user="$row" />
                        </div>
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-xs text-slate-500">Sin resultados.</p>
                @endforelse
            </div>
            <div wire:loading wire:target="{{ $listTargets }}" class="space-y-2 p-3">
                @for ($i = 0; $i < 4; $i++)
                    <div wire:key="usr-card-skel-{{ $i }}" class="animate-pulse rounded-lg border border-slate-100 p-2 dark:border-white/10">
                        <div class="h-3 w-40 rounded bg-slate-200 dark:bg-white/10"></div>
                    </div>
                @endfor
            </div>
        </div>

        @if ($users->hasPages())
            <div class="shrink-0 border-t border-slate-100 px-3 py-1.5 dark:border-white/10 [&_.pagination]:mb-0 [&_.pagination]:text-xs">{{ $users->links() }}</div>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/60 p-3 backdrop-blur-[2px] sm:p-6" wire:keydown.escape="closeForm">
            @include('components.users.user-form-modal', [
                'editingId' => $editingId,
                'field' => $field,
                'section' => $section,
                'sectionTitle' => $sectionTitle,
                'showOperationsToggles' => $showOperationsToggles,
                'operationsPermissionLabels' => $operationsPermissionLabels,
            ])
        </div>
    @endif

    @if ($showCredentialModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-[2px]"
             x-data="{ copied: false }" wire:keydown.escape="closeCredentialModal">
            <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-white/10">
                    <h3 class="font-bold text-slate-900 dark:text-white">Contraseña provisional</h3>
                    <button type="button" wire:click="closeCredentialModal" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <div class="space-y-4 p-6">
                    <p class="text-sm text-slate-600 dark:text-slate-400">Esta contraseña solo se muestra una vez. Cópiala y envíala por un canal seguro.</p>
                    <div>
                        <label class="{{ $usersLabel }}">Contraseña generada</label>
                        <input type="text" readonly id="provision-password-field" value="{{ $generatedPlainPassword }}" onclick="this.select()" class="{{ $usersReadonlyField }}">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                            x-on:click="navigator.clipboard.writeText(document.getElementById('provision-password-field').value); copied = true; setTimeout(() => copied = false, 2000)"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Copiar al portapapeles
                        </button>
                        <span x-show="copied" x-cloak class="self-center text-xs font-medium text-emerald-600 dark:text-emerald-400">¡Copiado!</span>
                    </div>
                    <button type="button" wire:click="closeCredentialModal"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-white/15 dark:text-slate-200">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showPasswordModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-[2px]"
             x-data="{ copied: false }" wire:keydown.escape="closePasswordModal">
            <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-white/10">
                    <h3 class="font-bold text-slate-900 dark:text-white">
                        {{ $passwordResetApplied ? 'Contraseña actualizada' : 'Reiniciar contraseña' }}
                    </h3>
                    <button type="button" wire:click="closePasswordModal" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <div class="space-y-4 p-6">
                    @if (! $passwordResetApplied)
                        <p class="text-sm text-slate-600 dark:text-slate-400">Al confirmar se guardará la contraseña provisional. El usuario deberá cambiarla en el primer ingreso.</p>
                    @else
                        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">Cambio aplicado. Copie la contraseña y compártala por un canal seguro.</p>
                    @endif
                    <div>
                        <label class="{{ $usersLabel }}">Contraseña {{ $passwordResetApplied ? 'activa' : 'generada' }}</label>
                        <input type="text" readonly id="admin-reset-password-field" value="{{ $provisionalResetPassword }}" onclick="this.select()" class="{{ $usersReadonlyField }}">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                            x-on:click="navigator.clipboard.writeText(document.getElementById('admin-reset-password-field').value); copied = true; setTimeout(() => copied = false, 2000)"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Copiar al portapapeles
                        </button>
                        <span x-show="copied" x-cloak class="self-center text-xs font-medium text-emerald-600 dark:text-emerald-400">¡Copiado!</span>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-white/10">
                        @if (! $passwordResetApplied)
                            <button type="button" wire:click="closePasswordModal"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-white/15 dark:text-slate-200">
                                Cancelar
                            </button>
                            <button type="button" wire:click="confirmPasswordReset" wire:loading.attr="disabled"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                                <span wire:loading.remove wire:target="confirmPasswordReset">Aceptar</span>
                                <span wire:loading wire:target="confirmPasswordReset">Guardando…</span>
                            </button>
                        @else
                            <button type="button" wire:click="closePasswordModal"
                                class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-white/15 dark:text-slate-200">
                                Cerrar
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
