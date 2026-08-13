@php
    $effectiveRole = $this->formEffectiveRoleLabel;
    $requiresCities = $this->requiresAuthorizedCities;
    $requiresZone = $this->requiresSupervisionZone;
    $selectedCities = count($authorizedMunicipalityCodes);
@endphp

<div
    class="flex max-h-[min(92dvh,880px)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15"
    role="dialog"
    aria-modal="true"
    aria-labelledby="user-form-title"
>
    <div class="shrink-0 border-b border-slate-200 bg-white/95 px-5 py-4 backdrop-blur-sm dark:border-white/10 dark:bg-dash-ink/95 sm:px-6">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <h2 id="user-form-title" class="text-lg font-bold text-slate-900 dark:text-white">
                    {{ $editingId ? 'Editar usuario' : 'Nuevo usuario' }}
                </h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    Área organizacional + cargo definen el perfil de permisos (nivel1–nivel9).
                </p>
            </div>
            <button type="button" wire:click="closeForm"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-slate-200"
                aria-label="Cerrar">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        @if ($effectiveRole || $requiresCities || $requiresZone)
            <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2.5 text-xs ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
                @if ($effectiveRole)
                    <p class="font-semibold text-slate-800 dark:text-slate-100">Rol efectivo: {{ $effectiveRole }}</p>
                @endif
                @if ($requiresZone)
                    <p @class(['text-slate-600 dark:text-slate-400', 'mt-1' => $effectiveRole])>
                        Zona de supervisión requerida (bandeja compartida de notificaciones).
                    </p>
                @endif
                @if ($requiresCities)
                    <p @class(['text-slate-600 dark:text-slate-400', 'mt-1' => ($effectiveRole || $requiresZone)])>
                        Ciudades autorizadas: <span class="font-semibold tabular-nums">{{ $selectedCities }}</span> seleccionadas (requerido).
                    </p>
                @endif
            </div>
        @endif
    </div>

    <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
            <section class="{{ $section }}">
                <h3 class="{{ $sectionTitle }}">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200">1</span>
                    Identidad
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-employees.form-field label="Nombre completo" required class="sm:col-span-2">
                        <input type="text" wire:model="name" class="{{ $field }}" autocomplete="name">
                        @error('name')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                    <x-employees.form-field label="Correo electrónico" required>
                        <input type="email" wire:model="email" class="{{ $field }}" autocomplete="email">
                        @error('email')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                    <x-employees.form-field label="Documento">
                        <input type="text" wire:model="documentNumber" class="{{ $field }}">
                        @error('documentNumber')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                    <x-employees.form-field label="Teléfono" class="sm:col-span-2">
                        <input type="tel" wire:model="phone" class="{{ $field }}">
                        @error('phone')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                </div>
            </section>

            <section class="{{ $section }}">
                <h3 class="{{ $sectionTitle }}">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200">2</span>
                    Organización
                </h3>
                <div class="mb-4 rounded-lg bg-slate-100/80 px-3 py-2 text-[11px] text-slate-600 ring-1 ring-slate-200 dark:bg-white/[0.04] dark:text-slate-400 dark:ring-white/10">
                    <strong class="text-slate-800 dark:text-slate-200">Área</strong> = ámbito (Jurídica, Operaciones…).
                    <strong class="text-slate-800 dark:text-slate-200">Cargo</strong> = puesto con perfil técnico en Organización.
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-employees.form-field label="Área" :required="! $assignPlatformAdmin">
                        <select wire:model.live="organizationalAreaId" class="{{ $field }}" @disabled($assignPlatformAdmin)>
                            <option value="">— Seleccionar —</option>
                            @foreach ($this->organizationalAreasList as $a)
                                <option value="{{ $a->id }}">{{ $a->name }}</option>
                            @endforeach
                        </select>
                        @error('organizationalAreaId')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                    <x-employees.form-field label="Cargo" :required="! $assignPlatformAdmin" hint="{{ $organizationalAreaId ? 'Perfil de permisos según cargo.' : 'Seleccione área primero.' }}">
                        <select wire:model.live="jobPositionId" class="{{ $field }}" @disabled(! $organizationalAreaId || $assignPlatformAdmin)>
                            <option value="">— Seleccionar —</option>
                            @foreach ($this->jobPositionsForArea as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('jobPositionId')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>

                    <div class="sm:col-span-2 rounded-lg ring-1 ring-violet-200 bg-violet-50/80 p-4 dark:bg-violet-500/10 dark:ring-violet-500/30">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="checkbox" wire:model.live="assignPlatformAdmin" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-white/25 dark:bg-transparent">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-900 dark:text-white">Administrador de la plataforma</span>
                                <span class="mt-1 block text-xs text-slate-600 dark:text-slate-400">Perfil global nivel1. Sin área ni cargo.</span>
                            </span>
                        </label>
                    </div>
                </div>
            </section>

            @if ($requiresCities || $requiresZone)
                <section class="{{ $section }}">
                    <h3 class="{{ $sectionTitle }}">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200">3</span>
                        Alcance territorial
                    </h3>
                    <div class="space-y-4">
                        @if ($requiresZone)
                            <x-employees.form-field label="Zona de supervisión" required hint="Las tareas de notificación se comparten con los supervisores de esta zona.">
                                <select wire:model="supervisionZoneId" class="{{ $field }}">
                                    <option value="">— Seleccionar —</option>
                                    @foreach ($this->supervisionZonesOptions as $zone)
                                        <option value="{{ $zone->id }}">{{ $zone->displayLabel() }}</option>
                                    @endforeach
                                </select>
                                @error('supervisionZoneId')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </x-employees.form-field>
                        @endif

                        @if ($requiresCities)
                            <x-employees.form-field label="Ciudades autorizadas" required hint="Supervisor y operador: disciplinarios de guardas en estas ciudades.">
                                <input type="search" wire:model.live.debounce.250ms="citySearch" class="{{ $field }} mb-2" placeholder="Buscar municipio o código DIVIPOLA…">
                                <div class="max-h-48 overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 dark:border-white/15 dark:bg-dash-lift">
                                    @forelse ($this->filteredMunicipalitiesForForm as $department => $municipalities)
                                        <p class="mt-2 first:mt-0 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-dash-muted">{{ $department }}</p>
                                        <div class="mt-1 grid gap-1 sm:grid-cols-2">
                                            @foreach ($municipalities as $mun)
                                                <label class="flex items-center gap-2 rounded px-1.5 py-1 text-xs text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-white/[0.04]">
                                                    <input type="checkbox" wire:model="authorizedMunicipalityCodes" value="{{ $mun['code'] }}"
                                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-white/25 dark:bg-transparent">
                                                    <span>{{ $mun['name'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @empty
                                        <p class="py-4 text-center text-xs text-slate-500">Sin municipios para «{{ $citySearch }}».</p>
                                    @endforelse
                                </div>
                                @error('authorizedMunicipalityCodes')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </x-employees.form-field>
                        @endif
                    </div>
                </section>
            @endif

            @if ($showOperationsToggles)
                <section class="{{ $section }}">
                    <h3 class="{{ $sectionTitle }}">
                        <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200">{{ $requiresCities ? '4' : '3' }}</span>
                        Permisos directos (Operaciones)
                    </h3>
                    <p class="-mt-2 mb-3 text-[11px] text-slate-500 dark:text-slate-400">Solo permisos concedidos directamente al usuario (no los del rol).</p>
                    <div class="space-y-2">
                        @foreach ($operationsPermissionLabels as $toggleKey => $label)
                            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-lg px-2 py-2 hover:bg-white/60 dark:hover:bg-white/[0.04]">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ $label }}</span>
                                <input type="checkbox" wire:key="op-{{ $toggleKey }}" wire:model.live="directPermissionToggles.{{ $toggleKey }}"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-white/25 dark:bg-transparent">
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="{{ $section }}">
                <h3 class="{{ $sectionTitle }}">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200">{{ $requiresCities ? ($showOperationsToggles ? '5' : '4') : ($showOperationsToggles ? '4' : '3') }}</span>
                    Acceso
                </h3>
                @if (! $editingId)
                    <div class="mb-4 rounded-lg bg-indigo-50 px-3 py-2.5 text-xs text-indigo-900 ring-1 ring-indigo-100 dark:bg-indigo-950/35 dark:text-indigo-200 dark:ring-indigo-500/25">
                        Se generará una <strong>contraseña provisional</strong> automáticamente al crear el usuario.
                    </div>
                @endif
                <div class="space-y-3">
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 bg-white px-3 py-2.5 dark:border-white/10 dark:bg-dash-lift">
                        <input type="checkbox" wire:model="allowChanges" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30 dark:border-white/20">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Puede realizar cambios</span>
                    </label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Desactivado = solo lectura (consulta sin mutaciones).</p>
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 bg-white px-3 py-2.5 dark:border-white/10 dark:bg-dash-lift">
                        <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30 dark:border-white/20">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Usuario activo (puede iniciar sesión)</span>
                    </label>
                </div>
            </section>
        </div>

        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur-sm dark:border-white/10 dark:bg-dash-ink/95 sm:px-6">
            <button type="button" wire:click="closeForm"
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/15 dark:text-slate-200 dark:hover:bg-white/5">
                Cancelar
            </button>
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-60">
                <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <span wire:loading.remove wire:target="save">{{ $editingId ? 'Guardar cambios' : 'Crear usuario' }}</span>
                <span wire:loading wire:target="save">Guardando…</span>
            </button>
        </div>
    </form>
</div>
