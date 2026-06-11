@if ($documentPreviewId !== null)
    @php
        $previewDoc = $case->documents->firstWhere('id', $documentPreviewId);
    @endphp
    @if ($previewDoc)
        @php
            $docPreviewUrl = route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $previewDoc]);
            $docDownloadUrl = route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $previewDoc, 'download' => 1]);
            $docIsPdf = $previewDoc->isPdf();
            $docIsImage = $previewDoc->isLikelyRasterImage();
        @endphp
        <div class="fixed inset-0 z-[72] flex items-center justify-center p-3 sm:p-4"
            @if ($docIsImage)
                x-data="window.sjInformePdfPreviewLightbox()"
                x-on:keydown.escape.window="zoomOpen ? closeZoom() : $wire.closeDocumentPreview()"
            @else
                x-data
                x-on:keydown.escape.window="$wire.closeDocumentPreview()"
            @endif
            role="dialog"
            aria-modal="true"
            aria-labelledby="case-document-preview-title"
            wire:key="case-document-preview-{{ $case->id }}-{{ $previewDoc->id }}">
            <div class="absolute inset-0 bg-black/50 dark:bg-black/60" wire:click="closeDocumentPreview" aria-hidden="true"></div>
            <div class="relative flex h-[min(92dvh,calc(100dvh-2rem))] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
                <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10 sm:px-5">
                    <div class="min-w-0 pr-2">
                        <h2 id="case-document-preview-title" class="truncate text-base font-bold text-slate-900 dark:text-white">
                            {{ $previewDoc->displayName() }}
                        </h2>
                        <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">
                            {{ $previewDoc->document_type->label() }}
                            @if ($previewDoc->form_code)
                                · <span class="font-mono">{{ $previewDoc->form_code }}</span>
                            @endif
                        </p>
                    </div>
                    <button type="button" wire:click="closeDocumentPreview"
                        class="shrink-0 rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white"
                        aria-label="Cerrar">
                        ✕
                    </button>
                </div>

                <div class="relative flex min-h-0 flex-1 flex-col bg-slate-100 dark:bg-black/40">
                    @if ($docIsPdf)
                        <iframe wire:ignore title="Vista previa {{ $previewDoc->displayName() }}"
                            class="min-h-0 flex-1 min-h-[200px] bg-white dark:bg-black/20"
                            src="{{ $docPreviewUrl }}"></iframe>
                    @elseif ($docIsImage)
                        <div class="flex min-h-0 flex-1 items-center justify-center overflow-auto p-4"
                            x-on:wheel="wheelZoom($event)">
                            <img src="{{ $docPreviewUrl }}" alt="{{ $previewDoc->displayName() }}"
                                x-bind:style="zoomOpen ? 'transform: scale(' + zoomScale + '); transform-origin: center center;' : ''"
                                class="max-h-full max-w-full cursor-zoom-in object-contain shadow-lg ring-1 ring-black/10 dark:ring-white/10"
                                x-on:click="openZoom(@js($docPreviewUrl), @js($previewDoc->displayName()))"
                                draggable="false">
                        </div>
                        <p class="shrink-0 border-t border-slate-200 bg-slate-50 px-4 py-2 text-center text-[11px] text-slate-500 dark:border-white/10 dark:bg-dash-ink/80 dark:text-slate-400">
                            Clic en la imagen para ampliar · rueda del ratón para zoom
                        </p>
                        <div x-show="zoomOpen"
                            x-cloak
                            class="absolute inset-0 z-[80] flex flex-col bg-black/85 p-3 backdrop-blur-[1px]"
                            x-transition
                            role="dialog"
                            aria-modal="true"
                            aria-label="Vista ampliada"
                            x-on:click.self="closeZoom()">
                            <div class="flex shrink-0 justify-end pb-2">
                                <button type="button" x-on:click="closeZoom()"
                                    class="rounded-md bg-white/10 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                                    Cerrar (Esc)
                                </button>
                            </div>
                            <div class="flex min-h-0 flex-1 items-center justify-center overflow-hidden"
                                x-on:wheel="wheelZoom($event)">
                                <img x-bind:src="zoomSrc"
                                    x-bind:alt="zoomAlt"
                                    x-bind:style="'transform: scale(' + zoomScale + '); transform-origin: center center;'"
                                    class="max-h-[88vh] max-w-[96vw] cursor-default select-none object-contain shadow-2xl ring-1 ring-white/15"
                                    x-on:click.stop
                                    draggable="false">
                            </div>
                        </div>
                    @else
                        <div class="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center">
                            <p class="text-sm text-slate-600 dark:text-slate-300">Este tipo de archivo no se puede previsualizar en el navegador.</p>
                            <x-ui.btn variant="indigo-soft" href="{{ $docPreviewUrl }}" target="_blank" rel="noopener noreferrer">
                                Abrir en nueva pestaña
                            </x-ui.btn>
                        </div>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3 dark:border-white/10 dark:bg-dash-ink/80 sm:px-5">
                    <x-ui.btn type="button" variant="ghost" wire:click="closeDocumentPreview">
                        Cerrar
                    </x-ui.btn>
                    <x-ui.btn variant="dark" href="{{ $docDownloadUrl }}">
                        Descargar
                    </x-ui.btn>
                </div>
            </div>
        </div>
    @endif
@endif
