{{-- Fase A: previsualización PDF antes de subir evidencia escaneada --}}
@if ($evidencePreviewCaseId !== null && $evidencePreviewUrl)
    <div class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-4"
        x-data
        x-on:keydown.escape.window="$wire.cancelEvidenceUpload()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="evidence-preview-title"
        wire:key="evidence-preview-{{ $evidencePreviewCaseId }}">
        <div class="absolute inset-0 bg-black/50 dark:bg-black/60" wire:click="cancelEvidenceUpload" aria-hidden="true"></div>
        <div class="relative flex h-[min(92dvh,calc(100dvh-2rem))] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
            <div class="shrink-0 border-b border-slate-200 px-4 py-3 dark:border-white/10 sm:px-5">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-fuchsia-400/90">Confirmación</p>
                <h2 id="evidence-preview-title" class="text-sm font-semibold text-slate-900 dark:text-white">Evidencia PDF</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Revise el documento antes de cargarlo al expediente.</p>
            </div>

            <div class="min-h-0 flex-1 bg-slate-100 dark:bg-black/40">
                <iframe wire:ignore title="Vista previa evidencia PDF" class="h-full min-h-[240px] w-full bg-white dark:bg-black/20"
                    src="{{ $evidencePreviewUrl }}"></iframe>
            </div>

            <div class="shrink-0 space-y-3 border-t border-slate-200 bg-slate-50 px-4 py-4 dark:border-white/10 dark:bg-dash-ink/80 sm:px-5">
                <fieldset>
                    <legend class="text-xs font-semibold text-slate-700 dark:text-slate-300">Tipo de evidencia</legend>
                    <div class="mt-2 flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-2 text-slate-800 dark:text-slate-200">
                            <input type="radio" wire:model.live="evidencePreviewType" value="signed" class="text-indigo-600">
                            Citación firmada
                        </label>
                        <label class="inline-flex items-center gap-2 text-slate-800 dark:text-slate-200">
                            <input type="radio" wire:model.live="evidencePreviewType" value="refused_witnesses" class="text-indigo-600">
                            Rechazo con testigos
                        </label>
                    </div>
                </fieldset>
                @error('citationEvidenceFileByCase.'.$evidencePreviewCaseId)
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="cancelEvidenceUpload"
                        class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmEvidenceUpload" wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-60">
                        <span wire:loading.remove wire:target="confirmEvidenceUpload">Confirmar y cargar</span>
                        <span wire:loading wire:target="confirmEvidenceUpload">Cargando…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Fase B: notificación HTML + firma del trabajador o testigos --}}
