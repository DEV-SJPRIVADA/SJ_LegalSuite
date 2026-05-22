@php
    $field = 'w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-white/15 dark:bg-dash-lift dark:text-slate-100 dark:placeholder:text-slate-500';
    $label = 'block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5 dark:text-slate-400';
    $section = 'rounded-xl border border-slate-200 bg-slate-50/80 p-5 dark:border-white/10 dark:bg-white/[0.03]';
    $sectionTitle = 'text-sm font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2';
@endphp
<div>
    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Recursos humanos</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">BD DE EMPLEADOS SJ</h1>
            </div>
            @can('create', \App\Models\Employee::class)
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="openBulk"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-white/15 dark:bg-white/10 dark:text-white">
                        Carga masiva
                    </button>
                    <button type="button" wire:click="openCreate"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Crear empleado
                    </button>
                </div>
            @endcan
        </div>
    </div>

    <div class="py-6 sm:py-8 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4 dark:bg-white/[0.04] dark:ring-white/10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-2">
                    <label class="{{ $label }}">Buscar</label>
                    <input type="search" wire:model.live.debounce.350ms="search" placeholder="Nombre, documento, email, cargo…" class="{{ $field }}">
                </div>
                <div>
                    <label class="{{ $label }}">Estado</label>
                    <select wire:model.live="status" class="{{ $field }}">
                        <option value="">Todos</option>
                        <option value="activos">Activos</option>
                        <option value="inactivos">Inactivos</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-white/5 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Documento</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Cargo</th>
                        <th class="px-4 py-3">Ciudad</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                    @forelse ($employees as $row)
                        <tr wire:key="emp-{{ $row->id }}" class="hover:bg-slate-50/80 dark:hover:bg-white/[0.03]">
                            <td class="px-4 py-3 font-mono text-xs">{{ $row->document_type?->value }} {{ $row->document_number }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $row->full_name }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $row->job_title ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $row->municipality?->municipality_name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($row->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">Activo</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-400">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('update', $row)
                                    <button type="button" wire:click="openEdit({{ $row->id }})" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Editar</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Sin empleados. Use crear o carga masiva.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-slate-100 dark:border-white/10">{{ $employees->links() }}</div>
        </div>
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
                                <label class="{{ $label }}">Ciudad / municipio (DIVIPOLA)</label>
                                <select wire:model="municipalityCode" class="{{ $field }}">
                                    <option value="">— Seleccionar —</option>
                                    @foreach ($this->municipalitiesGrouped as $dept => $rows)
                                        <optgroup label="{{ $dept }}">
                                            @foreach ($rows as $mun)
                                                <option value="{{ $mun['code'] }}">{{ $mun['name'] }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div><label class="{{ $label }}">Teléfono celular</label><input type="tel" wire:model="phone" class="{{ $field }}"></div>
                            <div class="sm:col-span-2"><label class="{{ $label }}">Correo electrónico</label><input type="email" wire:model="email" class="{{ $field }}">@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        </div>
                    </div>
                    <div class="{{ $section }}" x-data="{ contract: @entangle('contractType') }">
                        <h3 class="{{ $sectionTitle }}">3. Información laboral</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><label class="{{ $label }}">Fecha de ingreso</label><input type="date" wire:model="hiredAt" class="{{ $field }}"></div>
                            <div><label class="{{ $label }}">Tipo de contrato</label><select wire:model.live="contractType" class="{{ $field }}"><option value="">—</option>@foreach ($contractTypes as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach</select></div>
                            <div><label class="{{ $label }}">Cargo / puesto</label><input type="text" wire:model="jobTitle" class="{{ $field }}"></div>
                            <div><label class="{{ $label }}">Área o departamento</label><input type="text" wire:model="departmentArea" class="{{ $field }}"></div>
                            <div><label class="{{ $label }}">Salario base</label><input type="number" step="0.01" min="0" wire:model="baseSalary" class="{{ $field }}"></div>
                            <div x-show="contract === 'termino_fijo'" x-cloak>
                                <label class="{{ $label }}">Fecha de terminación</label>
                                <input type="date" wire:model="terminationAt" class="{{ $field }}">
                            </div>
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
    @if ($showBulkModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-dash-ink dark:ring-1 dark:ring-white/15">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Carga masiva de empleados</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Suba un archivo Excel (.xlsx) con la plantilla oficial. La primera fila debe contener los encabezados.</p>
                <p class="mt-2">
                    <a href="{{ route('employees.template') }}" class="text-sm font-semibold text-indigo-600 underline dark:text-indigo-400">Descargar plantilla</a>
                </p>
                <form
                    wire:submit="importBulk"
                    class="mt-4 space-y-4"
                    x-data="window.bulkImportElapsedTimer()"
                    x-on:submit="start()">
                    <input type="file" wire:model="bulkFile" accept=".xlsx" class="block w-full text-sm" wire:loading.attr="disabled" wire:target="importBulk,bulkFile">
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
                        <button type="button" wire:click="closeBulk" wire:loading.attr="disabled" wire:target="importBulk" class="rounded-lg border px-4 py-2 text-sm font-semibold dark:border-white/15">Cerrar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="importBulk" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="importBulk">Importar</span>
                            <span wire:loading wire:target="importBulk">Importando…</span>
                        </button>
                    </div>

                    <div wire:loading wire:target="importBulk">
                        <x-employees.bulk-import-loader />
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
