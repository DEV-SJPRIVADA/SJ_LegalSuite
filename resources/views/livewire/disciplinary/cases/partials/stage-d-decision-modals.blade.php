@php
    use App\Enums\Disciplinary\Decision;
    use App\Support\Disciplinary\DecisionBranch;
    use App\Support\Disciplinary\FoGj46HearingLead;

    $isFoGj46 = ($case->decision ?? null) === Decision::AMONESTACION_ESCRITA;
    $isFoGj47 = ($case->decision ?? null) === Decision::SUSPENSION;
    $isFoGj45 = in_array($case->decision ?? null, [
        Decision::AMONESTACION_VERBAL,
        Decision::ABSUELTO,
        Decision::ARCHIVADO,
    ], true);
@endphp

@if ($showDecisionTypeModal ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-black/50" wire:keydown.escape.window="closeDecisionTypeModal" wire:key="decision-type-modal">
        <div class="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-dash-ink dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Tipo de decisión disciplinaria</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Seleccione la rama y el tipo de sanción o cierre.</p>
            </div>
            <div class="space-y-4 px-5 py-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Rama</label>
                    <select wire:model.live="decisionBranchSelection" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5">
                        <option value="">— Seleccione —</option>
                        <option value="{{ DecisionBranch::SUSPENSION }}">{{ DecisionBranch::label(DecisionBranch::SUSPENSION) }}</option>
                        <option value="{{ DecisionBranch::NOTICE }}">{{ DecisionBranch::label(DecisionBranch::NOTICE) }}</option>
                        <option value="{{ DecisionBranch::TERMINATION }}">{{ DecisionBranch::label(DecisionBranch::TERMINATION) }}</option>
                        <option value="{{ DecisionBranch::CLOSURE }}">{{ DecisionBranch::label(DecisionBranch::CLOSURE) }}</option>
                    </select>
                    @error('decisionBranchSelection') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                @if ($decisionBranchSelection !== '')
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tipo específico</label>
                        <select wire:model="decisionTypeSelection" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5">
                            <option value="">— Seleccione —</option>
                            @foreach (DecisionBranch::choicesForBranch($decisionBranchSelection) as $choice)
                                <option value="{{ $choice->value }}">{{ $choice->label() }}</option>
                            @endforeach
                        </select>
                        @error('decisionTypeSelection') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
            <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-white/10">
                <button type="button" wire:click="closeDecisionTypeModal" class="rounded-md px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10">Cancelar</button>
                <button type="button" wire:click="confirmDecisionType" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">Confirmar</button>
            </div>
        </div>
    </div>
@endif

@if ($showDecisionDraftModal ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-black/50" wire:keydown.escape.window="closeDecisionDraftModal" wire:key="decision-draft-modal">
        <div class="flex max-h-[90vh] w-full max-w-2xl flex-col rounded-xl bg-white shadow-xl dark:bg-dash-ink dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
            <div class="shrink-0 border-b border-slate-200 px-5 py-4 dark:border-white/10">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                    @if ($isFoGj46)
                        Diligenciar FO-GJ-46 · Llamado de atención
                    @elseif ($isFoGj47)
                        Diligenciar FO-GJ-47 · Suspensión disciplinaria
                    @elseif ($isFoGj45)
                        Diligenciar FO-GJ-45 · Acta de archivo
                    @else
                        Diligenciar comunicado de decisión
                    @endif
                </h3>
                @if ($isFoGj46)
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Modalidad, fechas de diligencia e incumplimiento se toman del FO-GJ-03 / citación.
                    </p>
                @elseif ($isFoGj47)
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Indique los días; el sistema calcula fin y retorno a partir de la fecha de inicio (planeación). Arts. 55/57/60 desde FO-GJ-03.
                    </p>
                @elseif ($isFoGj45)
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Digite el párrafo completo, incluyendo «esta Dirección ha RESUELTO:». Los resolutivos PRIMERO/SEGUNDO y el firmante (Cordialmente) son editables.
                    </p>
                @endif
            </div>
            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                @if ($isFoGj46)
                    <div>
                        <label class="block text-sm font-medium">Apertura del párrafo (obligatorio)</label>
                        <select wire:model="foGj46HearingLead" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5">
                            <option value="">— Seleccione —</option>
                            @foreach (FoGj46HearingLead::cases() as $lead)
                                <option value="{{ $lead->value }}">{{ $lead->label() }}</option>
                            @endforeach
                        </select>
                        @error('foGj46HearingLead') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Relato de hechos (después de la fecha de incumplimiento)</label>
                        <textarea wire:model="foGj46FactsNarrative" rows="6" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" placeholder="…incurrió en…"></textarea>
                        @error('foGj46FactsNarrative') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium">Art. 55 (numerales)</label>
                            <input type="text" wire:model="foGj46Articles55" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj46Articles55') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Art. 57 (numerales)</label>
                            <input type="text" wire:model="foGj46Articles57" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj46Articles57') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Art. 60 (numerales)</label>
                            <input type="text" wire:model="foGj46Articles60" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj46Articles60') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium">Nombre de quien firma</label>
                            <input type="text" wire:model="foGj46SignerName" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj46SignerName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Cargo del firmante</label>
                            <input type="text" wire:model="foGj46SignerTitle" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj46SignerTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @elseif ($isFoGj47)
                    <div>
                        <label class="block text-sm font-medium">Párrafo introductorio (obligatorio)</label>
                        <textarea wire:model="foGj47OpeningNarrative" rows="7" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" placeholder="Por medio de la presente me permito comunicarle que…"></textarea>
                        @error('foGj47OpeningNarrative') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium">Días de suspensión</label>
                            <input type="number" min="1" max="90" wire:model="foGj47SuspensionDays" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj47SuspensionDays') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Inicio de la sanción</label>
                            <input type="date" wire:model="foGj47SuspensionStart" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            <p class="mt-1 text-xs text-slate-500">Preferible la fecha confirmada por planeación; el sistema calcula fin y retorno.</p>
                            @error('foGj47SuspensionStart') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium">Art. 55 (numerales)</label>
                            <input type="text" wire:model="foGj47Articles55" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj47Articles55') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Art. 57 (numerales)</label>
                            <input type="text" wire:model="foGj47Articles57" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj47Articles57') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Art. 60 (numerales)</label>
                            <input type="text" wire:model="foGj47Articles60" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj47Articles60') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium">Nombre firmante (Gestión Humana)</label>
                            <input type="text" wire:model="foGj47SignerName" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj47SignerName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Cargo del firmante</label>
                            <input type="text" wire:model="foGj47SignerTitle" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj47SignerTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @elseif ($isFoGj45)
                    <div>
                        <label class="block text-sm font-medium">Párrafo del acta (obligatorio)</label>
                        <textarea wire:model="foGj45BodyParagraph" rows="8" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" placeholder="Por medio de la presente, me permito comunicarle que, dando cumplimiento al debido proceso en el marco del trámite disciplinario iniciado con el informe de fecha … de … de …, derivado de …, esta Dirección ha RESUELTO:"></textarea>
                        @error('foGj45BodyParagraph') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">PRIMERO</label>
                        <textarea wire:model="foGj45ResolutiveFirst" rows="2" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5"></textarea>
                        @error('foGj45ResolutiveFirst') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">SEGUNDO</label>
                        <textarea wire:model="foGj45ResolutiveSecond" rows="2" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5"></textarea>
                        @error('foGj45ResolutiveSecond') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium">Nombre de quien firma</label>
                            <input type="text" wire:model="foGj45SignerName" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj45SignerName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Cargo del firmante</label>
                            <input type="text" wire:model="foGj45SignerTitle" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('foGj45SignerTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-medium">Asunto</label>
                        <input type="text" wire:model="decisionSubject" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                        @error('decisionSubject') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Cuerpo del comunicado</label>
                        <textarea wire:model="decisionBodyNarrative" rows="8" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5"></textarea>
                        @error('decisionBodyNarrative') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    @if ($decisionBranch === DecisionBranch::TERMINATION)
                        <div>
                            <label class="block text-sm font-medium">Observaciones de relevo</label>
                            <textarea wire:model="decisionReliefNotes" rows="3" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5"></textarea>
                            @error('decisionReliefNotes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                @endif
            </div>
            <div class="flex shrink-0 justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-white/10">
                <button type="button" wire:click="closeDecisionDraftModal" class="rounded-md px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10">Cancelar</button>
                <button type="button" wire:click="saveDecisionDraft" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">Guardar borrador</button>
            </div>
        </div>
    </div>
@endif

@if ($showDecisionPdfPreviewModal ?? false)
    <div class="fixed inset-0 z-[86] flex flex-col bg-black/60 p-4" wire:keydown.escape.window="closeDecisionPdfPreview" wire:key="decision-pdf-preview">
        <div class="mx-auto flex h-full w-full max-w-4xl flex-col rounded-xl bg-white shadow-xl dark:bg-dash-ink">
            <div class="flex shrink-0 items-center justify-between border-b px-4 py-3 dark:border-white/10">
                <h3 class="font-semibold text-slate-900 dark:text-white">
                    @if ($isFoGj46)
                        Vista previa · FO-GJ-46
                    @elseif ($isFoGj47)
                        Vista previa · FO-GJ-47
                    @elseif ($isFoGj45)
                        Vista previa · FO-GJ-45
                    @else
                        Vista previa · Comunicado de decisión
                    @endif
                </h3>
                <button type="button" wire:click="closeDecisionPdfPreview" class="text-sm font-medium text-violet-700 hover:underline dark:text-violet-300">Cerrar</button>
            </div>
            <iframe class="min-h-0 flex-1" src="{{ route('disciplinary.cases.decision-comunicado.pdf', ['case' => $case, 'inline' => 1]) }}"></iframe>
        </div>
    </div>
@endif

@if ($showDecisionFinalizeConfirm ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-black/50" wire:key="decision-finalize-confirm">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-dash-ink dark:ring-1 dark:ring-white/10">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Finalizar proceso disciplinario</h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Confirme que la notificación y los requisitos de cierre están completos.</p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" wire:click="cancelFinalizeDecision" class="rounded-md px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300">Cancelar</button>
                <button type="button" wire:click="confirmFinalizeDecision" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">Confirmar cierre</button>
            </div>
        </div>
    </div>
@endif
