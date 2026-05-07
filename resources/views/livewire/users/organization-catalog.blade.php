@php
    $usersField = 'w-full rounded-md border border-slate-300 bg-white !text-slate-900 shadow-sm text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:!border-white/15 dark:!bg-dash-lift dark:!text-slate-100 dark:placeholder:!text-slate-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-500/25';
    $usersLabel = 'block text-xs font-semibold text-slate-600 mb-1 dark:text-slate-400';
@endphp
<div>
    @push('module-nav')
        <x-users.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Organización</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Áreas y cargos</h1>
                <p class="text-sm text-slate-600 mt-1 dark:text-slate-300">Las áreas son el ámbito organizacional. Cada cargo define el nombre del puesto y el perfil de permisos que tendrá quien lo ocupe (por ejemplo Supervisor u Operador dentro de Operaciones).</p>
            </div>
            <a href="{{ route('users.index') }}" wire:navigate class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 dark:text-cyan-400 dark:hover:text-cyan-300">
                ← Volver a usuarios
            </a>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/35 dark:text-emerald-300 dark:ring-emerald-500/25">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/35 dark:text-rose-300 dark:ring-rose-500/25">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-1 space-y-4">
                    <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card p-4">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Áreas</h2>
                            <button type="button" wire:click="startCreateArea"
                                class="text-xs font-semibold text-indigo-700 hover:text-indigo-900 dark:text-cyan-400 dark:hover:text-cyan-300">
                                Nueva
                            </button>
                        </div>
                        <ul class="space-y-1 max-h-72 overflow-y-auto">
                            @foreach ($areas as $a)
                                <li>
                                    <button type="button" wire:click="$set('selectedAreaId', {{ $a->id }})"
                                        class="w-full text-left px-3 py-2 rounded-md text-sm transition
                                            {{ $selectedAreaId === $a->id ? 'bg-indigo-50 text-indigo-900 ring-1 ring-indigo-200 dark:bg-indigo-500/15 dark:text-white dark:ring-indigo-400/40' : 'hover:bg-slate-50 dark:hover:bg-white/[0.06] text-slate-800 dark:text-slate-200' }}">
                                        <span class="font-medium">{{ $a->name }}</span>
                                        @unless ($a->is_active)
                                            <span class="ml-2 text-[10px] uppercase tracking-wide text-amber-700 dark:text-amber-400">Inactiva</span>
                                        @endunless
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card p-4 space-y-3">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ $editingAreaId ? 'Editar área' : 'Nueva área' }}</h2>
                        <div>
                            <label class="{{ $usersLabel }}">Nombre</label>
                            <input type="text" wire:model.live="areaName" class="{{ $usersField }}" maxlength="120">
                            @error('areaName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $usersLabel }}">Slug (URL / sistema)</label>
                            <input type="text" wire:model="areaSlug" class="{{ $usersField }} font-mono text-xs" maxlength="64" placeholder="ej. operaciones">
                            @error('areaSlug') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $usersLabel }}">Orden</label>
                            <input type="number" wire:model="areaSortOrder" class="{{ $usersField }}" min="0" max="65535">
                            @error('areaSortOrder') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="areaIsActive" class="rounded border-slate-300 text-indigo-600 dark:border-white/25 dark:bg-transparent">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Área activa</span>
                        </label>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <button type="button" wire:click="saveArea"
                                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                Guardar área
                            </button>
                            @if ($editingAreaId)
                                <button type="button" wire:click="deleteArea({{ $editingAreaId }})"
                                    wire:confirm="¿Eliminar esta área? No debe tener cargos ni usuarios asignados."
                                    class="inline-flex items-center justify-center rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-300 dark:hover:bg-rose-950/40">
                                    Eliminar
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-2 space-y-6">
                    @if ($selectedAreaId)
                        <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                                <div>
                                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Cargos del área</h2>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cada cargo enlaza a un perfil de permisos del sistema (uso interno). Ej.: Supervisor y Operador son cargos dentro del área Operaciones.</p>
                                </div>
                                <button type="button" wire:click="startCreatePosition"
                                    class="text-xs font-semibold text-indigo-700 hover:text-indigo-900 dark:text-cyan-400 dark:hover:text-cyan-300">
                                    + Nuevo cargo
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10 text-sm">
                                    <thead class="bg-slate-50 dark:bg-white/[0.06]">
                                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            <th class="px-3 py-2 font-semibold">Cargo</th>
                                            <th class="px-3 py-2 font-semibold">Perfil permisos</th>
                                            <th class="px-3 py-2 font-semibold">Orden</th>
                                            <th class="px-3 py-2 font-semibold">Estado</th>
                                            <th class="px-3 py-2 font-semibold text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                                        @forelse ($positions as $p)
                                            <tr>
                                                <td class="px-3 py-2 text-slate-900 dark:text-white">{{ $p->name }}</td>
                                                <td class="px-3 py-2 font-mono text-xs text-slate-600 dark:text-slate-400">{{ $p->permission_role_name ?? '—' }}</td>
                                                <td class="px-3 py-2 text-slate-600 dark:text-slate-300">{{ $p->sort_order }}</td>
                                                <td class="px-3 py-2">
                                                    @if ($p->is_active)
                                                        <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Activo</span>
                                                    @else
                                                        <span class="text-xs font-medium text-slate-500">Inactivo</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                                    <button type="button" wire:click="editPosition({{ $p->id }})" class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold dark:text-cyan-400">Editar</button>
                                                    <button type="button" wire:click="deletePosition({{ $p->id }})"
                                                        wire:confirm="¿Eliminar este cargo?"
                                                        class="ml-3 text-rose-600 hover:text-rose-800 text-xs font-semibold">Eliminar</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-3 py-8 text-center text-slate-500 dark:text-slate-400">No hay cargos en esta área.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-200 dark:border-white/10 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2 space-y-3">
                                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-dash-muted">{{ $editingPositionId ? 'Editar cargo' : 'Nuevo cargo' }}</h3>
                                    <div>
                                        <label class="{{ $usersLabel }}">Nombre del cargo</label>
                                        <input type="text" wire:model="positionName" class="{{ $usersField }}" maxlength="160">
                                        @error('positionName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="{{ $usersLabel }}">Perfil de permisos (técnico)</label>
                                        <select wire:model="positionPermissionRole" class="{{ $usersField }}">
                                            <option value="">— Elija —</option>
                                            @foreach ($this->permissionRoleNameOptions as $rn)
                                                <option value="{{ $rn }}">{{ ucfirst($rn) }}</option>
                                            @endforeach
                                        </select>
                                        @error('positionPermissionRole') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-500">Define qué puede hacer en la app quien tenga este cargo; debe coincidir con un perfil existente en el sistema.</p>
                                    </div>
                                    <div>
                                        <label class="{{ $usersLabel }}">Orden</label>
                                        <input type="number" wire:model="positionSortOrder" class="{{ $usersField }}" min="0" max="65535">
                                        @error('positionSortOrder') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" wire:model="positionIsActive" class="rounded border-slate-300 text-indigo-600 dark:border-white/25 dark:bg-transparent">
                                        <span class="text-sm text-slate-700 dark:text-slate-300">Cargo activo</span>
                                    </label>
                                </div>
                                <div class="flex items-end">
                                    <button type="button" wire:click="savePosition"
                                        class="w-full inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                        Guardar cargo
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg bg-slate-50 ring-1 ring-slate-200 px-6 py-12 text-center text-sm text-slate-600 dark:bg-white/[0.04] dark:ring-white/10 dark:text-slate-400">
                            Cree un área para definir cargos.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
