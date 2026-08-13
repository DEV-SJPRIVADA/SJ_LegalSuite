@push('module-nav')
    <x-settings.nav active="zones" />
@endpush

<div class="mx-auto flex min-h-[calc(100dvh-3.25rem)] w-full max-w-5xl flex-col px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ajustes · Supervisión de campo</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Zonas de supervisión</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-400">
                Catálogo de bandejas compartidas. Planeación asigna notificaciones a una zona;
                cualquier supervisor miembro puede cargar la evidencia. El correo es el del celular corporativo (avisos).
            </p>
        </div>
        <button type="button" wire:click="openCreate"
            class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            + Nueva zona
        </button>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/30 dark:text-emerald-100">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-500/30 dark:bg-red-950/30 dark:text-red-100">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/[0.04]">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-white/10">
            <thead class="bg-slate-50 dark:bg-white/[0.03]">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Zona</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Correo celular</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Supervisores</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Estado</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-200">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                @forelse ($zones as $zone)
                    <tr wire:key="zone-{{ $zone->id }}">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900 dark:text-white">{{ $zone->name }}</p>
                            @if ($zone->code)
                                <p class="font-mono text-xs text-slate-500">{{ $zone->code }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                            {{ $zone->notification_email ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                            @if ($zone->users->isEmpty())
                                <span class="text-slate-400">Sin miembros</span>
                            @else
                                <ul class="space-y-0.5">
                                    @foreach ($zone->users as $member)
                                        <li>{{ $member->name }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($zone->is_active)
                                <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">Activa</span>
                            @else
                                <span class="rounded bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-white/10 dark:text-slate-300">Inactiva</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <button type="button" wire:click="openEdit({{ $zone->id }})"
                                    class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-200 dark:bg-white/10 dark:text-white dark:hover:bg-white/15">
                                    Editar
                                </button>
                                <button type="button"
                                    wire:click="deleteZone({{ $zone->id }})"
                                    wire:confirm="¿Eliminar la zona «{{ $zone->name }}»? Solo si no tiene supervisores ni casos asignados."
                                    class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-200 dark:hover:bg-red-900/40">
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            Aún no hay zonas. Cree la primera para asignar supervisores y notificaciones.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" wire:keydown.escape.window="closeForm">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-dash-ink">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ $editingId ? 'Editar zona' : 'Nueva zona de supervisión' }}
                    </h2>
                    <button type="button" wire:click="closeForm" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">✕</button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Nombre</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-white/15 dark:bg-white/5 dark:text-white" />
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Código (opcional)</label>
                        <input type="text" wire:model="code" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-white/15 dark:bg-white/5 dark:text-white" />
                        @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Correo del celular corporativo</label>
                        <input type="email" wire:model="notificationEmail" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-white/15 dark:bg-white/5 dark:text-white" placeholder="zona.norte@empresa.com" />
                        @error('notificationEmail')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="zone-active-settings" type="checkbox" wire:model="isActive" class="rounded border-slate-300" />
                        <label for="zone-active-settings" class="text-sm text-slate-700 dark:text-slate-300">Zona activa</label>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Orden</label>
                        <input type="number" wire:model="sortOrder" min="0" class="w-32 rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-white/15 dark:bg-white/5 dark:text-white" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="closeForm" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10">Cancelar</button>
                    <button type="button" wire:click="save" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Guardar</button>
                </div>
            </div>
        </div>
    @endif
</div>
