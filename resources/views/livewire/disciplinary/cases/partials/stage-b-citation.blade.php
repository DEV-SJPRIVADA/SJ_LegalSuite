@php
    use App\Enums\Disciplinary\CaseStatus;
    use App\Support\Disciplinary\CitationStageProgress;
    use App\Services\Disciplinary\DisciplinaryCitationWorkflowService;
    $isCitacion = $case->current_status === CaseStatus::CITACION_PROGRAMADA;
    $requirementLabels = $citationRequirementLabels ?? DisciplinaryCitationWorkflowService::requirementLabels();
    $agendaThread = $case->agendaThread;
    $coordinationIsClosed = $agendaThread?->isClosed() ?? false;
    $foGj03Labels = $foGj03GenerationLabels ?? [];
    $foGj03Checklist = $foGj03GenerationChecklist ?? collect();
    $stageSteps = $citationStageSteps ?? collect();
    $diligenceStatus = $diligenceDateRequestStatus ?? '';
    $notificationStatus = match (true) {
        ($notificationCompleted ?? false) => 'Completada',
        ($notificationPending ?? false) => 'Pendiente de respuesta de Planeación',
        $case->notification_requested_at !== null => 'Solicitada',
        default => 'Sin solicitar',
    };
@endphp

@if ($isCitacion)
    <div class="md:col-span-2 xl:col-span-3 rounded-xl border border-indigo-200 bg-indigo-50/40 p-5 space-y-5 dark:border-indigo-400/30 dark:bg-indigo-950/20">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h4 class="text-xs uppercase tracking-wider font-semibold text-indigo-900 dark:text-indigo-200">Etapa B · Citación a diligencia (FO-GJ-03)</h4>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400 max-w-2xl">
                    Coordine con Planeación la fecha de diligencia y la notificación física del trabajador. Luego genere el FO-GJ-03 y cargue la evidencia PDF.
                </p>
            </div>
            @can('transition', $case)
                <button type="button" wire:click="requestAdvanceFromCitacion"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 shadow-sm shrink-0">
                    Siguiente etapa
                </button>
            @endcan
        </div>

        {{-- Progreso guiado --}}
        <nav aria-label="Progreso citación" class="rounded-lg border border-indigo-200/80 bg-white/70 px-3 py-3 dark:border-white/10 dark:bg-white/5">
            <ol class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-4 sm:gap-y-2 text-xs">
                @foreach ($stageSteps as $step)
                    @php
                        $stepClass = match ($step['status']) {
                            CitationStageProgress::STATUS_DONE => 'text-emerald-700 dark:text-emerald-300',
                            CitationStageProgress::STATUS_CURRENT => 'text-indigo-800 font-semibold dark:text-indigo-200',
                            default => 'text-slate-500 dark:text-slate-400',
                        };
                        $icon = match ($step['status']) {
                            CitationStageProgress::STATUS_DONE => '✓',
                            CitationStageProgress::STATUS_CURRENT => '●',
                            default => '○',
                        };
                    @endphp
                    <li class="flex items-center gap-2 {{ $stepClass }}">
                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold
                            {{ $step['status'] === CitationStageProgress::STATUS_DONE ? 'bg-emerald-200 text-emerald-900 dark:bg-emerald-900/50' : ($step['status'] === CitationStageProgress::STATUS_CURRENT ? 'bg-indigo-200 text-indigo-900 dark:bg-indigo-900/50' : 'bg-slate-200 text-slate-600 dark:bg-white/10') }}">
                            {{ $icon }}
                        </span>
                        <span>{{ $step['label'] }}</span>
                    </li>
                @endforeach
            </ol>
        </nav>

        @error('citationAdvance')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('coordination')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2 text-xs border-t border-indigo-200/60 pt-3 dark:border-white/10">
            @foreach ($citationReadiness as $key => $done)
                <li class="flex items-center gap-2 {{ $done ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-400' }}">
                    <span class="font-semibold">{{ $done ? '✓' : '○' }}</span>
                    <span>{{ $requirementLabels[$key] ?? $key }}</span>
                </li>
            @endforeach
        </ul>

        @if ($showCitationAdvanceValidation)
            <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-4 dark:border-amber-500/40 dark:bg-amber-950/40" role="alert">
                <p class="text-sm font-semibold text-amber-950 dark:text-amber-100">No es posible avanzar a diligencia.</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($citationReadiness as $key => $done)
                        <li class="flex items-center gap-2 {{ $done ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-950 dark:text-amber-100' }}">
                            <span>{{ $done ? '✓' : '✗' }}</span>
                            {{ $requirementLabels[$key] ?? $key }}
                        </li>
                    @endforeach
                </ul>
                <button type="button" wire:click="closeCitationAdvanceValidation" class="mt-4 text-xs font-semibold text-amber-900 underline dark:text-amber-200">Cerrar</button>
            </div>
        @endif

        @can('startCoordination', $case)
            <button type="button" wire:click="startCoordination"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 shadow-sm">
                Paso 1 · Iniciar coordinación con Planeación
            </button>
        @endcan

        @if ($case->hasCoordinationStarted())
            {{-- Hilo compartido --}}
            <div class="rounded-lg border border-slate-200 bg-white/80 p-4 space-y-4 dark:border-white/10 dark:bg-white/5"
                x-data="window.sjAgendaAttachmentLightbox()">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <p class="text-xs text-slate-600 dark:text-slate-300">
                        @if ($coordinationIsClosed)
                            Coordinación cerrada. Historial conservado para auditoría.
                        @else
                            Chat de coordinación activo. Planeación atiende desde <strong>Coordinaciones</strong> (sin acceso a este expediente).
                        @endif
                    </p>
                    @can('closeCoordination', $case)
                        @if (! $coordinationIsClosed)
                            <button type="button" wire:click="openCloseCoordinationConfirm"
                                class="inline-flex items-center px-3 py-1.5 bg-slate-700 text-white text-xs font-semibold rounded-md hover:bg-slate-800">
                                Cerrar coordinación
                            </button>
                        @endif
                    @endcan
                </div>

                @if ($case->agendaThread && $case->agendaThread->messages->isNotEmpty())
                    <ul class="space-y-3 max-h-80 overflow-y-auto text-sm">
                        @foreach ($case->agendaThread->messages as $msg)
                            <x-disciplinary.agenda-message :message="$msg" :case="$case" wire:key="agenda-msg-{{ $msg->id }}" />
                        @endforeach
                    </ul>
                @else
                    <p class="text-xs text-slate-500 dark:text-slate-400 italic">Aún no hay mensajes en el hilo.</p>
                @endif
            </div>

            {{-- B.1 Programación de diligencia --}}
            <section class="rounded-xl border border-emerald-200 bg-white/90 p-4 space-y-4 dark:border-emerald-500/30 dark:bg-white/5">
                <header class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-200/60 pb-3 dark:border-white/10">
                    <div>
                        <h5 class="text-sm font-bold text-emerald-900 dark:text-emerald-100">Paso 2 · Programación de fechas de diligencia</h5>
                        <p class="mt-0.5 text-xs text-emerald-800/90 dark:text-emerald-200/80">Estado: <span class="font-semibold">{{ $diligenceStatus }}</span></p>
                    </div>
                </header>

                @can('postAgendaLawyer', $case)
                    @if (! $coordinationIsClosed && $case->canRequestDiligenceDateProgramming())
                        <div class="rounded-lg border border-emerald-300 bg-emerald-50/80 px-4 py-4 space-y-3 dark:border-emerald-500/40 dark:bg-emerald-950/25">
                            <p class="text-sm font-semibold text-emerald-950 dark:text-emerald-100">Solicitar programación de fechas</p>
                            <p class="text-xs text-emerald-900/80 dark:text-emerald-100/70">
                                Envíe la solicitud formal a Planeación. Ellos propondrán fechas disponibles en la bandeja de coordinaciones.
                            </p>
                            <textarea wire:model="diligenceDateRequestNotes" rows="2"
                                placeholder="Observaciones opcionales (preferencias de horario, sede, etc.)"
                                class="w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"></textarea>
                            @error('diligenceDateRequest')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <button type="button" wire:click="requestDiligenceDateProgramming"
                                class="inline-flex px-4 py-2 bg-emerald-700 text-white text-sm font-semibold rounded-md hover:bg-emerald-800">
                                Enviar solicitud a Planeación
                            </button>
                        </div>
                    @elseif ($case->citation_confirmed_date)
                        <div class="rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-100 dark:ring-emerald-500/30">
                            <span class="font-semibold">Fecha de diligencia confirmada:</span>
                            {{ $case->citation_confirmed_date->format('d/m/Y') }}
                            @if ($case->citation_confirmed_time)
                                — {{ \Illuminate\Support\Carbon::parse($case->citation_confirmed_time)->format('h:i A') }}
                            @endif
                        </div>
                    @elseif ($case->hasLawyerDiligenceDateRequest() && ! $case->hasAgendaPlanningReply())
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            Solicitud enviada. Espere a que Planeación proponga fechas en coordinaciones.
                        </p>
                    @endif

                    {{-- Selección fecha definitiva --}}
                    <div class="border-t border-emerald-200/60 pt-4 space-y-3 dark:border-white/10">
                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">Seleccionar fecha definitiva de citación</p>

                        @if ($case->citation_confirmed_date)
                            <p class="text-xs text-slate-500">Ya confirmó la fecha en el paso anterior.</p>
                        @elseif ($citationSlotChoices->isNotEmpty())
                            <fieldset class="space-y-2">
                                @foreach ($citationSlotChoices as $choice)
                                    <label wire:key="slot-{{ $choice['key'] }}"
                                        class="flex cursor-pointer items-start gap-3 rounded-lg border px-3 py-3 transition
                                            {{ $selectedCitationSlotKey === $choice['key'] ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/30 dark:border-indigo-400 dark:bg-indigo-950/40' : 'border-slate-200 bg-white hover:border-indigo-300 dark:border-white/15 dark:bg-white/5' }}">
                                        <input type="radio" name="citation_slot_choice" value="{{ $choice['key'] }}"
                                            wire:model.live="selectedCitationSlotKey"
                                            class="mt-1 border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-slate-900 dark:text-white">{{ $choice['label'] }}</span>
                                            @if ($choice['notes'])
                                                <span class="mt-0.5 block text-xs text-slate-600 dark:text-slate-400">{{ $choice['notes'] }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </fieldset>
                            @error('selectedCitationSlotKey')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <button type="button" wire:click="confirmCitationSlot"
                                @disabled($selectedCitationSlotKey === '' || $coordinationIsClosed)
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                Confirmar fecha de diligencia
                            </button>
                        @elseif ($case->hasLawyerDiligenceDateRequest())
                            <p class="text-xs text-slate-600 dark:text-slate-400">
                                Planeación aún no ha publicado fechas estructuradas. Revise el hilo cuando respondan.
                            </p>
                        @else
                            <p class="text-xs text-slate-500 dark:text-slate-400 italic">
                                Envíe primero la solicitud formal de programación de fechas.
                            </p>
                        @endif
                    </div>

                    @if ($case->hasLawyerDiligenceDateRequest() && ! $case->citation_confirmed_date && ! $coordinationIsClosed)
                        <div class="border-t border-emerald-200/60 pt-3 space-y-2 dark:border-white/10">
                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">Comentario adicional al hilo (opcional)</p>
                            <textarea wire:model="agendaLawyerBody" rows="2" class="w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"></textarea>
                            @error('agendaLawyerBody')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <button type="button" wire:click="postAgendaLawyer" class="px-3 py-1.5 bg-slate-600 text-white text-xs font-semibold rounded-md hover:bg-slate-700">Enviar comentario</button>
                        </div>
                    @endif
                @endcan

                @can('postAgendaPlanning', $case)
                    @if (auth()->user()->hasRole('admin'))
                        <div class="border-t border-dashed border-slate-300 pt-3 text-xs text-slate-500 dark:border-white/15">
                            <p class="font-semibold text-slate-600 dark:text-slate-300">Vista administrador — respuesta planeación</p>
                            <p class="mt-1">Planeación opera en <a href="{{ route('disciplinary.coordinations.index') }}" class="text-indigo-700 underline dark:text-cyan-300" wire:navigate>Coordinaciones</a>.</p>
                        </div>
                    @endif
                @endcan
            </section>

            {{-- B.2 Notificación física --}}
            @if ($case->citation_confirmed_date)
                <section class="rounded-xl border border-amber-200 bg-white/90 p-4 space-y-4 dark:border-amber-500/30 dark:bg-white/5">
                    <header class="border-b border-amber-200/60 pb-3 dark:border-white/10">
                        <h5 class="text-sm font-bold text-amber-950 dark:text-amber-100">Paso 3 · Notificación física del trabajador</h5>
                        <p class="mt-0.5 text-xs text-amber-900/80 dark:text-amber-100/70">Estado: {{ $notificationStatus }}</p>
                    </header>

                    @error('notification')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-500">Fecha ingreso</dt><dd class="font-medium">{{ $case->notification_date?->format('d/m/Y') ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Turno</dt><dd class="font-medium">{{ $case->notification_shift ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Zona</dt><dd class="font-medium">{{ $case->notification_zone ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Supervisor</dt><dd class="font-medium">{{ $case->notification_supervisor_name ?? '—' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Observaciones</dt><dd class="font-medium whitespace-pre-wrap">{{ $case->notification_notes ?? '—' }}</dd></div>
                    </dl>

                    @can('requestNotificationCoordination', $case)
                        <button type="button" wire:click="requestNotificationCoordination"
                            class="inline-flex px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-md hover:bg-amber-700">
                            Solicitar información de notificación a Planeación
                        </button>
                    @endcan

                    @can('reassignNotificationSupervisor', $case)
                        <button type="button" wire:click="openReassignSupervisorModal"
                            class="inline-flex px-4 py-2 bg-white text-amber-900 text-sm font-semibold rounded-md ring-1 ring-amber-400 hover:bg-amber-50 dark:bg-white/10 dark:text-amber-100">
                            Reasignar supervisor de notificación
                        </button>
                        @error('reassignSupervisor')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endcan
                </section>
            @endif

            {{-- FO-GJ-03 --}}
            @if ($case->citation_confirmed_date && (int) $case->assigned_lawyer_id === (int) auth()->id())
                @php $canGenerateFoGj03 = auth()->user()->can('generateFoGj03', $case); @endphp
                <section class="rounded-xl border border-indigo-200 bg-white/90 p-4 space-y-3 dark:border-indigo-500/30 dark:bg-white/5">
                    <h5 class="text-sm font-bold text-indigo-900 dark:text-indigo-200">Paso 4 · Generar FO-GJ-03</h5>
                    @if (! $canGenerateFoGj03)
                        <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-950/40">
                            <p class="text-sm font-semibold text-amber-950 dark:text-amber-100">No es posible generar FO-GJ-03. Falta:</p>
                            <ul class="mt-2 space-y-1 text-sm">
                                @foreach ($foGj03Checklist as $key => $done)
                                    <li class="{{ $done ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-950 dark:text-amber-100' }}">
                                        {{ $done ? '✓' : '✗' }} {{ $foGj03Labels[$key] ?? $key }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @can('generateFoGj03', $case)
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('disciplinary.cases.fo-gj-03.pdf', $case) }}" target="_blank"
                                class="inline-flex px-4 py-2 bg-white text-indigo-800 text-sm font-semibold rounded-md ring-1 ring-indigo-300 dark:bg-white/10 dark:text-indigo-200">
                                Vista previa PDF
                            </a>
                            <button type="button" wire:click="generateFoGj03"
                                class="inline-flex px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                Generar y guardar en expediente
                            </button>
                        </div>
                        @error('fo_gj_03')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    @endcan
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Tras generar el documento, el supervisor asignado y operaciones podrán cargar la evidencia de notificación.
                    </p>
                </section>
            @endif

            {{-- Evidencia --}}
            @can('viewCitationEvidence', $case)
                @php $citationEvidenceDoc = $case->latestCitationEvidenceDocument(); @endphp
                <section class="rounded-xl border border-slate-200 bg-white/90 p-4 space-y-3 dark:border-white/10 dark:bg-white/5">
                    <h5 class="text-sm font-bold text-slate-900 dark:text-white">Paso 5 · Evidencia de notificación (PDF)</h5>
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Citación firmada o acta de rechazo con testigos. Pueden cargar: supervisor asignado, operaciones aprobador, dirección de operaciones, abogado titular y dirección jurídica.
                    </p>

                    @if ($case->citation_evidence_uploaded_at)
                        <dl class="grid gap-2 text-sm sm:grid-cols-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-white/10">
                            <div><dt class="text-xs text-slate-500">Tipo</dt><dd class="font-medium">{{ $case->citation_evidence_type?->label() ?? '—' }}</dd></div>
                            <div><dt class="text-xs text-slate-500">Cargada</dt><dd class="font-medium">{{ $case->citation_evidence_uploaded_at->format('d/m/Y H:i') }}</dd></div>
                            @if ($citationEvidenceDoc)
                                <div class="sm:col-span-2">
                                    <a href="{{ route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $citationEvidenceDoc, 'download' => 1]) }}"
                                        class="text-sm font-semibold text-indigo-700 underline dark:text-indigo-300" target="_blank" rel="noopener">
                                        {{ $citationEvidenceDoc->original_name }}
                                    </a>
                                </div>
                            @endif
                        </dl>
                    @elseif (! $case->fo_gj_03_generated_at)
                        <p class="text-xs text-amber-700 dark:text-amber-300">Disponible después de generar el FO-GJ-03.</p>
                    @endif

                    @can('uploadCitationEvidence', $case)
                        <div class="space-y-2">
                            <select wire:model="citationEvidenceType" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white w-full max-w-md">
                                <option value="">— Tipo de evidencia —</option>
                                <option value="signed">Citación firmada por el trabajador</option>
                                <option value="refused_witnesses">Rechazo con firma de jefe y dos testigos</option>
                            </select>
                            @error('citationEvidenceType')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            <input type="file" wire:model="citationEvidenceFile" accept="application/pdf" class="text-sm">
                            @error('citationEvidenceFile')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            <button type="button" wire:click="uploadCitationEvidence"
                                class="inline-flex px-4 py-2 bg-emerald-700 text-white text-sm font-semibold rounded-md hover:bg-emerald-800">
                                Cargar evidencia PDF
                            </button>
                        </div>
                    @endcan
                </section>
            @endcan
        @endif
    </div>

    @include('livewire.disciplinary.cases.partials.stage-b-citation-modals', [
        'case' => $case,
        'citationAdvanceTargetLabel' => $citationAdvanceTargetLabel ?? null,
        'supervisorCandidates' => $supervisorCandidates ?? collect(),
    ])
@endif
