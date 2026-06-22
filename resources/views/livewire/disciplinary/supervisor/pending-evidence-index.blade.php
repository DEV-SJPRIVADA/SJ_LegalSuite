<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Supervision · Tareas</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Evidencias pendientes</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Cargue la evidencia de notificación FO-GJ-03 o del comunicado de decisión (PDF escaneado o firmado en pantalla).</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('disciplinary.forms.informe-fo-gj-51', ['vista_completa' => 1]) }}"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm">
                    Nuevo informe disciplinario (FO-GJ-51)
                </a>
                <a href="{{ route('disciplinary.forms.informe-fo-gj-51', ['vista_completa' => 1, 'cargar_pdf' => 1]) }}"
                    class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 shadow-sm dark:border-white/15 dark:bg-white/10 dark:text-white dark:hover:bg-white/15">
                    Cargar informe en PDF
                </a>
            </div>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-100 dark:ring-emerald-500/30">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 overflow-hidden dark:bg-white/[0.04] dark:ring-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                        <thead class="bg-slate-50 dark:bg-white/[0.06]">
                            <tr class="text-xs uppercase tracking-wider text-slate-500 dark:text-dash-muted">
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">N. caso</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Trabajador</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Estado</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Citación generada</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-200">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200 text-sm dark:bg-transparent dark:divide-white/10">
                            @forelse ($tasks as $task)
                                <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.04]" wire:key="pending-evidence-row-{{ $task->id }}">
                                    <td class="px-4 py-3 font-mono text-xs text-slate-700 dark:text-slate-300">{{ $task->case_number }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                                        {{ $task->employee?->first_name }} {{ $task->employee?->last_name }}
                                    </td>
                                    <td class="px-4 py-3 text-amber-700 dark:text-amber-300 font-medium">Evidencia de citacion pendiente</td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $task->fo_gj_03_generated_at?->timezone('America/Bogota')->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <input type="file"
                                                id="evidence-file-{{ $task->id }}"
                                                class="sr-only"
                                                accept="application/pdf"
                                                wire:model.live="citationEvidenceFileByCase.{{ $task->id }}">
                                            <label for="evidence-file-{{ $task->id }}"
                                                class="inline-flex cursor-pointer items-center rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800">
                                                Cargar evidencia PDF
                                            </label>

                                            @can('viewFoGj03NotificationForSupervisor', $task)
                                                <button type="button" wire:click="openNotificationModal({{ $task->id }})"
                                                    class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-indigo-800 ring-1 ring-indigo-300 hover:bg-indigo-50 dark:bg-white/10 dark:text-indigo-200 dark:ring-indigo-400/40">
                                                    Notificación
                                                </button>
                                            @endcan
                                        </div>
                                        @error('citationEvidenceFileByCase.'.$task->id)
                                            <p class="mt-1 text-right text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                        No tiene evidencias pendientes.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (($decisionTasks ?? collect())->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg ring-1 ring-violet-200 overflow-hidden dark:bg-white/[0.04] dark:ring-violet-500/20">
                    <div class="border-b border-violet-200 px-4 py-3 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-violet-900 dark:text-violet-200">Evidencias de decisión pendientes</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                            <tbody class="bg-white divide-y divide-slate-200 text-sm dark:bg-transparent dark:divide-white/10">
                                @foreach ($decisionTasks as $task)
                                    <tr wire:key="pending-decision-evidence-{{ $task->id }}">
                                        <td class="px-4 py-3 font-mono text-xs">{{ $task->case_number }}</td>
                                        <td class="px-4 py-3">{{ $task->employee?->first_name }} {{ $task->employee?->last_name }}</td>
                                        <td class="px-4 py-3 text-violet-700 dark:text-violet-300">Comunicado de decisión</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex flex-wrap items-center justify-end gap-2">
                                                <input type="file" id="decision-evidence-{{ $task->id }}" class="sr-only" accept="application/pdf" wire:model.live="citationEvidenceFileByCase.{{ $task->id }}">
                                                <label for="decision-evidence-{{ $task->id }}" class="inline-flex cursor-pointer rounded-md bg-violet-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-800">Cargar evidencia PDF</label>
                                                @can('viewDecisionComunicadoForSupervisor', $task)
                                                    <button type="button" wire:click="openDecisionNotificationModal({{ $task->id }})"
                                                        class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-violet-800 ring-1 ring-violet-300 hover:bg-violet-50 dark:bg-white/10 dark:text-violet-200 dark:ring-violet-400/40">
                                                        Notificación
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
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
</div>
