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
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Cuestionario <span class="text-red-600">*</span></label>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" wire:click="openFoGj04CatalogPicker"
                                class="rounded-md px-2 py-1 text-xs font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:text-indigo-200 dark:ring-indigo-400/40">
                                + Desde catálogo
                            </button>
                            <button type="button" wire:click="addFoGj04Question"
                                class="rounded-md px-2 py-1 text-xs font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:text-teal-200 dark:ring-teal-400/40">
                                + Pregunta personalizada
                            </button>
                        </div>
                    </div>
                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                        Seleccione preguntas del catálogo (texto fijo) o cree una personalizada solo para esta acta.
                        Puede reordenarlas. Al guardar se normalizan los signos ¿?.
                    </p>

                    @if (count($foGj04Questions ?? []) === 0)
                        <p class="mt-2 rounded-lg border border-dashed border-slate-300 px-3 py-4 text-center text-xs text-slate-500 dark:border-white/15 dark:text-slate-400">
                            Sin preguntas. Use «Desde catálogo» o «Pregunta personalizada».
                        </p>
                    @else
                        <div class="mt-2 space-y-4">
                            @foreach ($foGj04Questions as $index => $question)
                                @php
                                    $isCatalog = ($question['source'] ?? 'custom') === 'catalog';
                                @endphp
                                <div class="rounded-lg border border-slate-200 p-3 dark:border-white/10" wire:key="fo-gj-04-q-{{ $index }}-{{ $question['catalog_question_id'] ?? 'c' }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Pregunta {{ $index + 1 }}</span>
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $isCatalog ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200' : 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-300' }}">
                                                {{ $isCatalog ? 'Catálogo' : 'Personalizada' }}
                                            </span>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1">
                                            <button type="button" wire:click="moveFoGj04QuestionUp({{ $index }})"
                                                class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:text-slate-200 dark:ring-white/20"
                                                title="Subir">↑</button>
                                            <button type="button" wire:click="moveFoGj04QuestionDown({{ $index }})"
                                                class="rounded-md px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:text-slate-200 dark:ring-white/20"
                                                title="Bajar">↓</button>
                                            <button type="button" wire:click="removeFoGj04Question({{ $index }})"
                                                class="rounded-md px-2 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-300 hover:bg-red-50 dark:text-red-300 dark:ring-red-500/40"
                                                title="Quitar pregunta">
                                                Quitar
                                            </button>
                                        </div>
                                    </div>
                                    <label class="mt-2 block text-[11px] font-semibold text-slate-600 dark:text-slate-400">Pregunta</label>
                                    @if ($isCatalog)
                                        <p class="mt-1 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-100">
                                            {{ $question['question'] ?? '' }}
                                        </p>
                                    @else
                                        <input type="text" wire:model="foGj04Questions.{{ $index }}.question"
                                            placeholder="Ej. Reconoce los hechos descritos en la citación"
                                            class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                    @endif
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

