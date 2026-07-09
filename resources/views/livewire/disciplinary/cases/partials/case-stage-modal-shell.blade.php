@if ($openStageModal !== '')
    <div class="fixed inset-0 z-[68] flex items-end justify-center bg-black/55 p-0 sm:items-center sm:p-4"
        x-data x-on:keydown.escape.window="$wire.closeStageModal()">
        <div class="flex max-h-[min(92dvh,900px)] w-full max-w-5xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl dark:bg-dash-ink dark:ring-1 dark:ring-white/15 sm:rounded-2xl"
            x-on:click.outside="$wire.closeStageModal()">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-fuchsia-400/90">Gestión de etapa</p>
                    <h3 class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                        @switch($openStageModal)
                            @case('a') Etapa A · Informe disciplinario @break
                            @case('b') Etapa B · Citación a diligencia @break
                            @case('c') Etapa C · Diligencia y acta @break
                            @case('d') Etapa D · Comunicado de decisión @break
                        @endswitch
                        @if ($stageModalReadOnly)
                            <span class="ml-2 text-xs font-medium text-slate-500 dark:text-slate-400">(solo lectura)</span>
                        @endif
                    </h3>
                </div>
                <button type="button" wire:click="closeStageModal"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:hover:bg-white/10 dark:hover:text-white"
                    aria-label="Cerrar">
                    ✕
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-5">
                @include('livewire.disciplinary.cases.partials.case-stage-modal-body')
            </div>
        </div>
    </div>
@endif