@if ($notificationCaseId !== null && $notificationCase && $notificationViewData && empty($signedNotificationPreviewToken))
    <div class="fixed inset-0 z-[78] flex items-center justify-center p-2 sm:p-4"
        x-data="{ scale: 1 }"
        x-on:keydown.escape.window="$wire.closeNotificationModal()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="notification-modal-title"
        wire:key="notification-modal-{{ $notificationCaseId }}">
        <div class="absolute inset-0 bg-black/55 dark:bg-black/65" wire:click="closeNotificationModal" aria-hidden="true"></div>
        <div class="relative flex h-[min(96dvh,calc(100dvh-1rem))] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10 sm:px-5">
                <div>
                    <h2 id="notification-modal-title" class="text-base font-bold text-slate-900 dark:text-white">
                        Notificación · {{ $notificationCase->case_number }}
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ $notificationCase->employee?->first_name }} {{ $notificationCase->employee?->last_name }}
                        · Revise el documento y registre la recepción según el tipo de evidencia.
                    </p>
                </div>
                <button type="button" wire:click="closeNotificationModal"
                    class="shrink-0 rounded-md p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10" aria-label="Cerrar">✕</button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto bg-slate-100 p-3 dark:bg-black/30 sm:p-4"
                x-ref="letterScroller"
                x-init="
                    const updateScale = () => {
                        const sheet = $refs.letterSheet;
                        const scroller = $refs.letterScroller;
                        if (! sheet || ! scroller) return;
                        const available = scroller.clientWidth - 24;
                        const sheetWidth = sheet.offsetWidth;
                        scale = sheetWidth > available ? Math.max(available / sheetWidth, 0.45) : 1;
                    };
                    $nextTick(updateScale);
                    window.addEventListener('resize', updateScale);
                ">
                <div class="ogj-letter-screen-scaler">
                    <div class="ogj-letter-screen-sheet" x-ref="letterSheet" :style="`transform: scale(${scale});`">
                        @include('disciplinary.forms.partials.official-letter-pdf-styles')
                        <div class="ogj-wrap">
                            <div class="ogj-page ogj-page--screen-preview">
                                <table class="ogj-tbl ogj-head-grid" role="presentation">
                                    <colgroup>
                                        <col style="width:102px">
                                        <col>
                                        <col style="width:114px">
                                    </colgroup>
                                    <tbody>
                                        <tr>
                                            <td class="ogj-logo-cell">
                                                <img src="{{ \App\Support\Pdf\EmbeddedPublicAsset::disciplinaryLogoDataUri() }}" alt="SJ Seguridad">
                                            </td>
                                            <td class="ogj-title">Citación a diligencia disciplinaria</td>
                                            <td class="ogj-meta">
                                                <table class="ogj-meta-grid" role="presentation">
                                                    <tr><td class="ogj-meta-code">FO-GJ-03</td></tr>
                                                    <tr><td>Octubre de 2023</td></tr>
                                                    <tr><td>Versión 03</td></tr>
                                                    <tr><td>Página 1 de 1</td></tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                @include('disciplinary.forms.partials.fo-gj-03-body', array_merge($notificationViewData, ['blankForDownload' => false]))
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="shrink-0 space-y-3 border-t border-slate-200 bg-slate-50 px-4 py-3 dark:border-white/10 dark:bg-dash-ink/80 sm:px-5">
                <fieldset>
                    <legend class="text-xs font-semibold text-slate-700 dark:text-slate-300">Tipo de evidencia</legend>
                    <div class="mt-2 flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-2 text-slate-800 dark:text-slate-200">
                            <input type="radio" wire:model.live="notificationEvidenceType" value="signed" class="text-indigo-600">
                            Citación firmada
                        </label>
                        <label class="inline-flex items-center gap-2 text-slate-800 dark:text-slate-200">
                            <input type="radio" wire:model.live="notificationEvidenceType" value="refused_witnesses" class="text-indigo-600">
                            Rechazo con testigos
                        </label>
                    </div>
                </fieldset>

                @if ($notificationEvidenceType === 'signed')
                    @if ($workerSignatureDataUri)
                        <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">✓ Firma del trabajador capturada.</p>
                    @else
                        <p class="text-xs text-amber-700 dark:text-amber-300">Capture la firma del trabajador antes de continuar.</p>
                    @endif
                @else
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        El trabajador se registra como «Se niega a firmar». Capture las firmas y datos de los dos testigos.
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">Testigo 1</p>
                            <div class="mt-2 space-y-2">
                                <input type="text" wire:model.live="witness1Name" placeholder="Nombre"
                                    class="w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                <input type="text" wire:model.live="witness1Document" placeholder="Cédula" inputmode="numeric"
                                    class="w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="openWitnessSignaturePad(1)"
                                        class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:bg-white/10 dark:text-indigo-200 dark:ring-indigo-400/40">
                                        Firma testigo 1
                                    </button>
                                    @if ($witness1SignatureDataUri)
                                        <button type="button" wire:click="clearWitnessSignature(1)"
                                            class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10">
                                            Borrar firma
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @error('witness1Name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            @error('witness1Document')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            @error('witness1Signature')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">Testigo 2</p>
                            <div class="mt-2 space-y-2">
                                <input type="text" wire:model.live="witness2Name" placeholder="Nombre"
                                    class="w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                <input type="text" wire:model.live="witness2Document" placeholder="Cédula" inputmode="numeric"
                                    class="w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="openWitnessSignaturePad(2)"
                                        class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:bg-white/10 dark:text-indigo-200 dark:ring-indigo-400/40">
                                        Firma testigo 2
                                    </button>
                                    @if ($witness2SignatureDataUri)
                                        <button type="button" wire:click="clearWitnessSignature(2)"
                                            class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10">
                                            Borrar firma
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @error('witness2Name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            @error('witness2Document')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            @error('witness2Signature')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endif

                @error('workerSignature')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @error('signedNotification')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <button type="button" wire:click="closeNotificationModal"
                        class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                        Cerrar
                    </button>
                    @if ($notificationEvidenceType === 'signed')
                        @if ($workerSignatureDataUri)
                            <button type="button" wire:click="clearWorkerSignature"
                                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-slate-200 dark:ring-white/20">
                                Borrar firma
                            </button>
                        @endif
                        <button type="button" wire:click="openWorkerSignaturePad"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:bg-white/10 dark:text-indigo-200 dark:ring-indigo-400/40">
                            Firma trabajador
                        </button>
                    @endif
                    <button type="button" wire:click="acceptSignedNotificationPreview" wire:loading.attr="disabled"
                        @disabled(! $this->notificationUploadReady())
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                        <span wire:loading.remove wire:target="acceptSignedNotificationPreview">Aceptar</span>
                        <span wire:loading wire:target="acceptSignedNotificationPreview">Generando PDF…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Fase B (decisión): comunicado FO-GJ-DECISION + firma del trabajador o testigos --}}
