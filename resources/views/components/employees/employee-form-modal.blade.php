@php
    $formProfileComplete = $this->formProfileComplete;
    $formProfileIssues = $this->formProfileIssues;
    $selectedJobPosition = $this->selectedJobPosition;
    $isGuarda = (bool) ($selectedJobPosition?->is_guarda ?? false);
@endphp

<div
    class="flex max-h-[min(92dvh,880px)] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15"
    role="dialog"
    aria-modal="true"
    aria-labelledby="employee-form-title"
>
    {{-- Header sticky --}}
    <div class="shrink-0 border-b border-slate-200 bg-white/95 px-5 py-4 backdrop-blur-sm dark:border-white/10 dark:bg-dash-ink/95 sm:px-6">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <h2 id="employee-form-title" class="text-lg font-bold text-slate-900 dark:text-white">
                    {{ $editingId ? 'Editar empleado' : 'Nuevo empleado' }}
                </h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    Los campos con <span class="font-semibold text-red-500">*</span> son obligatorios para guardar.
                </p>
            </div>
            <button
                type="button"
                wire:click="closeForm"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-slate-200"
                aria-label="Cerrar formulario"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Estado perfil --}}
        <div
            wire:key="profile-status-{{ md5(implode('|', $formProfileIssues).($formProfileComplete ? '1' : '0')) }}"
            @class([
                'mt-3 flex flex-wrap items-start gap-2 rounded-xl px-3 py-2.5 text-xs ring-1 ring-inset',
                'bg-emerald-50 text-emerald-900 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-100 dark:ring-emerald-500/30' => $formProfileComplete,
                'bg-amber-50 text-amber-950 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-100 dark:ring-amber-500/30' => ! $formProfileComplete,
            ])
        >
            @if ($formProfileComplete)
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-semibold">Perfil completo</p>
                    <p class="mt-0.5 text-emerald-800/80 dark:text-emerald-200/80">Listo para procesos disciplinarios y reportes.</p>
                </div>
            @else
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div class="min-w-0">
                    <p class="font-semibold">Perfil incompleto</p>
                    <p class="mt-0.5 text-amber-900/80 dark:text-amber-100/80">Faltan: {{ implode(', ', $formProfileIssues) }}.</p>
                </div>
            @endif
        </div>
    </div>

    <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
            {{-- 1. Identificación --}}
            <section class="{{ $section }}">
                <h3 class="{{ $sectionTitle }}">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200">1</span>
                    Datos personales e identificación
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-employees.form-field label="Nombre completo" required class="sm:col-span-2">
                        <input type="text" wire:model="fullName" class="{{ $field }}" placeholder="Ej. Juan Carlos Pérez López" autocomplete="name">
                        @error('fullName')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                    <x-employees.form-field label="Tipo de documento" required>
                        <select wire:model="documentType" class="{{ $field }}">
                            @foreach ($documentTypes as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </x-employees.form-field>
                    <x-employees.form-field label="Número de documento" required>
                        <input type="text" wire:model.live="documentNumber" class="{{ $field }}" inputmode="numeric" pattern="[0-9]*" autocomplete="off" placeholder="Solo números">
                        @error('documentNumber')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                    <x-employees.form-field label="Fecha de nacimiento">
                        <input type="date" wire:model="birthDate" class="{{ $field }}">
                    </x-employees.form-field>
                    <x-employees.form-field label="Género">
                        <select wire:model="gender" class="{{ $field }}">
                            <option value="">—</option>
                            @foreach ($genders as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </x-employees.form-field>
                </div>
            </section>

            {{-- 2. Contacto y territorio --}}
            <section class="{{ $section }}">
                <h3 class="{{ $sectionTitle }}">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200">2</span>
                    Contacto y territorio
                </h3>
                <p class="-mt-2 mb-4 text-[11px] text-slate-500 dark:text-slate-400">
                    Indique al menos departamento o municipio en residencia y labor. Coincide con columnas Excel «Ciudad de residencia» y «Ciudad de labor».
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-employees.form-field label="Dirección de residencia" class="sm:col-span-2">
                        <input type="text" wire:model="address" class="{{ $field }}">
                    </x-employees.form-field>
                    <x-employees.form-field label="Departamento de residencia" required hint="Obligatorio si no selecciona municipio.">
                        <select wire:model.live="residenceDepartmentCode" class="{{ $field }}">
                            <option value="">— Seleccionar —</option>
                            @foreach ($this->departments as $dept)
                                <option value="{{ $dept['code'] }}">{{ $dept['name'] }}</option>
                            @endforeach
                        </select>
                        @error('residenceDepartmentCode')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                    <x-employees.form-field label="Municipio de residencia" hint="Opcional si ya indicó departamento.">
                        <select wire:model.live="residenceMunicipalityCode" class="{{ $field }}">
                            <option value="">— Opcional —</option>
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
                    </x-employees.form-field>
                    <x-employees.form-field label="Departamento de labor" required hint="Obligatorio si no selecciona municipio.">
                        <select wire:model.live="workDepartmentCode" class="{{ $field }}">
                            <option value="">— Seleccionar —</option>
                            @foreach ($this->departments as $dept)
                                <option value="{{ $dept['code'] }}">{{ $dept['name'] }}</option>
                            @endforeach
                        </select>
                        @error('workDepartmentCode')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                    <x-employees.form-field
                        label="Municipio de labor"
                        :required="$isGuarda"
                        :hint="$isGuarda ? 'Obligatorio para cargos de guarda.' : 'Opcional si ya indicó departamento.'"
                    >
                        <select wire:model.live="municipalityCode" @class([$field, 'ring-2 ring-amber-400/50 dark:ring-amber-500/40' => $isGuarda && $municipalityCode === ''])>
                            <option value="">— {{ $isGuarda ? 'Requerido para guarda' : 'Opcional' }} —</option>
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
                    </x-employees.form-field>
                    <x-employees.form-field label="Teléfono celular" hint="Use S/I, NN o NA si no aplica (se guardará vacío).">
                        <input type="tel" wire:model="phone" class="{{ $field }}" placeholder="Ej. 3001234567">
                    </x-employees.form-field>
                    <x-employees.form-field label="Correo electrónico" class="sm:col-span-2" hint="Use S/I o NA si no aplica.">
                        <input type="email" wire:model="email" class="{{ $field }}" placeholder="correo@ejemplo.com">
                        @error('email')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                </div>
            </section>

            {{-- 3. Laboral --}}
            <section class="{{ $section }}">
                <h3 class="{{ $sectionTitle }}">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200">3</span>
                    Información laboral
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-employees.form-field label="Fecha de ingreso" required>
                        <input type="date" wire:model.live="hiredAt" class="{{ $field }}">
                        @error('hiredAt')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                    <x-employees.form-field label="Tipo de contrato" required>
                        <select wire:model.live="contractType" class="{{ $field }}">
                            <option value="">— Seleccionar —</option>
                            @foreach ($contractTypes as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                        @error('contractType')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                    <x-employees.form-field
                        label="Rol empleado"
                        required
                        hint="Operativo o Administrativo. Equivale a la columna Excel «Área o departamento»."
                    >
                        <select wire:model.live="employeeScope" class="{{ $field }}">
                            <option value="">— Seleccionar primero —</option>
                            @foreach ($employeeScopes as $val => $lbl)
                                <option value="{{ $val }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                        @error('employeeScope')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>
                    <x-employees.form-field label="Cargo / puesto" required hint="{{ $employeeScope === '' ? 'Seleccione el rol para filtrar cargos.' : 'Catálogo filtrado por rol.' }}">
                        <select wire:model.live="employeeJobPositionId" class="{{ $field }}" @disabled($employeeScope === '')>
                            <option value="">{{ $employeeScope === '' ? '— Seleccione rol primero —' : '— Seleccione cargo —' }}</option>
                            @foreach ($this->employeeJobPositions as $position)
                                <option value="{{ $position->id }}">
                                    {{ $position->name }}@if ($position->is_guarda) (Guarda)@endif
                                </option>
                            @endforeach
                        </select>
                        @error('employeeJobPositionId')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </x-employees.form-field>

                    @if ($isGuarda)
                        <div class="sm:col-span-2 flex items-start gap-2 rounded-lg bg-amber-50 px-3 py-2.5 text-xs text-amber-950 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-100 dark:ring-amber-500/30">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p>
                                <span class="font-semibold">Cargo de guarda:</span>
                                debe indicar <strong>municipio de labor</strong> (no basta con departamento) para perfil completo y mapas disciplinarios.
                            </p>
                        </div>
                    @endif

                    <x-employees.form-field label="Salario base">
                        <input type="number" step="0.01" min="0" wire:model="baseSalary" class="{{ $field }}" placeholder="0.00">
                    </x-employees.form-field>
                    <div class="flex items-center gap-2.5 self-end rounded-lg border border-slate-200 bg-white px-3 py-2.5 dark:border-white/10 dark:bg-dash-lift">
                        <input type="checkbox" wire:model="isActive" id="emp-active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30 dark:border-white/20">
                        <label for="emp-active" class="text-sm font-medium text-slate-700 dark:text-slate-200">Empleado activo</label>
                    </div>
                </div>
            </section>

            {{-- 4. Emergencias --}}
            <section class="{{ $section }}">
                <h3 class="{{ $sectionTitle }}">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-[11px] font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-200">4</span>
                    Contacto de emergencia
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-employees.form-field label="Nombre contacto" hint="S/I o NN si no aplica.">
                        <input type="text" wire:model="emergencyContactName" class="{{ $field }}">
                    </x-employees.form-field>
                    <x-employees.form-field label="Teléfono contacto" hint="S/I, NN o NA si no aplica.">
                        <input type="tel" wire:model="emergencyContactPhone" class="{{ $field }}">
                    </x-employees.form-field>
                </div>
            </section>
        </div>

        {{-- Footer sticky --}}
        <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur-sm dark:border-white/10 dark:bg-dash-ink/95 sm:px-6">
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                @if ($formProfileComplete)
                    <span class="font-semibold text-emerald-700 dark:text-emerald-300">Perfil completo</span> al guardar.
                @else
                    Puede guardar borrador; complete {{ count($formProfileIssues) }} {{ count($formProfileIssues) === 1 ? 'campo' : 'campos' }} para perfil operativo.
                @endif
            </p>
            <div class="flex gap-2">
                <button type="button" wire:click="closeForm" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/15 dark:text-slate-200 dark:hover:bg-white/5">
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-60"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span wire:loading.remove wire:target="save">Guardar empleado</span>
                    <span wire:loading wire:target="save">Guardando…</span>
                </button>
            </div>
        </div>
    </form>
</div>
