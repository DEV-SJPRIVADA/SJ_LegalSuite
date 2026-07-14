<div data-live-case-id="{{ $case->id }}" wire:key="case-detail-{{ $case->id }}">
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="border-b border-slate-200 bg-white dark:border-white/10 dark:bg-dash-ink/60">
        <div class="mx-auto max-w-[1600px] px-4 py-2 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-fuchsia-400/90">Disciplinarios · Expediente</p>
                    <h1 class="truncate text-sm font-semibold leading-tight text-slate-900 dark:text-white">
                        Caso <span class="font-mono">{{ $case->case_number }}</span>
                    </h1>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <x-ui.btn variant="teal" href="{{ route('disciplinary.cases.index') }}" wire:navigate class="!h-8 text-xs">
                        ← Volver al listado
                    </x-ui.btn>
                    @unless (auth()->user()->isDisciplinaryOperacionesReviewer())
                        <x-disciplinary.status-badge :status="$case->current_status" size="md" />
                        @if ($case->current_status === \App\Enums\Disciplinary\CaseStatus::INFORME)
                            @can('transition', $case)
                                <x-ui.btn type="button" wire:click="openAdvanceStageConfirm" class="!h-8 text-xs">
                                    Cambiar de etapa
                                </x-ui.btn>
                                <x-ui.btn type="button" variant="secondary" wire:click="openArchiveConfirm" class="!h-8 text-xs">
                                    Archivar
                                </x-ui.btn>
                            @endcan
                        @endif
                    @endunless
                </div>
            </div>
        </div>
    </div>

    <div class="py-4 sm:py-6">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/35 dark:text-emerald-300 dark:ring-emerald-500/25">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200 dark:bg-red-950/35 dark:text-red-300 dark:ring-red-500/25">
                    {{ session('error') }}
                </div>
            @endif

            @can('claim', $case)
                <div class="rounded-lg bg-amber-50 px-4 py-4 ring-1 ring-amber-200 flex flex-wrap items-center justify-between gap-3 dark:bg-amber-950/30 dark:ring-amber-500/30">
                    <div>
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">Bandeja compartida (etapa informe)</p>
                        <p class="text-xs text-amber-800/90 mt-1 dark:text-amber-100/80">
                            Este expediente aún no tiene abogado titular. Confirme la gestión para asignárselo y continuar el trámite.
                        </p>
                    </div>
                    <x-ui.btn type="button" wire:click="openClaimConfirm">
                        Gestionar caso
                    </x-ui.btn>
                </div>
            @endcan

            @if (auth()->user()->isDisciplinaryOperacionesReviewer())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden dark:bg-white/[0.04] dark:ring-1 dark:ring-white/10 dark:shadow-dash-card">
                    <div class="p-6">
                        @include('livewire.disciplinary.cases.partials.operaciones-follow-up', ['case' => $case])
                    </div>
                </div>
            @else
            {{-- Tabs --}}
            @php
                $actor = auth()->user();
                $tabs = [
                    'gestion' => 'Gestión',
                    'timeline' => 'Línea de tiempo',
                    'documents' => 'Documentos',
                    'history' => 'Historial (misma cédula)',
                    'audit' => 'Actuaciones',
                ];
                if ($actor->isDisciplinaryFieldOperator()) {
                    unset($tabs['timeline'], $tabs['audit']);
                } elseif ($actor->isDisciplinaryProgramador()) {
                    unset($tabs['audit']);
                }
            @endphp
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden dark:bg-white/[0.04] dark:ring-1 dark:ring-white/10 dark:shadow-dash-card">
                <div class="flex border-b border-gray-200 text-sm dark:border-white/10 overflow-x-auto">
                    @foreach ($tabs as $key => $label)
                        <button wire:click="setTab('{{ $key }}')"
                            class="px-5 py-3 font-medium border-b-2 transition
                                {{ $activeTab === $key ? 'border-indigo-600 text-indigo-700 dark:border-indigo-400 dark:text-indigo-300' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="p-6">
                    @if ($activeTab === 'gestion')
                        <div class="space-y-5">
                            @include('livewire.disciplinary.cases.partials.case-summary-strip', [
                                'case' => $case,
                                'lawyerCandidates' => $lawyerCandidates,
                            ])
                            @include('livewire.disciplinary.cases.partials.case-stage-cards', [
                                'case' => $case,
                                'stageCards' => $stageCards,
                                'stageLetterColors' => $stageLetterColors,
                            ])
                        </div>

                    @elseif ($activeTab === 'history')
                        <div class="space-y-4">
                            <p class="text-sm text-gray-700 dark:text-slate-300 max-w-3xl">
                                Procesos <span class="font-semibold">distintos a este caso</span> que el sistema encuentra por el mismo
                                número de documento del trabajador ({{ $case->employee?->document_number ?? '—' }}), según la BD de empleados.
                            </p>
                            @php
                                /** @var \Illuminate\Support\Collection|null $relatedCases */
                                $__related = $relatedCases ?? collect();
                            @endphp
                            @if ($case->employee === null || ! filled($case->employee->document_number ?? null))
                                <p class="text-sm text-gray-500 dark:text-slate-400">Este caso no tiene trabajador vinculado; no puede armarse historial por cédula.</p>
                            @elseif ($__related->isEmpty())
                                <p class="text-sm text-gray-500 dark:text-slate-400">No aparecen otros expedientes registrados para esta cédula con su usuario actual.</p>
                            @else
                                <ul class="divide-y divide-gray-200 dark:divide-white/10 rounded-lg border border-gray-200 dark:border-white/10 overflow-hidden bg-white dark:bg-dash-ink/40">
                                    @foreach ($__related as $rel)
                                        <li class="px-4 py-3 flex flex-wrap items-start justify-between gap-3 hover:bg-gray-50 dark:hover:bg-white/5">
                                            <div>
                                                <a href="{{ route('disciplinary.cases.show', $rel) }}" wire:navigate class="font-mono font-semibold text-indigo-700 hover:underline dark:text-cyan-300">
                                                    {{ $rel->case_number }}</a>
                                                <p class="text-xs text-gray-600 dark:text-slate-400 mt-1">
                                                    Apertura {{ $rel->opened_at?->format('Y-m-d') ?? '—' }}
                                                    · Estado: {{ $rel->current_status->label() }}
                                                    @if ($rel->assignedLawyer)
                                                        · Abogado: {{ $rel->assignedLawyer->name }}
                                                    @endif
                                                </p>
                                            </div>
                                            <span class="text-xs uppercase tracking-wide text-gray-400 dark:text-slate-500">Ver expediente</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                    @elseif ($activeTab === 'timeline')
                        <ol class="relative border-s border-gray-200 ms-4 space-y-6 dark:border-white/15">
                            @forelse ($case->stages as $stage)
                                <li class="ms-6">
                                    <span class="absolute -start-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 ring-4 ring-white dark:ring-dash-ink">
                                        <span class="text-[10px] text-white font-bold">{{ $stage->sequence }}</span>
                                    </span>
                                    <div class="bg-gray-50 rounded-md p-4 ring-1 ring-gray-200 dark:bg-white/[0.06] dark:ring-white/10">
                                        <div class="flex items-center justify-between gap-3 flex-wrap">
                                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                                {{ $stage->stage_type->label() }}
                                                @if ($stage->form_code)
                                                    <span class="text-xs text-gray-500 dark:text-slate-400 font-mono">({{ $stage->form_code }})</span>
                                                @endif
                                            </h4>
                                            <div class="flex items-center gap-2 flex-shrink-0">
                                                @can('assignDate', $case)
                                                    <button type="button" wire:click="openScheduleStage({{ $stage->id }})"
                                                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 px-2 py-1 rounded ring-1 ring-indigo-200 bg-white dark:bg-white/10 dark:text-indigo-300 dark:ring-indigo-400/40 dark:hover:text-indigo-200">
                                                        Programar fechas
                                                    </button>
                                                @endcan
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ring-1 ring-inset
                                                    {{ $stage->status->value === 'completada' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30' : ($stage->status->value === 'en_curso' ? 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-500/30' : 'bg-gray-50 text-gray-700 dark:text-slate-300 ring-gray-200 dark:bg-white/10 dark:text-slate-300 dark:ring-white/15') }}">
                                                    {{ $stage->status->label() }}
                                                </span>
                                            </div>
                                        </div>
                                        <dl class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-xs text-gray-600 dark:text-slate-400">
                                            @if ($stage->scheduled_at)
                                                <div><dt class="inline font-semibold">Programada:</dt> <dd class="inline">{{ $stage->scheduled_at->format('Y-m-d H:i') }}</dd></div>
                                            @endif
                                            @if ($stage->performed_at)
                                                <div><dt class="inline font-semibold">Ejecutada:</dt> <dd class="inline">{{ $stage->performed_at->format('Y-m-d H:i') }}</dd></div>
                                            @endif
                                            @if ($stage->deadline_at)
                                                <div><dt class="inline font-semibold">Plazo:</dt> <dd class="inline">{{ $stage->deadline_at->format('Y-m-d') }}</dd></div>
                                            @endif
                                            @if ($stage->performer)
                                                <div><dt class="inline font-semibold">Responsable:</dt> <dd class="inline">{{ $stage->performer->name }}</dd></div>
                                            @endif
                                        </dl>
                                        @if ($stage->notes)
                                            <p class="mt-2 text-sm text-gray-700 dark:text-slate-300 whitespace-pre-line">{{ $stage->notes }}</p>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="ms-6 text-sm text-gray-500 dark:text-slate-400">Sin etapas registradas todavía.</li>
                            @endforelse
                        </ol>

                    @elseif ($activeTab === 'documents')
                        @include('livewire.disciplinary.cases.partials.case-documents-tab', ['case' => $case])

                    @elseif ($activeTab === 'audit')
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:bg-white/5 dark:text-slate-400">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold">Fecha</th>
                                        <th class="px-3 py-2 text-left font-semibold">Acción</th>
                                        <th class="px-3 py-2 text-left font-semibold">De → A</th>
                                        <th class="px-3 py-2 text-left font-semibold">Usuario</th>
                                        <th class="px-3 py-2 text-left font-semibold">Notas</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-transparent dark:divide-white/10">
                                    @forelse ($case->actions as $a)
                                        <tr>
                                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500 dark:text-slate-400">
                                                {{ $a->performed_at->format('Y-m-d H:i') }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <code class="text-xs bg-gray-100 rounded px-1.5 py-0.5 dark:bg-white/10 dark:text-slate-200">{{ $a->action_type->value }}</code>
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-700 dark:text-slate-300">
                                                @if ($a->from_status)
                                                    {{ $a->from_status->label() }}
                                                @endif
                                                @if ($a->from_status && $a->to_status) → @endif
                                                @if ($a->to_status)
                                                    <span class="font-semibold">{{ $a->to_status->label() }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-gray-700 dark:text-slate-300">{{ $a->user?->name ?? '—' }}</td>
                                            <td class="px-3 py-2 text-gray-600 dark:text-slate-400">{{ $a->description }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-slate-400">Sin actuaciones registradas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Confirmación A → B (etapa Informe) --}}
    @if ($showAdvanceStageConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            x-data x-on:keydown.escape.window="$wire.closeAdvanceStageConfirm()">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full dark:bg-dash-ink dark:ring-1 dark:ring-white/15" x-on:click.outside="$wire.closeAdvanceStageConfirm()">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Cambiar de etapa</h3>
                    <button type="button" wire:click="closeAdvanceStageConfirm" class="text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-gray-700 dark:text-slate-200 leading-relaxed">
                        Pasarás el caso a la etapa <strong class="text-gray-900 dark:text-white">{{ $advanceStageLabel }}</strong>.
                        Podrás coordinar fechas con planeación en la pestaña Gestión.
                    </p>
                    @error('advanceStage')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <div class="flex justify-end gap-2 pt-2">
                        <x-ui.btn type="button" variant="muted" wire:click="closeAdvanceStageConfirm">
                            Cancelar
                        </x-ui.btn>
                        <x-ui.btn type="button" wire:click="confirmAdvanceStage" wire:loading.attr="disabled">
                            Confirmar
                        </x-ui.btn>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Confirmación archivar (etapa Informe) --}}
    @if ($showArchiveConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            x-data x-on:keydown.escape.window="$wire.closeArchiveConfirm()">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full dark:bg-dash-ink dark:ring-1 dark:ring-white/15" x-on:click.outside="$wire.closeArchiveConfirm()">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Archivar expediente</h3>
                    <button type="button" wire:click="closeArchiveConfirm" class="text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-gray-700 dark:text-slate-200 leading-relaxed">
                        ¿Archivar este expediente? El caso quedará en estado <strong class="text-gray-900 dark:text-white">archivado</strong> y no continuará el flujo disciplinario.
                    </p>
                    @error('archiveCase')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <div class="flex justify-end gap-2 pt-2">
                        <x-ui.btn type="button" variant="muted" wire:click="closeArchiveConfirm">
                            Cancelar
                        </x-ui.btn>
                        <x-ui.btn type="button" variant="warning" wire:click="confirmArchive" wire:loading.attr="disabled">
                            Confirmar
                        </x-ui.btn>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal fechas de etapa (Planeación / Jurídico) --}}
    @if ($showScheduleModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            x-data x-on:keydown.escape.window="$wire.closeScheduleModal()">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full dark:bg-dash-ink dark:ring-1 dark:ring-white/15" x-on:click.outside="$wire.closeScheduleModal()">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Programar fechas de etapa</h3>
                    <button wire:click="closeScheduleModal" type="button" class="text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300">✕</button>
                </div>
                <form wire:submit="saveSchedule" class="p-6 space-y-4">
                    <p class="text-xs text-gray-600 dark:text-slate-400">
                        Define la fecha programada y el plazo sin cambiar el estado del proceso.
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Programado para</label>
                            <input type="datetime-local" wire:model="scheduleAt"
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100">
                            @error('scheduleAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Plazo</label>
                            <input type="date" wire:model="scheduleDeadline"
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100">
                            @error('scheduleDeadline') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Nota (opcional)</label>
                        <textarea wire:model="scheduleNote" rows="2"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100 placeholder:dark:text-slate-500"
                            placeholder="Motivo del cambio de fecha…"></textarea>
                        @error('scheduleNote') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <x-ui.btn type="button" variant="muted" wire:click="closeScheduleModal">
                            Cancelar
                        </x-ui.btn>
                        <x-ui.btn type="submit">
                            Guardar fechas
                        </x-ui.btn>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($fo51PdfPreviewDocumentId !== null)
        @php
            $fo51PreviewDoc = $case->documents->firstWhere('id', $fo51PdfPreviewDocumentId);
        @endphp
        @if ($fo51PreviewDoc)
            @php
                $fo51PdfPreviewUrl = route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $fo51PreviewDoc]);
                $fo51PdfDownloadUrl = route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $fo51PreviewDoc, 'download' => 1]);
                $fo51PreviewEvidence = $case->fo51InformePreviewEvidenceDocuments($fo51PreviewDoc);
            @endphp
            <div class="fixed inset-0 z-[70] flex items-center justify-center p-3 sm:p-4"
                x-data="window.sjInformePdfPreviewLightbox()"
                x-on:keydown.escape.window="zoomOpen ? closeZoom() : $wire.closeFo51PdfPreview()"
                role="dialog"
                aria-modal="true"
                aria-labelledby="case-fo51-pdf-preview-title"
                wire:key="case-fo51-pdf-preview-{{ $case->id }}-{{ $fo51PdfPreviewDocumentId }}">
                <div class="absolute inset-0 bg-black/50 dark:bg-black/60" wire:click="closeFo51PdfPreview" aria-hidden="true"></div>
                <div class="relative flex h-[min(92dvh,calc(100dvh-2rem))] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15">
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10 sm:px-5">
                        <h2 id="case-fo51-pdf-preview-title" class="text-base font-bold text-slate-900 dark:text-white">Informe FO-GJ-51 (PDF del expediente)</h2>
                        <button type="button" wire:click="closeFo51PdfPreview"
                            class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white"
                            aria-label="Cerrar">
                            ✕
                        </button>
                    </div>
                    <div class="relative flex min-h-0 flex-1 flex-col">
                        <iframe wire:ignore title="Vista previa informe PDF"
                            class="min-h-0 flex-1 min-h-[200px] bg-slate-100 dark:bg-black/40"
                            src="{{ $fo51PdfPreviewUrl }}"></iframe>

                        @if ($fo51PreviewEvidence->isNotEmpty())
                            <section class="shrink-0 border-t border-slate-200 bg-slate-50/90 px-4 py-3 dark:border-white/10 dark:bg-dash-ink/90 sm:px-5">
                                <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-700 dark:text-emerald-300/90">Evidencia</p>
                                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">En imágenes: clic para ampliar y rueda del ratón para zoom. Otros archivos se abren en una pestaña nueva.</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    @foreach ($fo51PreviewEvidence as $evIdx => $evDoc)
                                        @php
                                            $evidenceUrl = route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $evDoc]);
                                            $evidenceLabel = 'Evidencia '.($evIdx + 1);
                                        @endphp
                                        @if ($evDoc->isLikelyRasterImage())
                                            <button type="button"
                                                title="Ver en grande"
                                                class="group relative h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-200/70 ring-emerald-500/20 transition hover:ring-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-white/15 dark:bg-black/50 dark:ring-emerald-400/30"
                                                x-on:click="openZoom(@js($evidenceUrl), @js($evidenceLabel))">
                                                <img src="{{ $evidenceUrl }}" alt="{{ $evidenceLabel }}" loading="lazy"
                                                    class="pointer-events-none h-full w-full object-cover transition group-hover:brightness-95 dark:group-hover:brightness-110">
                                            </button>
                                        @else
                                            <a href="{{ $evidenceUrl }}" target="_blank" rel="noopener noreferrer"
                                                class="inline-flex max-w-[12rem] items-center gap-1 truncate rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-indigo-700 ring-emerald-500/15 hover:bg-slate-50 dark:border-white/15 dark:bg-dash-lift dark:text-cyan-300 dark:hover:bg-white/10"
                                                title="Abrir archivo">
                                                {{ $evDoc->original_name ?: $evidenceLabel }}
                                            </a>
                                        @endif
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
                        <x-ui.btn type="button" variant="ghost" wire:click="closeFo51PdfPreview">
                            Cerrar
                        </x-ui.btn>
                        <x-ui.btn type="button" variant="dark"
                            x-on:click="(() => { const a = document.createElement('a'); a.href = @js($fo51PdfDownloadUrl); a.target = '_blank'; a.rel = 'noopener noreferrer'; document.body.appendChild(a); a.click(); a.remove(); })()">
                            Descargar PDF
                        </x-ui.btn>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @include('livewire.disciplinary.cases.partials.case-document-preview-modal', [
        'case' => $case,
        'documentPreviewId' => $documentPreviewId,
    ])

    @can('assign', $case)
        @if ($showLawyerConfirmModal)
            <div class="fixed inset-0 z-[88] flex items-center justify-center p-4"
                x-data
                x-on:keydown.escape.window="$wire.cancelLawyerAssignment()"
                wire:key="lawyer-confirm-{{ $case->id }}">
                <div class="absolute inset-0 bg-black/55 dark:bg-black/65" wire:click="cancelLawyerAssignment" aria-hidden="true"></div>
                <div class="relative w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="lawyer-confirm-title">
                    <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                        <h2 id="lawyer-confirm-title" class="text-lg font-bold text-slate-900 dark:text-white">
                            @if ($lawyerConfirmKind === 'assign')
                                Confirmar asignación
                            @elseif ($lawyerConfirmKind === 'change')
                                Confirmar cambio de abogado
                            @else
                                Quitar abogado titular
                            @endif
                        </h2>
                        <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                            @if ($lawyerConfirmKind === 'assign')
                                ¿Confirma asignar a <strong class="text-slate-900 dark:text-white">{{ $lawyerConfirmTargetName }}</strong> como abogado titular de este expediente?
                            @elseif ($lawyerConfirmKind === 'change')
                                ¿Confirma cambiar el abogado titular a <strong class="text-slate-900 dark:text-white">{{ $lawyerConfirmTargetName }}</strong>?
                            @else
                                ¿Confirma dejar este expediente <strong class="text-slate-900 dark:text-white">sin abogado titular</strong> asignado?
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 dark:border-white/10 dark:bg-dash-ink/80">
                        <x-ui.btn type="button" variant="ghost" wire:click="cancelLawyerAssignment">
                            Cancelar
                        </x-ui.btn>
                        @if ($lawyerConfirmKind === 'change')
                            <x-ui.btn type="button" wire:click="confirmLawyerAssignment">
                                Confirmar cambio
                            </x-ui.btn>
                        @else
                            <x-ui.btn type="button" wire:click="confirmLawyerAssignment">
                                Confirmar
                            </x-ui.btn>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endcan

    @if ($showClaimConfirm)
        <div class="fixed inset-0 z-[88] flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.cancelClaimConfirm()"
            wire:key="claim-confirm-{{ $case->id }}">
            <div class="absolute inset-0 bg-black/55 dark:bg-black/65" wire:click="cancelClaimConfirm" aria-hidden="true"></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-200 dark:bg-dash-ink dark:ring-white/15"
                role="dialog" aria-modal="true" aria-labelledby="claim-confirm-detail-title">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-white/10">
                    <h2 id="claim-confirm-detail-title" class="text-lg font-bold text-slate-900 dark:text-white">
                        Confirmar gestión del caso
                    </h2>
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                        ¿Confirma que tomará la gestión del expediente
                        <strong class="font-mono text-slate-900 dark:text-white">{{ $case->case_number }}</strong>?
                        Se le asignará como abogado titular.
                    </p>
                </div>
                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 dark:border-white/10 dark:bg-dash-ink/80">
                    <x-ui.btn type="button" variant="ghost" wire:click="cancelClaimConfirm">
                        Cancelar
                    </x-ui.btn>
                    <x-ui.btn type="button" wire:click="confirmClaimCase" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="confirmClaimCase">Sí, gestionar caso</span>
                        <span wire:loading wire:target="confirmClaimCase">Asignando…</span>
                    </x-ui.btn>
                </div>
            </div>
        </div>
    @endif

    @if ($planningChatFabVisible)
        <button type="button" wire:click="openPlanningChatModal"
            class="fixed bottom-5 right-5 z-[60] inline-flex items-center gap-2 rounded-full bg-indigo-600 px-4 py-3 text-sm font-semibold text-white ring-1 ring-indigo-500/40 hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 dark:bg-indigo-500 dark:hover:bg-indigo-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3h6.75M12 21a9 9 0 1 0-5.89-2.25L3 21l2.25-3.11A8.96 8.96 0 0 0 12 21Z" />
            </svg>
            Chat planeación
        </button>
    @endif

    @include('livewire.disciplinary.cases.partials.planning-chat-modal', [
        'case' => $case,
        'citationReadOnly' => $citationReadOnly ?? false,
        'showsDecisionStageReadOnly' => $showsDecisionStageReadOnly ?? false,
        'diligenceSlotDisplay' => $diligenceSlotDisplay,
    ])

    @include('livewire.disciplinary.cases.partials.case-stage-modal-shell', [
        'openStageModal' => $openStageModal,
        'stageModalReadOnly' => $stageModalReadOnly,
    ])

    @include('livewire.disciplinary.cases.partials.case-stage-foot-modals', [
        'case' => $case,
        'citationAdvanceTargetLabel' => $citationAdvanceTargetLabel,
        'supervisorCandidates' => $supervisorCandidates,
        'citationReadOnly' => $citationReadOnly ?? false,
        'decisionBranch' => $decisionBranch ?? null,
        'diligenceAdvanceTargetLabel' => $diligenceAdvanceTargetLabel ?? null,
        'showDiligenceAdvanceConfirm' => $showDiligenceAdvanceConfirm ?? false,
    ])
</div>
