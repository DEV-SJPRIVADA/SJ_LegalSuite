@php
    $actionNeeded = ($canManageCitationCoordination ?? false)
        || ($awaitingDecisionPlanning ?? false)
        || ($canRepublishDecisionPlanning ?? false)
        || ($canRegisterDecisionNotification ?? false);
@endphp

<div class="coordinations-cockpit mx-auto flex h-[calc(100dvh-3.25rem)] max-h-[calc(100dvh-3.25rem)] w-full max-w-[1600px] flex-col overflow-hidden px-3 py-2 sm:px-5 sm:py-3 lg:px-6">
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <header class="mb-2 flex shrink-0 flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-2 dark:border-white/10">
        <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-fuchsia-400/90">Planeación · Coordinaciones</p>
            <h1 class="truncate text-sm font-semibold text-slate-900 dark:text-white">Bandeja abierta con jurídico</h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 dark:border-white/10 dark:bg-white/[0.04]">
                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-dash-muted">Abiertas</span>
                <span class="text-sm font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($threadTotal) }}</span>
            </div>
            <div class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200/80 bg-emerald-50 px-2.5 py-1.5 dark:border-emerald-500/30 dark:bg-emerald-950/30">
                <span class="text-[10px] font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-200">Fechas</span>
                <span class="text-sm font-bold tabular-nums text-emerald-800 dark:text-emerald-100">{{ number_format($kpiFechas) }}</span>
            </div>
            <div class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200/80 bg-amber-50 px-2.5 py-1.5 dark:border-amber-500/30 dark:bg-amber-950/30">
                <span class="text-[10px] font-bold uppercase tracking-wide text-amber-900 dark:text-amber-200">Notif.</span>
                <span class="text-sm font-bold tabular-nums text-amber-900 dark:text-amber-100">{{ number_format($kpiNotif) }}</span>
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="mb-2 shrink-0 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-100 dark:ring-emerald-500/30">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-white/10 dark:bg-white/[0.03] lg:flex-row">
        {{-- Lista --}}
        <aside class="flex max-h-[40%] w-full shrink-0 flex-col border-b border-slate-200 dark:border-white/10 lg:max-h-none lg:w-[22rem] lg:border-b-0 lg:border-r">
            <div class="shrink-0 border-b border-slate-200 p-2.5 dark:border-white/10">
                <label for="coord-search" class="sr-only">Buscar coordinación</label>
                <div class="relative overflow-hidden">
                    <x-ui.search-field-icon />
                    <input
                        id="coord-search"
                        type="text"
                        inputmode="search"
                        autocomplete="off"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Caso, trabajador, ciudad…"
                        class="h-8 w-full rounded-lg border border-slate-300 bg-white pl-8 pr-2.5 text-xs text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-white/15 dark:bg-dash-lift dark:text-slate-100 dark:placeholder:text-slate-500"
                    >
                </div>
                <p class="mt-1.5 text-[10px] text-slate-500 dark:text-slate-400">
                    {{ $threads->count() }} {{ $threads->count() === 1 ? 'resultado' : 'resultados' }}
                    @if (filled($search))
                        · filtro activo
                    @endif
                </p>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                @forelse ($threads as $thread)
                    @php
                        $caseRow = $thread->case;
                        $workerName = trim((string) ($caseRow?->employee?->first_name.' '.$caseRow?->employee?->last_name));
                        $cityLabel = $caseRow?->municipality?->municipality_name ?: ($caseRow?->city ?? '—');
                        $isSelected = (int) $selectedThread === (int) $thread->id;
                        $badge = $caseRow?->awaitingCitationNotificationInformation()
                            ? 'Notificación'
                            : (($caseRow && ($caseRow->awaitingPlanningDiligenceSlots() || $caseRow->awaitingDecisionPlanningSlots())) ? 'Fechas' : null);
                        $badgeClass = $badge === 'Notificación'
                            ? 'bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-100'
                            : 'bg-emerald-100 text-emerald-900 dark:bg-emerald-500/20 dark:text-emerald-100';
                    @endphp
                    <button
                        type="button"
                        wire:click="selectThread({{ $thread->id }})"
                        wire:key="coord-thread-{{ $thread->id }}"
                        @class([
                            'w-full border-b border-slate-100 px-3 py-2.5 text-left transition dark:border-white/10',
                            'border-l-2 border-l-indigo-500 bg-indigo-50/80 dark:border-l-indigo-400 dark:bg-indigo-500/10' => $isSelected,
                            'border-l-2 border-l-transparent hover:bg-slate-50 dark:hover:bg-white/[0.04]' => ! $isSelected,
                        ])
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-mono text-[10px] text-slate-500 dark:text-slate-400">{{ $caseRow?->case_number }}</p>
                            @if ($badge)
                                <span class="shrink-0 rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide {{ $badgeClass }}">{{ $badge }}</span>
                            @endif
                        </div>
                        <p class="mt-0.5 truncate text-sm font-semibold text-slate-900 dark:text-white">
                            {{ $workerName !== '' ? $workerName : 'Sin trabajador' }}
                        </p>
                        <p class="mt-0.5 truncate text-[11px] text-slate-500 dark:text-slate-400">
                            {{ $cityLabel }}
                            · {{ optional($thread->coordination_started_at)->diffForHumans() }}
                        </p>
                    </button>
                @empty
                    <div class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                        @if (filled($search))
                            Sin resultados para el filtro.
                        @else
                            No hay coordinaciones abiertas.
                        @endif
                    </div>
                @endforelse
            </div>
        </aside>

        {{-- Hilo --}}
        <section
            class="flex min-h-0 min-w-0 flex-1 flex-col"
            @if ($liveCaseId) data-live-case-id="{{ $liveCaseId }}" @endif
        >
            @if ($selectedThreadModel)
                @php
                    $case = $selectedThreadModel->case;
                    $workerName = trim((string) ($case?->employee?->first_name.' '.$case?->employee?->last_name));
                    $cityLabel = $case?->municipality?->municipality_name ?: ($case?->city ?? '—');
                    $lawyerName = $case?->assignedLawyer?->name;
                    $statusPill = ($isDecisionCase ?? false) ? 'Decisión' : 'Citación';
                    $msgCount = $selectedThreadModel->messages->count();
                    $lastMsgId = optional($selectedThreadModel->messages->last())->id ?? 0;
                @endphp

                <div class="flex shrink-0 flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                                {{ $workerName !== '' ? $workerName : 'Sin trabajador' }}
                            </h2>
                            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:bg-white/10 dark:text-slate-300">
                                Abierta
                            </span>
                            <span class="rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200">
                                {{ $statusPill }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            <span class="font-mono text-slate-700 dark:text-slate-300">{{ $case?->case_number }}</span>
                            · {{ $cityLabel }}
                            @if ($case?->employee?->document_number)
                                · CC {{ $case->employee->document_number }}
                            @endif
                            @if ($lawyerName)
                                · Jurídico: {{ $lawyerName }}
                            @endif
                        </p>
                    </div>
                </div>

                <div
                    class="flex min-h-0 flex-1 flex-col bg-slate-50 dark:bg-dash-ink/50"
                    x-data="window.sjAgendaAttachmentLightbox()"
                    x-on:open-agenda-lightbox="openAgendaAttachment($event.detail)"
                    wire:poll.visible.10s
                >
                    <div
                        class="min-h-0 flex-1 overflow-y-auto px-3 py-4 sm:px-4"
                        wire:key="coord-scroll-{{ $selectedThreadModel->id }}-{{ $msgCount }}-{{ $lastMsgId }}"
                        x-data
                        x-init="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
                    >
                        <ul class="mx-auto max-w-3xl space-y-3.5">
                            @forelse ($selectedThreadModel->messages as $msg)
                                <x-disciplinary.agenda-message
                                    :message="$msg"
                                    :thread="$selectedThreadModel"
                                    perspective="planning"
                                    wire:key="coord-msg-{{ $msg->id }}" />
                            @empty
                                <li class="list-none py-16 text-center">
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Sin mensajes aún</p>
                                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Responda abajo o use una acción rápida cuando aplique.</p>
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    @if ($canPostPlanning ?? false)
                        <div class="shrink-0 border-t border-slate-200 bg-white dark:border-white/10 dark:bg-dash-ink/80">
                            @if ($actionNeeded)
                                <div class="flex flex-wrap gap-1.5 border-b border-slate-100 px-3 py-2 dark:border-white/10">
                                    @if ($canManageCitationCoordination ?? false)
                                        <button type="button" wire:click="openNotificationModal"
                                            class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">
                                            {{ ($citationNotificationCompleted ?? false) ? 'Actualizar notificación' : 'Registrar notificación' }}
                                        </button>
                                        <button type="button" wire:click="openDiligenceModal"
                                            class="inline-flex items-center rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">
                                            {{ ($citationHasPlanningSlots ?? false) ? 'Reproponer fechas de diligencia' : 'Proponer fechas de diligencia' }}
                                        </button>
                                    @endif
                                    @if (($awaitingDecisionPlanning ?? false) || ($canRepublishDecisionPlanning ?? false))
                                        <button type="button" wire:click="openDecisionPlanningModal"
                                            class="inline-flex items-center rounded-lg bg-violet-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-800">
                                            {{ ($decisionHasPlanningSlots ?? false) ? 'Reproponer opciones de decisión' : 'Programar decisión' }}
                                        </button>
                                    @endif
                                    @if ($canRegisterDecisionNotification ?? false)
                                        <button type="button" wire:click="openNotificationModal"
                                            class="inline-flex items-center rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-700">
                                            Notificación de decisión
                                        </button>
                                    @endif
                                </div>
                            @endif
                            <div class="p-3">
                                <x-disciplinary.agenda-chat-composer
                                    body-model="agendaPlanningBody"
                                    uploads-property="agendaPlanningUploads"
                                    send-action="postPlanningChat"
                                    remove-upload-method="removeAgendaPlanningUploadAt"
                                    :uploads="$agendaPlanningUploads"
                                    placeholder="Mensaje para el abogado…"
                                    input-id="agenda-planning-body-coord"
                                    error-field="agendaPlanningBody"
                                    variant="drawer" />
                            </div>
                        </div>
                    @endif

                    <x-disciplinary.agenda-attachment-lightbox-modal />
                </div>

                {{-- Modales (misma lógica) --}}
                @if ($showDiligenceModal)
                    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:keydown.escape.window="closeDiligenceModal">
                        <div class="max-h-[90vh] w-full max-w-lg space-y-4 overflow-y-auto rounded-xl bg-white p-5 dark:bg-dash-ink dark:ring-1 dark:ring-white/10">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                {{ ($citationHasPlanningSlots ?? false) ? 'Reproponer fechas de diligencia' : 'Fechas de diligencia' }}
                            </h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400">
                                Se publicará en el chat y el abogado podrá confirmar una opción.
                                @unless ($citationNotificationCompleted ?? false)
                                    <span class="font-semibold text-amber-700 dark:text-amber-300">Registre primero la notificación física.</span>
                                @endunless
                            </p>
                            <textarea wire:model="agendaPlanningBody" rows="2"
                                class="w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white"
                                placeholder="Comentario opcional..."></textarea>
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Fechas propuestas</p>
                            @foreach ($planningSlots as $i => $slot)
                                <div class="grid grid-cols-3 gap-2">
                                    <input type="date" wire:model="planningSlots.{{ $i }}.date" required class="rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                    <input type="time" wire:model="planningSlots.{{ $i }}.time" class="rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                    <input type="text" wire:model="planningSlots.{{ $i }}.notes" placeholder="Notas" class="rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                </div>
                            @endforeach
                            <button type="button" wire:click="addPlanningSlotRow" class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">+ Otra fecha</button>
                            <input type="file" wire:model="agendaPlanningUploads" multiple accept="image/*,application/pdf" class="block text-xs">
                            @error('diligenceModal')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" wire:click="closeDiligenceModal" class="px-3 py-2 text-sm text-slate-700 dark:text-slate-300">Cancelar</button>
                                <button type="button" wire:click="submitDiligenceModal" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Aceptar y publicar</button>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($showNotificationModal)
                    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:keydown.escape.window="closeNotificationModal">
                        <div class="max-h-[90vh] w-full max-w-lg space-y-4 overflow-y-auto rounded-xl bg-white p-5 dark:bg-dash-ink dark:ring-1 dark:ring-white/10">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                @if ($isDecisionCase ?? false)
                                    Notificación de decisión
                                @elseif ($citationNotificationCompleted ?? false)
                                    Actualizar notificación física
                                @else
                                    Notificación física
                                @endif
                            </h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400">
                                {{ ($isDecisionCase ?? false)
                                    ? 'Datos para notificar la decisión disciplinaria al trabajador (lugar y zona de supervisión).'
                                    : 'Datos para FO-GJ-03: lugar físico y zona de supervisión que recibirá la tarea.' }}
                            </p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Fecha ingreso trabajador</label>
                                    <input type="date" wire:model="notificationDate" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Turno</label>
                                    <input type="text" wire:model="notificationShift" placeholder="Ej. Mañana" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Lugar</label>
                                    <input type="text" wire:model="notificationZone" placeholder="Lugar físico" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Zona de supervisión</label>
                                    <select wire:model="notificationSupervisionZoneId" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                        <option value="">— Seleccione —</option>
                                        @foreach ($supervisionZones as $supervisionZone)
                                            <option value="{{ $supervisionZone->id }}">{{ $supervisionZone->displayLabel() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Observaciones (opcional)</label>
                                    <textarea wire:model="notificationNotes" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white"></textarea>
                                </div>
                            </div>
                            @error('notificationDate')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('notificationShift')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('notificationZone')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('notificationSupervisionZoneId')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" wire:click="closeNotificationModal" class="px-3 py-2 text-sm text-slate-700 dark:text-slate-300">Cancelar</button>
                                @if ($isDecisionCase ?? false)
                                    <button type="button" wire:click="submitDecisionNotificationModal" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">Aceptar y publicar</button>
                                @else
                                    <button type="button" wire:click="submitNotificationModal" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Aceptar y publicar</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if ($showDecisionPlanningModal ?? false)
                    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:keydown.escape.window="closeDecisionPlanningModal">
                        <div class="max-h-[90vh] w-full max-w-2xl space-y-5 overflow-y-auto rounded-xl bg-white p-5 dark:bg-dash-ink dark:ring-1 dark:ring-white/10">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Programación de decisión</h3>

                            @if ($decisionBranch && \App\Support\Disciplinary\DecisionBranch::requiresSuspensionDates($decisionBranch))
                                <section class="space-y-3 rounded-lg border border-slate-200 p-4 dark:border-white/10">
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Inicio de suspensión</h4>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">
                                        Indique la fecha de inicio de la sanción. El abogado define los días en el FO-GJ-47 y el sistema calcula fin y retorno a labores.
                                    </p>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Inicio suspensión</label>
                                        <input type="date" wire:model="decisionSuspensionStart" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                    </div>
                                    @error('decisionSuspensionStart')
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </section>
                            @endif

                            @if ($decisionBranch === \App\Support\Disciplinary\DecisionBranch::TERMINATION)
                                <section class="space-y-2 rounded-lg border border-slate-200 p-4 dark:border-white/10">
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Relevo</h4>
                                    <textarea wire:model="decisionReliefNotes" rows="2" class="w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white" placeholder="Notas de relevo…"></textarea>
                                </section>
                            @endif

                            <section class="space-y-3 rounded-lg border border-violet-200 p-4 dark:border-violet-500/30">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Opciones para notificar al trabajador</h4>
                                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                                        Proponga una o más ventanas (fecha, hora, turno, lugar y zona de supervisión). El abogado confirmará una opción; no hace falta un segundo registro.
                                    </p>
                                </div>
                                @foreach ($decisionNotificationSlots as $i => $slot)
                                    <div class="space-y-2 rounded-md bg-violet-50/50 p-3 dark:bg-violet-950/20">
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            <div>
                                                <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">Fecha</label>
                                                <input type="date" wire:model="decisionNotificationSlots.{{ $i }}.date" required class="mt-0.5 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">Hora</label>
                                                <input type="time" wire:model="decisionNotificationSlots.{{ $i }}.time" class="mt-0.5 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                            <div>
                                                <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">Turno</label>
                                                <input type="text" wire:model="decisionNotificationSlots.{{ $i }}.notes" placeholder="Ej. Mañana" class="mt-0.5 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">Lugar</label>
                                                <input type="text" wire:model="decisionNotificationSlots.{{ $i }}.zone" placeholder="Lugar físico" class="mt-0.5 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">Zona de supervisión</label>
                                                <select wire:model="decisionNotificationSlots.{{ $i }}.supervision_zone_id" class="mt-0.5 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                                    <option value="">— Seleccione —</option>
                                                    @foreach ($supervisionZones as $supervisionZone)
                                                        <option value="{{ $supervisionZone->id }}">{{ $supervisionZone->displayLabel() }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @error('decisionNotificationSlots.'.$i.'.date')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('decisionNotificationSlots.'.$i.'.notes')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('decisionNotificationSlots.'.$i.'.zone')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('decisionNotificationSlots.'.$i.'.supervision_zone_id')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                                <button type="button" wire:click="addDecisionNotificationSlotRow" class="text-xs font-semibold text-violet-700 dark:text-violet-300">+ Otra opción</button>
                            </section>

                            <div>
                                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Comentario opcional</label>
                                <textarea wire:model="agendaPlanningBody" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white" placeholder="Comentario opcional…"></textarea>
                            </div>

                            @error('decisionPlanningModal')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" wire:click="closeDecisionPlanningModal" class="px-3 py-2 text-sm text-slate-700 dark:text-slate-300">Cancelar</button>
                                <button type="button" wire:click="submitDecisionPlanningModal" class="rounded-md bg-violet-700 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-800">Aceptar y publicar</button>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Seleccione una coordinación</p>
                    <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-slate-400">
                        Elija un caso en la bandeja para conversar con jurídico y registrar fechas o notificación.
                    </p>
                </div>
            @endif
        </section>
    </div>
</div>
