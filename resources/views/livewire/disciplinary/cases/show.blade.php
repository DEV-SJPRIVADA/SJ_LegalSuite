<div data-live-case-id="{{ $case->id }}" wire:key="case-detail-{{ $case->id }}">
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto py-5 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <a href="{{ route('disciplinary.cases.index') }}" wire:navigate
                        class="text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white">← Volver al listado</a>
                    <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold mt-2 dark:text-dash-muted">Disciplinarios · Detalle</p>
                    <h1 class="font-bold text-2xl text-slate-900 leading-tight mt-1 dark:text-white">
                        Caso <span class="font-mono">{{ $case->case_number }}</span>
                    </h1>
                    <p class="text-sm text-slate-600 mt-1 dark:text-slate-300">
                        {{ $case->employee?->first_name }} {{ $case->employee?->last_name }}
                        @if ($case->employee?->document_number)
                            · CC {{ $case->employee->document_number }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <x-disciplinary.status-badge :status="$case->current_status" class="text-sm px-3 py-1" />
                    @if ($case->current_status === \App\Enums\Disciplinary\CaseStatus::INFORME)
                        @can('transition', $case)
                            <button type="button" wire:click="openAdvanceStageConfirm"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                Cambiar de etapa
                            </button>
                            <button type="button" wire:click="openArchiveConfirm"
                                class="inline-flex items-center px-4 py-2 bg-white text-slate-700 text-sm font-semibold rounded-md ring-1 ring-slate-300 hover:bg-slate-50 dark:bg-white/10 dark:text-slate-200 dark:ring-white/20 dark:hover:bg-white/15">
                                Archivar
                            </button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="py-8">
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
                    <button type="button" wire:click="openClaimConfirm"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 shrink-0">
                        Gestionar caso
                    </button>
                </div>
            @endcan

            {{-- Tabs --}}
            @php
                $actor = auth()->user();
                $tabs = [
                    'overview' => 'Información',
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
                    @if ($activeTab === 'overview')
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Disciplinado</dt>
                                    <dd class="text-gray-900 dark:text-white font-medium">
                                        {{ $case->employee?->first_name }} {{ $case->employee?->last_name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Documento</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $case->employee?->document_type }} {{ $case->employee?->document_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Cargo / Sede</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $case->employee?->job_title ?? '—' }} · {{ $case->sede ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Ciudad</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $case->city ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Reportado por</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $case->reporter?->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="sr-only">Abogado titular del caso</dt>
                                    <dd class="text-gray-900 dark:text-white">
                                        @can('assign', $case)
                                            <select wire:model="assignedLawyerId" wire:change="onLawyerSelectChanged"
                                                class="w-full max-w-md rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100">
                                                <option value="">Seleccionar abogado</option>
                                                @foreach ($lawyerCandidates as $law)
                                                    <option value="{{ $law->id }}">{{ $law->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('assignedLawyerId')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        @else
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $case->assignedLawyer?->name ?? 'Sin abogado asignado' }}</span>
                                        @endcan
                                    </dd>
                                </div>
                            </dl>
                            <dl class="space-y-3 text-sm md:col-span-1 xl:col-span-1">
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Apertura</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $case->opened_at?->format('Y-m-d') }}</dd>
                                </div>
                                @if ($case->closed_at)
                                    <div>
                                        <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Cierre</dt>
                                        <dd class="text-gray-900 dark:text-white">{{ $case->closed_at?->format('Y-m-d') }}</dd>
                                    </div>
                                @endif
                                @if ($case->decision)
                                    <div>
                                        <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Decisión</dt>
                                        <dd class="text-gray-900 dark:text-white font-semibold">{{ $case->decision->label() }}</dd>
                                    </div>
                                    @if ($case->decision_notes)
                                        <div>
                                            <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Notas de la decisión</dt>
                                            <dd class="text-gray-700 dark:text-slate-300 whitespace-pre-line">{{ $case->decision_notes }}</dd>
                                        </div>
                                    @endif
                                @endif
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted">Resumen</dt>
                                    <dd class="text-gray-700 dark:text-slate-300 whitespace-pre-line">{{ $case->summary ?? '—' }}</dd>
                                </div>
                            </dl>

                            {{-- Faltas: tercera columna en xl, fila completa abajo en md --}}
                            <div class="md:col-span-2 xl:col-span-1">
                                <h4 class="text-xs uppercase tracking-wider text-gray-500 font-semibold dark:text-dash-muted mb-2">Faltas imputadas</h4>
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($case->faults as $f)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/35 dark:text-rose-300 dark:ring-rose-500/30">
                                            {{ $f->code }} · {{ $f->name }}
                                            @if ($f->pivot->extra_info)
                                                <span class="text-rose-500 ml-1">({{ $f->pivot->extra_info }})</span>
                                            @endif
                                        </span>
                                    @empty
                                        <span class="text-sm text-gray-500 dark:text-slate-400">Sin faltas registradas todavía.</span>
                                    @endforelse
                                </div>
                            </div>

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
                                <div class="md:col-span-2 xl:col-span-3 rounded-xl border border-emerald-200 bg-emerald-50/70 p-5 dark:border-emerald-400/35 dark:bg-emerald-950/30">
                                    {{-- Fila 1: título + acceso PDF --}}
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
                                        {{-- Fila 2 --}}
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

                                        {{-- Fila 3: quien autoriza el informe y crea el expediente (no quien solo envía a revisión) --}}
                                        <p class="leading-relaxed">
                                            <span class="font-semibold text-emerald-900 dark:text-emerald-100">Autorización y creación del caso:</span>
                                            @if ($stageAAutorPor)
                                                <strong>{{ $stageAAutorCargo }}</strong> <strong>{{ $stageAAutorPor->name }}</strong>
                                            @else
                                                <span class="text-emerald-900/85 dark:text-emerald-100/75">Sin registro del usuario que autorizó y generó el expediente.</span>
                                            @endif
                                        </p>

                                        {{-- Fila 4: solo asignación titular (acción CASO_ASIGNADO); sin operaciones ni texto de dirección jurídica --}}
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
                                <div class="md:col-span-2 xl:col-span-3 rounded-xl border border-slate-200 bg-slate-50/80 p-5 space-y-3 dark:border-white/15 dark:bg-slate-900/40">
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

                            @if ($case->allowsAgendaThread() && ($case->agendaThread || Gate::allows('postAgendaLawyer', $case) || Gate::allows('postAgendaPlanning', $case)))
                                <div class="md:col-span-2 xl:col-span-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-5 space-y-4 dark:border-emerald-400/25 dark:bg-emerald-950/20"
                                    x-data="window.sjAgendaAttachmentLightbox()"
                                    x-on:keydown.escape.window="(open && closeLightbox()) || (contextOpen && closeImageContextMenu())">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <h4 class="text-xs uppercase tracking-wider text-emerald-800 font-semibold dark:text-emerald-200">Citación · Coordinación con planeación (FO-GJ-03)</h4>
                                            <p class="text-xs text-emerald-900/80 mt-1 dark:text-emerald-100/80">El titular solicita <strong>fechas y detalle</strong> al área de planeación; planeación responde en el mismo hilo y puede <strong>adjuntar imágenes</strong> de la programación. Luego el abogado puede diligenciar la citación, imprimirla y seguir el flujo para notificación firmada y avance a diligencia (etapa C).</p>
                                        </div>
                                        @if ($case->agendaThread?->organizationalArea)
                                            <span class="text-xs font-medium text-emerald-800 dark:text-emerald-200 shrink-0">Área: {{ $case->agendaThread->organizationalArea->name }}</span>
                                        @endif
                                    </div>

                                    @if ($case->agendaThread && $case->agendaThread->messages->isNotEmpty())
                                        <ul class="space-y-3 max-h-80 overflow-y-auto pr-1">
                                            @foreach ($case->agendaThread->messages as $msg)
                                                <li class="rounded-lg border border-emerald-100 bg-white/90 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5">
                                                    <div class="flex flex-wrap justify-between gap-2 text-xs text-gray-500 dark:text-slate-400">
                                                        <span class="font-semibold text-gray-800 dark:text-slate-200">{{ $msg->author?->name ?? '—' }}</span>
                                                        <span>{{ $msg->created_at->format('Y-m-d H:i') }}</span>
                                                    </div>
                                                    <p class="mt-1 text-gray-800 dark:text-slate-200 whitespace-pre-wrap">{{ $msg->body }}</p>
                                                    @foreach ($msg->attachments as $att)
                                                        @if ($att->isImage())
                                                            @php
                                                                $agendaInlineUrl = route('disciplinary.cases.agenda-attachment.inline', [$case, $att]);
                                                                $agendaDownloadUrl = route('disciplinary.cases.agenda-attachment.download', [$case, $att]);
                                                            @endphp
                                                            <div class="mt-2">
                                                                <button type="button"
                                                                    class="group relative shrink-0 overflow-hidden rounded-md bg-slate-100 ring-1 ring-emerald-300/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:bg-slate-800 dark:ring-white/25"
                                                                    title="Clic: ver grande · Clic derecho: descargar"
                                                                    x-on:click="openLightbox(@js($agendaInlineUrl), @js($att->original_name))"
                                                                    x-on:contextmenu.prevent="openImageContextMenu($event, @js($agendaDownloadUrl))">
                                                                    <img src="{{ $agendaInlineUrl }}" alt="" loading="lazy"
                                                                        class="block h-20 w-20 object-cover transition group-hover:brightness-95 dark:group-hover:brightness-110">
                                                                </button>
                                                            </div>
                                                        @else
                                                            <a href="{{ route('disciplinary.cases.agenda-attachment.download', [$case, $att]) }}"
                                                                class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-indigo-700 hover:underline dark:text-cyan-300">
                                                                Descargar: {{ $att->original_name }}
                                                            </a>
                                                        @endif
                                                    @endforeach
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif (Gate::allows('postAgendaLawyer', $case))
                                        <p class="text-xs text-gray-600 dark:text-slate-400">Aún no hay mensajes en este hilo. Inicie la solicitud de fechas a planeación.</p>
                                    @endif

                                    @can('postAgendaLawyer', $case)
                                        <div class="border-t border-emerald-200 pt-4 space-y-3 dark:border-white/10">
                                            <p class="text-xs font-semibold text-gray-700 dark:text-slate-300">Nuevo mensaje (abogado)</p>
                                            @if (! $case->agendaThread)
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Área de planeación</label>
                                                    <select wire:model="agendaOrganizationalAreaId"
                                                        class="w-full max-w-md rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100">
                                                        <option value="">— Seleccione —</option>
                                                        @foreach ($organizationalAreasForAgenda as $ar)
                                                            <option value="{{ $ar->id }}">{{ $ar->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('agendaOrganizationalAreaId')
                                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            @endif
                                            <textarea wire:model="agendaLawyerBody" rows="3"
                                                class="w-full rounded-md border-gray-300 shadow-sm text-sm dark:bg-dash-lift dark:border-white/15 dark:text-slate-100"
                                                placeholder="Solicitud de fechas y detalle para la citación FO-GJ-03 (planeación)…"></textarea>
                                            @error('agendaLawyerBody')
                                                <p class="text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                            <button type="button" wire:click="postAgendaLawyer"
                                                class="inline-flex items-center px-4 py-2 bg-emerald-700 text-white text-sm font-semibold rounded-md hover:bg-emerald-800">
                                                Enviar solicitud a planeación
                                            </button>
                                        </div>
                                    @endcan

                                    @can('postAgendaPlanning', $case)
                                        @if ($case->agendaThread)
                                            <div class="border-t border-emerald-200 pt-4 space-y-3 dark:border-white/10">
                                                <p class="text-xs font-semibold text-gray-700 dark:text-slate-300">Responder (planeación)</p>

                                                <div class="overflow-hidden rounded-lg border border-gray-300 bg-white shadow-sm ring-emerald-500/20 focus-within:ring-2 dark:border-white/15 dark:bg-dash-lift dark:focus-within:ring-emerald-400/30"
                                                    x-data="window.sjDisciplinaryPlanningComposer()">
                                                    @if (filled($agendaPlanningUploads ?? []))
                                                        <div class="flex flex-wrap gap-2 px-3 pt-3 pb-1">
                                                            @foreach ($agendaPlanningUploads as $idx => $planFile)
                                                                @if (! $planFile)
                                                                    @continue
                                                                @endif
                                                                <div wire:key="planning-upload-{{ $idx }}-{{ $planFile->getFilename() }}" class="group relative shrink-0">
                                                                    <img src="{{ $planFile->temporaryUrl() }}" alt=""
                                                                        class="h-14 w-14 rounded-md object-cover ring-1 ring-slate-200 dark:ring-white/15">
                                                                    <button type="button" wire:click="removeAgendaPlanningUploadAt({{ $idx }})"
                                                                        class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-rose-600 text-[10px] font-bold leading-none text-white shadow hover:bg-rose-700"
                                                                        title="Quitar imagen">×</button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    <textarea wire:model="agendaPlanningBody" x-ref="planningBody" rows="4"
                                                        x-on:paste="pastePlanningImages($event)"
                                                        class="block min-h-[7.5rem] w-full resize-y border-0 bg-transparent px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"
                                                        placeholder="Respuesta o propuesta de fechas… Pegue una imagen (Ctrl+V) o use el icono para adjuntar."></textarea>

                                                    <input type="file" x-ref="planningFiles" multiple
                                                        accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp"
                                                        class="sr-only"
                                                        x-on:change="pickPlanningImages($event)">

                                                    <div class="flex items-center justify-between gap-2 px-3 pb-2.5 pt-0.5">
                                                        <p class="min-w-0 flex-1 truncate text-[11px] text-gray-500 dark:text-slate-500">JPEG, PNG, WebP o GIF · máx. 5 MB · hasta 6</p>
                                                        <button type="button" x-on:click.prevent="openPicker()"
                                                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-200/80 bg-white/90 text-slate-600 hover:bg-slate-50 dark:border-white/12 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10"
                                                            title="Adjuntar imagen">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3A1.5 1.5 0 0 0 1.5 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                @error('agendaPlanningBody')
                                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                                @error('agendaPlanningUploads.*')
                                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                                @enderror
                                                <div wire:loading wire:target="agendaPlanningUploads" class="text-xs text-gray-500">Subiendo imágenes…</div>
                                                <button type="button" wire:click="postAgendaPlanning"
                                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                                    Publicar respuesta
                                                </button>
                                            </div>
                                        @endif
                                    @endcan

                                    <div x-show="contextOpen" x-cloak
                                        class="fixed z-[210] w-56 rounded-lg border border-gray-200 bg-white py-1 shadow-xl ring-1 ring-black/5 dark:border-white/15 dark:bg-dash-lift dark:ring-white/10"
                                        x-bind:style="'left: ' + contextX + 'px; top: ' + contextY + 'px'"
                                        role="menu"
                                        x-on:click.outside="closeImageContextMenu()">
                                        <button type="button" role="menuitem"
                                            class="w-full px-3 py-2.5 text-left text-sm text-gray-800 hover:bg-gray-100 dark:text-slate-100 dark:hover:bg-white/10"
                                            x-on:click="downloadFromContextMenu()">
                                            Descargar en este equipo
                                        </button>
                                    </div>

                                    <div x-show="open" x-cloak
                                        class="fixed inset-0 z-[200] flex flex-col bg-black/80 p-3 backdrop-blur-[1px]"
                                        x-transition
                                        role="dialog" aria-modal="true"
                                        x-on:click.self="closeLightbox()">
                                        <div class="flex shrink-0 justify-end pb-2">
                                            <button type="button" x-on:click="closeLightbox()"
                                                class="rounded-md bg-white/10 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/20 hover:bg-white/20">
                                                Cerrar (Esc)
                                            </button>
                                        </div>
                                        <div class="flex min-h-0 flex-1 items-center justify-center overflow-hidden"
                                            x-on:wheel="wheelZoom($event)">
                                            <img x-bind:src="src" x-bind:alt="alt"
                                                x-bind:style="'transform: scale(' + scale + '); transform-origin: center center;'"
                                                class="max-h-[88vh] max-w-[96vw] cursor-default select-none object-contain shadow-2xl ring-1 ring-white/15"
                                                x-on:click.stop
                                                draggable="false">
                                        </div>
                                    </div>
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
                                <div class="md:col-span-2 xl:col-span-3 rounded-xl border border-indigo-100 bg-indigo-50/80 p-4 dark:border-cyan-400/25 dark:bg-cyan-500/10">
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
                        @if (auth()->user()->isDisciplinaryFieldOperator())
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                                Para diligenciar el <strong class="text-slate-800 dark:text-slate-200">informe disciplinario FO-GJ-51</strong>, use la pestaña
                                <strong class="text-slate-800 dark:text-slate-200">Información</strong>. Adjunte aquí las evidencias de notificación cuando el sistema lo permita.</p>
                        @endif
                        @if ($case->documents->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-slate-400">No hay documentos cargados todavía.</p>
                        @else
                            <ul class="divide-y divide-gray-200 dark:divide-white/10">
                                @foreach ($case->documents as $doc)
                                    <li class="py-3 flex items-start justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $doc->original_name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-slate-400">
                                                {{ $doc->document_type->label() }}
                                                @if ($doc->form_code) · <span class="font-mono">{{ $doc->form_code }}</span> @endif
                                                · {{ number_format($doc->size_bytes / 1024, 1) }} KB
                                                · subido por {{ $doc->uploader?->name ?? '—' }}
                                                · {{ $doc->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

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
                        Podrás coordinar fechas con planeación en la pestaña Información.
                    </p>
                    @error('advanceStage')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeAdvanceStageConfirm"
                            class="px-4 py-2 bg-gray-100 text-gray-700 dark:text-slate-300 rounded-md text-sm hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15">
                            Cancelar
                        </button>
                        <button type="button" wire:click="confirmAdvanceStage" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-60">
                            Confirmar
                        </button>
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
                        <button type="button" wire:click="closeArchiveConfirm"
                            class="px-4 py-2 bg-gray-100 text-gray-700 dark:text-slate-300 rounded-md text-sm hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15">
                            Cancelar
                        </button>
                        <button type="button" wire:click="confirmArchive" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-amber-700 text-white text-sm font-semibold rounded-md hover:bg-amber-800 disabled:opacity-60">
                            Confirmar
                        </button>
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
                        <button type="button" wire:click="closeScheduleModal"
                            class="px-4 py-2 bg-gray-100 text-gray-700 dark:text-slate-300 rounded-md text-sm hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                            Guardar fechas
                        </button>
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
                        <button type="button" wire:click="closeFo51PdfPreview"
                            class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                            Cerrar
                        </button>
                        <button type="button"
                            class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-dash-lift dark:ring-1 dark:ring-white/15 dark:hover:bg-dash-lift/90"
                            x-on:click="(() => { const a = document.createElement('a'); a.href = @js($fo51PdfDownloadUrl); a.target = '_blank'; a.rel = 'noopener noreferrer'; document.body.appendChild(a); a.click(); a.remove(); })()">
                            Descargar PDF
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

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
                        <button type="button" wire:click="cancelLawyerAssignment"
                            class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                            Cancelar
                        </button>
                        @if ($lawyerConfirmKind === 'change')
                            <button type="button" wire:click="confirmLawyerAssignment"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                                Confirmar cambio
                            </button>
                        @else
                            <button type="button" wire:click="confirmLawyerAssignment"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                                Confirmar
                            </button>
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
                    <button type="button" wire:click="cancelClaimConfirm"
                        class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white dark:border-white/15 dark:text-white dark:hover:bg-white/10">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmClaimCase" wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                        <span wire:loading.remove wire:target="confirmClaimCase">Sí, gestionar caso</span>
                        <span wire:loading wire:target="confirmClaimCase">Asignando…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
