@php
    use App\Support\Disciplinary\SupervisorEvidenceQueueService;
@endphp

<div class="disciplinary-supervisor-queue mx-auto flex h-[calc(100dvh-3.25rem)] max-h-[calc(100dvh-3.25rem)] w-full max-w-[1600px] flex-col overflow-hidden px-3 py-2 sm:px-5 sm:py-3 lg:px-6">
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <header class="mb-2 flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-white/10 pb-2 dark:border-white/10">
        <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-fuchsia-400/90">Supervisión · Notificaciones</p>
            <h1 class="truncate text-sm font-semibold text-slate-900 dark:text-white">Evidencias pendientes</h1>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @can('generateFo51Inform', \App\Models\Disciplinary\DisciplinaryCase::class)
                <x-ui.btn type="button" wire:click="openFo51Modal(false)" class="!h-8 text-xs">
                    Nuevo informe (FO-GJ-51)
                </x-ui.btn>
                <x-ui.btn type="button" variant="ghost" wire:click="openFo51Modal(true)" class="!h-8 text-xs">
                    Cargar PDF
                </x-ui.btn>
            @endcan
        </div>
    </header>

    @if (session('success'))
        <div class="mb-2 shrink-0 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-100 dark:ring-emerald-500/30">
            {{ session('success') }}
        </div>
    @endif

    @if (! auth()->user()->hasFieldDisciplinaryScopeConfigured())
        <div class="mb-2 shrink-0 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900 ring-1 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-100 dark:ring-amber-500/30">
            Su usuario no tiene ciudades autorizadas. Contacte al administrador para asignarle municipios antes de cargar evidencias o generar informes.
        </div>
    @endif

  {{-- Rail Citación / Decisión / Todos --}}
    <nav
        class="mb-2 flex shrink-0 flex-wrap items-center gap-0.5 overflow-x-auto rounded-lg border border-slate-200 bg-white px-1 py-1 shadow-sm ring-1 ring-slate-200 dark:border-white/10 dark:bg-white/[0.04] dark:ring-white/5 sm:gap-1 sm:px-1.5"
        aria-label="Filtrar cola de evidencias">
        @php
            $railItems = [
                ['key' => SupervisorEvidenceQueueService::QUEUE_CITATION, 'label' => 'Citación', 'count' => $queueCounts['citation'] ?? 0, 'accent' => 'text-orange-400'],
                ['key' => SupervisorEvidenceQueueService::QUEUE_DECISION, 'label' => 'Decisión', 'count' => $queueCounts['decision'] ?? 0, 'accent' => 'text-fuchsia-400'],
            ];
        @endphp
        @foreach ($railItems as $item)
            @php $isActive = $activeQueue === $item['key']; @endphp
            <button type="button" wire:click="setQueue('{{ $item['key'] }}')" title="{{ $item['label'] }}"
                @class([
                    'inline-flex min-w-[5.5rem] shrink-0 items-center justify-center gap-1.5 rounded-md px-2 py-1.5 text-xs font-semibold tabular-nums transition',
                    'bg-slate-100 ring-1 ring-slate-300 dark:bg-white/10 dark:ring-white/20' => $isActive,
                    'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-white/[0.06]' => ! $isActive,
                ])>
                <span class="{{ $item['accent'] }}">{{ $item['label'] }}</span>
                <span class="text-slate-700 dark:text-slate-200">{{ number_format($item['count']) }}</span>
            </button>
        @endforeach

        <span class="mx-0.5 hidden h-5 w-px shrink-0 bg-slate-200 dark:bg-white/15 sm:block" aria-hidden="true"></span>

        <button type="button" wire:click="setQueue('')" title="Todas las evidencias pendientes"
            @class([
                'inline-flex shrink-0 items-center gap-1.5 rounded-md px-2 py-1.5 text-xs font-semibold tabular-nums transition',
                'bg-slate-100 ring-1 ring-slate-300 dark:bg-white/10 dark:ring-white/20' => $activeQueue === '',
                'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-white/[0.06]' => $activeQueue !== '',
            ])>
            Todos
            <span class="text-slate-700 dark:text-slate-200">{{ number_format($queueCounts['total'] ?? 0) }}</span>
        </button>
    </nav>

    @php
        $hasQueueFilters = $search !== '' || $activeQueue !== '';
        $visibleCount = $queueTasks->count();
    @endphp

    <div class="mb-2 shrink-0">
        <label for="supervisor-queue-search" class="sr-only">Buscar</label>
        <input id="supervisor-queue-search" type="search" wire:model.live.debounce.350ms="search"
            placeholder="N° de caso, nombre o documento…" autocomplete="off"
            class="w-full max-w-md rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-white/15 dark:bg-dash-lift dark:text-white">
        @if ($search !== '')
            <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                <span class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($visibleCount) }}</span>
                {{ $visibleCount === 1 ? 'resultado' : 'resultados' }}
            </p>
        @endif
    </div>

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm ring-1 ring-slate-200 dark:border-white/10 dark:bg-white/[0.04] dark:ring-white/10">
        <div class="min-h-0 flex-1 overflow-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-white/10">
                <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase tracking-wider text-slate-500 shadow-sm dark:bg-dash-ink/95 dark:text-dash-muted">
                    <tr>
                        <th class="px-3 py-2.5 text-left font-semibold">N. caso</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Trabajador</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Tipo</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Notificación</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Documento</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Generado</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white dark:divide-white/10 dark:bg-transparent">
                    @forelse ($queueTasks as $row)
                        @php
                            $task = $row['case'];
                            $isCitation = $row['queue_type'] === SupervisorEvidenceQueueService::QUEUE_CITATION;
                            $typeBadgeClass = $isCitation
                                ? 'bg-orange-50 text-orange-800 ring-orange-200 dark:bg-orange-500/15 dark:text-orange-200 dark:ring-orange-500/30'
                                : 'bg-fuchsia-50 text-fuchsia-800 ring-fuchsia-200 dark:bg-fuchsia-500/15 dark:text-fuchsia-200 dark:ring-fuchsia-500/30';
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.04]" wire:key="supervisor-queue-{{ $row['queue_type'] }}-{{ $task->id }}">
                            <td class="whitespace-nowrap px-3 py-2.5 font-mono text-xs text-slate-700 dark:text-slate-300">{{ $task->case_number }}</td>
                            <td class="px-3 py-2.5">
                                <p class="font-medium text-slate-900 dark:text-slate-100">
                                    {{ $task->employee?->first_name }} {{ $task->employee?->last_name }}
                                </p>
                                @if ($task->employee?->document_number)
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $task->employee->document_number }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-inset {{ $typeBadgeClass }}">
                                    {{ $isCitation ? 'Citación' : 'Decisión' }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-xs text-slate-700 dark:text-slate-300">{{ $row['notification_summary'] }}</td>
                            <td class="px-3 py-2.5 text-xs font-semibold text-slate-800 dark:text-slate-200">{{ $row['document_label'] }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-xs tabular-nums text-slate-600 dark:text-slate-400">
                                {{ $row['generated_at']?->timezone('America/Bogota')->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <input type="file"
                                        id="evidence-file-{{ $task->id }}"
                                        class="sr-only"
                                        accept="application/pdf"
                                        wire:model.live="citationEvidenceFileByCase.{{ $task->id }}">
                                    <label for="evidence-file-{{ $task->id }}"
                                        class="sj-btn {{ $isCitation ? 'sj-btn--teal' : 'sj-btn--primary' }} !inline-flex !h-7 cursor-pointer items-center !px-2.5 !text-[11px]">
                                        Cargar PDF
                                    </label>

                                    @if ($isCitation)
                                        @can('viewFoGj03NotificationForSupervisor', $task)
                                            <x-ui.btn type="button" variant="ghost" wire:click="openNotificationModal({{ $task->id }})" class="!h-7 !px-2.5 !text-[11px]">
                                                Notificación
                                            </x-ui.btn>
                                        @endcan
                                    @else
                                        @can('viewDecisionComunicadoForSupervisor', $task)
                                            <x-ui.btn type="button" variant="ghost" wire:click="openDecisionNotificationModal({{ $task->id }})" class="!h-7 !px-2.5 !text-[11px]">
                                                Notificación
                                            </x-ui.btn>
                                        @endcan
                                    @endif
                                </div>
                                @error('citationEvidenceFileByCase.'.$task->id)
                                    <p class="mt-1 text-right text-[11px] text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center">
                                <div class="mx-auto max-w-md space-y-2.5">
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        @if ($search !== '')
                                            Sin resultados para su búsqueda
                                        @elseif ($activeQueue === SupervisorEvidenceQueueService::QUEUE_CITATION)
                                            No hay citaciones pendientes
                                        @elseif ($activeQueue === SupervisorEvidenceQueueService::QUEUE_DECISION)
                                            No hay decisiones pendientes
                                        @else
                                            No hay evidencias pendientes
                                        @endif
                                    </p>
                                    <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                                        @if ($search !== '')
                                            Pruebe con otro número de caso, nombre o documento.
                                        @elseif ($activeQueue === SupervisorEvidenceQueueService::QUEUE_CITATION)
                                            Cuando planeación le asigne una citación, aparecerá aquí para cargar el PDF o registrar la firma.
                                        @elseif ($activeQueue === SupervisorEvidenceQueueService::QUEUE_DECISION)
                                            Cuando planeación le asigne un comunicado de decisión, aparecerá aquí para cargar el PDF o registrar la firma.
                                        @else
                                            Cuando planeación le asigne una notificación, aparecerá aquí para cargar el PDF o registrar la firma en pantalla.
                                        @endif
                                    </p>
                                    @if ($search !== '' || $activeQueue !== '')
                                        <div>
                                            <x-ui.btn type="button" variant="ghost" wire:click="clearQueueFilters" class="!h-8 text-xs">
                                                Ver todas las tareas
                                            </x-ui.btn>
                                        </div>
                                    @elseif (Gate::allows('generateFo51Inform', \App\Models\Disciplinary\DisciplinaryCase::class))
                                        <div>
                                            <x-ui.btn type="button" wire:click="openFo51Modal(false)" class="!h-8 text-xs">
                                                Nuevo informe FO-GJ-51
                                            </x-ui.btn>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($visibleCount > 0)
            <div class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-3 py-2 text-xs text-slate-500 dark:border-white/10 dark:text-dash-muted">
                <p>
                    Mostrando
                    <span class="font-semibold tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($visibleCount) }}</span>
                    {{ $visibleCount === 1 ? 'tarea' : 'tareas' }}
                    @if ($hasQueueFilters)
                        en esta vista
                    @endif
                </p>
            </div>
        @endif
    </div>

    @include('livewire.disciplinary.supervisor.partials.pending-evidence-modals', [
        'evidencePreviewCaseId' => $evidencePreviewCaseId,
        'evidencePreviewUrl' => $evidencePreviewUrl,
        'notificationCaseId' => $notificationCaseId,
        'notificationCase' => $notificationCase,
        'notificationViewData' => $notificationViewData,
        'decisionNotificationCaseId' => $decisionNotificationCaseId,
        'decisionNotificationCase' => $decisionNotificationCase,
        'decisionNotificationViewData' => $decisionNotificationViewData,
        'signedNotificationPreviewToken' => $signedNotificationPreviewToken,
        'signedNotificationPreviewUrl' => $signedNotificationPreviewUrl,
        'signedNotificationDownloadUrl' => $signedNotificationDownloadUrl,
        'signedNotificationPreviewFilename' => $signedNotificationPreviewFilename,
        'notificationEvidenceType' => $notificationEvidenceType,
        'workerSignatureDataUri' => $workerSignatureDataUri,
        'witness1SignatureDataUri' => $witness1SignatureDataUri,
        'witness2SignatureDataUri' => $witness2SignatureDataUri,
        'signaturePadTarget' => $signaturePadTarget,
        'showSignaturePadModal' => $showSignaturePadModal,
    ])

    @if ($showFo51Modal)
        @include('disciplinary.forms.partials.fo-gj-51-informe-modal-shell', [
            'prefillWorkerName' => $fo51PrefillName,
            'prefillWorkerDocument' => $fo51PrefillDocument,
            'openPdfUploadModal' => $fo51OpenPdfFirst,
            'operacionesReviewers' => $operacionesReviewers ?? collect(),
        ])
    @endif
</div>
