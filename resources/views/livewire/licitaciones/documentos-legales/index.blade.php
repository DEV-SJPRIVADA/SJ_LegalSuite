<div>
    @push('module-nav')
        <x-licitaciones.nav />
    @endpush

    <div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-sj-orange">Licitaciones · Documentos legales</p>
                <h1 class="mt-1 text-xl font-bold text-sj-blue dark:text-white">Carpetas por área (MT-GJ-06)</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Cada director solo ve su carpeta y puede subir/reemplazar archivos. El administrador crea carpetas, define fechas y la frecuencia de los correos.
                </p>
            </div>
            @if ($canCreateFolder)
                <button type="button" wire:click="openCreateFolder" class="sj-btn sj-btn--accent shrink-0">
                    + Nueva carpeta
                </button>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30">
                {{ session('success') }}
            </div>
        @endif

        @if ($canCreateFolder && $showCreateFolder)
            <div class="rounded-xl bg-white p-5 ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
                <h2 class="font-semibold text-sj-blue dark:text-white">Nueva carpeta / área</h2>
                <p class="mt-1 text-xs text-slate-500">Crea un espacio para otro departamento. Los documentos se agregan dentro de la carpeta.</p>
                <form wire:submit="createFolder" class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre del área</label>
                        <input type="text" wire:model="nuevaCarpetaNombre" class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-white/15 dark:bg-dash-ink" placeholder="Ej. Jurídica, Compras, Talento humano…">
                        @error('nuevaCarpetaNombre')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex cursor-pointer items-start gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model="nuevaCarpetaSinRecordatorios" class="mt-0.5 rounded border-slate-300 text-indigo-600">
                            <span>Sin recordatorios por correo (p. ej. área jurídica gestionada a mano)</span>
                        </label>
                    </div>
                    <div class="sm:col-span-2 flex flex-wrap gap-2">
                        <button type="submit" class="sj-btn sj-btn--primary">Crear carpeta</button>
                        <button type="button" wire:click="cancelCreateFolder" class="sj-btn sj-btn--ghost">Cancelar</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($folders as $folder)
                @php
                    $busqueda = (string) ($directorBusqueda[$folder->id] ?? '');
                    $resultados = $resultadosPorCarpeta[$folder->id] ?? collect();
                    $people = collect($assign[$folder->id] ?? []);
                    $director = $people->firstWhere('is_primary', true) ?? $people->first();
                    $extras = $people->reject(fn ($p) => ($p['email'] ?? '') === ($director['email'] ?? null))->values();
                @endphp
                <div class="rounded-xl bg-white p-5 ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 flex flex-col gap-3" wire:key="folder-{{ $folder->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h2 class="font-semibold text-sj-blue dark:text-white">{{ $folder->name }}</h2>
                            <p class="text-xs text-slate-500">{{ $folder->slug }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            @if ($folder->exclude_reminders)
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:bg-white/10 dark:text-slate-300">Sin recordatorio</span>
                            @endif
                            @if ($canDeleteFolder)
                                <button
                                    type="button"
                                    wire:click="deleteFolder({{ $folder->id }})"
                                    wire:confirm="¿Eliminar la carpeta «{{ $folder->name }}»? Dejará de mostrarse en el listado."
                                    class="text-[11px] font-semibold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    Eliminar
                                </button>
                            @endif
                        </div>
                    </div>

                    <dl class="grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="rounded-lg bg-slate-50 p-2 dark:bg-white/5">
                            <dt class="text-slate-500">Docs</dt>
                            <dd class="text-lg font-bold tabular-nums text-sj-blue dark:text-white">{{ $folder->items_total }}</dd>
                        </div>
                        <div class="rounded-lg bg-sj-orange/10 p-2">
                            <dt class="text-slate-500">Por renovar</dt>
                            <dd class="text-lg font-bold tabular-nums text-sj-orange">{{ $folder->items_due }}</dd>
                        </div>
                        <div class="rounded-lg bg-emerald-50 p-2 dark:bg-emerald-500/10">
                            <dt class="text-slate-500">Con archivo</dt>
                            <dd class="text-lg font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ $folder->items_with_file }}</dd>
                        </div>
                    </dl>

                    <div class="text-xs text-slate-500 space-y-1">
                        <p>
                            Director:
                            @if ($director)
                                <span class="font-medium text-slate-700 dark:text-slate-200">{{ ($director['name'] ?? '') !== '' ? $director['name'] : $director['email'] }}</span>
                                <span class="block truncate">{{ $director['email'] }}</span>
                            @else
                                <span class="text-amber-700 dark:text-amber-300">sin asignar</span>
                            @endif
                        </p>
                        @if ($extras->isNotEmpty())
                            <p>
                                Otros responsables:
                                <span class="font-medium text-slate-700 dark:text-slate-200">
                                    {{ $extras->map(fn ($p) => ($p['name'] ?? '') !== '' ? $p['name'] : $p['email'])->implode(', ') }}
                                </span>
                            </p>
                        @endif
                    </div>
                    @unless ($folder->exclude_reminders)
                        <p class="text-[11px] text-slate-500">Recordatorio: <span class="font-semibold text-sj-blue dark:text-sj-orange">{{ $folder->reminderIntervalLabel() }}</span></p>
                    @endunless

                    @if ($canAssign)
                        <div class="space-y-2 border-t border-slate-100 pt-3 dark:border-white/10">
                            <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Buscar y agregar responsables</label>
                            <div class="relative">
                                <input
                                    type="search"
                                    wire:model.live.debounce.250ms="directorBusqueda.{{ $folder->id }}"
                                    class="w-full rounded-lg border-slate-300 text-sm dark:border-white/15 dark:bg-dash-ink"
                                    placeholder="Nombre o correo (directorio o usuario del sistema)…"
                                    autocomplete="off"
                                >
                                @if (mb_strlen(trim($busqueda)) >= 2)
                                    <ul class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg dark:border-white/15 dark:bg-dash-ink">
                                        @forelse ($resultados as $person)
                                            <li>
                                                <button
                                                    type="button"
                                                    wire:click="seleccionarDirector({{ $folder->id }}, @js($person['email']), @js($person['name']))"
                                                    class="flex w-full flex-col px-3 py-2 text-left text-sm hover:bg-sj-orange/10"
                                                >
                                                    <span class="font-medium text-sj-blue dark:text-white">{{ $person['name'] }}</span>
                                                    <span class="text-xs text-slate-500">{{ $person['email'] }}</span>
                                                </button>
                                            </li>
                                        @empty
                                            <li class="px-3 py-2 text-xs text-slate-500">Sin coincidencias en directorio ni usuarios del sistema.</li>
                                        @endforelse
                                    </ul>
                                @endif
                            </div>

                            @forelse ($people as $person)
                                <div class="flex items-center justify-between gap-2 rounded-lg bg-sj-orange/10 px-3 py-2 text-xs" wire:key="resp-{{ $folder->id }}-{{ $person['email'] }}">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-sj-blue dark:text-sj-orange">
                                            {{ ($person['name'] ?? '') !== '' ? $person['name'] : $person['email'] }}
                                            @if (! empty($person['is_primary']))
                                                <span class="ml-1 rounded bg-sj-blue/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sj-blue dark:bg-sj-orange/20 dark:text-sj-orange">Director</span>
                                            @endif
                                        </p>
                                        <p class="truncate text-slate-500">{{ $person['email'] }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        @if (empty($person['is_primary']))
                                            <button type="button" wire:click="marcarDirector({{ $folder->id }}, @js($person['email']))" class="font-semibold text-sj-blue hover:underline dark:text-sj-orange">Director</button>
                                        @endif
                                        <button type="button" wire:click="quitarDirector({{ $folder->id }}, @js($person['email']))" class="font-semibold text-slate-500 hover:text-red-600">Quitar</button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-amber-700 dark:text-amber-300">Sin responsables seleccionados.</p>
                            @endforelse

                            @error('assign.'.$folder->id)
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror

                            <button type="button" wire:click="saveResponsible({{ $folder->id }})" class="sj-btn sj-btn--secondary w-full">Guardar responsables</button>
                        </div>
                    @endif

                    <a href="{{ route('licitaciones.documentos-legales.folder', $folder) }}" wire:navigate
                       class="sj-btn sj-btn--primary mt-auto w-full">Abrir carpeta</a>
                </div>
            @empty
                <div class="sm:col-span-2 xl:col-span-3 rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-10 text-center dark:border-white/15 dark:bg-white/[0.03]">
                    <p class="text-sm font-semibold text-sj-blue dark:text-white">No hay carpetas asignadas a su usuario</p>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        Solo verá la carpeta cuyo <strong>director</strong> coincida con su correo
                        (<span class="font-mono text-xs">{{ auth()->user()?->email }}</span>).
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        Pida a un administrador que, en Documentos Legales, asigne su correo como responsable de la carpeta (ej. SST → sst@…).
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</div>