@if ($showFoGj04CatalogPicker ?? false)
    @php
        $foGj04CatalogItems = \App\Models\Disciplinary\DiligenceActaQuestion::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $foGj04AlreadyCatalogIds = collect($foGj04Questions ?? [])
            ->filter(fn ($row) => ($row['source'] ?? '') === 'catalog')
            ->map(fn ($row) => (int) ($row['catalog_question_id'] ?? 0))
            ->filter()
            ->all();
    @endphp
    <div class="fixed inset-0 z-[88] flex items-center justify-center p-3 sm:p-4 bg-slate-900/50" wire:key="fo-gj-04-catalog-picker">
        <div class="flex max-h-[85dvh] w-full max-w-xl flex-col overflow-hidden rounded-xl bg-white shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10">
            <div class="border-b px-4 py-3 dark:border-white/10">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Seleccionar preguntas del catálogo</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">El texto quedará bloqueado en el acta. Puede reordenar después.</p>
            </div>
            <div class="overflow-y-auto px-4 py-3 space-y-2">
                @forelse ($foGj04CatalogItems as $catalogItem)
                    @php $already = in_array($catalogItem->id, $foGj04AlreadyCatalogIds, true); @endphp
                    <label @class([
                        'flex items-start gap-2 rounded-lg border px-3 py-2 text-sm',
                        'border-slate-200 dark:border-white/10' => ! $already,
                        'border-slate-100 bg-slate-50 opacity-60 dark:border-white/5 dark:bg-white/[0.02]' => $already,
                    ])>
                        <input type="checkbox"
                            value="{{ $catalogItem->id }}"
                            wire:model="foGj04CatalogPickerIds"
                            @disabled($already)
                            class="mt-0.5 rounded border-slate-300 text-indigo-600">
                        <span class="text-slate-800 dark:text-slate-100">
                            {{ $catalogItem->text }}
                            @if ($already)
                                <span class="ml-1 text-[10px] font-semibold uppercase text-slate-500">Ya agregada</span>
                            @endif
                        </span>
                    </label>
                @empty
                    <p class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                        No hay preguntas en el catálogo. Un administrador puede crearlas en Ajustes · Preguntas.
                    </p>
                @endforelse
                @error('foGj04CatalogPickerIds')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-2 border-t px-4 py-3 dark:border-white/10">
                <button type="button" wire:click="closeFoGj04CatalogPicker" class="rounded-md px-4 py-2 text-sm font-semibold ring-1 ring-slate-300">Cancelar</button>
                <button type="button" wire:click="addFoGj04QuestionsFromCatalog"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    @disabled($foGj04CatalogItems->isEmpty())>
                    Agregar seleccionadas
                </button>
            </div>
        </div>
    </div>
@endif

@if (($showFoGj04SignedUploadPreview ?? false) && ($foGj04SignedUploadPreviewUrl ?? null))
    <div class="fixed inset-0 z-[87] flex items-center justify-center p-3 sm:p-4"
        x-data
        x-on:keydown.escape.window="$wire.cancelFoGj04SignedUpload()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="fo-gj-04-signed-upload-title"
        wire:key="fo-gj-04-signed-upload-preview-{{ $case->id }}">
        <div class="absolute inset-0 bg-black/50 dark:bg-black/60" wire:click="cancelFoGj04SignedUpload" aria-hidden="true"></div>
        <div class="relative flex h-[min(92dvh,calc(100dvh-2rem))] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
            <div class="shrink-0 border-b border-slate-200 px-4 py-3 dark:border-white/10 sm:px-5">
                <h2 id="fo-gj-04-signed-upload-title" class="text-base font-bold text-slate-900 dark:text-white">Confirmar acta firmada</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Revise el PDF escaneado antes de cargarlo al expediente.</p>
            </div>

            <div class="min-h-0 flex-1 bg-slate-100 dark:bg-black/40">
                <iframe wire:ignore title="Vista previa acta firmada FO-GJ-04" class="h-full min-h-[240px] w-full bg-white dark:bg-black/20"
                    src="{{ $foGj04SignedUploadPreviewUrl }}"></iframe>
            </div>

            <div class="shrink-0 space-y-3 border-t border-slate-200 bg-slate-50 px-4 py-4 dark:border-white/10 dark:bg-dash-ink/80 sm:px-5">
                @error('fo_gj_04')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="cancelFoGj04SignedUpload"
                        class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmFoGj04SignedUpload" wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-60">
                        <span wire:loading.remove wire:target="confirmFoGj04SignedUpload">Confirmar y cargar</span>
                        <span wire:loading wire:target="confirmFoGj04SignedUpload">Cargando…</span>
                    </button>
                </div>
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

@if ($showFoGj04SignaturePadModal ?? false)
    <x-disciplinary.signature-capture-modal
        :show="true"
        title="Firma del trabajador · FO-GJ-04"
        wire-key="fo-gj-04-worker-signature-pad"
        close-action="closeFoGj04WorkerSignaturePad"
        save-action="saveFoGj04WorkerSignature"
        variant="teal"
    >
        @error('foGj04WorkerSignature')
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </x-disciplinary.signature-capture-modal>
@endif

