<div>
    @push('module-nav')
        <x-users.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Usuarios · Listado</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Gestión de usuarios</h1>
                <p class="text-sm text-slate-600 mt-1 dark:text-slate-300">Crea, edita y administra los usuarios del sistema y sus roles.</p>
            </div>
            @can('create', \App\Models\User::class)
                <button wire:click="openCreate"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo usuario
                </button>
            @endcan
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filtros --}}
            <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Buscador</label>
                        <input type="search" wire:model.live.debounce.350ms="search"
                            placeholder="Nombre, email, documento..."
                            class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Rol</label>
                        <select wire:model.live="role" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Todos —</option>
                            @foreach ($this->rolesList as $r)
                                <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Área</label>
                        <select wire:model.live="area" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Todas —</option>
                            @foreach ($this->areasList as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Estado</label>
                        <select wire:model.live="status" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Todos —</option>
                            <option value="activos">Activos</option>
                            <option value="inactivos">Inactivos</option>
                        </select>
                    </div>
                </div>
                @if ($search !== '' || $role !== '' || $area !== '' || $status !== '')
                    <div class="mt-3">
                        <button wire:click="clearFilters" class="text-xs text-slate-500 hover:text-slate-700 underline">
                            Limpiar filtros
                        </button>
                    </div>
                @endif
            </div>

            {{-- Tabla --}}
            <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-xs uppercase tracking-wider text-slate-500">
                                <th class="px-4 py-3 text-left font-semibold">Usuario</th>
                                <th class="px-4 py-3 text-left font-semibold">Documento</th>
                                <th class="px-4 py-3 text-left font-semibold">Rol</th>
                                <th class="px-4 py-3 text-left font-semibold">Área</th>
                                <th class="px-4 py-3 text-center font-semibold">Casos</th>
                                <th class="px-4 py-3 text-center font-semibold">Acceso</th>
                                <th class="px-4 py-3 text-center font-semibold">Estado</th>
                                <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200 text-sm dark:bg-transparent dark:divide-white/10">
                            @forelse ($users as $u)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-semibold">
                                                {{ strtoupper(substr($u->name, 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ route('users.show', $u) }}" wire:navigate class="font-medium text-slate-900 hover:text-indigo-700 truncate block">
                                                    {{ $u->name }}
                                                </a>
                                                <p class="text-xs text-slate-500 truncate">{{ $u->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $u->document_number ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @forelse ($u->roles as $r)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200 mr-1">
                                                {{ $r->name }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-400">— Sin rol —</span>
                                        @endforelse
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $u->area?->label() ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-slate-700">
                                        {{ $u->assigned_cases_count }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($u->read_only)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-800 ring-1 ring-amber-200">
                                                Solo lectura
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-50 text-slate-700 ring-1 ring-slate-200">
                                                Cambios
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($u->is_active)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 ring-1 ring-slate-200">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            @can('update', $u)
                                                <button wire:click="openEdit({{ $u->id }})" title="Editar"
                                                    class="p-1.5 rounded-md text-indigo-600 hover:bg-indigo-50 hover:text-indigo-700 ring-1 ring-transparent hover:ring-indigo-200/80 transition-colors">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                                    </svg>
                                                </button>
                                            @endcan
                                            @can('changePassword', $u)
                                                <button wire:click="openPasswordModal({{ $u->id }})" title="Cambiar contraseña"
                                                    class="p-1.5 rounded-md text-amber-600 hover:bg-amber-50 hover:text-amber-700 ring-1 ring-transparent hover:ring-amber-200/80 transition-colors">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                                    </svg>
                                                </button>
                                            @endcan
                                            @can('toggleActive', $u)
                                                <button wire:click="toggleActive({{ $u->id }})" title="{{ $u->is_active ? 'Desactivar' : 'Activar' }}"
                                                    class="p-1.5 rounded-md transition-colors ring-1 ring-transparent {{ $u->is_active ? 'text-orange-600 hover:bg-orange-50 hover:text-orange-700 hover:ring-orange-200/80' : 'text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 hover:ring-emerald-200/80' }}">
                                                    @if ($u->is_active)
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                    @else
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                    @endif
                                                </button>
                                            @endcan
                                            @can('delete', $u)
                                                <button wire:click="deleteUser({{ $u->id }})"
                                                    wire:confirm="¿Estás seguro? Esto eliminará al usuario {{ $u->name }}."
                                                    title="Eliminar"
                                                    class="p-1.5 rounded-md text-rose-600 hover:bg-rose-50 hover:text-rose-700 ring-1 ring-transparent hover:ring-rose-200/80 transition-colors">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                                        No se encontraron usuarios con los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de creación / edición --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             x-data x-on:keydown.escape.window="$wire.closeForm()">
            <div class="bg-white rounded-lg shadow-xl dark:bg-dash-ink dark:ring-1 dark:ring-white/10 dark:shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                 x-on:click.outside="$wire.closeForm()">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-white/10 flex items-center justify-between sticky top-0 z-10 bg-white dark:bg-dash-ink">
                    <h3 class="font-semibold text-slate-900 dark:text-white">
                        {{ $editingId ? 'Editar usuario' : 'Crear usuario nuevo' }}
                    </h3>
                    <button wire:click="closeForm" class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300">✕</button>
                </div>

                <form wire:submit="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre completo *</label>
                            <input type="text" wire:model="name" class="w-full rounded-md border-slate-300 shadow-sm text-sm">
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Email *</label>
                            <input type="email" wire:model="email" class="w-full rounded-md border-slate-300 shadow-sm text-sm">
                            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Documento</label>
                            <input type="text" wire:model="documentNumber" class="w-full rounded-md border-slate-300 shadow-sm text-sm">
                            @error('documentNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Teléfono</label>
                            <input type="text" wire:model="phone" class="w-full rounded-md border-slate-300 shadow-sm text-sm">
                            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Cargo</label>
                            <input type="text" wire:model="position" class="w-full rounded-md border-slate-300 shadow-sm text-sm">
                            @error('position') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Área</label>
                            <select wire:model="area_value" class="w-full rounded-md border-slate-300 shadow-sm text-sm">
                                <option value="">— Sin área —</option>
                                @foreach ($this->areasList as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('area_value') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @if (! $editingId)
                            <div class="md:col-span-2 rounded-md bg-indigo-50 ring-1 ring-indigo-100 px-3 py-2">
                                <p class="text-xs text-indigo-900 font-medium">Contraseña inicial</p>
                                <p class="text-xs text-indigo-800 mt-1">Se generará una contraseña segura automáticamente. Podrá copiarla en el siguiente paso para enviarla al usuario por un canal seguro.</p>
                            </div>
                        @endif

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-2">Roles</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach ($this->rolesList as $r)
                                    <label class="flex items-center gap-2 p-2 rounded-md ring-1 ring-slate-200 hover:bg-slate-50 cursor-pointer text-sm">
                                        <input type="checkbox" value="{{ $r }}" wire:model="userRoles" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>{{ ucfirst($r) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('userRoles') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="allowChanges" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-slate-700">Puede realizar cambios</span>
                            </label>
                            <p class="mt-1 text-xs text-slate-500 ml-7">Si está desactivado, el usuario solo puede consultar (listados y detalle); no podrá crear, editar procesos ni gestionar otros usuarios.</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-slate-700">Usuario activo (puede iniciar sesión)</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-slate-200">
                        <button type="button" wire:click="closeForm"
                            class="px-4 py-2 bg-slate-100 text-slate-700 rounded-md text-sm hover:bg-slate-200">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                            {{ $editingId ? 'Guardar cambios' : 'Crear usuario' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Contraseña provisional (solo tras crear) --}}
    @if ($showCredentialModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4"
             x-data="{ copied: false }"
             x-on:keydown.escape.window="$wire.closeCredentialModal()">
            <div class="bg-white rounded-lg shadow-xl dark:bg-dash-ink dark:ring-1 dark:ring-white/10 dark:shadow-2xl max-w-lg w-full ring-1 ring-slate-200"
                 x-on:click.outside="$wire.closeCredentialModal()">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">Contraseña provisional</h3>
                    <button type="button" wire:click="closeCredentialModal" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-slate-600">
                        Esta contraseña solo se muestra una vez. Cópiala y envíala al usuario por un medio seguro.
                        La primera vez que inicie sesión deberá definir una nueva contraseña obligatoriamente.
                    </p>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Contraseña generada</label>
                        <input type="text" readonly id="provision-password-field"
                               value="{{ $generatedPlainPassword }}"
                               onclick="this.select()"
                               class="w-full rounded-md border-slate-300 bg-slate-50 font-mono text-sm px-3 py-2">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                                x-on:click="navigator.clipboard.writeText(document.getElementById('provision-password-field').value); copied = true; setTimeout(() => copied = false, 2000)"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a2.25 2.25 0 0 1-.084.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>
                            Copiar al portapapeles
                        </button>
                        <span x-show="copied" x-cloak class="text-xs font-medium text-emerald-600 self-center">¡Copiado!</span>
                    </div>
                    <button type="button" wire:click="closeCredentialModal"
                        class="w-full px-4 py-2 bg-slate-100 text-slate-800 rounded-md text-sm font-semibold hover:bg-slate-200">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal reinicio de contraseña (generada + confirmar) --}}
    @if ($showPasswordModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             x-data="{ copied: false }"
             x-on:keydown.escape.window="$wire.closePasswordModal()">
            <div class="bg-white rounded-lg shadow-xl dark:bg-dash-ink dark:ring-1 dark:ring-white/10 dark:shadow-2xl max-w-lg w-full ring-1 ring-slate-200"
                 x-on:click.outside="$wire.closePasswordModal()">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">
                        @if ($passwordResetApplied)
                            Contraseña actualizada
                        @else
                            Reiniciar contraseña
                        @endif
                    </h3>
                    <button type="button" wire:click="closePasswordModal" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    @if (! $passwordResetApplied)
                        <p class="text-sm text-slate-600">
                            Contraseña provisional generada. Al <strong>Aceptar</strong> se guardará y el usuario deberá cambiarla en el primer ingreso.
                            Puede copiarla antes o después de confirmar.
                        </p>
                    @else
                        <p class="text-sm text-emerald-700 font-medium">
                            Cambio aplicado. Copie la contraseña y compártala por un canal seguro.
                        </p>
                    @endif
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Contraseña {{ $passwordResetApplied ? 'activa' : 'generada' }}
                        </label>
                        <input type="text" readonly id="admin-reset-password-field"
                               value="{{ $provisionalResetPassword }}"
                               onclick="this.select()"
                               class="w-full rounded-md border-slate-300 bg-slate-50 font-mono text-sm px-3 py-2">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                                x-on:click="navigator.clipboard.writeText(document.getElementById('admin-reset-password-field').value); copied = true; setTimeout(() => copied = false, 2000)"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a2.25 2.25 0 0 1-.084.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>
                            Copiar al portapapeles
                        </button>
                        <span x-show="copied" x-cloak class="text-xs font-medium text-emerald-600 self-center">¡Copiado!</span>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        @if (! $passwordResetApplied)
                            <button type="button" wire:click="closePasswordModal"
                                class="px-4 py-2 bg-slate-100 text-slate-700 rounded-md text-sm hover:bg-slate-200">
                                Cancelar
                            </button>
                            <button type="button" wire:click="confirmPasswordReset"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-60">
                                <span wire:loading.remove wire:target="confirmPasswordReset">Aceptar</span>
                                <span wire:loading wire:target="confirmPasswordReset">Guardando…</span>
                            </button>
                        @else
                            <button type="button" wire:click="closePasswordModal"
                                class="w-full px-4 py-2 bg-slate-100 text-slate-800 rounded-md text-sm font-semibold hover:bg-slate-200">
                                Cerrar
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
