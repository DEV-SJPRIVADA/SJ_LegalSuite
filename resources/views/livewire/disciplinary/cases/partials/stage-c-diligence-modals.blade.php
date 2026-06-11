@php
    use App\Services\Disciplinary\FoGj04DraftService;
@endphp

@if ($showFoGj04DraftModal ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-3 sm:p-4 bg-slate-900/50" wire:key="fo-gj-04-draft-modal">
        <div class="flex h-[min(92dvh,calc(100dvh-1.5rem))] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10" role="dialog" aria-modal="true" aria-labelledby="fo-gj-04-draft-title">
            <div class="shrink-0 border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-white/10">
                <h2 id="fo-gj-04-draft-title" class="text-lg font-bold text-slate-900 dark:text-white">Diligenciar FO-GJ-04 · Acta de diligencia</h2>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                    Complete el contexto, la manifestación del trabajador y el cuestionario. Los datos del trabajador y la fecha se toman del expediente.
                </p>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                    <p><span class="font-semibold">Caso:</span> <span class="font-mono">{{ $case->case_number }}</span></p>
                    <p class="mt-1"><span class="font-semibold">Trabajador:</span> {{ $case->employee?->first_name }} {{ $case->employee?->last_name }} · {{ $case->employee?->document_number }}</p>
                    <p class="mt-1"><span class="font-semibold">Fecha diligencia:</span> {{ $case->citation_confirmed_date?->format('d/m/Y') ?? '—' }}</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Hora de inicio</label>
                    <input type="text" wire:model="foGj04OpeningTime" placeholder="Ej. 10:30 AM"
                        class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                    @error('foGj04OpeningTime')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Hora de finalización</label>
                    <input type="text" wire:model="foGj04ClosingTime" placeholder="Ej. 11:45 AM"
                        class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                    @error('foGj04ClosingTime')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @php
                    $fo03Citation = app(FoGj04DraftService::class)->citationDataFromFo03($case);
                    $fo03Missing = app(FoGj04DraftService::class)->missingFo03CitationData($case);
                @endphp
                <div class="sm:col-span-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Fecha del incumplimiento y cargos (desde FO-GJ-03)</p>
                    @if ($fo03Missing === [])
                        <p class="mt-1 text-slate-700 dark:text-slate-300">
                            Día {{ $fo03Citation['breach_day'] }} de {{ $fo03Citation['breach_month'] }} de {{ $fo03Citation['breach_year'] }} —
                            {{ $fo03Citation['charges_description'] }}
                        </p>
                    @else
                        <p class="mt-1 text-amber-800 dark:text-amber-200">
                            Complete el FO-GJ-03 antes de diligenciar el acta. Falta: {{ implode(', ', $fo03Missing) }}.
                        </p>
                    @endif
                </div>

                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Manifestación del trabajador <span class="text-red-600">*</span></p>
                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                        Texto fijo en el acta: «Una vez enterado y entiendo perfectamente sus derechos, EL TRABAJADOR, manifestó:»
                    </p>
                    <div class="mt-2 space-y-2">
                        @foreach (FoGj04DraftService::manifestationOptions() as $value => $label)
                            <label class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300">
                                <input type="radio" wire:model="foGj04WorkerManifestation" value="{{ $value }}"
                                    class="mt-0.5 border-slate-300 text-teal-600 focus:ring-teal-500 dark:border-white/20">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('foGj04WorkerManifestation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Cuestionario <span class="text-red-600">*</span></label>
                        <button type="button" wire:click="addFoGj04Question"
                            class="rounded-md px-2 py-1 text-xs font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:text-teal-200 dark:ring-teal-400/40">
                            + Agregar pregunta
                        </button>
                    </div>
                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                        Redacte cada pregunta y la respuesta que manifestó el trabajador. Al guardar se normalizan los signos ¿?.
                        La firma del trabajador quedará en blanco para captura posterior.
                    </p>

                    @if (count($foGj04Questions ?? []) === 0)
                        <p class="mt-2 rounded-lg border border-dashed border-slate-300 px-3 py-4 text-center text-xs text-slate-500 dark:border-white/15 dark:text-slate-400">
                            Sin preguntas. Use «Agregar pregunta» para comenzar el cuestionario.
                        </p>
                    @else
                        <div class="mt-2 space-y-4">
                            @foreach ($foGj04Questions as $index => $question)
                                <div class="rounded-lg border border-slate-200 p-3 dark:border-white/10" wire:key="fo-gj-04-q-{{ $index }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Pregunta {{ $index + 1 }}</span>
                                        <button type="button" wire:click="removeFoGj04Question({{ $index }})"
                                            class="shrink-0 rounded-md px-2 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-300 hover:bg-red-50 dark:text-red-300 dark:ring-red-500/40"
                                            title="Quitar pregunta">
                                            Quitar
                                        </button>
                                    </div>
                                    <label class="mt-2 block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Pregunta</label>
                                    <input type="text" wire:model="foGj04Questions.{{ $index }}.question"
                                        placeholder="Ej. Reconoce los hechos descritos en la citación"
                                        class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                    <label class="mt-2 block text-[11px] font-semibold text-slate-600 dark:text-slate-400">R: Respuesta del trabajador <span class="text-red-600">*</span></label>
                                    <textarea wire:model="foGj04Questions.{{ $index }}.answer" rows="3"
                                        placeholder="Transcripción de lo manifestado por el trabajador"
                                        class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"></textarea>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @error('foGj04Questions')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @unless (auth()->user()->hasSignature())
                    <div class="sm:col-span-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-950 dark:border-amber-500/40 dark:bg-amber-950/30 dark:text-amber-100">
                        Suba su firma digital en <a href="{{ route('profile') }}" class="font-semibold underline" target="_blank" rel="noopener">Mi perfil</a> antes de guardar.
                    </div>
                @endunless

                @error('fo_gj_04')
                    <p class="sm:col-span-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            </div>

            <div class="shrink-0 flex flex-wrap justify-end gap-2 border-t border-slate-200 px-4 py-4 sm:px-6 dark:border-white/10">
                <button type="button" wire:click="closeFoGj04DraftModal" class="px-4 py-2 text-sm font-semibold text-slate-700 rounded-md ring-1 ring-slate-300 dark:text-slate-200 dark:ring-white/20">Cancelar</button>
                <button type="button" wire:click="saveFoGj04Draft" class="px-4 py-2 text-sm font-semibold text-white bg-teal-600 rounded-md hover:bg-teal-700">Guardar diligenciamiento</button>
            </div>
        </div>
    </div>
@endif

@if ($showFoGj04PdfPreviewModal ?? false)
    @php
        $foGj04PdfPreviewUrl = route('disciplinary.cases.fo-gj-04.pdf', ['case' => $case, 'inline' => 1]);
        $foGj04PdfDownloadUrl = route('disciplinary.cases.fo-gj-04.pdf', $case);
    @endphp
    <div class="fixed inset-0 z-[86] flex items-center justify-center p-3 sm:p-4"
        x-on:keydown.escape.window="$wire.closeFoGj04PdfPreview()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="fo-gj-04-pdf-preview-title"
        wire:key="fo-gj-04-pdf-preview-{{ $case->id }}">
        <div class="absolute inset-0 bg-black/50 dark:bg-black/60" wire:click="closeFoGj04PdfPreview" aria-hidden="true"></div>
        <div class="relative flex h-[min(92dvh,calc(100dvh-2rem))] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10 sm:px-5">
                <h2 id="fo-gj-04-pdf-preview-title" class="text-base font-bold text-slate-900 dark:text-white">
                    Vista previa · FO-GJ-04 ({{ $case->case_number }})
                </h2>
                <div class="flex items-center gap-2">
                    <a href="{{ $foGj04PdfDownloadUrl }}" target="_blank" rel="noopener"
                        class="rounded-md px-3 py-1.5 text-xs font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:text-teal-200 dark:ring-teal-400/40 dark:hover:bg-white/10">
                        Descargar PDF
                    </a>
                    <button type="button" wire:click="closeFoGj04PdfPreview"
                        class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white"
                        aria-label="Cerrar">
                        ✕
                    </button>
                </div>
            </div>
            <div class="relative flex min-h-0 flex-1 flex-col">
                <iframe wire:ignore title="Vista previa FO-GJ-04"
                    class="min-h-0 flex-1 min-h-[200px] bg-slate-100 dark:bg-black/40"
                    src="{{ $foGj04PdfPreviewUrl }}"></iframe>
            </div>
        </div>
    </div>
@endif
