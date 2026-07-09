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
    $showLegacyInformeHistory = $case->current_status === CaseStatus::INFORME
        && $agendaThread
        && $agendaThread->messages->isNotEmpty();
    $chatTitle = $isDecisionFlow && ! $isCitacionFlow
        ? 'Chat jurídico ↔ planeación (decisión)'
        : 'Chat jurídico ↔ planeación';
    $composerPlaceholder = $isDecisionFlow && ! $isCitacionFlow
        ? 'Mensaje a planeación sobre la decisión…'
        : 'Escriba un mensaje para Planeación…';
    $composerDisabled = $coordinationIsClosed && ! $showLegacyInformeHistory;
@endphp

@if ($showPlanningChatModal)
    <div class="fixed inset-0 z-[72] flex items-end justify-center bg-black/55 p-0 sm:items-center sm:p-4"
        x-data x-on:keydown.escape.window="$wire.closePlanningChatModal()">
        <div class="flex max-h-[min(88dvh,760px)] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl dark:bg-dash-ink dark:ring-1 dark:ring-white/15 sm:rounded-2xl"
            x-on:click.outside="$wire.closePlanningChatModal()">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-fuchsia-400/90">Coordinación</p>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Chat planeación</h3>
                </div>
                <button type="button" wire:click="closePlanningChatModal"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10"
                    aria-label="Cerrar chat">
                    ✕
                </button>
            </div>

            <div class="flex min-h-0 flex-1 flex-col" x-data="window.sjAgendaAttachmentLightbox()" x-on:open-agenda-lightbox="openAgendaAttachment($event.detail)"
                @if (! $composerDisabled) wire:poll.visible.10s @endif>
                <div class="flex-1 overflow-y-auto px-4 py-3 space-y-4">
                    @if ($showLegacyInformeHistory)
                        <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-3 dark:border-white/10 dark:bg-slate-900/40">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Historial anterior (etapa Informe)</h4>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">Solo lectura. La conversación vigente con planeación es la de citación o decisión.</p>
                            <ul class="mt-3 max-h-40 space-y-2 overflow-y-auto text-sm">
                                @foreach ($agendaThread->messages as $msg)
                                    <li class="rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/5">
                                        <div class="flex flex-wrap justify-between gap-2 text-xs text-slate-500">
                                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $msg->author?->name ?? '—' }}</span>
                                            <span>{{ $msg->created_at->format('Y-m-d H:i') }}</span>
                                        </div>
                                        <p class="mt-1 whitespace-pre-wrap text-slate-800 dark:text-slate-200">{{ $msg->body }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @unless ($showLegacyInformeHistory && $composerDisabled)
                        <div class="rounded-lg border border-slate-200 dark:border-white/10">
                            <div class="border-b border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                                {{ $chatTitle }}
                            </div>
                            <div class="max-h-72 overflow-y-auto p-3 space-y-3">
                                @if ($coordinationIsClosed)
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Coordinación cerrada — historial de solo lectura.</p>
                                @else
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Planeación responde desde <strong class="font-semibold text-slate-700 dark:text-slate-300">Coordinaciones</strong>.
                                    </p>
                                @endif

                                @if ($agendaThread && $agendaThread->messages->isNotEmpty())
                                    <ul class="space-y-2">
                                        @foreach ($agendaThread->messages as $msg)
                                            <x-disciplinary.agenda-message
                                                :message="$msg"
                                                :case="$case"
                                                :selectable-slots="$canSelectCitationSlot"
                                                wire:key="planning-chat-msg-{{ $msg->id }}" />
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="py-6 text-center text-sm italic text-slate-400">Sin mensajes aún. Escriba abajo para iniciar el diálogo.</p>
                                @endif
                            </div>

                            @can('postAgendaLawyer', $case)
                                @if (! $coordinationIsClosed)
                                    <div class="border-t border-slate-200 p-3 dark:border-white/10">
                                        <x-disciplinary.agenda-chat-composer
                                            body-model="agendaLawyerBody"
                                            uploads-property="agendaLawyerUploads"
                                            send-action="postAgendaLawyer"
                                            remove-upload-method="removeAgendaLawyerUploadAt"
                                            :uploads="$agendaLawyerUploads ?? []"
                                            :placeholder="$composerPlaceholder"
                                            :input-id="'planning-chat-lawyer-'.$case->id"
                                            error-field="agendaLawyerBody" />
                                    </div>
                                @endif
                            @endcan
                        </div>
                    @endunless
                </div>

                <x-disciplinary.agenda-attachment-lightbox-modal />
            </div>
        </div>
    </div>
@endif
