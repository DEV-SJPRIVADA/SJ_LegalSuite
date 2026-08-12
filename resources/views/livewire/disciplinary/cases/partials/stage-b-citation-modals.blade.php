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
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Texto después de los dos puntos (formulación de cargos) <span class="text-red-600">*</span></label>
                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                        Se inserta en el párrafo oficial tras la fecha del incumplimiento. Ej.: «no diligenció la minuta de rondas…».
                    </p>
                    <textarea wire:model="foGj03ChargesDescription" rows="3" required class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white" placeholder="Texto obligatorio que continúa después de «:»"></textarea>
                    @error('foGj03ChargesDescription')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Artículos y numerales (Reglamento Interno de Trabajo)</label>
                        <button type="button" wire:click="addFoGj03StatuteArticleRow"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-300 dark:hover:text-indigo-200">
                            + Agregar artículo
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Con una sola falta en el informe se precargan según Ajustes; con varias faltas se muestran los artículos y usted diligencia los numerales.
                    </p>

                    @forelse ($foGj03StatuteArticles as $index => $articleRow)
                        <div wire:key="fo-gj-03-article-{{ $index }}" class="grid gap-2 rounded-lg border border-slate-200 p-3 dark:border-white/10 sm:grid-cols-12">
                            <div class="sm:col-span-3">
                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Artículo</label>
                                <input type="text" wire:model="foGj03StatuteArticles.{{ $index }}.article_number" placeholder="Ej. 74"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                @error('foGj03StatuteArticles.'.$index.'.article_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-8">
                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Numerales</label>
                                <input type="text" wire:model="foGj03StatuteArticles.{{ $index }}.numerals" placeholder="Ej. 1, 3, 6.1, 32"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                @error('foGj03StatuteArticles.'.$index.'.numerals')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex items-end justify-end sm:col-span-1">
                                <button type="button" wire:click="removeFoGj03StatuteArticleRow({{ $index }})"
                                    class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950/40">
                                    Quitar
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 px-3 py-4 text-center text-sm text-slate-500 dark:border-white/15 dark:text-slate-400">
                            Sin artículos. Use «Agregar artículo» para diligenciar la citación.
                        </div>
                    @endforelse

                    @error('foGj03StatuteArticles')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Elementos probatorios</label>
                        <button type="button" wire:click="addFoGj03EvidenceItemRow"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-300 dark:hover:text-indigo-200">
                            + Agregar elemento
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        El informe disciplinario se incluye automáticamente. Agregue debajo otros elementos con viñeta (videos, testimonios, actas, etc.).
                    </p>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                        <span class="font-medium text-slate-800 dark:text-slate-100">• Informes Disciplinarios</span>
                        @if (filled($foGj03InformeReportDate))
                            <span class="text-slate-600 dark:text-slate-300"> del {{ $foGj03InformeReportDate }}</span>
                        @endif
                        <span class="ml-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">(automático)</span>
                    </div>

                    @foreach ($foGj03EvidenceItems as $index => $evidenceRow)
                        <div wire:key="fo-gj-03-evidence-{{ $index }}" class="flex gap-2">
                            <div class="min-w-0 flex-1">
                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Elemento adicional #{{ $index + 1 }}</label>
                                <input type="text" wire:model="foGj03EvidenceItems.{{ $index }}.text"
                                    placeholder="Ej. Video de cámara del puesto X"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                @error('foGj03EvidenceItems.'.$index.'.text')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex items-end">
                                <button type="button" wire:click="removeFoGj03EvidenceItemRow({{ $index }})"
                                    class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950/40">
                                    Quitar
                                </button>
                            </div>
                        </div>
                    @endforeach

                    @error('foGj03EvidenceItems')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
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

@if ($showFoGj03PdfPreviewModal ?? false)
    @php
        $foGj03PdfPreviewUrl = route('disciplinary.cases.fo-gj-03.pdf', ['case' => $case, 'inline' => 1]);
        $foGj03PdfDownloadUrl = route('disciplinary.cases.fo-gj-03.pdf', $case);
    @endphp
    <div class="fixed inset-0 z-[86] flex items-center justify-center p-3 sm:p-4"
        x-on:keydown.escape.window="$wire.closeFoGj03PdfPreview()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="fo-gj-03-pdf-preview-title"
        wire:key="fo-gj-03-pdf-preview-{{ $case->id }}">
        <div class="absolute inset-0 bg-black/50 dark:bg-black/60" wire:click="closeFoGj03PdfPreview" aria-hidden="true"></div>
        <div class="relative flex h-[min(92dvh,calc(100dvh-2rem))] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10 sm:px-5">
                <h2 id="fo-gj-03-pdf-preview-title" class="text-base font-bold text-slate-900 dark:text-white">
                    Vista previa · FO-GJ-03 ({{ $case->case_number }})
                </h2>
                <div class="flex items-center gap-2">
                    <a href="{{ $foGj03PdfDownloadUrl }}" target="_blank" rel="noopener"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:text-indigo-200 dark:ring-indigo-400/40 dark:hover:bg-white/10">
                        Descargar PDF
                    </a>
                    <button type="button" wire:click="closeFoGj03PdfPreview"
                        class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white"
                        aria-label="Cerrar">
                        ✕
                    </button>
                </div>
            </div>
            <div class="relative flex min-h-0 flex-1 flex-col">
                <iframe wire:ignore title="Vista previa FO-GJ-03"
                    class="min-h-0 flex-1 min-h-[200px] bg-slate-100 dark:bg-black/40"
                    src="{{ $foGj03PdfPreviewUrl }}"></iframe>
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

