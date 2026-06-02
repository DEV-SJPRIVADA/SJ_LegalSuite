@if ($showReassignSupervisorModal ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-900/50" wire:key="reassign-supervisor-modal">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Reasignar supervisor de notificación</h2>
            <div class="mt-4 space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Supervisor nuevo</label>
                    <select wire:model="reassignSupervisorUserId" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                        <option value="">— Seleccione —</option>
                        @foreach ($supervisorCandidates ?? [] as $supervisor)
                            @if ((int) $supervisor->id !== (int) $case->notification_supervisor_user_id)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('reassignSupervisorUserId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Motivo (obligatorio)</label>
                    <textarea wire:model="reassignSupervisorReason" rows="3" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"></textarea>
                    @error('reassignSupervisorReason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="closeReassignSupervisorModal" class="px-4 py-2 text-sm font-semibold text-slate-700 rounded-md ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">Cancelar</button>
                <button type="button" wire:click="confirmReassignNotificationSupervisor" class="px-4 py-2 text-sm font-semibold text-white bg-amber-600 rounded-md hover:bg-amber-700">Confirmar</button>
            </div>
        </div>
    </div>
@endif

@if ($showCitationAdvanceConfirm ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-900/50" wire:key="citation-advance-confirm">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Confirmar avance de etapa</h2>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                ¿Pasar el expediente a <strong>{{ $citationAdvanceTargetLabel ?? 'diligencia disciplinaria' }}</strong>?
            </p>
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="closeCitationAdvanceConfirm" class="px-4 py-2 text-sm font-semibold text-slate-700 rounded-md ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">Cancelar</button>
                <button type="button" wire:click="confirmAdvanceFromCitacion" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Confirmar y avanzar</button>
            </div>
        </div>
    </div>
@endif

@if ($showCloseCoordinationConfirm ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-900/50" wire:key="coordination-close-confirm">
        <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Cerrar coordinación</h2>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                Planeación dejará de ver este caso en su bandeja y no podrá responder más en el chat.
            </p>
            @if (!empty($closeCoordinationBlockers))
                <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-500/40 dark:bg-amber-950/40">
                    <p class="text-sm font-semibold text-amber-950 dark:text-amber-100">Complete antes de cerrar:</p>
                    <ul class="mt-2 list-disc pl-5 text-sm text-amber-900 dark:text-amber-100 space-y-1">
                        @foreach ($closeCoordinationBlockers as $blocker)
                            <li>{{ $blocker }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="mt-3 text-xs text-emerald-700 dark:text-emerald-300">Todos los pasos de coordinación con Planeación están completos.</p>
            @endif
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="closeCloseCoordinationConfirm" class="px-4 py-2 text-sm font-semibold text-slate-700 rounded-md ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">Cancelar</button>
                <button type="button" wire:click="confirmCloseCoordination"
                    @disabled(!empty($closeCoordinationBlockers))
                    class="px-4 py-2 text-sm font-semibold text-white bg-slate-700 rounded-md hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Confirmar cierre
                </button>
            </div>
        </div>
    </div>
@endif
