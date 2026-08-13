@php
    $listTargets = 'search,gotoPage,nextPage,previousPage,approve,reject,confirmApprove';
    $fieldCompact = 'h-8 w-full max-w-sm rounded-lg border border-slate-300 bg-white pl-8 pr-2.5 text-xs text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-white/15 dark:bg-dash-lift dark:text-slate-100 dark:placeholder:text-slate-500';
@endphp

<div class="informes-pendientes mx-auto flex min-h-[calc(100dvh-6.25rem)] w-full max-w-[1600px] flex-col px-3 py-2 sm:px-5 sm:py-3 lg:px-6">
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <header class="mb-3 flex shrink-0 flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-3 dark:border-white/10">
        <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-dash-muted">Disciplinarios · Etapa A</p>
            <h1 class="mt-0.5 text-lg font-bold text-slate-900 dark:text-white sm:text-xl">Revisión FO-GJ-51</h1>
            <p class="mt-1 max-w-2xl text-xs text-slate-500 dark:text-slate-400">
                Autorizar crea el expediente · Rechazar elimina el PDF pendiente.
            </p>
        </div>
        <div class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 shadow-sm dark:border-white/10 dark:bg-white/[0.04]">
            <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-dash-muted">Pendientes</span>
            <span class="text-xl font-bold tabular-nums text-amber-600 dark:text-amber-300">{{ number_format($pendingCount) }}</span>
        </div>
    </header>

    @if (session('success'))
        <div class="mb-3 shrink-0 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-500/30">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
        <div class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 dark:border-white/10 sm:px-4">
            <div class="relative min-w-0 flex-1 overflow-hidden">
                <x-ui.search-field-icon />
                <input type="text" inputmode="search" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Buscar empleado, documento o remitente…" class="{{ $fieldCompact }}" aria-label="Buscar informes">
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400" wire:loading.remove wire:target="{{ $listTargets }}">
                @if ($pending->total() > 0)
                    <span class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($pending->firstItem() ?? 0) }}–{{ number_format($pending->lastItem() ?? 0) }}</span>
                    de {{ number_format($pending->total()) }}
                @else
                    Sin resultados
                @endif
            </p>
            <p wire:loading wire:target="{{ $listTargets }}" class="inline-flex items-center gap-1.5 text-[11px] text-indigo-600 dark:text-indigo-400">
                <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Actualizando…
            </p>
        </div>

        <div class="min-h-0 flex-1 overflow-auto" wire:loading.remove wire:target="{{ $listTargets }}">
            @forelse ($pending as $row)
                @php
                    $emp = $row->employee;
                    $name = $emp?->displayName() ?? 'Sin nombre';
                    $initials = $emp?->initials() ?? '?';
                    $snap = is_array($row->form_snapshot) ? $row->form_snapshot : [];
                    $cargo = $snap['fo51_cargo'] ?? $snap['cargo'] ?? $snap['job_title'] ?? null;
                    $ciudad = $snap['fo51_city'] ?? $snap['ciudad'] ?? null;
                    $evidenceCount = is_array($row->evidence_paths) ? count($row->evidence_paths) : 0;
                    $hours = $row->created_at?->diffInHours(now()) ?? 0;
                    $stale = $hours >= 24;
                @endphp
                <article
                    wire:key="informe-{{ $row->id }}"
                    @class([
                        'group border-b border-slate-100 px-3 py-3 transition last:border-b-0 hover:bg-slate-50/80 dark:border-white/10 dark:hover:bg-white/[0.03] sm:px-4',
                        'border-l-2 border-l-amber-400 dark:border-l-amber-500/80' => $stale,
                        'border-l-2 border-l-transparent' => ! $stale,
                    ])
                >
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between lg:gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[11px] font-bold text-indigo-700 ring-1 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-200 dark:ring-indigo-400/30">
                                {{ $initials }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $name }}</p>
                                    @if ($emp?->document_number)
                                        <span class="font-mono text-[11px] text-slate-500 dark:text-slate-400">CC {{ $emp->document_number }}</span>
                                    @endif
                                    @if ($stale)
                                        <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-900 dark:bg-amber-500/15 dark:text-amber-200">{{ $hours }}h en cola</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                    Envío {{ $row->created_at->format('d/m/Y H:i') }}
                                    · {{ $row->created_at->diffForHumans() }}
                                </p>
                                <p class="mt-1 truncate text-xs text-slate-600 dark:text-slate-300">
                                    Enviado por <span class="font-medium">{{ $row->submitter?->name ?? 'Usuario' }}</span>
                                    @if ($row->assignedReviewer)
                                        · Revisor <span class="font-medium">{{ $row->assignedReviewer->name }}</span>
                                    @endif
                                    @if ($cargo)
                                        · {{ $cargo }}
                                    @endif
                                    @if ($ciudad)
                                        · {{ $ciudad }}
                                    @endif
                                    @if ($evidenceCount > 0)
                                        · {{ $evidenceCount }} {{ $evidenceCount === 1 ? 'evidencia' : 'evidencias' }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 lg:shrink-0 lg:justify-end">
                            <button type="button" wire:click="openPdfPreview({{ $row->id }})"
                                    class="inline-flex h-8 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/15 dark:bg-white/5 dark:text-slate-100 dark:hover:bg-white/10">
                                Ver PDF
                            </button>
                            <button type="button" wire:click="openApproveConfirm({{ $row->id }})"
                                    class="inline-flex h-8 items-center rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                Autorizar
                            </button>
                            <button type="button" wire:click="openReject({{ $row->id }})"
                                    class="inline-flex h-8 items-center rounded-lg px-3 text-xs font-semibold text-red-700 transition hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-500/10">
                                Rechazar
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="flex flex-col items-center justify-center px-4 py-16 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">
                        {{ $search !== '' ? 'Sin resultados' : 'Cola vacía' }}
                    </p>
                    <p class="mt-1 max-w-sm text-xs text-slate-500 dark:text-slate-400">
                        {{ $search !== ''
                            ? 'Prueba otro término de búsqueda.'
                            : 'No hay informes FO-GJ-51 pendientes de autorización.' }}
                    </p>
                    @if ($search !== '')
                        <button type="button" wire:click="$set('search', '')" class="mt-3 text-xs font-semibold text-indigo-600 dark:text-indigo-400">Limpiar búsqueda</button>
                    @else
                        <a href="{{ route('disciplinary.cases.index') }}" wire:navigate class="mt-3 text-xs font-semibold text-indigo-600 dark:text-indigo-400">Ir a disciplinarios →</a>
                    @endif
                </div>
            @endforelse
        </div>

        <div wire:loading wire:target="{{ $listTargets }}" class="space-y-3 p-4">
            @for ($i = 0; $i < 4; $i++)
                <div wire:key="inf-skel-{{ $i }}" class="animate-pulse rounded-lg border border-slate-100 p-3 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-slate-200 dark:bg-white/10"></div>
                        <div class="h-3 w-48 rounded bg-slate-200 dark:bg-white/10"></div>
                    </div>
                </div>
            @endfor
        </div>

        @if ($pending->hasPages())
            <div class="shrink-0 border-t border-slate-100 px-3 py-1.5 dark:border-white/10 [&_.pagination]:mb-0 [&_.pagination]:text-xs">{{ $pending->links() }}</div>
        @endif
    </div>

    {{-- Modal autorizar --}}
    @if ($approveConfirmId !== null)
        <div class="fixed inset-0 z-[75] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-[2px]"
             wire:keydown.escape="cancelApproveConfirm"
             role="dialog" aria-modal="true" aria-labelledby="approve-informe-title"
             wire:key="approve-confirm-{{ $approveConfirmId }}">
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                    <h2 id="approve-informe-title" class="text-lg font-bold text-slate-900 dark:text-white">Confirmar autorización</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        ¿Autorizar el informe de
                        <strong class="text-slate-900 dark:text-white">{{ $approveTarget?->employee?->displayName() ?? 'este trabajador' }}</strong>
                        y crear el expediente disciplinario?
                    </p>
                </div>
                <div class="flex flex-wrap justify-end gap-2 bg-slate-50 px-5 py-4 dark:bg-dash-ink/80">
                    <button type="button" wire:click="cancelApproveConfirm"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-white/15 dark:text-slate-200">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmApprove" wire:loading.attr="disabled"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="confirmApprove">Autorizar y crear caso</span>
                        <span wire:loading wire:target="confirmApprove">Procesando…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal rechazar --}}
    @if ($rejectId !== null)
        <div class="fixed inset-0 z-[75] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-[2px]"
             wire:keydown.escape="cancelReject"
             role="dialog" aria-modal="true" aria-labelledby="reject-informe-title"
             wire:key="reject-confirm-{{ $rejectId }}">
            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                    <h2 id="reject-informe-title" class="text-lg font-bold text-slate-900 dark:text-white">Rechazar informe</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        Se eliminará el PDF de
                        <strong class="text-slate-900 dark:text-white">{{ $rejectTarget?->employee?->displayName() ?? 'este envío' }}</strong>.
                        No podrá recuperarse desde la aplicación.
                    </p>
                </div>
                <div class="space-y-3 px-5 py-4">
                    <label for="reject-notes" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Motivo interno (opcional)</label>
                    <textarea id="reject-notes" wire:model="rejectNotes" rows="3" placeholder="Ej. Documento incompleto, falta evidencia…"
                              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-white/15 dark:bg-dash-lift dark:text-white"></textarea>
                    @error('rejectNotes')<p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    @error('rejectId')<p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 dark:border-white/10 dark:bg-dash-ink/80">
                    <button type="button" wire:click="cancelReject"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-white/15 dark:text-slate-200">
                        Cancelar
                    </button>
                    <button type="button" wire:click="reject" wire:loading.attr="disabled"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="reject">Confirmar rechazo</span>
                        <span wire:loading wire:target="reject">Eliminando…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal PDF (sin cambios de flujo) --}}
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
                            class="min-h-0 min-h-[200px] flex-1 bg-slate-100 dark:bg-black/40"
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
                            class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                        Cerrar
                    </button>
                    <button type="button"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-dash-lift dark:ring-1 dark:ring-white/15"
                            x-on:click="(() => { const a = document.createElement('a'); a.href = @js($pdfDownloadUrl); a.target = '_blank'; a.rel = 'noopener noreferrer'; document.body.appendChild(a); a.click(); a.remove(); })()">
                        Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
