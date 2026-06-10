{{-- Etapa A: informe FO-GJ-51 (siempre al final de la pila de etapas). --}}
<div class="space-y-6" data-stage-block="a">
    @if ($case->current_status === \App\Enums\Disciplinary\CaseStatus::INFORME && $case->assigned_lawyer_id)
        @php
            $tzCo = 'America/Bogota';
            $stageAFo51 = $case->primaryFo51InformeDocument();
            $stageASubmission = $case->informeSubmission;
            $stageAEnviadoPor = $stageASubmission?->submitter ?? $case->reporter;
            $stageAEnviadoEn = $stageASubmission?->created_at;
            $stageAAutorPor = $stageASubmission?->reviewer ?? $stageAFo51?->uploader;
            $stageADtFmt = fn ($dt) => $dt ? $dt->copy()->timezone($tzCo)->format('d/m/Y H:i') : null;
            $stageALatestAssign = $case->actions->first(fn ($a) => $a->action_type === \App\Enums\Disciplinary\ActionType::CASO_ASIGNADO);
            $stageAAssigner = $stageALatestAssign?->user;
            $stageAAssignerCargo = $stageAAssigner ? ($stageAAssigner->jobPosition?->name ?? (filled($stageAAssigner->position) ? trim((string) $stageAAssigner->position) : null) ?? 'Sin cargo registrado') : null;
            $stageAAutorCargo = $stageAAutorPor ? ($stageAAutorPor->jobPosition?->name ?? (filled($stageAAutorPor->position) ? trim((string) $stageAAutorPor->position) : null) ?? 'Sin cargo registrado') : null;
            $fo51PdfEtapaA = ($stageAFo51 && $stageAFo51->path !== '') ? $stageAFo51 : null;
            $canGenerateFo51EtapaA = Gate::allows('generateFo51Inform', \App\Models\Disciplinary\DisciplinaryCase::class);
        @endphp
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-5 dark:border-emerald-400/35 dark:bg-emerald-950/30">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h4 class="text-xs uppercase tracking-wider font-semibold text-emerald-900 dark:text-emerald-200">Etapa A:</h4>
                    <p class="mt-1 text-xs font-medium text-emerald-800/95 dark:text-emerald-100/85">Falta e informe disciplinario (FO-GJ-51)</p>
                </div>
                <div class="flex shrink-0 flex-col items-stretch gap-2 sm:items-end">
                    @can('viewFo51InformePdf', $case)
                        @if ($fo51PdfEtapaA)
                            <button type="button" wire:click="openFo51PdfPreview"
                                class="inline-flex items-center justify-center px-4 py-2 bg-white text-emerald-800 text-sm font-semibold rounded-md ring-1 ring-emerald-300/90 hover:bg-emerald-100/80 dark:bg-white/10 dark:text-emerald-200 dark:ring-emerald-400/40 dark:hover:bg-white/15">
                                Ver informe (PDF)
                            </button>
                            @error('fo51')
                                <p class="text-xs text-red-600 dark:text-red-400 sm:text-right">{{ $message }}</p>
                            @enderror
                        @elseif ($canGenerateFo51EtapaA)
                            <a href="{{ $case->employee ? route('disciplinary.cases.index', ['informe_modal' => 1, 'nombre' => trim($case->employee->first_name.' '.$case->employee->last_name), 'cedula' => $case->employee->document_number]) : route('disciplinary.cases.index', ['informe_modal' => 1]) }}" wire:navigate
                                class="inline-flex items-center justify-center px-4 py-2 bg-white text-emerald-800 text-sm font-semibold rounded-md ring-1 ring-emerald-300/90 hover:bg-emerald-100/80 dark:bg-white/10 dark:text-emerald-200 dark:ring-emerald-400/40 dark:hover:bg-white/15">
                                Abrir formulario FO-GJ-51
                            </a>
                        @endif
                    @endcan
                </div>
            </div>

            <div class="mt-4 space-y-3 text-sm text-emerald-950/95 dark:text-emerald-50/95 border-t border-emerald-200/70 dark:border-emerald-500/25 pt-4">
                @if ($stageAEnviadoPor || $stageAEnviadoEn || $stageAFo51)
                    <p class="leading-relaxed">
                        <span class="font-semibold text-emerald-900 dark:text-emerald-100">Informe FO-GJ-51:</span>
                        @if ($stageAEnviadoPor && $stageAEnviadoEn)
                            enviado a revisión por <strong>{{ $stageAEnviadoPor->name }}</strong>
                            el {{ $stageADtFmt($stageAEnviadoEn) }} <span class="text-xs text-emerald-800/80 dark:text-emerald-200/70">(Colombia)</span>.
                        @elseif ($stageAEnviadoPor)
                            registrado a nombre de <strong>{{ $stageAEnviadoPor->name }}</strong>.
                        @endif
                        @if ($stageAFo51 && $stageAFo51->created_at)
                            @if ($stageAEnviadoPor || $stageAEnviadoEn)
                                El PDF fue incorporado al expediente el <strong>{{ $stageADtFmt($stageAFo51->created_at) }}</strong> <span class="text-xs text-emerald-800/80 dark:text-emerald-200/70">(Colombia)</span>.
                            @else
                                PDF incorporado al expediente el <strong>{{ $stageADtFmt($stageAFo51->created_at) }}</strong> <span class="text-xs text-emerald-800/80 dark:text-emerald-200/70">(Colombia)</span>.
                            @endif
                        @endif
                    </p>
                @else
                    <p class="text-emerald-900/85 dark:text-emerald-100/80">
                        <span class="font-semibold">Informe FO-GJ-51:</span> no hay registro detallado del envío; consulte el PDF del expediente y la línea de tiempo.
                    </p>
                @endif

                <p class="leading-relaxed">
                    <span class="font-semibold text-emerald-900 dark:text-emerald-100">Autorización y creación del caso:</span>
                    @if ($stageAAutorPor)
                        <strong>{{ $stageAAutorCargo }}</strong> <strong>{{ $stageAAutorPor->name }}</strong>
                    @else
                        <span class="text-emerald-900/85 dark:text-emerald-100/75">Sin registro del usuario que autorizó y generó el expediente.</span>
                    @endif
                </p>

                <p class="leading-relaxed">
                    <span class="font-semibold text-emerald-900 dark:text-emerald-100">Revisión y asignación:</span>
                    @if ($stageALatestAssign && $stageAAssigner && $case->assignedLawyer && $stageALatestAssign->performed_at)
                        <strong>{{ $stageAAssignerCargo }}</strong> <strong>{{ $stageAAssigner->name }}</strong>
                        asignó el caso al abogado <strong>{{ $case->assignedLawyer->name }}</strong>
                        el <strong>{{ $stageADtFmt($stageALatestAssign->performed_at) }}</strong> <span class="text-xs text-emerald-800/80 dark:text-emerald-200/70">(Colombia)</span>.
                    @elseif ($case->assignedLawyer)
                        Abogado titular: <strong>{{ $case->assignedLawyer->name }}</strong>.
                        <span class="text-emerald-900/85 dark:text-emerald-100/75"> No consta en el historial quién registró la asignación.</span>
                    @else
                        <span class="text-emerald-900/85 dark:text-emerald-100/75">Sin abogado asignado registrado.</span>
                    @endif
                </p>
            </div>
        </div>
    @endif

    @if ($case->current_status === \App\Enums\Disciplinary\CaseStatus::INFORME && $case->agendaThread && $case->agendaThread->messages->isNotEmpty())
        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5 space-y-3 dark:border-white/15 dark:bg-slate-900/40">
            <h4 class="text-xs uppercase tracking-wider text-slate-600 font-semibold dark:text-slate-300">Historial de mensajes (registro anterior en etapa Informe)</h4>
            <p class="text-xs text-slate-600 dark:text-slate-400">Sólo lectura. La conversación vigente con planeación es la de <strong>citación / reprogramación</strong>.</p>
            <ul class="space-y-3 max-h-72 overflow-y-auto pr-1 text-sm">
                @foreach ($case->agendaThread->messages as $msg)
                    <li class="rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/5">
                        <div class="flex flex-wrap justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $msg->author?->name ?? '—' }}</span>
                            <span>{{ $msg->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <p class="mt-1 text-slate-800 dark:text-slate-200 whitespace-pre-wrap">{{ $msg->body }}</p>
                        @foreach ($msg->attachments as $att)
                            @if ($att->isImage())
                                <a href="{{ route('disciplinary.cases.agenda-attachment.inline', [$case, $att]) }}" target="_blank" rel="noopener" class="mt-2 inline-block text-xs font-semibold text-indigo-700 hover:underline dark:text-cyan-300">Ver imagen: {{ $att->original_name }}</a>
                            @else
                                <a href="{{ route('disciplinary.cases.agenda-attachment.download', [$case, $att]) }}" class="mt-2 inline-flex text-xs font-semibold text-indigo-700 hover:underline dark:text-cyan-300">Descargar: {{ $att->original_name }}</a>
                            @endif
                        @endforeach
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @can('viewFo51InformePdf', $case)
        @php
            $fo51PdfOnCase = $case->primaryFo51InformeDocument();
            $canGenerateFo51 = Gate::allows('generateFo51Inform', \App\Models\Disciplinary\DisciplinaryCase::class);
            $fo51MergedIntoEtapaA = $case->current_status === \App\Enums\Disciplinary\CaseStatus::INFORME
                && filled($case->assigned_lawyer_id);
        @endphp
        @unless ($fo51MergedIntoEtapaA)
            <div class="rounded-xl border border-indigo-100 bg-indigo-50/80 p-4 dark:border-cyan-400/25 dark:bg-cyan-500/10">
                <p class="text-sm font-semibold text-indigo-900 dark:text-cyan-100">Informe disciplinario FO-GJ-51</p>
                @if ($fo51PdfOnCase && $fo51PdfOnCase->path !== '')
                    <p class="text-xs text-indigo-800/90 mt-1 dark:text-slate-300">
                        @if ($canGenerateFo51)
                            Este expediente ya incluye el PDF del informe autorizado; no se puede editar en formulario. Use la vista previa para consultarlo y las miniaturas para la evidencia conservada.
                        @else
                            PDF del informe autorizado en el expediente. Use la vista previa para consultarlo y las miniaturas para la evidencia conservada.
                        @endif
                    </p>
                    <button type="button" wire:click="openFo51PdfPreview"
                        class="mt-3 inline-flex items-center px-4 py-2 bg-white text-indigo-700 text-sm font-semibold rounded-md ring-1 ring-indigo-200 hover:bg-indigo-50 dark:bg-white/10 dark:text-cyan-200 dark:ring-cyan-400/35 dark:hover:bg-white/15">
                        Ver informe (PDF)
                    </button>
                    @error('fo51')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                @elseif ($canGenerateFo51)
                    <p class="text-xs text-indigo-800/90 mt-1 dark:text-slate-300">
                        Antes de existir expediente, el informe pasa por la cola «Revisión informes». Si el caso ya existe, puede generar de nuevo el PDF o cargar un anexo cuando corresponda.</p>
                    <a href="{{ $case->employee ? route('disciplinary.cases.index', ['informe_modal' => 1, 'nombre' => trim($case->employee->first_name.' '.$case->employee->last_name), 'cedula' => $case->employee->document_number]) : route('disciplinary.cases.index', ['informe_modal' => 1]) }}" wire:navigate
                        class="mt-3 inline-flex items-center px-4 py-2 bg-white text-indigo-700 text-sm font-semibold rounded-md ring-1 ring-indigo-200 hover:bg-indigo-50 dark:bg-white/10 dark:text-cyan-200 dark:ring-cyan-400/35 dark:hover:bg-white/15">
                        Abrir formulario FO-GJ-51
                    </a>
                @endif
            </div>
        @endunless
    @endcan
</div>
