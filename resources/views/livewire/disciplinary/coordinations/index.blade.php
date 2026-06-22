<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Disciplinarios · Planeación</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Coordinaciones abiertas</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300 max-w-3xl">
                Chat con abogados y registro de fechas de diligencia y notificación. Sin acceso al expediente completo.
            </p>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-100 dark:ring-emerald-500/30">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <aside class="lg:col-span-4 bg-white rounded-lg ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
                    <div class="px-4 py-3 border-b border-slate-200 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Bandeja de coordinación</h2>
                    </div>
                    <div class="divide-y divide-slate-200 dark:divide-white/10">
                        @forelse ($threads as $thread)
                            @php
                                $case = $thread->case;
                                $workerName = trim((string) ($case?->employee?->first_name.' '.$case?->employee?->last_name));
                                $cityLabel = $case?->municipality?->municipality_name ?: ($case?->city ?? '—');
                            @endphp
                            <button type="button" wire:click="selectThread({{ $thread->id }})"
                                class="w-full px-4 py-3 text-left hover:bg-slate-50 dark:hover:bg-white/[0.04] {{ (int) $selectedThread === (int) $thread->id ? 'bg-indigo-50 dark:bg-indigo-500/10' : '' }}">
                                <p class="text-xs font-mono text-slate-600 dark:text-slate-300">{{ $case?->case_number }}</p>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $workerName !== '' ? $workerName : 'Sin trabajador' }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $cityLabel }} · {{ optional($thread->coordination_started_at)->diffForHumans() }}</p>
                                @php
                                    $tc = $thread->case;
                                    $badge = $tc?->notification_requested_at && ! $tc?->notification_information_completed_at
                                        ? 'Notificación pendiente'
                                        : ($tc && $tc->awaitingPlanningDiligenceSlots() ? 'Fechas pendientes' : null);
                                @endphp
                                @if ($badge)
                                    <span class="mt-1 inline-block rounded px-1.5 py-0.5 text-[10px] font-bold uppercase {{ str_contains($badge, 'Notificación') ? 'bg-amber-200 text-amber-900' : 'bg-emerald-200 text-emerald-900' }}">{{ $badge }}</span>
                                @endif
                            </button>
                        @empty
                            <div class="px-4 py-8 text-sm text-slate-500 dark:text-slate-400">
                                No hay coordinaciones abiertas.
                            </div>
                        @endforelse
                    </div>
                </aside>

                <section class="lg:col-span-8 bg-white rounded-lg ring-1 ring-slate-200 p-4 sm:p-5 dark:bg-white/[0.04] dark:ring-white/10"
                    @if ($liveCaseId) data-live-case-id="{{ $liveCaseId }}" @endif>
                    @if ($selectedThreadModel)
                        @php
                            $case = $selectedThreadModel->case;
                            $workerName = trim((string) ($case?->employee?->first_name.' '.$case?->employee?->last_name));
                            $cityLabel = $case?->municipality?->municipality_name ?: ($case?->city ?? '—');
                        @endphp

                        <div class="space-y-4">
                            <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                                <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">Datos de coordinación</p>
                                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-slate-700 dark:text-slate-200">
                                    <p><span class="font-semibold">Caso:</span> <span class="font-mono">{{ $case?->case_number }}</span></p>
                                    <p><span class="font-semibold">Estado:</span> Abierta</p>
                                    <p><span class="font-semibold">Trabajador:</span> {{ $workerName !== '' ? $workerName : '—' }}</p>
                                    <p><span class="font-semibold">Ciudad:</span> {{ $cityLabel }}</p>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-md border border-slate-200 dark:border-white/10"
                                x-data="window.sjAgendaAttachmentLightbox()"
                                x-on:open-agenda-lightbox="openAgendaAttachment($event.detail)"
                                wire:poll.visible.10s>
                                <div class="px-3 py-3">
                                    <p class="mb-2 text-xs font-semibold text-slate-600 dark:text-slate-300">Historial del chat</p>
                                    <ul class="max-h-72 space-y-3 overflow-y-auto">
                                        @foreach ($selectedThreadModel->messages as $msg)
                                            <x-disciplinary.agenda-message :message="$msg" :thread="$selectedThreadModel" wire:key="coord-msg-{{ $msg->id }}" />
                                        @endforeach
                                    </ul>
                                </div>

                                @if ($canPostPlanning ?? false)
                                    <x-disciplinary.agenda-chat-composer
                                        body-model="agendaPlanningBody"
                                        uploads-property="agendaPlanningUploads"
                                        send-action="postPlanningChat"
                                        remove-upload-method="removeAgendaPlanningUploadAt"
                                        :uploads="$agendaPlanningUploads"
                                        placeholder="Mensaje para el abogado…"
                                        input-id="agenda-planning-body-coord"
                                        error-field="agendaPlanningBody"
                                        class="border-t border-slate-200 dark:border-white/10" />
                                    <div class="flex flex-wrap gap-2 border-t border-slate-200 bg-slate-50/80 px-3 py-2.5 dark:border-white/10 dark:bg-indigo-950/30">
                                        @if ($awaitingDiligenceDates ?? false)
                                            <button type="button" wire:click="openDiligenceModal"
                                                class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                                                Proponer fechas de diligencia
                                            </button>
                                        @endif
                                        @if ($awaitingDecisionPlanning ?? false)
                                            <button type="button" wire:click="openDecisionPlanningModal"
                                                class="rounded-md bg-violet-700 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-800">
                                                Programar decisión
                                            </button>
                                        @endif
                                        @if ($canRegisterNotification ?? false)
                                            <button type="button" wire:click="openNotificationModal"
                                                class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                                                Registrar notificación y supervisor
                                            </button>
                                        @endif
                                        @if ($canRegisterDecisionNotification ?? false)
                                            <button type="button" wire:click="openNotificationModal"
                                                class="rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">
                                                Registrar notificación de decisión
                                            </button>
                                        @endif
                                    </div>
                                @endif

                                <x-disciplinary.agenda-attachment-lightbox-modal />
                            </div>
                        </div>

                        {{-- Modal fechas diligencia --}}
                        @if ($showDiligenceModal)
                            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:keydown.escape.window="closeDiligenceModal">
                                <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl dark:bg-dash-ink dark:ring-1 dark:ring-white/10 space-y-4 max-h-[90vh] overflow-y-auto">
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Fechas de diligencia</h3>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">Se publicará en el chat y el abogado podrá confirmar una opción.</p>
                                    <textarea wire:model="agendaPlanningBody" rows="2"
                                        class="w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"
                                        placeholder="Comentario opcional..."></textarea>
                                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Fechas propuestas</p>
                                    @foreach ($planningSlots as $i => $slot)
                                        <div class="grid grid-cols-3 gap-2">
                                            <input type="date" wire:model="planningSlots.{{ $i }}.date" required class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                            <input type="time" wire:model="planningSlots.{{ $i }}.time" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                            <input type="text" wire:model="planningSlots.{{ $i }}.notes" placeholder="Notas" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                        </div>
                                    @endforeach
                                    <button type="button" wire:click="addPlanningSlotRow" class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">+ Otra fecha</button>
                                    <input type="file" wire:model="agendaPlanningUploads" multiple accept="image/*,application/pdf" class="text-xs block">
                                    @error('diligenceModal')
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" wire:click="closeDiligenceModal" class="px-3 py-2 text-sm text-slate-700 dark:text-slate-300">Cancelar</button>
                                        <button type="button" wire:click="submitDiligenceModal" class="px-4 py-2 bg-emerald-700 text-white text-sm font-semibold rounded-md hover:bg-emerald-800">Aceptar y publicar</button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Modal notificación --}}
                        @if ($showNotificationModal)
                            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:keydown.escape.window="closeNotificationModal">
                                <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl dark:bg-dash-ink dark:ring-1 dark:ring-white/10 space-y-4 max-h-[90vh] overflow-y-auto">
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                        {{ ($isDecisionCase ?? false) ? 'Notificación de decisión y supervisor' : 'Notificación física y supervisor' }}
                                    </h3>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">
                                        {{ ($isDecisionCase ?? false) ? 'Datos para notificar la decisión disciplinaria al trabajador.' : 'Datos para FO-GJ-03 y asignación al supervisor que notificará.' }}
                                    </p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Fecha ingreso trabajador</label>
                                            <input type="date" wire:model="notificationDate" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Turno</label>
                                            <input type="text" wire:model="notificationShift" placeholder="Ej. Mañana" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Zona</label>
                                            <input type="text" wire:model="notificationZone" placeholder="Zona operativa" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Supervisor asignado</label>
                                            <select wire:model="notificationSupervisorUserId" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                                <option value="">— Seleccione —</option>
                                                @foreach ($supervisorCandidates as $supervisor)
                                                    <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Observaciones (opcional)</label>
                                            <textarea wire:model="notificationNotes" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"></textarea>
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
                                    @error('notificationSupervisorUserId')
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" wire:click="closeNotificationModal" class="px-3 py-2 text-sm text-slate-700 dark:text-slate-300">Cancelar</button>
                                        @if ($isDecisionCase ?? false)
                                            <button type="button" wire:click="submitDecisionNotificationModal" class="px-4 py-2 bg-violet-600 text-white text-sm font-semibold rounded-md hover:bg-violet-700">Aceptar y publicar</button>
                                        @else
                                            <button type="button" wire:click="submitNotificationModal" class="px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-md hover:bg-amber-700">Aceptar y publicar</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($showDecisionPlanningModal ?? false)
                            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:keydown.escape.window="closeDecisionPlanningModal">
                                <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl dark:bg-dash-ink dark:ring-1 dark:ring-white/10 space-y-4 max-h-[90vh] overflow-y-auto">
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Programación de decisión</h3>
                                    @if ($decisionBranch && \App\Support\Disciplinary\DecisionBranch::requiresSuspensionDates($decisionBranch))
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-xs font-semibold">Inicio suspensión</label>
                                                <input type="date" wire:model="decisionSuspensionStart" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold">Fin suspensión</label>
                                                <input type="date" wire:model="decisionSuspensionEnd" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                            </div>
                                        </div>
                                    @endif
                                    @if ($decisionBranch === \App\Support\Disciplinary\DecisionBranch::TERMINATION)
                                        <div>
                                            <label class="text-xs font-semibold">Relevo</label>
                                            <textarea wire:model="decisionReliefNotes" rows="2" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"></textarea>
                                        </div>
                                    @endif
                                    <textarea wire:model="agendaPlanningBody" rows="2" class="w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white" placeholder="Comentario opcional…"></textarea>
                                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Turnos / fechas para notificar al trabajador</p>
                                    @foreach ($planningSlots as $i => $slot)
                                        <div class="grid grid-cols-3 gap-2">
                                            <input type="date" wire:model="planningSlots.{{ $i }}.date" required class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                            <input type="time" wire:model="planningSlots.{{ $i }}.time" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                            <input type="text" wire:model="planningSlots.{{ $i }}.notes" placeholder="Turno / notas" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                        </div>
                                    @endforeach
                                    <button type="button" wire:click="addPlanningSlotRow" class="text-xs font-semibold text-violet-700 dark:text-violet-300">+ Otra fecha</button>
                                    @error('decisionPlanningModal')
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" wire:click="closeDecisionPlanningModal" class="px-3 py-2 text-sm text-slate-700 dark:text-slate-300">Cancelar</button>
                                        <button type="button" wire:click="submitDecisionPlanningModal" class="px-4 py-2 bg-violet-700 text-white text-sm font-semibold rounded-md hover:bg-violet-800">Aceptar y publicar</button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">Seleccione una coordinación abierta para responder.</p>
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>
