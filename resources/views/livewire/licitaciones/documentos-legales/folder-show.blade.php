<div>
    @push('module-nav')
        <x-licitaciones.nav />
    @endpush

    <div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <a href="{{ route('licitaciones.documentos-legales.index') }}" wire:navigate class="text-sm font-semibold text-sj-blue dark:text-sj-orange">← Documentos legales</a>
                <h1 class="mt-1 text-xl font-bold text-sj-blue dark:text-white">{{ $folder->name }}</h1>
                <p class="text-sm text-slate-500">
                    @if ($folder->exclude_reminders)
                        Área Jurídica: sin recordatorios automáticos (gestión de la abogada).
                    @elseif ($canEditRules)
                        Como administrador puede crear solicitudes, definir fechas de renovación y la frecuencia de correos.
                        Recordatorios actuales:
                        <span class="font-semibold text-sj-blue dark:text-sj-orange">{{ $folder->reminderIntervalLabel() }}</span>.
                    @else
                        Suba o reemplace el archivo vigente de cada documento. Las fechas y los recordatorios los define el administrador.
                        @unless ($folder->exclude_reminders)
                            Aviso actual:
                            <span class="font-semibold text-sj-blue dark:text-sj-orange">{{ $folder->reminderIntervalLabel() }}</span>.
                        @endunless
                    @endif
                </p>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30">
                {{ session('success') }}
            </div>
        @endif

        @if ($canManageReminders && ! $folder->exclude_reminders)
            <div class="rounded-xl bg-white p-5 ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
                <h2 class="font-semibold text-sj-blue dark:text-white mb-1">Frecuencia de recordatorios por correo</h2>
                <p class="text-xs text-slate-500 mb-3">
                    Al guardar, se aplica de inmediato y se envía un aviso si hay documentos por renovar.
                    Luego el sistema respeta este intervalo (programador cada minuto).
                </p>
                <form wire:submit="saveReminderInterval" class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[16rem] flex-1">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Enviar cada</label>
                        <select wire:model="reminderEveryMinutes" class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-white/15 dark:bg-dash-ink">
                            @foreach ($reminderOptions as $minutes => $label)
                                <option value="{{ $minutes }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reminderEveryMinutes')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="sj-btn sj-btn--secondary">Guardar frecuencia</button>
                </form>
            </div>
        @endif

        @if ($canAddRequest)
            <div class="rounded-xl bg-white p-5 ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
                <h2 class="font-semibold text-sj-blue dark:text-white mb-3">Agregar solicitud de documento</h2>
                <form wire:submit="agregarSolicitud" class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre del documento</label>
                        <input type="text" wire:model="nuevoTitulo" class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-white/15 dark:bg-dash-ink" placeholder="Ej. Certificado adicional…">
                        @error('nuevoTitulo')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Frecuencia</label>
                        <input type="text" wire:model="nuevaFrecuencia" class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-white/15 dark:bg-dash-ink" placeholder="MENSUAL, ANUAL…">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de expedición</label>
                        <input type="date" wire:model="nuevaExpedicion" class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-white/15 dark:bg-dash-ink">
                        <p class="mt-1 text-[10px] text-slate-500">Con frecuencia, se calcula sola la fecha de renovación.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Observaciones</label>
                        <textarea wire:model="nuevasObservaciones" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-white/15 dark:bg-dash-ink"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="sj-btn sj-btn--accent">Agregar a esta carpeta</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="rounded-xl bg-white ring-1 ring-slate-200 overflow-hidden dark:bg-white/[0.04] dark:ring-white/10">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3">Código</th>
                            <th class="px-4 py-3">Documento</th>
                            <th class="px-4 py-3 text-center">Frecuencia</th>
                            <th class="px-4 py-3 text-center">Expedición</th>
                            <th class="px-4 py-3">Renueva / restante</th>
                            <th class="px-4 py-3">Archivo vigente</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                        @forelse ($items as $item)
                            @php
                                $due = $item->isDueForRenewal();
                                $fresh = $item->hasFreshFileForCurrentRenewal();
                                $daysLeft = $item->daysUntilRenewal();
                            @endphp
                            <tr class="align-top" wire:key="item-{{ $item->id }}">
                                <td class="px-4 py-3 tabular-nums text-slate-500">{{ $item->code ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-sj-blue dark:text-white">{{ $item->displayTitle() }}</p>
                                    @if ($item->issued_by)
                                        <p class="text-xs text-slate-500">{{ $item->issued_by }}</p>
                                    @endif
                                    @if ($item->source === 'manual')
                                        <span class="mt-1 inline-block rounded bg-sj-orange/15 px-1.5 py-0.5 text-[10px] font-bold uppercase text-sj-blue">Extra</span>
                                    @endif
                                    @if ($item->observations)
                                        <p class="mt-1 text-xs text-slate-500">{{ $item->observations }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($canEditRules && $editingFrequencyItemId === $item->id)
                                        <div class="mx-auto flex flex-col gap-2 min-w-[9rem] max-w-[12rem] text-left">
                                            <input type="text" wire:model="editingFrequency" class="w-full rounded-lg border-slate-300 text-sm dark:border-white/15 dark:bg-dash-ink" placeholder="MENSUAL, ANUAL…">
                                            @error('editingFrequency')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" wire:click="saveFrequency" class="sj-btn sj-btn--primary text-xs">Guardar</button>
                                                <button type="button" wire:click="cancelEditFrequency" class="sj-btn sj-btn--ghost text-xs">Cancelar</button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex flex-col items-center gap-1 text-center">
                                            <span class="text-slate-600 dark:text-slate-300">{{ $item->frequency ?: '—' }}</span>
                                            @if ($canEditRules)
                                                <button type="button" wire:click="startEditFrequency({{ $item->id }})" class="whitespace-nowrap text-[11px] font-semibold text-sj-blue underline dark:text-sj-orange">Cambiar frecuencia</button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($canEditRules && $editingIssuedItemId === $item->id)
                                        <div class="mx-auto flex flex-col gap-2 min-w-[11rem] max-w-[14rem] text-left">
                                            <input type="date" wire:model="editingIssuedDate" class="w-full rounded-lg border-slate-300 text-sm dark:border-white/15 dark:bg-dash-ink">
                                            @error('editingIssuedDate')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" wire:click="saveIssuedDate" class="sj-btn sj-btn--primary text-xs">Guardar</button>
                                                <button type="button" wire:click="cancelEditIssued" class="sj-btn sj-btn--ghost text-xs">Cancelar</button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex flex-col items-center gap-1 text-center">
                                            <span class="tabular-nums text-slate-600 dark:text-slate-300">{{ $item->issued_on?->format('d/m/Y') ?: '—' }}</span>
                                            @if ($canEditRules)
                                                <button type="button" wire:click="startEditIssued({{ $item->id }})" class="whitespace-nowrap text-[11px] font-semibold text-sj-blue underline dark:text-sj-orange">Cambiar expedición</button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-0.5">
                                        <span @class([
                                            'font-semibold tabular-nums',
                                            'text-red-600 dark:text-red-400' => $due && ! $fresh,
                                            'text-emerald-700 dark:text-emerald-300' => $fresh || ($daysLeft !== null && $daysLeft > 7),
                                            'text-amber-600 dark:text-amber-300' => ! $due && $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7,
                                            'text-slate-600 dark:text-slate-300' => $daysLeft === null,
                                        ])>
                                            {{ $item->renew_on?->format('d/m/Y') ?? ($item->renew_label ?: '—') }}
                                        </span>
                                        <span @class([
                                            'text-[11px]',
                                            'text-red-600 dark:text-red-400' => $due && ! $fresh,
                                            'text-amber-600 dark:text-amber-300' => ! $due && $daysLeft !== null && $daysLeft <= 7,
                                            'text-slate-500' => ! $due && ($daysLeft === null || $daysLeft > 7),
                                        ])>
                                            {{ $item->renewalCountdownLabel() }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($item->currentFile)
                                        <a href="{{ route('licitaciones.documentos-legales.file', $item->currentFile) }}" target="_blank" class="font-medium text-sj-blue underline dark:text-sj-orange">
                                            {{ $item->currentFile->original_name }}
                                        </a>
                                        <p class="text-[11px] text-slate-500">
                                            {{ $item->currentFile->created_at?->format('d/m/Y H:i') }}
                                            @if ($item->currentFile->uploadedBy)
                                                · {{ $item->currentFile->uploadedBy->name }}
                                            @endif
                                        </p>
                                    @else
                                        <span class="text-amber-700 dark:text-amber-300">Sin archivo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if ($canUpload)
                                        @if ($uploadItemId === $item->id)
                                            <div class="inline-flex flex-col items-end gap-2 text-left">
                                                <input type="file" wire:model="uploadFile" class="text-xs max-w-[14rem]">
                                                @error('uploadFile')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                                <div class="flex gap-2">
                                                    <button type="button" wire:click="confirmUpload" class="sj-btn sj-btn--primary">Reemplazar</button>
                                                    <button type="button" wire:click="cancelUpload" class="sj-btn sj-btn--ghost">Cancelar</button>
                                                </div>
                                                <p class="text-[10px] text-slate-500">Al reemplazar se descarta el archivo anterior.</p>
                                            </div>
                                        @else
                                            <button type="button" wire:click="startUpload({{ $item->id }})" class="sj-btn sj-btn--secondary">
                                                {{ $item->currentFile ? 'Reemplazar' : 'Subir' }}
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">Sin documentos en esta carpeta.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl bg-white ring-1 ring-slate-200 overflow-hidden dark:bg-white/[0.04] dark:ring-white/10">
            <div class="border-b border-slate-100 px-4 py-3 dark:border-white/10">
                <h2 class="font-semibold text-sj-blue dark:text-white">Historial de cambios</h2>
                <p class="text-xs text-slate-500">Últimos movimientos en esta carpeta (fechas, archivos, frecuencia, director).</p>
            </div>
            <ul class="divide-y divide-slate-100 dark:divide-white/10 max-h-96 overflow-y-auto">
                @forelse ($activities as $activity)
                    <li class="px-4 py-3 text-sm" wire:key="act-{{ $activity->id }}">
                        <p class="text-sj-blue dark:text-slate-100">{{ $activity->summary }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500">
                            {{ $activity->created_at?->format('d/m/Y H:i') }}
                            @if ($activity->user)
                                · {{ $activity->user->name }}
                            @endif
                        </p>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-sm text-slate-500">Aún no hay cambios registrados en esta carpeta.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
