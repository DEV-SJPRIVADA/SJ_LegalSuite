@php
    use App\Enums\Disciplinary\CaseStatus;
    use App\Services\Disciplinary\DisciplinaryCitationWorkflowService;
    $isCitacion = $case->current_status === CaseStatus::CITACION_PROGRAMADA;
    $requirementLabels = $citationRequirementLabels ?? DisciplinaryCitationWorkflowService::requirementLabels();
@endphp

@if ($isCitacion)
    <div class="md:col-span-2 xl:col-span-3 rounded-xl border border-indigo-200 bg-indigo-50/40 p-5 space-y-4 dark:border-indigo-400/30 dark:bg-indigo-950/20">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <h4 class="text-xs uppercase tracking-wider font-semibold text-indigo-900 dark:text-indigo-200">Etapa B · Citación (FO-GJ-03)</h4>
            @can('transition', $case)
                <button type="button" wire:click="requestAdvanceFromCitacion"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 shadow-sm">
                    Siguiente etapa
                </button>
            @endcan
        </div>

        @error('citationAdvance')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <ul class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
            @foreach ($citationReadiness as $key => $done)
                <li class="flex items-center gap-2 {{ $done ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-400' }}">
                    <span class="font-semibold">{{ $done ? '✓' : '○' }}</span>
                    <span>{{ $requirementLabels[$key] ?? $key }}</span>
                </li>
            @endforeach
        </ul>

        @if ($showCitationAdvanceValidation)
            <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-4 dark:border-amber-500/40 dark:bg-amber-950/40" role="alert">
                <p class="text-sm font-semibold text-amber-950 dark:text-amber-100">No es posible avanzar.</p>
                <p class="mt-1 text-xs text-amber-900/90 dark:text-amber-100/80">Requisitos para pasar a la siguiente etapa:</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($citationReadiness as $key => $done)
                        <li class="flex items-center gap-2 {{ $done ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-950 dark:text-amber-100' }}">
                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $done ? 'bg-emerald-200 text-emerald-900 dark:bg-emerald-900/50 dark:text-emerald-200' : 'bg-amber-200 text-amber-950 dark:bg-amber-900/60 dark:text-amber-100' }}">
                                {{ $done ? '✓' : '✗' }}
                            </span>
                            {{ $requirementLabels[$key] ?? $key }}
                        </li>
                    @endforeach
                </ul>
                <button type="button" wire:click="closeCitationAdvanceValidation"
                    class="mt-4 text-xs font-semibold text-amber-900 underline dark:text-amber-200">
                    Cerrar
                </button>
            </div>
        @endif

        @can('startCoordination', $case)
            <button type="button" wire:click="startCoordination"
                class="inline-flex items-center px-4 py-2 bg-white text-indigo-800 text-sm font-semibold rounded-md ring-1 ring-indigo-300 hover:bg-indigo-50 dark:bg-white/10 dark:text-indigo-200 dark:ring-indigo-400/40">
                Iniciar coordinación
            </button>
        @endcan

        @if ($case->hasCoordinationStarted())
            <div class="rounded-lg border border-emerald-200 bg-white/80 p-4 space-y-4 dark:border-emerald-500/30 dark:bg-white/5"
                x-data="window.sjAgendaAttachmentLightbox()">
                <p class="text-xs text-emerald-800 dark:text-emerald-200">Hilo activo con planeación. Proponga fechas estructuradas y adjunte imágenes o PDF.</p>

                @if ($case->agendaThread && $case->agendaThread->messages->isNotEmpty())
                    <ul class="space-y-3 max-h-72 overflow-y-auto text-sm">
                        @foreach ($case->agendaThread->messages as $msg)
                            <li class="rounded-md border border-slate-200 px-3 py-2 dark:border-white/10">
                                <div class="flex justify-between text-xs text-slate-500">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $msg->author?->name }}</span>
                                    <span>{{ $msg->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                                <p class="mt-1 whitespace-pre-wrap">{{ $msg->body }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @can('postAgendaLawyer', $case)
                    <div class="space-y-2 border-t pt-3 dark:border-white/10">
                        <p class="text-xs font-semibold">Mensaje (abogado)</p>
                        <textarea wire:model="agendaLawyerBody" rows="2" class="w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"></textarea>
                        @error('agendaLawyerBody')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <button type="button" wire:click="postAgendaLawyer" class="px-3 py-1.5 bg-emerald-700 text-white text-xs font-semibold rounded-md">Enviar</button>
                    </div>

                    @if ($case->agendaThread?->hasPlanningReply())
                        <div class="border-t pt-4 space-y-3 dark:border-white/10">
                            <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">Seleccionar fecha definitiva de citación</p>

                            @if ($case->citation_confirmed_date)
                                <div class="rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-100 dark:ring-emerald-500/30">
                                    <span class="font-semibold">Fecha elegida:</span>
                                    {{ $case->citation_confirmed_date->format('d/m/Y') }}
                                    @if ($case->citation_confirmed_time)
                                        — {{ \Illuminate\Support\Carbon::parse($case->citation_confirmed_time)->format('h:i A') }}
                                    @endif
                                </div>
                            @elseif ($citationSlotChoices->isNotEmpty())
                                <fieldset class="space-y-2">
                                    <legend class="sr-only">Propuestas de planeación</legend>
                                    @foreach ($citationSlotChoices as $choice)
                                        <label wire:key="slot-{{ $choice['key'] }}"
                                            class="flex cursor-pointer items-start gap-3 rounded-lg border px-3 py-3 transition
                                                {{ $selectedCitationSlotKey === $choice['key'] ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/30 dark:border-indigo-400 dark:bg-indigo-950/40' : 'border-slate-200 bg-white hover:border-indigo-300 dark:border-white/15 dark:bg-white/5 dark:hover:border-indigo-400/50' }}">
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
                                    @disabled($selectedCitationSlotKey === '')
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Confirmar fecha
                                </button>
                            @else
                                <p class="text-xs text-slate-600 dark:text-slate-400">Planeación aún no ha propuesto fechas estructuradas en el hilo.</p>
                            @endif
                        </div>
                    @endif
                @endcan

                @can('postAgendaPlanning', $case)
                    @if ($case->agendaThread)
                        <div class="space-y-2 border-t pt-3 dark:border-white/10">
                            <p class="text-xs font-semibold">Responder (planeación)</p>
                            <textarea wire:model="agendaPlanningBody" rows="2" class="w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white" placeholder="Observaciones…"></textarea>
                            @foreach ($planningSlots as $i => $slot)
                                <div class="grid grid-cols-3 gap-2">
                                    <input type="date" wire:model="planningSlots.{{ $i }}.date" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                    <input type="time" wire:model="planningSlots.{{ $i }}.time" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                    <input type="text" wire:model="planningSlots.{{ $i }}.notes" placeholder="Notas" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                </div>
                            @endforeach
                            <button type="button" wire:click="addPlanningSlotRow" class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">+ Otra fecha propuesta</button>
                            <input type="file" wire:model="agendaPlanningUploads" multiple accept="image/*,application/pdf" class="text-xs">
                            <button type="button" wire:click="postAgendaPlanning" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md">Publicar respuesta</button>
                        </div>
                    @endif
                @endcan
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
                    Generar y guardar FO-GJ-03
                </button>
            </div>
        @endcan

        @can('uploadCitationEvidence', $case)
            <div class="border-t border-indigo-200 pt-4 space-y-3 dark:border-white/10">
                <p class="text-xs font-semibold">Evidencia de notificación (PDF)</p>
                <select wire:model="citationEvidenceType" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                    <option value="">— Tipo —</option>
                    <option value="signed">Firmada por el trabajador</option>
                    <option value="refused_witnesses">Rechazo de firma (jefe + 2 testigos)</option>
                </select>
                <input type="file" wire:model="citationEvidenceFile" accept="application/pdf" class="text-sm">
                <button type="button" wire:click="uploadCitationEvidence"
                    class="inline-flex px-4 py-2 bg-emerald-700 text-white text-sm font-semibold rounded-md">
                    Cargar evidencia
                </button>
            </div>
        @endcan
    </div>

    @if ($showCitationAdvanceConfirm)
        <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-900/50" wire:key="citation-advance-confirm">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Confirmar avance de etapa</h2>
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                    Todos los requisitos de citación están completos. ¿Desea pasar el expediente a
                    <strong class="text-slate-900 dark:text-white">{{ $citationAdvanceTargetLabel ?? 'diligencia disciplinaria' }}</strong>?
                </p>
                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="closeCitationAdvanceConfirm"
                        class="px-4 py-2 text-sm font-semibold text-slate-700 rounded-md ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmAdvanceFromCitacion"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                        Confirmar y avanzar
                    </button>
                </div>
            </div>
        </div>
    @endif
@endif