@if (($decisionNotificationCaseId ?? null) !== null && ($decisionNotificationCase ?? null) && ($decisionNotificationViewData ?? null) && empty($signedNotificationPreviewToken))
    <div class="fixed inset-0 z-[78] flex items-center justify-center p-2 sm:p-4"
        x-data="{ scale: 1 }"
        x-on:keydown.escape.window="$wire.closeDecisionNotificationModal()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="decision-notification-modal-title"
        wire:key="decision-notification-modal-{{ $decisionNotificationCaseId }}">
        <div class="absolute inset-0 bg-black/55 dark:bg-black/65" wire:click="closeDecisionNotificationModal" aria-hidden="true"></div>
        <div class="relative flex h-[min(96dvh,calc(100dvh-1rem))] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-violet-200 dark:bg-dash-ink dark:ring-violet-500/30">
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10 sm:px-5">
                <div>
                    <h2 id="decision-notification-modal-title" class="text-base font-bold text-slate-900 dark:text-white">
                        Comunicado de decisión · {{ $decisionNotificationCase->case_number }}
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ $decisionNotificationCase->employee?->first_name }} {{ $decisionNotificationCase->employee?->last_name }}
                        · Revise el documento y registre la recepción según el tipo de evidencia.
                    </p>
                </div>
                <button type="button" wire:click="closeDecisionNotificationModal"
                    class="shrink-0 rounded-md p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-white/10" aria-label="Cerrar">✕</button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto bg-slate-100 p-3 dark:bg-black/30 sm:p-4"
                x-ref="decisionLetterScroller"
                x-init="
                    const updateScale = () => {
                        const sheet = $refs.decisionLetterSheet;
                        const scroller = $refs.decisionLetterScroller;
                        if (! sheet || ! scroller) return;
                        const available = scroller.clientWidth - 24;
                        const sheetWidth = sheet.offsetWidth;
                        scale = sheetWidth > available ? Math.max(available / sheetWidth, 0.45) : 1;
                    };
                    $nextTick(updateScale);
                    window.addEventListener('resize', updateScale);
                ">
                <div class="ogj-letter-screen-scaler">
                    <div class="ogj-letter-screen-sheet" x-ref="decisionLetterSheet" :style="`transform: scale(${scale});`">
                        @include('disciplinary.forms.partials.official-letter-pdf-styles')
                        <div class="ogj-wrap">
                            <div class="ogj-page ogj-page--screen-preview">
                                <table class="ogj-tbl ogj-head-grid" role="presentation">
                                    <colgroup>
                                        <col style="width:102px">
                                        <col>
                                        <col style="width:114px">
                                    </colgroup>
                                    <tbody>
                                        <tr>
                                            <td class="ogj-logo-cell">
                                                <img src="{{ \App\Support\Pdf\EmbeddedPublicAsset::disciplinaryLogoDataUri() }}" alt="SJ Seguridad">
                                            </td>
                                            <td class="ogj-title">Comunicado de decisión de sanción o cierre del proceso</td>
                                            <td class="ogj-meta">
                                                <table class="ogj-meta-grid" role="presentation">
                                                    <tr><td class="ogj-meta-code">FO-GJ-DECISION</td></tr>
                                                    <tr><td>{{ $decisionNotificationViewData['issuedDate'] ?? '' }}</td></tr>
                                                    <tr><td>Versión 01</td></tr>
                                                    <tr><td>Página 1 de 1</td></tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                @include('disciplinary.forms.partials.decision-comunicado-body', array_merge($decisionNotificationViewData, ['blankForDownload' => false]))
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="shrink-0 space-y-3 border-t border-slate-200 bg-slate-50 px-4 py-3 dark:border-white/10 dark:bg-dash-ink/80 sm:px-5">
                <fieldset>
                    <legend class="text-xs font-semibold text-slate-700 dark:text-slate-300">Tipo de evidencia</legend>
                    <div class="mt-2 flex flex-wrap gap-4 text-sm">
                        <label class="inline-flex items-center gap-2 text-slate-800 dark:text-slate-200">
                            <input type="radio" wire:model.live="notificationEvidenceType" value="signed" class="text-violet-600">
                            Comunicado firmado
                        </label>
                        <label class="inline-flex items-center gap-2 text-slate-800 dark:text-slate-200">
                            <input type="radio" wire:model.live="notificationEvidenceType" value="refused_witnesses" class="text-violet-600">
                            Rechazo con testigos
                        </label>
                    </div>
                </fieldset>

                @if ($notificationEvidenceType === 'signed')
                    @if ($workerSignatureDataUri)
                        <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">✓ Firma del trabajador capturada.</p>
                    @else
                        <p class="text-xs text-amber-700 dark:text-amber-300">Capture la firma del trabajador antes de continuar.</p>
                    @endif
                @else
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        El trabajador se registra como «Se niega a firmar». Capture las firmas y datos de los dos testigos.
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">Testigo 1</p>
                            <div class="mt-2 space-y-2">
                                <input type="text" wire:model.live="witness1Name" placeholder="Nombre"
                                    class="w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                <input type="text" wire:model.live="witness1Document" placeholder="Cédula" inputmode="numeric"
                                    class="w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="openWitnessSignaturePad(1)"
                                        class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-violet-800 ring-1 ring-violet-300 hover:bg-violet-50 dark:bg-white/10 dark:text-violet-200 dark:ring-violet-400/40">
                                        Firma testigo 1
                                    </button>
                                    @if ($witness1SignatureDataUri)
                                        <button type="button" wire:click="clearWitnessSignature(1)"
                                            class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10">
                                            Borrar firma
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @error('witness1Name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            @error('witness1Document')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            @error('witness1Signature')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">Testigo 2</p>
                            <div class="mt-2 space-y-2">
                                <input type="text" wire:model.live="witness2Name" placeholder="Nombre"
                                    class="w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                <input type="text" wire:model.live="witness2Document" placeholder="Cédula" inputmode="numeric"
                                    class="w-full rounded-md border-slate-300 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="openWitnessSignaturePad(2)"
                                        class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-violet-800 ring-1 ring-violet-300 hover:bg-violet-50 dark:bg-white/10 dark:text-violet-200 dark:ring-violet-400/40">
                                        Firma testigo 2
                                    </button>
                                    @if ($witness2SignatureDataUri)
                                        <button type="button" wire:click="clearWitnessSignature(2)"
                                            class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10">
                                            Borrar firma
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @error('witness2Name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            @error('witness2Document')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            @error('witness2Signature')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endif

                @error('workerSignature')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @error('signedDecisionNotification')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <button type="button" wire:click="closeDecisionNotificationModal"
                        class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                        Cerrar
                    </button>
                    @if ($notificationEvidenceType === 'signed')
                        @if ($workerSignatureDataUri)
                            <button type="button" wire:click="clearWorkerSignature"
                                class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-slate-200 dark:ring-white/20">
                                Borrar firma
                            </button>
                        @endif
                        <button type="button" wire:click="openWorkerSignaturePad"
                            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-violet-800 ring-1 ring-violet-300 hover:bg-violet-50 dark:bg-white/10 dark:text-violet-200 dark:ring-violet-400/40">
                            Firma trabajador
                        </button>
                    @endif
                    <button type="button" wire:click="acceptSignedNotificationPreview" wire:loading.attr="disabled"
                        @disabled(! $this->notificationUploadReady())
                        class="inline-flex items-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-violet-500 dark:hover:bg-violet-400">
                        <span wire:loading.remove wire:target="acceptSignedNotificationPreview">Aceptar</span>
                        <span wire:loading wire:target="acceptSignedNotificationPreview">Generando PDF…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Fase C: vista previa del PDF firmado antes de enviar al expediente --}}
