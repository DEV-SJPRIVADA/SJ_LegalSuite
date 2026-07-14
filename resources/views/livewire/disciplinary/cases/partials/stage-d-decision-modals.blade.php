@php
    use App\Enums\Disciplinary\Decision;
    use App\Support\Disciplinary\DecisionBranch;
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
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Diligenciar comunicado de decisión</h3>
            </div>
            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
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
                @if ($decisionBranch && \App\Support\Disciplinary\DecisionBranch::requiresSuspensionDates($decisionBranch))
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium">Inicio suspensión</label>
                            <input type="date" wire:model="decisionSuspensionStart" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('decisionSuspensionStart') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Fin suspensión</label>
                            <input type="date" wire:model="decisionSuspensionEnd" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5" />
                            @error('decisionSuspensionEnd') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif
                @if ($decisionBranch === DecisionBranch::TERMINATION)
                    <div>
                        <label class="block text-sm font-medium">Observaciones de relevo</label>
                        <textarea wire:model="decisionReliefNotes" rows="3" class="mt-1 w-full rounded-md border-slate-300 dark:border-white/20 dark:bg-white/5"></textarea>
                        @error('decisionReliefNotes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
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
                <h3 class="font-semibold text-slate-900 dark:text-white">Vista previa · Comunicado de decisión</h3>
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
