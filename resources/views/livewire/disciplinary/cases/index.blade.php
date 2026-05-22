<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                @if (auth()->user()->isDisciplinaryProgramador())
                    <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Planeación · Solicitudes</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Citaciones y agendas asignadas</h1>
                @elseif (auth()->user()->isDisciplinaryFieldOperator())
                    <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Disciplinarios · Campo</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Notificaciones asignadas</h1>
                @else
                    <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Disciplinarios · Listado</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Procesos disciplinarios</h1>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @can('generateFo51Inform', \App\Models\Disciplinary\DisciplinaryCase::class)
                    <button type="button" wire:click="openFo51Modal(false)"
                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm dark:hover:bg-indigo-500">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Nuevo informe disciplinario (FO-GJ-51)
                    </button>
                    <button type="button" wire:click="openFo51Modal(true)"
                        class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 shadow-sm dark:border-white/15 dark:bg-white/10 dark:text-white dark:hover:bg-white/15">
                        Cargar informe en PDF
                    </button>
                @endcan
                @unless(auth()->user()->isMinimalDisciplinaryPortalUser())
                    @can('viewDashboard', \App\Models\Disciplinary\DisciplinaryCase::class)
                        <a href="{{ route('disciplinary.dashboard') }}" wire:navigate
                           class="text-sm text-slate-600 hover:text-slate-900 dark:text-dash-muted dark:hover:text-white">← Dashboard</a>
                    @else
                        <a href="{{ route('dashboard') }}" wire:navigate
                           class="text-sm text-slate-600 hover:text-slate-900 dark:text-dash-muted dark:hover:text-white">← Inicio</a>
                    @endcan
                @endunless
            </div>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-100 dark:ring-emerald-500/30">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Tarjetas vistas rápidas --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <button wire:click="setBucket('pendiente')"
                    class="text-left bg-white shadow-sm rounded-lg p-5 ring-1 transition dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-none
                        {{ $bucket === 'pendiente' ? 'ring-amber-400 dark:ring-amber-400/70' : 'ring-slate-200 hover:ring-amber-200 dark:ring-white/10 dark:hover:ring-amber-400/40' }}">
                    <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold dark:text-dash-muted">Pendientes</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600">{{ number_format($this->quickStats['pendientes']) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Borrador / Informe inicial</p>
                </button>
                <button wire:click="setBucket('en_proceso')"
                    class="text-left bg-white shadow-sm rounded-lg p-5 ring-1 transition dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-none
                        {{ $bucket === 'en_proceso' ? 'ring-blue-400 dark:ring-sky-400/70' : 'ring-slate-200 hover:ring-blue-200 dark:ring-white/10 dark:hover:ring-sky-400/40' }}">
                    <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold dark:text-dash-muted">En proceso</p>
                    <p class="mt-2 text-3xl font-bold text-blue-600">{{ number_format($this->quickStats['en_proceso']) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Citación / Diligencia / Decisión</p>
                </button>
                <button wire:click="setBucket('finalizado')"
                    class="text-left bg-white shadow-sm rounded-lg p-5 ring-1 transition dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-none
                        {{ $bucket === 'finalizado' ? 'ring-emerald-400 dark:ring-emerald-400/70' : 'ring-slate-200 hover:ring-emerald-200 dark:ring-white/10 dark:hover:ring-emerald-400/40' }}">
                    <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold dark:text-dash-muted">Finalizados</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-600">{{ number_format($this->quickStats['finalizados']) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cerrados / Archivados</p>
                </button>
            </div>

            {{-- Filtros --}}
            @unless (auth()->user()->isMinimalDisciplinaryPortalUser())
                <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 p-4 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3">
                    <div class="lg:col-span-2 xl:col-span-2">
                        <label for="dcf-case-search" class="block text-xs font-semibold text-slate-600 mb-1 dark:text-slate-400">Buscador</label>
                        <input id="dcf-case-search" name="dcf_case_search" type="search" wire:model.live.debounce.350ms="search"
                            placeholder="N° de caso, nombre, documento..."
                            autocomplete="off"
                            class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                    </div>
                    <div>
                        <label for="dcf-case-status" class="block text-xs font-semibold text-slate-600 mb-1 dark:text-slate-400">Estado</label>
                        <select id="dcf-case-status" name="dcf_case_status" wire:model.live="status" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                            <option value="">— Todos —</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="dcf-case-lawyer" class="block text-xs font-semibold text-slate-600 mb-1 dark:text-slate-400">Abogado</label>
                        <select id="dcf-case-lawyer" name="dcf_case_lawyer" wire:model.live="lawyerId" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                            <option value="">— Todos —</option>
                            @foreach ($this->lawyers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="dcf-case-city" class="block text-xs font-semibold text-slate-600 mb-1 dark:text-slate-400">Municipio / ciudad</label>
                        <select id="dcf-case-city" name="dcf_case_city" wire:model.live="city" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                            <option value="">— Todas —</option>
                            @foreach ($this->cities as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="dcf-case-fault" class="block text-xs font-semibold text-slate-600 mb-1 dark:text-slate-400">Falta</label>
                        <select id="dcf-case-fault" name="dcf_case_fault" wire:model.live="faultId" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                            <option value="">— Todas —</option>
                            @foreach ($this->faults as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="dcf-case-from" class="block text-xs font-semibold text-slate-600 mb-1 dark:text-slate-400">Desde</label>
                        <input id="dcf-case-from" name="dcf_case_from" type="date" wire:model.live="from" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                    </div>
                    <div>
                        <label for="dcf-case-to" class="block text-xs font-semibold text-slate-600 mb-1 dark:text-slate-400">Hasta</label>
                        <input id="dcf-case-to" name="dcf_case_to" type="date" wire:model.live="to" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                    </div>
                    <div class="flex items-end">
                        <button type="button" wire:click="clearFilters"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-slate-100 text-slate-700 rounded-md text-sm hover:bg-slate-200">
                            Limpiar
                        </button>
                    </div>
                </div>
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 p-4 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card">
                    <label for="dcf-case-search-min" class="block text-xs font-semibold text-slate-600 mb-1 dark:text-slate-400">Buscador</label>
                    <input id="dcf-case-search-min" name="dcf_case_search_min" type="search" wire:model.live.debounce.350ms="search"
                        placeholder="N° de caso, nombre, documento..."
                        autocomplete="off"
                        class="w-full max-w-md rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm dark:border-white/15 dark:bg-dash-lift dark:text-white">
                </div>
            @endunless
            <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 overflow-hidden dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                        <thead class="bg-slate-50 dark:bg-white/[0.06]">
                            <tr class="text-xs uppercase tracking-wider text-slate-500 dark:text-dash-muted">
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">N° Caso</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Disciplinado</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Ciudad</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Estado</th>
                                @unless (auth()->user()->isMinimalDisciplinaryPortalUser())
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Abogado</th>
                                @endunless
                                <th class="px-4 py-3 text-center font-semibold text-slate-700 dark:text-slate-200">Faltas</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Apertura</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-700 dark:text-slate-200">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200 text-sm dark:bg-transparent dark:divide-white/10">
                            @forelse ($cases as $case)
                                <tr class="hover:bg-slate-50 dark:hover:bg-white/[0.04]">
                                    <td class="px-4 py-3 font-mono text-xs text-slate-700 dark:text-slate-300">{{ $case->case_number }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-900 dark:text-slate-100">
                                            {{ $case->employee?->first_name }} {{ $case->employee?->last_name }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">CC {{ $case->employee?->document_number }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $case->city ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <x-disciplinary.status-badge :status="$case->current_status" />
                                    </td>
                                    @unless (auth()->user()->isMinimalDisciplinaryPortalUser())
                                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                                            {{ $case->assignedLawyer?->name ?? '— Sin asignar —' }}
                                        </td>
                                    @endunless
                                    <td class="px-4 py-3 text-center text-slate-700 dark:text-slate-300">{{ $case->faults_count }}</td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $case->opened_at?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('disciplinary.cases.show', $case) }}" wire:navigate
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                                            @if (auth()->user()->isDisciplinaryProgramador())
                                                Programar
                                            @else
                                                Gestionar
                                            @endif
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->isMinimalDisciplinaryPortalUser() ? 7 : 8 }}" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                                        No se encontraron casos con los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200 dark:border-white/10">
                    {{ $cases->links() }}
                </div>
            </div>
        </div>
    </div>

    @if ($showFo51Modal)
        @include('disciplinary.forms.partials.fo-gj-51-informe-modal-shell', [
            'prefillWorkerName' => $fo51PrefillName,
            'prefillWorkerDocument' => $fo51PrefillDocument,
            'openPdfUploadModal' => $fo51OpenPdfFirst,
        ])
    @endif
</div>
