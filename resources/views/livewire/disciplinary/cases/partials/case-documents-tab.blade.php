@php
    /** @var \App\Models\Disciplinary\DisciplinaryCase $case */
    $sortedDocuments = $case->documents->sortByDesc('created_at')->values();
@endphp

@if (auth()->user()->isDisciplinaryFieldOperator())
    <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
        Para diligenciar el <strong class="text-slate-800 dark:text-slate-200">informe disciplinario FO-GJ-51</strong>, use la pestaña
        <strong class="text-slate-800 dark:text-slate-200">Información</strong>. Adjunte aquí las evidencias de notificación cuando el sistema lo permita.
    </p>
@endif

@error('documents')
    <p class="mb-4 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
@enderror

@if ($sortedDocuments->isEmpty())
    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-12 text-center dark:border-white/15 dark:bg-white/[0.02]">
        <p class="text-sm font-medium text-slate-600 dark:text-slate-300">No hay documentos cargados todavía.</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Los formularios y evidencias del expediente aparecerán aquí.</p>
    </div>
@else
    <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">
        {{ $sortedDocuments->count() }} {{ $sortedDocuments->count() === 1 ? 'documento' : 'documentos' }} en el expediente
    </p>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($sortedDocuments as $doc)
            @php
                $downloadUrl = route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $doc, 'download' => 1]);
                $previewUrl = route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $doc]);
                $isPdf = $doc->isPdf();
                $isImage = $doc->isLikelyRasterImage();
            @endphp
            <article
                wire:key="case-doc-card-{{ $case->id }}-{{ $doc->id }}"
                class="flex flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-slate-900/5 transition hover:border-indigo-200 hover:shadow-md dark:border-white/10 dark:bg-dash-lift/40 dark:ring-white/5 dark:hover:border-indigo-400/30">
                <div class="flex min-w-0 items-start gap-3">
                    <div @class([
                        'flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-xs font-bold uppercase tracking-wide',
                        'bg-red-50 text-red-700 ring-1 ring-red-200/80 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-500/30' => $isPdf,
                        'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30' => $isImage,
                        'bg-slate-100 text-slate-600 ring-1 ring-slate-200/80 dark:bg-white/10 dark:text-slate-300 dark:ring-white/15' => ! $isPdf && ! $isImage,
                    ]) aria-hidden="true">
                        @if ($isPdf)
                            PDF
                        @elseif ($isImage)
                            IMG
                        @else
                            DOC
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-sm font-semibold text-slate-900 dark:text-white" title="{{ $doc->displayName() }}">
                            {{ $doc->displayName() }}
                        </h3>
                        <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                            {{ $doc->document_type->label() }}
                        </p>
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            @if ($doc->form_code)
                                <span class="inline-flex rounded-md bg-indigo-50 px-2 py-0.5 font-mono text-[10px] font-semibold text-indigo-800 ring-1 ring-indigo-200/80 dark:bg-indigo-950/50 dark:text-indigo-200 dark:ring-indigo-400/30">
                                    {{ $doc->form_code }}
                                </span>
                            @endif
                            <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400">
                                {{ number_format($doc->size_bytes / 1024, 1) }} KB
                            </span>
                        </div>
                    </div>
                </div>

                <dl class="mt-3 grid gap-1 border-t border-slate-100 pt-3 text-[11px] text-slate-500 dark:border-white/10 dark:text-slate-400">
                    <div class="flex justify-between gap-2">
                        <dt>Subido por</dt>
                        <dd class="truncate text-right font-medium text-slate-700 dark:text-slate-300">{{ $doc->uploader?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt>Fecha</dt>
                        <dd class="text-right font-medium text-slate-700 dark:text-slate-300">
                            {{ $doc->created_at->timezone('America/Bogota')->format('d/m/Y H:i') }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($doc->supportsInlinePreview())
                        <button type="button" wire:click="openDocumentPreview({{ $doc->id }})"
                            class="inline-flex flex-1 items-center justify-center rounded-md bg-white px-3 py-2 text-xs font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:bg-white/10 dark:text-indigo-200 dark:ring-indigo-400/40 dark:hover:bg-white/15 sm:flex-none">
                            Previsualizar
                        </button>
                    @else
                        <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer"
                            class="inline-flex flex-1 items-center justify-center rounded-md bg-white px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-slate-200 dark:ring-white/20 dark:hover:bg-white/15 sm:flex-none">
                            Abrir
                        </a>
                    @endif
                    <a href="{{ $downloadUrl }}"
                        class="inline-flex flex-1 items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400 sm:flex-none">
                        Descargar
                    </a>
                </div>
            </article>
        @endforeach
    </div>
@endif
