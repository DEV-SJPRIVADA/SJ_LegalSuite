<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Disciplinarios · Listado</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Procesos disciplinarios</h1>
            </div>
            <a href="{{ route('disciplinary.dashboard') }}" wire:navigate
               class="text-sm text-slate-600 hover:text-slate-900 dark:text-dash-muted dark:hover:text-white">← Dashboard</a>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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
            <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 p-4 dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3">
                    <div class="lg:col-span-2 xl:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Buscador</label>
                        <input type="search" wire:model.live.debounce.350ms="search"
                            placeholder="N° de caso, nombre, documento..."
                            class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Estado</label>
                        <select wire:model.live="status" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Todos —</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Abogado</label>
                        <select wire:model.live="lawyerId" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Todos —</option>
                            @foreach ($this->lawyers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Ciudad</label>
                        <select wire:model.live="city" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Todas —</option>
                            @foreach ($this->cities as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Falta</label>
                        <select wire:model.live="faultId" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Todas —</option>
                            @foreach ($this->faults as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Desde</label>
                        <input type="date" wire:model.live="from" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Hasta</label>
                        <input type="date" wire:model.live="to" class="w-full rounded-md border-slate-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div class="flex items-end">
                        <button type="button" wire:click="clearFilters"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-slate-100 text-slate-700 rounded-md text-sm hover:bg-slate-200">
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="bg-white shadow-sm rounded-lg ring-1 ring-slate-200 overflow-hidden dark:bg-white/[0.04] dark:ring-white/10 dark:shadow-dash-card">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-white/10">
                        <thead class="bg-slate-50 dark:bg-white/[0.06]">
                            <tr class="text-xs uppercase tracking-wider text-slate-500 dark:text-dash-muted">
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">N° Caso</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Disciplinado</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Ciudad</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Estado</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700 dark:text-slate-200">Abogado</th>
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
                                            {{ $case->personnel?->first_name }} {{ $case->personnel?->last_name }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">CC {{ $case->personnel?->document_number }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $case->city ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <x-disciplinary.status-badge :status="$case->current_status" />
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                                        {{ $case->assignedLawyer?->name ?? '— Sin asignar —' }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-slate-700 dark:text-slate-300">{{ $case->faults_count }}</td>
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $case->opened_at?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('disciplinary.cases.show', $case) }}" wire:navigate
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                                            Gestionar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
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
</div>
