<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Disciplinarios · Etapa A</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Informes FO-GJ-51 pendientes de autorización</h1>
            <p class="mt-2 text-sm text-slate-600 max-w-3xl dark:text-slate-300">
                Revise cada PDF; si autoriza el sistema creará el expediente en etapa <span class="font-medium text-slate-800 dark:text-slate-200">Informe disciplinario</span>.
                Si rechaza el archivo se elimina; no existe expediente.
            </p>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-100 dark:ring-emerald-500/30">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-x-auto rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 sm:p-6">
                @if ($rejectId !== null)
                    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/35 dark:bg-amber-500/10">
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">Rechazar informe {{ $rejectId }}</p>
                        <p class="mt-1 text-xs text-amber-800 dark:text-amber-200/90">El PDF pendiente será eliminado; no podrá recuperarse desde la aplicación.</p>
                        <textarea wire:model="rejectNotes" rows="3" placeholder="Motivo interno (opcional)"
                                  class="mt-3 block w-full max-w-xl rounded-md border border-amber-200 bg-white text-sm shadow-sm dark:border-white/15 dark:bg-dash-ink dark:text-white"></textarea>
                        @error('rejectNotes')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @error('rejectId')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" wire:click="reject"
                                    class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                Confirmar rechazo y eliminar
                            </button>
                            <button type="button" wire:click="cancelReject"
                                    class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                                Cancelar
                            </button>
                        </div>
                    </div>
                @endif

                @forelse ($pending as $row)
                    <article class="mb-4 rounded-lg border border-slate-200 p-4 last:mb-0 dark:border-white/10" wire:key="informe-{{ $row->id }}">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-dash-muted">
                                    Envío {{ $row->created_at->format('d/m/Y H:i') }}
                                </p>
                                <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">
                                    {{ $row->personnel?->full_name ?? 'Sin nombre' }}
                                    @if ($row->personnel?->document_number)
                                        <span class="text-sm font-normal text-slate-500 dark:text-slate-400">· {{ $row->personnel->document_number }}</span>
                                    @endif
                                </p>
                                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                    Enviado por <span class="font-medium">{{ $row->submitter?->name ?? 'Usuario' }}</span>
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="openPdfPreview({{ $row->id }})"
                                        class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                                    Ver / descargar PDF
                                </button>
                                <button type="button" wire:click="openApproveConfirm({{ $row->id }})"
                                        class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                    Autorizar y crear caso
                                </button>
                                <button type="button" wire:click="openReject({{ $row->id }})"
                                        class="inline-flex items-center rounded-md bg-red-50 px-4 py-2 text-sm font-semibold text-red-800 ring-1 ring-red-200 hover:bg-red-100 dark:bg-red-500/15 dark:text-red-100 dark:ring-red-500/35">
                                    Rechazar…
                                </button>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-slate-600 dark:text-slate-400">No hay informes esperando revisión.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if ($approveConfirmId !== null)
        <div class="fixed inset-0 z-[75] flex items-center justify-center p-4 sm:p-6"
             x-data
             x-on:keydown.escape.window="$wire.cancelApproveConfirm()"
             role="dialog"
             aria-modal="true"
             aria-labelledby="approve-informe-title"
             aria-describedby="approve-informe-desc"
             wire:key="approve-confirm-{{ $approveConfirmId }}">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[1px] dark:bg-black/60" wire:click="cancelApproveConfirm" aria-hidden="true"></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                    <h2 id="approve-informe-title" class="text-lg font-bold text-slate-900 dark:text-white">Confirmar autorización</h2>
                    <p id="approve-informe-desc" class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        ¿Autorizar este informe y crear el expediente disciplinario?
                    </p>
                </div>
                <div class="flex flex-wrap justify-end gap-2 px-5 py-4 bg-slate-50 dark:bg-dash-ink/80">
                    <button type="button" wire:click="cancelApproveConfirm"
                            class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmApprove"
                            class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400">
                        Autorizar y crear caso
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($previewSubmissionId !== null)
        @php
            $pdfPreviewUrl = route('disciplinary.informes-pendientes.pdf', ['submission' => $previewSubmissionId, 'inline' => 1]);
            $pdfDownloadUrl = route('disciplinary.informes-pendientes.pdf', ['submission' => $previewSubmissionId]);
        @endphp
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-3 sm:p-4"
             x-data="window.sjInformePdfPreviewLightbox()"
             x-on:keydown.escape.window="zoomOpen ? closeZoom() : $wire.closePdfPreview()"
             role="dialog"
             aria-modal="true"
             aria-labelledby="informe-pdf-preview-title"
             wire:key="pdf-preview-{{ $previewSubmissionId }}">
            <div class="absolute inset-0 bg-black/50 dark:bg-black/60" wire:click="closePdfPreview" aria-hidden="true"></div>
            <div class="relative flex h-[min(92dvh,calc(100dvh-2rem))] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10 sm:px-5">
                    <h2 id="informe-pdf-preview-title" class="text-base font-bold text-slate-900 dark:text-white">Vista previa del PDF</h2>
                    <button type="button" wire:click="closePdfPreview"
                            class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white"
                            aria-label="Cerrar">
                        ✕
                    </button>
                </div>
                <div class="relative flex min-h-0 flex-1 flex-col">
                    <iframe wire:ignore title="Vista previa informe PDF"
                            class="min-h-0 flex-1 min-h-[200px] bg-slate-100 dark:bg-black/40"
                            src="{{ $pdfPreviewUrl }}"></iframe>

                    @if ($previewEvidencePaths !== [])
                        <section class="shrink-0 border-t border-slate-200 bg-slate-50/90 px-4 py-3 dark:border-white/10 dark:bg-dash-ink/90 sm:px-5">
                            <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-300/90">Evidencia</p>
                            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">Clic en una miniatura para ampliar. Use la rueda del ratón para acercar o alejar.</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($previewEvidencePaths as $idx => $path)
                                    @php
                                        $evidenceUrl = route('disciplinary.informes-pendientes.evidence', ['submission' => $previewSubmissionId, 'index' => $idx]);
                                        $evidenceLabel = 'Evidencia '.($idx + 1);
                                    @endphp
                                    <button type="button"
                                            title="Ver en grande"
                                            class="group relative h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-200/70 ring-emerald-500/20 transition hover:ring-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-white/15 dark:bg-black/50 dark:ring-emerald-400/30"
                                            x-on:click="openZoom(@js($evidenceUrl), @js($evidenceLabel))">
                                        <img src="{{ $evidenceUrl }}" alt="{{ $evidenceLabel }}" loading="lazy"
                                             class="pointer-events-none h-full w-full object-cover transition group-hover:brightness-95 dark:group-hover:brightness-110">
                                    </button>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <div x-show="zoomOpen"
                         x-cloak
                         class="absolute inset-0 z-[80] flex flex-col bg-black/85 p-3 backdrop-blur-[1px]"
                         x-transition
                         role="dialog"
                         aria-modal="true"
                         aria-label="Vista ampliada de evidencia"
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
                </div>

                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3 dark:border-white/10 dark:bg-dash-ink/80 sm:px-5">
                    <button type="button" wire:click="closePdfPreview"
                            class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                        Cerrar
                    </button>
                    <button type="button"
                            class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-dash-lift dark:ring-1 dark:ring-white/15 dark:hover:bg-dash-lift/90"
                            x-on:click="(() => { const a = document.createElement('a'); a.href = @js($pdfDownloadUrl); a.target = '_blank'; a.rel = 'noopener noreferrer'; document.body.appendChild(a); a.click(); a.remove(); })()">
                        Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
