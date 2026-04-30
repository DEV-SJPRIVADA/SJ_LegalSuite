<div>
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Procesos disciplinarios</h2>
            <a href="{{ route('disciplinary.dashboard') }}" wire:navigate
                class="text-sm text-gray-600 hover:text-gray-900">← Dashboard</a>
        </div>
    </header>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Tarjetas de vistas rápidas --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <button wire:click="setBucket('pendiente')"
                    class="text-left bg-white shadow-sm rounded-lg p-5 ring-1 transition
                        {{ $bucket === 'pendiente' ? 'ring-amber-400' : 'ring-gray-100 hover:ring-amber-200' }}">
                    <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Pendientes</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600">{{ number_format($this->quickStats['pendientes']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Borrador / Informe inicial</p>
                </button>
                <button wire:click="setBucket('en_proceso')"
                    class="text-left bg-white shadow-sm rounded-lg p-5 ring-1 transition
                        {{ $bucket === 'en_proceso' ? 'ring-blue-400' : 'ring-gray-100 hover:ring-blue-200' }}">
                    <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">En proceso</p>
                    <p class="mt-2 text-3xl font-bold text-blue-600">{{ number_format($this->quickStats['en_proceso']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Citación / Diligencia / Decisión</p>
                </button>
                <button wire:click="setBucket('finalizado')"
                    class="text-left bg-white shadow-sm rounded-lg p-5 ring-1 transition
                        {{ $bucket === 'finalizado' ? 'ring-emerald-400' : 'ring-gray-100 hover:ring-emerald-200' }}">
                    <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Finalizados</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-600">{{ number_format($this->quickStats['finalizados']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Cerrados / Archivados</p>
                </button>
            </div>

            {{-- Filtros --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Buscador</label>
                        <input type="search" wire:model.live.debounce.350ms="search"
                            placeholder="Número de caso, nombre, documento, resumen..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Estado</label>
                        <select wire:model.live="status"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Todos —</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Abogado</label>
                        <select wire:model.live="lawyerId"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Todos —</option>
                            @foreach ($this->lawyers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Ciudad</label>
                        <select wire:model.live="city"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Todas —</option>
                            @foreach ($this->cities as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Falta</label>
                        <select wire:model.live="faultId"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Todas —</option>
                            @foreach ($this->faults as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Desde</label>
                        <input type="date" wire:model.live="from"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Hasta</label>
                        <input type="date" wire:model.live="to"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>
                    <div class="flex items-end">
                        <button type="button" wire:click="clearFilters"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">
                            Limpiar filtros
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr class="text-xs uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 text-left font-semibold">N° Caso</th>
                                <th class="px-4 py-3 text-left font-semibold">Disciplinado</th>
                                <th class="px-4 py-3 text-left font-semibold">Ciudad</th>
                                <th class="px-4 py-3 text-left font-semibold">Estado</th>
                                <th class="px-4 py-3 text-left font-semibold">Abogado</th>
                                <th class="px-4 py-3 text-center font-semibold">Faltas</th>
                                <th class="px-4 py-3 text-left font-semibold">Apertura</th>
                                <th class="px-4 py-3 text-right font-semibold">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm">
                            @forelse ($cases as $case)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $case->case_number }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $case->personnel?->first_name }} {{ $case->personnel?->last_name }}
                                        </div>
                                        <div class="text-xs text-gray-500">CC {{ $case->personnel?->document_number }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $case->city ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <x-disciplinary.status-badge :status="$case->current_status" />
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $case->assignedLawyer?->name ?? '— Sin asignar —' }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ $case->faults_count }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $case->opened_at?->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('disciplinary.cases.show', $case) }}" wire:navigate
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                                            Gestionar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                                        No se encontraron casos con los filtros actuales.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-200">
                    {{ $cases->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
