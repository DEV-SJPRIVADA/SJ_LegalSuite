<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Disciplinarios · Planeación</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Coordinaciones abiertas</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300 max-w-3xl">
                Solo coordinaciones abiertas remitidas por abogados. Responda fechas de diligencia y, cuando corresponda, la información de notificación física. Sin acceso a expedientes disciplinarios.
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
                                        : ($tc && $tc->hasLawyerDiligenceDateRequest() && ! $tc->hasAgendaPlanningReply() ? 'Fechas pendientes' : null);
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

                <section class="lg:col-span-8 bg-white rounded-lg ring-1 ring-slate-200 p-4 sm:p-5 dark:bg-white/[0.04] dark:ring-white/10">
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

                            <div class="rounded-md border border-slate-200 px-3 py-3 max-h-72 overflow-y-auto dark:border-white/10">
                                <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 mb-2">Historial del chat</p>
                                <ul class="space-y-3">
                                    @foreach ($selectedThreadModel->messages as $msg)
                                        <x-disciplinary.agenda-message :message="$msg" :thread="$selectedThreadModel" wire:key="coord-msg-{{ $msg->id }}" />
                                    @endforeach
                                </ul>
                            </div>

                            @if ($awaitingDiligenceDates && ($canPostPlanning ?? false))
                                <div class="rounded-md border-2 border-emerald-400 bg-emerald-50 px-4 py-4 space-y-3 dark:border-emerald-500/50 dark:bg-emerald-950/30">
                                    <p class="text-sm font-bold text-emerald-950 dark:text-emerald-100">Responder: programación de fechas de diligencia</p>
                                    <p class="text-xs text-emerald-900/90 dark:text-emerald-100/80">
                                        El abogado envió una solicitud formal. Proponga al menos una fecha y hora disponibles (obligatorio).
                                    </p>
                                    <textarea wire:model="agendaPlanningBody" rows="2"
                                        class="w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"
                                        placeholder="Mensaje para el abogado (opcional si solo envía fechas)..."></textarea>
                                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Fechas propuestas</p>
                                    @foreach ($planningSlots as $i => $slot)
                                        <div class="grid grid-cols-3 gap-2">
                                            <input type="date" wire:model="planningSlots.{{ $i }}.date" required class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                            <input type="time" wire:model="planningSlots.{{ $i }}.time" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                            <input type="text" wire:model="planningSlots.{{ $i }}.notes" placeholder="Notas" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                        </div>
                                    @endforeach
                                    <button type="button" wire:click="addPlanningSlotRow" class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">+ Otra fecha propuesta</button>
                                    <input type="file" wire:model="agendaPlanningUploads" multiple accept="image/*,application/pdf" class="text-xs block">
                                    @error('agendaPlanningBody')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                    <button type="button" wire:click="postPlanningReply" class="px-4 py-2 bg-emerald-700 text-white text-sm font-semibold rounded-md hover:bg-emerald-800">
                                        Publicar fechas al abogado
                                    </button>
                                </div>
                            @elseif ($canPostPlanning ?? false)
                                <div class="rounded-md border border-indigo-200 bg-indigo-50/50 px-4 py-3 space-y-3 dark:border-indigo-500/30 dark:bg-indigo-950/20">
                                    <p class="text-xs font-semibold text-indigo-900 dark:text-indigo-100">Actualizar fechas u observaciones (opcional)</p>
                                    <textarea wire:model="agendaPlanningBody" rows="2" class="w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white" placeholder="Observaciones..."></textarea>
                                    @foreach ($planningSlots as $i => $slot)
                                        <div class="grid grid-cols-3 gap-2">
                                            <input type="date" wire:model="planningSlots.{{ $i }}.date" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                            <input type="time" wire:model="planningSlots.{{ $i }}.time" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                            <input type="text" wire:model="planningSlots.{{ $i }}.notes" placeholder="Notas" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                        </div>
                                    @endforeach
                                    <button type="button" wire:click="addPlanningSlotRow" class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">+ Otra fecha</button>
                                    <input type="file" wire:model="agendaPlanningUploads" multiple accept="image/*,application/pdf" class="text-xs">
                                    @error('agendaPlanningBody')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                    <button type="button" wire:click="postPlanningReply" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md">Publicar respuesta</button>
                                </div>
                            @endif

                            @if ($hasPendingNotification)
                                <div class="rounded-md border border-amber-300 bg-amber-50 px-4 py-4 space-y-3 dark:border-amber-500/40 dark:bg-amber-950/30">
                                    <p class="text-xs font-bold uppercase tracking-wider text-amber-950 dark:text-amber-100">Paso 3 · Notificación física del trabajador</p>
                                    <p class="text-xs text-amber-900/90 dark:text-amber-100/80">El abogado solicitó información para la notificación. Complete los campos obligatorios.</p>
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
                                    <button type="button" wire:click="postNotificationCoordination"
                                        class="px-3 py-1.5 bg-amber-600 text-white text-xs font-semibold rounded-md hover:bg-amber-700">
                                        Registrar información de notificación
                                    </button>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">Seleccione una coordinación abierta para responder.</p>
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>
