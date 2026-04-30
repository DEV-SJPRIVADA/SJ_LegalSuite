<div>
    @push('module-nav')
        <x-users.nav />
    @endpush

    <div class="bg-white border-b border-slate-200">
        <div class="max-w-[1600px] mx-auto py-5 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl font-bold flex-shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <a href="{{ route('users.index') }}" wire:navigate class="text-xs text-slate-500 hover:text-slate-700">← Volver al listado</a>
                        <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold mt-1">Usuarios · Detalle</p>
                        <h1 class="font-bold text-2xl text-slate-900 leading-tight">{{ $user->name }}</h1>
                        <p class="text-sm text-slate-600 mt-0.5">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($user->is_active)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Activo
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium bg-slate-100 text-slate-600 ring-1 ring-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                            Inactivo
                        </span>
                    @endif

                    @if ($user->read_only)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium bg-amber-50 text-amber-800 ring-1 ring-amber-200">
                            Solo lectura
                        </span>
                    @endif

                    @can('changePassword', $user)
                        <button wire:click="openPasswordModal"
                                class="inline-flex items-center gap-2 rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                            </svg>
                            Cambiar contraseña
                        </button>
                    @endcan

                    @can('toggleActive', $user)
                        <button wire:click="toggleActive"
                                class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium
                                    {{ $user->is_active ? 'bg-amber-50 text-amber-700 hover:bg-amber-100 ring-1 ring-amber-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 ring-1 ring-emerald-200' }}">
                            {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Datos personales --}}
                <div class="bg-white rounded-lg shadow-sm ring-1 ring-slate-200 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Datos personales</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Nombre</dt>
                            <dd class="text-slate-900 font-medium">{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Email</dt>
                            <dd class="text-slate-900">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Documento</dt>
                            <dd class="text-slate-900">{{ $user->document_number ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Teléfono</dt>
                            <dd class="text-slate-900">{{ $user->phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Cargo</dt>
                            <dd class="text-slate-900">{{ $user->position ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Área</dt>
                            <dd class="text-slate-900">{{ $user->area?->label() ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Roles y permisos --}}
                <div class="bg-white rounded-lg shadow-sm ring-1 ring-slate-200 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Roles y permisos</h3>

                    <div>
                        <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold mb-2">Roles asignados</p>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($user->roles as $r)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200">
                                    {{ ucfirst($r->name) }}
                                </span>
                            @empty
                                <span class="text-xs text-slate-400">Sin roles asignados</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-5">
                        <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold mb-2">Permisos efectivos</p>
                        @php $perms = $user->getAllPermissions(); @endphp
                        @if ($perms->isEmpty())
                            <p class="text-xs text-slate-400">Sin permisos.</p>
                        @else
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($perms as $p)
                                    <code class="text-[11px] bg-slate-100 text-slate-700 rounded px-1.5 py-0.5">{{ $p->name }}</code>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Estadísticas --}}
                <div class="bg-white rounded-lg shadow-sm ring-1 ring-slate-200 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Actividad</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-600">Casos asignados (abogado)</dt>
                            <dd class="font-bold text-2xl text-indigo-700">{{ $user->assignedCases()->count() }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-600">Casos reportados</dt>
                            <dd class="font-bold text-2xl text-slate-700">{{ $user->reportedCases()->count() }}</dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                            <dt class="text-slate-600">Cuenta creada</dt>
                            <dd class="text-slate-700">{{ $user->created_at?->locale('es')->translatedFormat('d M Y') }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-600">Última actualización</dt>
                            <dd class="text-slate-700">{{ $user->updated_at?->diffForHumans() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Casos asignados (si tiene) --}}
            @if ($user->assignedCases->isNotEmpty())
                <div class="bg-white rounded-lg shadow-sm ring-1 ring-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-sm font-semibold text-slate-700">Últimos 10 casos asignados</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-xs uppercase tracking-wider text-slate-500">
                                    <th class="px-4 py-3 text-left font-semibold">N° Caso</th>
                                    <th class="px-4 py-3 text-left font-semibold">Disciplinado</th>
                                    <th class="px-4 py-3 text-left font-semibold">Estado</th>
                                    <th class="px-4 py-3 text-left font-semibold">Apertura</th>
                                    <th class="px-4 py-3 text-right font-semibold">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200 text-sm">
                                @foreach ($user->assignedCases as $c)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-mono text-xs">{{ $c->case_number }}</td>
                                        <td class="px-4 py-3">
                                            {{ $c->personnel?->first_name }} {{ $c->personnel?->last_name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-disciplinary.status-badge :status="$c->current_status" />
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $c->opened_at?->format('Y-m-d') }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('disciplinary.cases.show', $c) }}" wire:navigate
                                               class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                                Ver →
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal reinicio de contraseña (generada + confirmar) --}}
    @if ($showPasswordModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             x-data="{ copied: false }"
             x-on:keydown.escape.window="$wire.closePasswordModal()">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full ring-1 ring-slate-200"
                 x-on:click.outside="$wire.closePasswordModal()">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">
                        @if ($passwordResetApplied)
                            Contraseña actualizada · {{ $user->name }}
                        @else
                            Reiniciar contraseña · {{ $user->name }}
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
                        <input type="text" readonly id="user-detail-reset-password-field"
                               value="{{ $provisionalResetPassword }}"
                               onclick="this.select()"
                               class="w-full rounded-md border-slate-300 bg-slate-50 font-mono text-sm px-3 py-2">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                                x-on:click="navigator.clipboard.writeText(document.getElementById('user-detail-reset-password-field').value); copied = true; setTimeout(() => copied = false, 2000)"
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