@if ($showFoGj44DraftModal ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-3 sm:p-4 bg-slate-900/50" wire:key="fo-gj-44-draft-modal">
        <div class="flex max-h-[92dvh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10">
            <div class="border-b px-4 py-4 dark:border-white/10"><h2 class="text-lg font-bold">Diligenciar FO-GJ-44</h2></div>
            <div class="overflow-y-auto px-4 py-4 space-y-3">
                <input type="text" wire:model="foGj44SignTime" placeholder="Hora de firma" class="w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                <div class="grid grid-cols-3 gap-2">
                    <input type="text" wire:model="foGj44SignDay" placeholder="Día" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                    <input type="text" wire:model="foGj44SignMonth" placeholder="Mes" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                    <input type="text" wire:model="foGj44SignYearSuffix" placeholder="Año (dígito)" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                </div>
                @foreach ([1, 2] as $w)
                    <div class="rounded-lg border p-3 dark:border-white/10">
                        <p class="text-xs font-semibold">Testigo {{ $w }}</p>
                        @if ($w === 1)
                            <input type="text" wire:model="foGj44Witness1Name" placeholder="Nombre" class="mt-2 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                            <input type="text" wire:model="foGj44Witness1Cargo" placeholder="Cargo" class="mt-2 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                            <input type="text" wire:model="foGj44Witness1Date" placeholder="Fecha" class="mt-2 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                        @else
                            <input type="text" wire:model="foGj44Witness2Name" placeholder="Nombre" class="mt-2 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                            <input type="text" wire:model="foGj44Witness2Cargo" placeholder="Cargo" class="mt-2 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                            <input type="text" wire:model="foGj44Witness2Date" placeholder="Fecha" class="mt-2 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                        @endif
                    </div>
                @endforeach
                @error('fo_gj_44')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-2 border-t px-4 py-4 dark:border-white/10">
                <button type="button" wire:click="closeFoGj44DraftModal" class="px-4 py-2 text-sm font-semibold ring-1 ring-slate-300 rounded-md">Cancelar</button>
                <button type="button" wire:click="saveFoGj44Draft" class="px-4 py-2 text-sm font-semibold text-white bg-teal-600 rounded-md">Guardar</button>
            </div>
        </div>
    </div>
@endif

@if ($showFoGj44PdfPreviewModal ?? false)
    <div class="fixed inset-0 z-[86] flex items-center justify-center p-3 sm:p-4" wire:key="fo-gj-44-pdf-preview">
        <div class="absolute inset-0 bg-black/50" wire:click="closeFoGj44PdfPreview"></div>
        <div class="relative flex h-[min(92dvh,calc(100dvh-2rem))] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-dash-ink">
            <div class="flex justify-between border-b px-4 py-3"><h2 class="font-bold">FO-GJ-44</h2><button type="button" wire:click="closeFoGj44PdfPreview">✕</button></div>
            <iframe class="min-h-0 flex-1" src="{{ route('disciplinary.cases.fo-gj-44.pdf', ['case' => $case, 'inline' => 1]) }}"></iframe>
        </div>
    </div>
@endif

@if ($showFoGj54DraftModal ?? false)
    @php
        $foGj54ChargesPreview = app(\App\Services\Disciplinary\FoGj54DraftService::class)->chargesFromFo03($case);
    @endphp
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-3 sm:p-4 bg-slate-900/50" wire:key="fo-gj-54-draft-modal">
        <div class="flex max-h-[92dvh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10">
            <div class="border-b px-4 py-4 dark:border-white/10">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    {{ ($foGj54OperationalMode ?? false) ? 'Reprogramar diligencia · FO-GJ-54' : 'Diligenciar FO-GJ-54' }}
                </h2>
                @if ($foGj54OperationalMode ?? false)
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                        FO-GJ-03 se conserva. Genere FO-GJ-54, notifique al trabajador y cargue la evidencia de recibido.
                    </p>
                @endif
            </div>
            <div class="overflow-y-auto px-4 py-4 space-y-3">
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Formulación de cargos (FO-GJ-03)</p>
                    <p class="mt-1 text-slate-700 dark:text-slate-200">
                        @if (filled($foGj54ChargesPreview['informe_report_date_long'] ?? null))
                            Informe del {{ $foGj54ChargesPreview['informe_report_date_long'] }}.
                        @else
                            <span class="text-amber-700 dark:text-amber-300">Sin fecha de informe en FO-GJ-03.</span>
                        @endif
                    </p>
                    <p class="mt-1 text-slate-700 dark:text-slate-200">
                        @if (filled($foGj54ChargesPreview['charges_description'] ?? null))
                            {{ $foGj54ChargesPreview['charges_description'] }}
                        @else
                            <span class="text-amber-700 dark:text-amber-300">Sin formulación de cargos en FO-GJ-03.</span>
                        @endif
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Motivo de reprogramación <span class="text-red-600">*</span></label>
                    <select wire:model="foGj54RescheduleCause"
                        class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                        <option value="">Seleccione…</option>
                        @foreach (\App\Support\Disciplinary\FoGj54RescheduleCause::cases() as $causeOption)
                            <option value="{{ $causeOption->value }}">{{ $causeOption->label() }}</option>
                        @endforeach
                    </select>
                    @error('foGj54RescheduleCause')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @if (($foGj54OperationalMode ?? false) && $case->current_status === \App\Enums\Disciplinary\CaseStatus::DILIGENCIA)
                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/[0.03]">
                        <input type="checkbox" wire:model.live="foGj54DeferDateToPlanning" class="mt-0.5 rounded border-slate-300 text-teal-600">
                        <span class="text-slate-700 dark:text-slate-200">
                            <strong>Coordinar fechas con planeación</strong> (chat). Al guardar se inicia la reprogramación
                            sin generar aún el FO-GJ-54. Cuando haya fecha, diligencie y genere el documento.
                        </span>
                    </label>
                @endif

                @if (! ($foGj54OperationalMode ?? false) || ! ($foGj54DeferDateToPlanning ?? false))
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Modalidad <span class="text-red-600">*</span></label>
                        <select wire:model.live="foGj54Modality"
                            class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                            <option value="presencial">Presencial</option>
                            <option value="virtual">Virtual</option>
                        </select>
                        @error('foGj54Modality')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        @if ($foGj54Modality === 'presencial')
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ \App\Services\Disciplinary\FoGj03DraftService::PRESENCIAL_LOCATION }}
                            </p>
                        @endif
                    </div>
                    @if ($foGj54Modality === 'virtual')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Enlace Microsoft Teams <span class="text-red-600">*</span></label>
                            <input type="url" wire:model="foGj54VirtualLink" placeholder="https://…"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                            @error('foGj54VirtualLink')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Nueva fecha de diligencia <span class="text-red-600">*</span></label>
                        <input type="date" wire:model="foGj54NewHearingDate" class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                        @error('foGj54NewHearingDate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Nueva hora <span class="text-red-600">*</span></label>
                        <input type="text" wire:model="foGj54NewHearingTime" placeholder="HH:MM"
                            class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                        @error('foGj54NewHearingTime')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif
                @error('fo_gj_54')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-2 border-t px-4 py-4 dark:border-white/10">
                <button type="button" wire:click="closeFoGj54DraftModal" class="px-4 py-2 text-sm font-semibold ring-1 ring-slate-300 rounded-md">Cancelar</button>
                <button type="button" wire:click="saveFoGj54Draft" class="px-4 py-2 text-sm font-semibold text-white bg-teal-600 rounded-md">Guardar</button>
            </div>
        </div>
    </div>
@endif

@if ($showFoGj54PdfPreviewModal ?? false)
    <div class="fixed inset-0 z-[86] flex items-center justify-center p-3 sm:p-4" wire:key="fo-gj-54-pdf-preview">
        <div class="absolute inset-0 bg-black/50" wire:click="closeFoGj54PdfPreview"></div>
        <div class="relative flex h-[min(92dvh,calc(100dvh-2rem))] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-dash-ink">
            <div class="flex justify-between border-b px-4 py-3"><h2 class="font-bold">FO-GJ-54</h2><button type="button" wire:click="closeFoGj54PdfPreview">✕</button></div>
            <iframe class="min-h-0 flex-1" src="{{ route('disciplinary.cases.fo-gj-54.pdf', ['case' => $case, 'inline' => 1]) }}"></iframe>
        </div>
    </div>
@endif

@if ($showComiteDraftModal ?? false)
    <div class="fixed inset-0 z-[85] flex items-center justify-center p-3 sm:p-4 bg-slate-900/50" wire:key="comite-draft-modal">
        <div class="flex max-h-[92dvh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-xl dark:bg-dash-lift dark:ring-1 dark:ring-white/10">
            <div class="border-b px-4 py-4 dark:border-white/10">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Diligenciar acta de comité</h2>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                    La fecha del comité se registrará automáticamente al generar el PDF.
                </p>
            </div>
            <div class="overflow-y-auto px-4 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Decisión / acuerdo del comité <span class="text-red-600">*</span></label>
                    <textarea wire:model="comiteDecisionNarrative" rows="5"
                        class="mt-1 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"
                        placeholder="Describa la decisión o acuerdo del comité disciplinario"></textarea>
                    @error('comiteDecisionNarrative')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Integrantes del comité</p>
                        <button type="button" wire:click="addComiteAttendee"
                            class="rounded-md px-2 py-1 text-xs font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:text-teal-200 dark:ring-teal-400/40">
                            + Agregar integrante
                        </button>
                    </div>
                    @foreach ($comiteAttendees as $index => $attendee)
                        <div class="rounded-lg border p-3 dark:border-white/10" wire:key="comite-attendee-{{ $index }}">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-slate-600 dark:text-slate-400">Integrante {{ $index + 1 }}</p>
                                @if (count($comiteAttendees) > 1)
                                    <button type="button" wire:click="removeComiteAttendee({{ $index }})"
                                        class="text-xs font-semibold text-red-600 hover:text-red-700">Quitar</button>
                                @endif
                            </div>
                            <input type="text" wire:model="comiteAttendees.{{ $index }}.name" placeholder="Nombre"
                                class="mt-2 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                            <input type="text" wire:model="comiteAttendees.{{ $index }}.cargo" placeholder="Cargo"
                                class="mt-2 w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                            <button type="button" wire:click="openComiteAttendeeSignaturePad({{ $index }})"
                                class="mt-2 inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-teal-800 ring-1 ring-teal-300 hover:bg-teal-50 dark:bg-white/10 dark:text-teal-100 dark:ring-teal-400/40">
                                {{ ! empty($attendee['signature_data_uri']) ? 'Editar firma' : 'Capturar firma' }}
                            </button>
                        </div>
                    @endforeach
                    @error('comiteAttendees')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t px-4 py-4 dark:border-white/10">
                <button type="button" wire:click="closeComiteDraftModal" class="px-4 py-2 text-sm font-semibold ring-1 ring-slate-300 rounded-md">Cancelar</button>
                <button type="button" wire:click="saveComiteDraft" class="px-4 py-2 text-sm font-semibold text-white bg-teal-600 rounded-md">Guardar</button>
            </div>
        </div>
    </div>
@endif

@if ($showComitePdfPreviewModal ?? false)
    <div class="fixed inset-0 z-[86] flex items-center justify-center p-3 sm:p-4" wire:key="comite-pdf-preview">
        <div class="absolute inset-0 bg-black/50" wire:click="closeComitePdfPreview"></div>
        <div class="relative flex h-[min(92dvh,calc(100dvh-2rem))] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-dash-ink">
            <div class="flex justify-between border-b px-4 py-3"><h2 class="font-bold">Acta de comité</h2><button type="button" wire:click="closeComitePdfPreview">✕</button></div>
            <iframe class="min-h-0 flex-1" src="{{ route('disciplinary.cases.comite-acta.pdf', ['case' => $case, 'inline' => 1]) }}"></iframe>
        </div>
    </div>
@endif

@if ($comiteSignatureAttendeeIndex !== null)
    <x-disciplinary.signature-capture-modal
        wire:key="comite-signature-pad-{{ $comiteSignatureAttendeeIndex }}"
        :show="true"
        title="Firma del integrante"
        :initial-data-uri="$comiteSignaturePendingDataUri"
        close-action="closeComiteAttendeeSignaturePad"
        save-action="saveComiteAttendeeSignature"
        variant="teal"
    />
@endif