@if (! empty($signedNotificationPreviewToken) && $signedNotificationPreviewUrl)
    <div class="fixed inset-0 z-[82] flex items-center justify-center p-3 sm:p-4"
        x-data
        x-on:keydown.escape.window="$wire.cancelSignedNotificationPreview()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="signed-notification-preview-title"
        wire:key="signed-notification-preview-{{ $signedNotificationPreviewToken }}">
        <div class="absolute inset-0 bg-black/55 dark:bg-black/65" wire:click="cancelSignedNotificationPreview" aria-hidden="true"></div>
        <div class="relative flex h-[min(92dvh,calc(100dvh-2rem))] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
            <div class="shrink-0 border-b border-slate-200 px-4 py-3 dark:border-white/10 sm:px-5">
                <h2 id="signed-notification-preview-title" class="text-base font-bold text-slate-900 dark:text-white">
                    Documento firmado
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Descargue una copia para el trabajador y luego envíe el documento al expediente.
                    @if (! empty($signedNotificationPreviewFilename))
                        <span class="block font-mono text-[11px] mt-0.5">{{ $signedNotificationPreviewFilename }}</span>
                    @endif
                </p>
            </div>

            <div class="min-h-0 flex-1 bg-slate-100 dark:bg-black/40">
                <iframe wire:ignore title="Vista previa notificación firmada" class="h-full min-h-[240px] w-full bg-white dark:bg-black/20"
                    src="{{ $signedNotificationPreviewUrl }}"></iframe>
            </div>

            <div class="shrink-0 space-y-3 border-t border-slate-200 bg-slate-50 px-4 py-4 dark:border-white/10 dark:bg-dash-ink/80 sm:px-5">
                @error('signedNotification')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @error('signedDecisionNotification')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" wire:click="cancelSignedNotificationPreview"
                        class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                        Volver
                    </button>
                    @if ($signedNotificationDownloadUrl)
                        <a href="{{ $signedNotificationDownloadUrl }}"
                            class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-800 ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-white dark:ring-white/20">
                            Descargar
                        </a>
                    @endif
                    <button type="button" wire:click="confirmSignedNotificationUpload" wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-60">
                        <span wire:loading.remove wire:target="confirmSignedNotificationUpload">Enviar</span>
                        <span wire:loading wire:target="confirmSignedNotificationUpload">Enviando…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@if ($showSignaturePadModal)
    @php
        $signaturePadTitle = match ($signaturePadTarget) {
            'witness1' => 'Firma del testigo 1',
            'witness2' => 'Firma del testigo 2',
            default => 'Firma del trabajador',
        };
    @endphp
    <x-disciplinary.signature-capture-modal
        :show="true"
        :title="$signaturePadTitle"
        wire-key="worker-signature-pad-{{ $signaturePadTarget }}"
        close-action="closeWorkerSignaturePad"
        save-action="saveCapturedSignature"
        variant="indigo"
    />
@endif
