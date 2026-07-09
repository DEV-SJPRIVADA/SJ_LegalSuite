@if ($showDiligenceAttendanceConfirm ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-900/50" wire:key="diligence-attendance-confirm">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Confirmar asistencia</h2>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                ¿Registrar que el trabajador
                <strong>{{ $diligenceAttendancePending === 'attended' ? 'asistió' : 'no asistió' }}</strong>
                a la diligencia programada?
            </p>
            <p class="mt-2 text-xs text-amber-700 dark:text-amber-300">Esta decisión no podrá modificarse después.</p>
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="closeDiligenceAttendanceConfirm" class="rounded-md px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">Cancelar</button>
                <button type="button" wire:click="confirmRegisterDiligenceAttendance" class="rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">Confirmar</button>
            </div>
        </div>
    </div>
@endif

@if ($showDiligenceAdvanceConfirm ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-900/50" wire:key="diligence-advance-confirm">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Confirmar avance de etapa</h2>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                ¿Pasar el expediente a <strong>{{ $diligenceAdvanceTargetLabel ?? 'comunicado de decisión' }}</strong>?
            </p>
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="closeDiligenceAdvanceConfirm" class="rounded-md px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">Cancelar</button>
                <button type="button" wire:click="confirmAdvanceFromDiligencia" class="rounded-md bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">Confirmar y avanzar</button>
            </div>
        </div>
    </div>
@endif

@if ($showJustificationRejectConfirm ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-900/50" wire:key="justification-reject-confirm">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Rechazar justificación</h2>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                El expediente pasará a <strong>comité disciplinario</strong>. Opcionalmente indique el motivo:
            </p>
            <textarea wire:model="justificationRejectNote" rows="3" class="mt-3 w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white" placeholder="Motivo del rechazo (opcional)"></textarea>
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="closeJustificationRejectConfirm" class="rounded-md px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">Cancelar</button>
                <button type="button" wire:click="confirmRejectDiligenceJustification" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Confirmar rechazo</button>
            </div>
        </div>
    </div>
@endif
