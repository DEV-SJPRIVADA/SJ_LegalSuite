@php
    use App\Enums\Disciplinary\CaseStatus;

    $agendaThread = $case->agendaThread;
    $coordinationIsClosed = $agendaThread?->isClosed() ?? false;
    $isCitacionFlow = in_array($case->current_status, [
        CaseStatus::CITACION_PROGRAMADA,
        CaseStatus::REPROGRAMADO,
        CaseStatus::CITACION_NO_ASISTIO,
        CaseStatus::JUSTIFICACION_PENDIENTE,
    ], true) || ($citationReadOnly ?? false);
    $isDecisionFlow = $case->current_status === CaseStatus::DECISION || ($showsDecisionStageReadOnly ?? false);
    $diligenceSlotDisplay = $diligenceSlotDisplay ?? ['date' => '—', 'time' => '—', 'confirmed' => false];
    $canSelectCitationSlot = $isCitacionFlow
        && ! ($citationReadOnly ?? false)
        && ! $coordinationIsClosed
        && ! ($diligenceSlotDisplay['confirmed'] ?? false)
        && auth()->user()->can('postAgendaLawyer', $case);
    $canSelectDecisionSlot = $isDecisionFlow
        && ! $isCitacionFlow
        && ! ($decisionReadOnly ?? false)
        && ! $coordinationIsClosed
        && ! ($case->hasDecisionNotificationConfirmed() ?? false)
        && auth()->user()->can('postAgendaLawyer', $case);
    $selectableSlots = $canSelectCitationSlot || $canSelectDecisionSlot;
    $slotWireModel = $canSelectDecisionSlot ? 'selectedDecisionSlotKey' : 'selectedCitationSlotKey';
    $showLegacyInformeHistory = $case->current_status === CaseStatus::INFORME
        && $agendaThread
        && $agendaThread->messages->isNotEmpty();
    $composerPlaceholder = $isDecisionFlow && ! $isCitacionFlow
        ? 'Mensaje a planeación sobre la decisión…'
        : 'Escriba un mensaje para Planeación…';
    $composerDisabled = $coordinationIsClosed && ! $showLegacyInformeHistory;
    $subtitle = $isDecisionFlow && ! $isCitacionFlow
        ? 'Coordinación de decisión'
        : 'Jurídico ↔ Planeación';
@endphp

@if ($showPlanningChatModal)
    <div
        class="fixed inset-0 z-[72] flex justify-end"
        x-data
        x-on:keydown.escape.window="$wire.closePlanningChatModal()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="planning-chat-title"
    >
        <button
            type="button"
            class="absolute inset-0 bg-slate-950/45 transition dark:bg-black/55"
            wire:click="closePlanningChatModal"
            aria-label="Cerrar chat"
        ></button>

        <aside
            class="relative flex h-full w-full max-w-[26rem] flex-col overflow-hidden border-l border-slate-200 bg-white dark:border-white/10 dark:bg-dash-ink sm:max-w-[24rem]"
            x-data="window.sjAgendaAttachmentLightbox()"
            x-on:open-agenda-lightbox="openAgendaAttachment($event.detail)"
            @if (! $composerDisabled) wire:poll.visible.10s @endif
        >
            <header class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-fuchsia-500/90 dark:text-fuchsia-400/90">Coordinación</p>
                    <h3 id="planning-chat-title" class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                        Chat planeación
                    </h3>
                    <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                        {{ $subtitle }} · <span class="font-mono">{{ $case->case_number }}</span>
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="closePlanningChatModal"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-white/10"
                    aria-label="Cerrar chat"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </header>

            <div
                class="min-h-0 flex-1 overflow-y-auto bg-slate-50 px-3 py-4 dark:bg-dash-ink/80"
                wire:key="planning-chat-scroll-{{ $agendaThread?->messages->count() ?? 0 }}-{{ optional($agendaThread?->messages->last())->id ?? 0 }}"
                x-data
                x-init="$nextTick(() => { $el.scrollTop = $el.scrollHeight })"
            >
                @if ($showLegacyInformeHistory)
                    <div class="mb-4 rounded-xl bg-white/80 px-3 py-2.5 text-xs text-slate-600 ring-1 ring-slate-200/80 dark:bg-white/[0.04] dark:text-slate-300 dark:ring-white/10">
                        <p class="font-semibold text-slate-800 dark:text-slate-200">Historial etapa Informe</p>
                        <p class="mt-0.5">Solo lectura. La conversación vigente es la de citación o decisión.</p>
                        <ul class="mt-3 max-h-36 space-y-2 overflow-y-auto">
                            @foreach ($agendaThread->messages as $msg)
                                <li class="rounded-lg bg-slate-50 px-2.5 py-2 dark:bg-black/20">
                                    <div class="flex justify-between gap-2 text-[10px] text-slate-500">
                                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $msg->author?->name ?? '—' }}</span>
                                        <span>{{ $msg->created_at->format('d/m H:i') }}</span>
                                    </div>
                                    <p class="mt-1 whitespace-pre-wrap text-xs text-slate-700 dark:text-slate-200">{{ $msg->body }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @unless ($showLegacyInformeHistory && $composerDisabled)
                    @if ($coordinationIsClosed)
                        <p class="mb-3 text-center text-[11px] text-slate-500 dark:text-slate-400">
                            Coordinación cerrada — historial de solo lectura
                        </p>
                    @endif

                    @if ($agendaThread && $agendaThread->messages->isNotEmpty())
                        <ul class="space-y-3.5">
                            @foreach ($agendaThread->messages as $msg)
                                <x-disciplinary.agenda-message
                                    :message="$msg"
                                    :case="$case"
                                    perspective="lawyer"
                                    :selectable-slots="$selectableSlots"
                                    :slot-wire-model="$slotWireModel"
                                    wire:key="planning-chat-msg-{{ $msg->id }}" />
                            @endforeach
                        </ul>
                    @else
                        <div class="flex h-full min-h-[12rem] flex-col items-center justify-center px-4 text-center">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Sin mensajes aún</p>
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Escriba abajo para iniciar el diálogo con planeación.</p>
                        </div>
                    @endif
                @endunless
            </div>

            @can('postAgendaLawyer', $case)
                @if (! $coordinationIsClosed && ! ($showLegacyInformeHistory && $composerDisabled))
                    <div class="shrink-0 border-t border-slate-200 bg-white p-3 dark:border-white/10 dark:bg-dash-ink">
                        @if ($canSelectDecisionSlot && ($selectedDecisionSlotKey ?? '') !== '')
                            <div class="mb-2 flex justify-end">
                                <button type="button" wire:click="confirmDecisionSlot"
                                    class="inline-flex items-center rounded-md bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-700">
                                    Confirmar opción seleccionada
                                </button>
                            </div>
                        @endif
                        <x-disciplinary.agenda-chat-composer
                            body-model="agendaLawyerBody"
                            uploads-property="agendaLawyerUploads"
                            send-action="postAgendaLawyer"
                            remove-upload-method="removeAgendaLawyerUploadAt"
                            :uploads="$agendaLawyerUploads ?? []"
                            :placeholder="$composerPlaceholder"
                            :input-id="'planning-chat-lawyer-'.$case->id"
                            error-field="agendaLawyerBody"
                            variant="drawer" />
                    </div>
                @endif
            @endcan

            <x-disciplinary.agenda-attachment-lightbox-modal />
        </aside>
    </div>
@endif
