<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto py-5 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <a href="{{ route('disciplinary.cases.index') }}" wire:navigate
                        class="text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white">← Volver al listado</a>
                    <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold mt-2 dark:text-dash-muted">Disciplinarios · Detalle</p>
                    <h1 class="font-bold text-2xl text-slate-900 leading-tight mt-1 dark:text-white">
                        Caso <span class="font-mono">{{ $case->case_number }}</span>
                    </h1>
                    <p class="text-sm text-slate-600 mt-1 dark:text-slate-300">
                        {{ $case->personnel?->first_name }} {{ $case->personnel?->last_name }}
                        @if ($case->personnel?->document_number)
                            · CC {{ $case->personnel->document_number }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <x-disciplinary.status-badge :status="$case->current_status" class="text-sm px-3 py-1" />
                    @can('transition', $case)
                        @if (count($allowedTransitions) > 0)
                            <button wire:click="openTransition"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                Gestionar →
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/35 dark:text-emerald-300 dark:ring-emerald-500/25">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Tabs --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden dark:bg-white/[0.04] dark:ring-1 dark:ring-white/10 dark:shadow-dash-card">
                <div class="flex border-b border-gray-200 text-sm dark:border-white/10">
                    @foreach (['overview' => 'Información', 'timeline' => 'Línea de tiempo', 'documents' => 'Documentos', 'audit' => 'Actuaciones'] as $key => $label)
                        <button wire:click="setTab('{{ $key }}')"
                            class="px-5 py-3 font-medium border-b-2 transition
                                {{ $activeTab === $key ? 'border-indigo-600 text-indigo-700 dark:border-indigo-400 dark:text-indigo-300' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="p-6">
                    @if ($activeTab === 'overview')
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Disciplinado</dt>
                                    <dd class="text-gray-900 dark:text-white font-medium">
                                        {{ $case->personnel?->first_name }} {{ $case->personnel?->last_name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Documento</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $case->personnel?->document_type }} {{ $case->personnel?->document_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Cargo / Sede</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $case->personnel?->position ?? '—' }} · {{ $case->sede ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Ciudad</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $case->city ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Reportado por</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $case->reporter?->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Abogado asignado</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $case->assignedLawyer?->name ?? '— Sin asignar —' }}</dd>
                                </div>
                            </dl>
                            <dl class="space-y-3 text-sm md:col-span-1 xl:col-span-1">
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Apertura</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $case->opened_at?->format('Y-m-d') }}</dd>
                                </div>
                                @if ($case->closed_at)
                                    <div>
                                        <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Cierre</dt>
                                        <dd class="text-gray-900 dark:text-white">{{ $case->closed_at?->format('Y-m-d') }}</dd>
                                    </div>
                                @endif
                                @if ($case->decision)
                                    <div>
                                        <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Decisión</dt>
                                        <dd class="text-gray-900 dark:text-white font-semibold">{{ $case->decision->label() }}</dd>
                                    </div>
                                    @if ($case->decision_notes)
                                        <div>
                                            <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Notas de la decisión</dt>
                                            <dd class="text-gray-700 dark:text-slate-300 whitespace-pre-line">{{ $case->decision_notes }}</dd>
                                        </div>
                                    @endif
                                @endif
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Resumen</dt>
                                    <dd class="text-gray-700 dark:text-slate-300 whitespace-pre-line">{{ $case->summary ?? '—' }}</dd>
                                </div>
                            </dl>

                            {{-- Faltas: tercera columna en xl, fila completa abajo en md --}}
                            <div class="md:col-span-2 xl:col-span-1">
                                <h4 class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted mb-2">Faltas imputadas</h4>
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($case->faults as $f)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/35 dark:text-rose-300 dark:ring-rose-500/30">
                                            {{ $f->code }} · {{ $f->name }}
                                            @if ($f->pivot->extra_info)
                                                <span class="text-rose-500 ml-1">({{ $f->pivot->extra_info }})</span>
                                            @endif
                                        </span>
                                    @empty
                                        <span class="text-sm text-gray-500 dark:text-slate-400">Sin faltas registradas todavía.</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                    @elseif ($activeTab === 'timeline')
                        <ol class="relative border-s border-gray-200 ms-4 space-y-6 dark:border-white/15">
                            @forelse ($case->stages as $stage)
                                <li class="ms-6">
                                    <span class="absolute -start-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 ring-4 ring-white dark:ring-dash-ink">
                                        <span class="text-[10px] text-white font-bold">{{ $stage->sequence }}</span>
                                    </span>
                                    <div class="bg-gray-50 rounded-md p-4 ring-1 ring-gray-200 dark:bg-white/[0.06] dark:ring-white/10">
                                        <div class="flex items-center justify-between gap-3 flex-wrap">
                                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                                {{ $stage->stage_type->label() }}
                                                @if ($stage->form_code)
                                                    <span class="text-xs text-gray-500 dark:text-slate-400 font-mono">({{ $stage->form_code }})</span>
                                                @endif
                                            </h4>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                @can('assignDate', $case)
                                                    <button type="button" wire:click="openScheduleStage({{ $stage->id }})"
                                                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 px-2 py-1 rounded ring-1 ring-indigo-200 bg-white dark:bg-white/10 dark:text-indigo-300 dark:ring-indigo-400/40 dark:hover:text-indigo-200">
                                                        Programar fechas
                                                    </button>
                                                @endcan
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ring-1 ring-inset
                                                    {{ $stage->status->value === 'completada' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30' : ($stage->status->value === 'en_curso' ? 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-500/30' : 'bg-gray-50 text-gray-700 dark:text-slate-300 ring-gray-200 dark:bg-white/10 dark:text-slate-300 dark:ring-white/15') }}">
                                                    {{ $stage->status->label() }}
                                                </span>
                                            </div>
                                        </div>
                                        <dl class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-xs text-gray-600 dark:text-slate-400">
                                            @if ($stage->scheduled_at)
                                                <div><dt class="inline font-semibold">Programada:</dt> <dd class="inline">{{ $stage->scheduled_at->format('Y-m-d H:i') }}</dd></div>
                                            @endif
                                            @if ($stage->performed_at)
                                                <div><dt class="inline font-semibold">Ejecutada:</dt> <dd class="inline">{{ $stage->performed_at->format('Y-m-d H:i') }}</dd></div>
                                            @endif
                                            @if ($stage->deadline_at)
                                                <div><dt class="inline font-semibold">Plazo:</dt> <dd class="inline">{{ $stage->deadline_at->format('Y-m-d') }}</dd></div>
                                            @endif
                                            @if ($stage->performer)
                                                <div><dt class="inline font-semibold">Responsable:</dt> <dd class="inline">{{ $stage->performer->name }}</dd></div>
                                            @endif
                                        </dl>
                                        @if ($stage->notes)
                                            <p class="mt-2 text-sm text-gray-700 dark:text-slate-300 whitespace-pre-line">{{ $stage->notes }}</p>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="ms-6 text-sm text-gray-500 dark:text-slate-400">Sin etapas registradas todavía.</li>
                            @endforelse
                        </ol>

                    @elseif ($activeTab === 'documents')
                        @if ($case->documents->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-slate-400">No hay documentos cargados todavía.</p>
                        @else
                            <ul class="divide-y divide-gray-200 dark:divide-white/10">
                                @foreach ($case->documents as $doc)
                                    <li class="py-3 flex items-start justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $doc->original_name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-slate-400">
                                                {{ $doc->document_type->label() }}
                                                @if ($doc->form_code) · <span class="font-mono">{{ $doc->form_code }}</span> @endif
                                                · {{ number_format($doc->size_bytes / 1024, 1) }} KB
                                                · subido por {{ $doc->uploader?->name ?? '—' }}
                                                · {{ $doc->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                    @elseif ($activeTab === 'audit')
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:bg-white/5 dark:text-slate-400">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold">Fecha</th>
                                        <th class="px-3 py-2 text-left font-semibold">Acción</th>
                                        <th class="px-3 py-2 text-left font-semibold">De → A</th>
                                        <th class="px-3 py-2 text-left font-semibold">Usuario</th>
                                        <th class="px-3 py-2 text-left font-semibold">Notas</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-transparent dark:divide-white/10">
                                    @forelse ($case->actions as $a)
                                        <tr>
                                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500 dark:text-slate-400">
                                                {{ $a->performed_at->format('Y-m-d H:i') }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <code class="text-xs bg-gray-100 rounded px-1.5 py-0.5 dark:bg-white/10 dark:text-slate-200">{{ $a->action_type->value }}</code>
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-700 dark:text-slate-300">
                                                @if ($a->from_status)
                                                    {{ $a->from_status->label() }}
                                                @endif
                                                @if ($a->from_status && $a->to_status) → @endif
                                                @if ($a->to_status)
                                                    <span class="font-semibold">{{ $a->to_status->label() }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-gray-700 dark:text-slate-300">{{ $a->user?->name ?? '—' }}</td>
                                            <td class="px-3 py-2 text-gray-600 dark:text-slate-400">{{ $a->description }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-slate-400">Sin actuaciones registradas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de transición --}}
    @if ($showTransition)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            x-data x-on:keydown.escape.window="$wire.closeTransition()">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full dark:bg-dash-ink dark:ring-1 dark:ring-white/15" x-on:click.outside="$wire.closeTransition()">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Mover el caso</h3>
                    <button wire:click="closeTransition" class="text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300">✕</button>
                </div>
                <form wire:submit="saveTransition" class="p-6 space-y-4">
                    <div class="text-sm text-gray-600 dark:text-slate-400">
                        Estado actual: <x-disciplinary.status-badge :status="$case->current_status" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Nuevo estado</label>
                        <select wire:model="newStatus"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100">
                            <option value="">— Seleccionar —</option>
                            @foreach ($allowedTransitions as $t)
                                <option value="{{ $t->value }}">{{ $t->label() }}</option>
                            @endforeach
                        </select>
                        @error('newStatus') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Programado para (opcional)</label>
                            <input type="datetime-local" wire:model="scheduledAt"
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Plazo (opcional)</label>
                            <input type="date" wire:model="deadlineAt"
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Nota</label>
                        <textarea wire:model="note" rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100 placeholder:dark:text-slate-500"
                            placeholder="Detalles, observaciones, motivo..."></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeTransition"
                            class="px-4 py-2 bg-gray-100 text-gray-700 dark:text-slate-300 rounded-md text-sm hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                            Aplicar transición
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal fechas de etapa (Planeación / Jurídico) --}}
    @if ($showScheduleModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            x-data x-on:keydown.escape.window="$wire.closeScheduleModal()">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full dark:bg-dash-ink dark:ring-1 dark:ring-white/15" x-on:click.outside="$wire.closeScheduleModal()">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Programar fechas de etapa</h3>
                    <button wire:click="closeScheduleModal" type="button" class="text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300">✕</button>
                </div>
                <form wire:submit="saveSchedule" class="p-6 space-y-4">
                    <p class="text-xs text-gray-600 dark:text-slate-400">
                        Define la fecha programada y el plazo sin cambiar el estado del proceso.
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Programado para</label>
                            <input type="datetime-local" wire:model="scheduleAt"
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100">
                            @error('scheduleAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Plazo</label>
                            <input type="date" wire:model="scheduleDeadline"
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100">
                            @error('scheduleDeadline') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Nota (opcional)</label>
                        <textarea wire:model="scheduleNote" rows="2"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100 placeholder:dark:text-slate-500"
                            placeholder="Motivo del cambio de fecha…"></textarea>
                        @error('scheduleNote') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeScheduleModal"
                            class="px-4 py-2 bg-gray-100 text-gray-700 dark:text-slate-300 rounded-md text-sm hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                            Guardar fechas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
