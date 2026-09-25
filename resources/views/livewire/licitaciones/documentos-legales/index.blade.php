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
                    $assignedEmail = strtolower((string) ($folder->responsible_email ?: $folder->responsible?->email ?: ''));
                    $assignedPerson = $assignedEmail !== '' ? ($directoryByEmail[$assignedEmail] ?? null) : null;
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
                        @if ($assignedPerson)
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $assignedPerson->name }}</span>
                            <span class="block truncate">{{ $assignedPerson->email }}</span>
                        @elseif ($assignedEmail !== '')
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $assignedEmail }}</span>
                        @else
                            <span class="text-amber-700 dark:text-amber-300">sin asignar</span>
                        @endif
                    </p>

                    @if ($canAssign)
                        <div class="space-y-2 border-t border-slate-100 pt-3 dark:border-white/10">
                            <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Correo del directorio</label>
                            <select wire:model="assign.{{ $folder->id }}.email" class="w-full rounded-lg border-slate-300 text-sm dark:border-white/15 dark:bg-dash-ink">
                                <option value="">— Seleccione correo —</option>
                                @foreach ($directoryPeople as $person)
                                    <option value="{{ strtolower($person->email) }}">{{ $person->name }} — {{ $person->email }}</option>
                                @endforeach
                            </select>
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
