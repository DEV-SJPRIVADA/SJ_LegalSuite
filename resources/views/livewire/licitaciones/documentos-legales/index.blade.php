<div>
    @push('module-nav')
        <x-licitaciones.nav />
    @endpush

    <div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-sj-orange">Licitaciones · Documentos legales</p>
            <h1 class="mt-1 text-xl font-bold text-sj-blue dark:text-white">Carpetas por área (MT-GJ-06)</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Cada director actualiza los documentos de su carpeta. Los recordatorios horarios usan la fecha de renovación de la matriz (área Jurídica excluida: lo gestiona la abogada).
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($folders as $folder)
                @php
                    $selectedEmail = strtolower((string) ($assign[$folder->id]['email'] ?? ''));
                    $selectedName = (string) ($assign[$folder->id]['name'] ?? '');
                    $busqueda = (string) ($directorBusqueda[$folder->id] ?? '');
                    $resultados = $resultadosPorCarpeta[$folder->id] ?? collect();
                @endphp
                <div class="rounded-xl bg-white p-5 ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 flex flex-col gap-3" wire:key="folder-{{ $folder->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h2 class="font-semibold text-sj-blue dark:text-white">{{ $folder->name }}</h2>
                            <p class="text-xs text-slate-500">{{ $folder->slug }}</p>
                        </div>
                        @if ($folder->exclude_reminders)
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:bg-white/10 dark:text-slate-300">Sin recordatorio</span>
                        @endif
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

                    <p class="text-xs text-slate-500">
                        Director:
                        @if ($selectedEmail !== '')
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $selectedName !== '' ? $selectedName : $selectedEmail }}</span>
                            @if ($selectedName !== '')
                                <span class="block truncate">{{ $selectedEmail }}</span>
                            @endif
                        @else
                            <span class="text-amber-700 dark:text-amber-300">sin asignar</span>
                        @endif
                    </p>

                    @if ($canAssign)
                        <div class="space-y-2 border-t border-slate-100 pt-3 dark:border-white/10">
                            <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Buscar director</label>
                            <div class="relative">
                                <input
                                    type="search"
                                    wire:model.live.debounce.250ms="directorBusqueda.{{ $folder->id }}"
                                    class="w-full rounded-lg border-slate-300 text-sm dark:border-white/15 dark:bg-dash-ink"
                                    placeholder="Escriba nombre o correo…"
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
                                            <li class="px-3 py-2 text-xs text-slate-500">Sin coincidencias en el directorio.</li>
                                        @endforelse
                                    </ul>
                                @endif
                            </div>

                            @if ($selectedEmail !== '')
                                <div class="flex items-center justify-between gap-2 rounded-lg bg-sj-orange/10 px-3 py-2 text-xs">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-sj-blue dark:text-sj-orange">{{ $selectedName !== '' ? $selectedName : $selectedEmail }}</p>
                                        <p class="truncate text-slate-500">{{ $selectedEmail }}</p>
                                    </div>
                                    <button type="button" wire:click="quitarDirector({{ $folder->id }})" class="shrink-0 font-semibold text-slate-500 hover:text-red-600">Quitar</button>
                                </div>
                            @endif

                            @error('assign.'.$folder->id.'.email')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror

                            <button type="button" wire:click="saveResponsible({{ $folder->id }})" class="sj-btn sj-btn--secondary w-full">Guardar responsable</button>
                        </div>
                    @endif

                    <a href="{{ route('licitaciones.documentos-legales.folder', $folder) }}" wire:navigate
                       class="sj-btn sj-btn--primary mt-auto w-full">Abrir carpeta</a>
                </div>
            @endforeach
        </div>
    </div>
</div>
