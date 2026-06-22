<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gestión humana · Terminación</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    Casos en terminación de contrato: cargue anexos laborales al expediente y marque la gestión como completada.
                </p>
            </div>

            @if (session('success'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-100">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-500/15 dark:text-red-100">{{ session('error') }}</div>
            @endif

            @if ($tasks->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400">No hay casos pendientes de gestión humana.</p>
            @else
                <ul class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white dark:divide-white/10 dark:border-white/10 dark:bg-white/[0.04]">
                    @foreach ($tasks as $task)
                        @php
                            $annexCount = $task->decisionHrAnnexDocuments()->count();
                        @endphp
                        <li class="px-4 py-4 space-y-3" wire:key="decision-hr-task-{{ $task->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-mono font-semibold text-slate-900 dark:text-white">{{ $task->case_number }}</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        {{ $task->employee?->first_name }} {{ $task->employee?->last_name }}
                                        · C.C. {{ $task->employee?->document_number }}
                                    </p>
                                    <p class="mt-1 text-xs text-violet-700 dark:text-violet-300">
                                        {{ $annexCount > 0 ? $annexCount.' anexo(s) cargado(s)' : 'Sin anexos laborales aún' }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <input type="file"
                                        id="hr-annex-{{ $task->id }}"
                                        class="sr-only"
                                        accept="application/pdf"
                                        wire:model="hrAnnexFileByCase.{{ $task->id }}">
                                    <label for="hr-annex-{{ $task->id }}"
                                        class="inline-flex cursor-pointer items-center rounded-md bg-white px-3 py-2 text-xs font-semibold text-violet-800 ring-1 ring-violet-300 hover:bg-violet-50 dark:bg-white/10 dark:text-violet-200 dark:ring-violet-400/40">
                                        Seleccionar PDF
                                    </label>
                                    @if (! empty($hrAnnexFileByCase[$task->id] ?? null))
                                        <button type="button" wire:click="uploadHrAnnex({{ $task->id }})" wire:loading.attr="disabled"
                                            class="inline-flex items-center rounded-md bg-violet-600 px-3 py-2 text-xs font-semibold text-white hover:bg-violet-700 disabled:opacity-60">
                                            <span wire:loading.remove wire:target="uploadHrAnnex({{ $task->id }})">Subir anexo</span>
                                            <span wire:loading wire:target="uploadHrAnnex({{ $task->id }})">Subiendo…</span>
                                        </button>
                                    @endif
                                    @can('completeDecisionHrReview', $task)
                                        <button type="button" wire:click="completeHrReview({{ $task->id }})"
                                            wire:confirm="¿Confirma que los anexos laborales fueron gestionados?"
                                            class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                            Marcar gestión completada
                                        </button>
                                    @else
                                        <span class="text-xs text-amber-700 dark:text-amber-300">Cargue al menos un anexo PDF</span>
                                    @endcan
                                </div>
                            </div>
                            @error('hrAnnexFileByCase.'.$task->id)
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @if ($annexCount > 0)
                                <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1">
                                    @foreach ($task->decisionHrAnnexDocuments() as $doc)
                                        <li>· {{ $doc->original_name }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
