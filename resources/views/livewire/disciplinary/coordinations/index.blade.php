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

                            <div class="rounded-md border border-slate-200 px-3 py-3 dark:border-white/10" wire:poll.visible.10s>
                                <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 mb-2">Historial del chat</p>
                                <ul class="space-y-3 max-h-72 overflow-y-auto">
                                    @foreach ($selectedThreadModel->messages as $msg)
                                        <x-disciplinary.agenda-message :message="$msg" :thread="$selectedThreadModel" wire:key="coord-msg-{{ $msg->id }}" />
                                    @endforeach
                                </ul>
                            </div>

                            @if ($canPostPlanning ?? false)
                                <div class="rounded-md border border-slate-200 px-3 py-3 space-y-3 dark:border-white/10">
                                    <label class="text-xs font-semibold text-slate-700 dark:text-slate-200">Escribir en el chat</label>
                                    <textarea wire:model="agendaPlanningBody" rows="2"
                                        class="w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"
                                        placeholder="Mensaje para el abogado..."></textarea>
                                    @error('agendaPlanningBody')
                                        <p class="text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" wire:click="postPlanningChat"
                                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                            Enviar mensaje
                                        </button>
                                        @if ($awaitingDiligenceDates)
                                            <button type="button" wire:click="openDiligenceModal"
                                                class="px-4 py-2 bg-emerald-700 text-white text-sm font-semibold rounded-md hover:bg-emerald-800">
                                                Proponer fechas de diligencia
                                            </button>
                                        @endif
                                        @if ($canRegisterNotification ?? false)
                                            <button type="button" wire:click="openNotificationModal"
                                                class="px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-md hover:bg-amber-700">
                                                Registrar notificación y supervisor
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif
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
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Notificación física y supervisor</h3>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">Datos para FO-GJ-03 y asignación al supervisor que notificará.</p>
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
                                        <button type="button" wire:click="submitNotificationModal" class="px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-md hover:bg-amber-700">Aceptar y publicar</button>
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
