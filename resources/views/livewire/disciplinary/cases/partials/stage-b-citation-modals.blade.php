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

@if ($showFoGj03DraftModal ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-4 bg-slate-900/50 overflow-y-auto" wire:key="fo-gj-03-draft-modal">
        <div class="w-full max-w-2xl my-6 rounded-xl bg-white p-6 shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Diligenciar FO-GJ-03 · Citación</h2>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                Complete los campos obligatorios. Los datos del trabajador y la fecha del informe se toman del expediente.
            </p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                    <p><span class="font-semibold">Caso:</span> <span class="font-mono">{{ $case->case_number }}</span></p>
                    <p class="mt-1"><span class="font-semibold">Trabajador:</span> {{ $case->employee?->first_name }} {{ $case->employee?->last_name }} · {{ $case->employee?->document_number }}</p>
                    <p class="mt-1"><span class="font-semibold">Fecha diligencia:</span> {{ $case->citation_confirmed_date?->format('d/m/Y') ?? '—' }}</p>
                    <p class="mt-1"><span class="font-semibold">Fecha informe:</span> {{ $foGj03InformeReportDate ?: '—' }}</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Hora de diligencia (editable)</label>
                    <input type="time" wire:model="foGj03HearingTime" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                    @error('foGj03HearingTime')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Modalidad</label>
                    <select wire:model.live="foGj03Modality" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                        <option value="presencial">Presencial (SJ Seguridad · Cali)</option>
                        <option value="virtual">Virtual (enlace de reunión)</option>
                    </select>
                    @error('foGj03Modality')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @if ($foGj03Modality === 'virtual')
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Enlace reunión virtual</label>
                        <input type="url" wire:model="foGj03VirtualLink" placeholder="https://..." class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                        @error('foGj03VirtualLink')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Fecha del incumplimiento</label>
                    <input type="date" wire:model="foGj03BreachDate" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                    @error('foGj03BreachDate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Descripción de hechos (formulación de cargos)</label>
                    <textarea wire:model="foGj03ChargesDescription" rows="4" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"></textarea>
                    @error('foGj03ChargesDescription')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Art. 66 — numerales</label>
                    <input type="text" wire:model="foGj03Article66Numerals" placeholder="Ej. 1, 3, 4, 6" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                    @error('foGj03Article66Numerals')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Art. 68 — numerales</label>
                    <input type="text" wire:model="foGj03Article68Numerals" placeholder="Ej. 10, 34" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                    @error('foGj03Article68Numerals')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Art. 76 — numerales</label>
                    <input type="text" wire:model="foGj03Article76Numerals" placeholder="Ej. 3, 12, 15, 22" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                    @error('foGj03Article76Numerals')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @unless (auth()->user()->hasSignature())
                    <div class="sm:col-span-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-950 dark:border-amber-500/40 dark:bg-amber-950/30 dark:text-amber-100">
                        Suba su firma digital en <a href="{{ route('profile') }}" class="font-semibold underline" target="_blank" rel="noopener">Mi perfil</a> antes de guardar.
                    </div>
                @endunless

                @error('fo_gj_03')
                    <p class="sm:col-span-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="closeFoGj03DraftModal" class="px-4 py-2 text-sm font-semibold text-slate-700 rounded-md ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">Cancelar</button>
                <button type="button" wire:click="saveFoGj03Draft" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Guardar diligenciamiento</button>
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

