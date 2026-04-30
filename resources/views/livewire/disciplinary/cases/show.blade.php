<div>
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <a href="{{ route('disciplinary.cases.index') }}" wire:navigate
                        class="text-xs text-gray-500 hover:text-gray-700">← Volver al listado</a>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mt-1">
                        Caso <span class="font-mono">{{ $case->case_number }}</span>
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $case->personnel?->first_name }} {{ $case->personnel?->last_name }}
                        @if ($case->personnel?->document_number)
                            · CC {{ $case->personnel->document_number }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <x-disciplinary.status-badge :status="$case->current_status" class="text-sm px-3 py-1" />
                    @can('transition', $case)
                        @if (count($allowedTransitions) > 0)
                            <button wire:click="openTransition"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                Gestionar →
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </header>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 ring-1 ring-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Tabs --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="flex border-b border-gray-200 text-sm">
                    @foreach (['overview' => 'Información', 'timeline' => 'Línea de tiempo', 'documents' => 'Documentos', 'audit' => 'Actuaciones'] as $key => $label)
                        <button wire:click="setTab('{{ $key }}')"
                            class="px-5 py-3 font-medium border-b-2 transition
                                {{ $activeTab === $key ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="p-6">
                    @if ($activeTab === 'overview')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Disciplinado</dt>
                                    <dd class="text-gray-900 font-medium">
                                        {{ $case->personnel?->first_name }} {{ $case->personnel?->last_name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Documento</dt>
                                    <dd class="text-gray-900">{{ $case->personnel?->document_type }} {{ $case->personnel?->document_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Cargo / Sede</dt>
                                    <dd class="text-gray-900">{{ $case->personnel?->position ?? '—' }} · {{ $case->sede ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Ciudad</dt>
                                    <dd class="text-gray-900">{{ $case->city ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Reportado por</dt>
                                    <dd class="text-gray-900">{{ $case->reporter?->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Abogado asignado</dt>
                                    <dd class="text-gray-900">{{ $case->assignedLawyer?->name ?? '— Sin asignar —' }}</dd>
                                </div>
                            </dl>
                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Apertura</dt>
                                    <dd class="text-gray-900">{{ $case->opened_at?->format('Y-m-d') }}</dd>
                                </div>
                                @if ($case->closed_at)
                                    <div>
                                        <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Cierre</dt>
                                        <dd class="text-gray-900">{{ $case->closed_at?->format('Y-m-d') }}</dd>
                                    </div>
                                @endif
                                @if ($case->decision)
                                    <div>
                                        <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Decisión</dt>
                                        <dd class="text-gray-900 font-semibold">{{ $case->decision->label() }}</dd>
                                    </div>
                                    @if ($case->decision_notes)
                                        <div>
                                            <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Notas de la decisión</dt>
                                            <dd class="text-gray-700 whitespace-pre-line">{{ $case->decision_notes }}</dd>
                                        </div>
                                    @endif
                                @endif
                                <div>
                                    <dt class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Resumen</dt>
                                    <dd class="text-gray-700 whitespace-pre-line">{{ $case->summary ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="mt-6">
                            <h4 class="text-xs uppercase tracking-wider text-gray-500 font-semibold mb-2">Faltas imputadas</h4>
                            <div class="flex flex-wrap gap-2">
                                @forelse ($case->faults as $f)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 ring-1 ring-rose-200">
                                        {{ $f->code }} · {{ $f->name }}
                                        @if ($f->pivot->extra_info)
                                            <span class="text-rose-500 ml-1">({{ $f->pivot->extra_info }})</span>
                                        @endif
                                    </span>
                                @empty
                                    <span class="text-sm text-gray-500">Sin faltas registradas todavía.</span>
                                @endforelse
                            </div>
                        </div>

                    @elseif ($activeTab === 'timeline')
                        <ol class="relative border-s border-gray-200 ms-4 space-y-6">
                            @forelse ($case->stages as $stage)
                                <li class="ms-6">
                                    <span class="absolute -start-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 ring-4 ring-white">
                                        <span class="text-[10px] text-white font-bold">{{ $stage->sequence }}</span>
                                    </span>
                                    <div class="bg-gray-50 rounded-md p-4 ring-1 ring-gray-200">
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-semibold text-gray-900">
                                                {{ $stage->stage_type->label() }}
                                                @if ($stage->form_code)
                                                    <span class="text-xs text-gray-500 font-mono">({{ $stage->form_code }})</span>
                                                @endif
                                            </h4>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ring-1 ring-inset
                                                {{ $stage->status->value === 'completada' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : ($stage->status->value === 'en_curso' ? 'bg-blue-50 text-blue-700 ring-blue-200' : 'bg-gray-50 text-gray-700 ring-gray-200') }}">
                                                {{ $stage->status->label() }}
                                            </span>
                                        </div>
                                        <dl class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-xs text-gray-600">
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
                                            <p class="mt-2 text-sm text-gray-700 whitespace-pre-line">{{ $stage->notes }}</p>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="ms-6 text-sm text-gray-500">Sin etapas registradas todavía.</li>
                            @endforelse
                        </ol>

                    @elseif ($activeTab === 'documents')
                        @if ($case->documents->isEmpty())
                            <p class="text-sm text-gray-500">No hay documentos cargados todavía.</p>
                        @else
                            <ul class="divide-y divide-gray-200">
                                @foreach ($case->documents as $doc)
                                    <li class="py-3 flex items-start justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $doc->original_name }}</p>
                                            <p class="text-xs text-gray-500">
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
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold">Fecha</th>
                                        <th class="px-3 py-2 text-left font-semibold">Acción</th>
                                        <th class="px-3 py-2 text-left font-semibold">De → A</th>
                                        <th class="px-3 py-2 text-left font-semibold">Usuario</th>
                                        <th class="px-3 py-2 text-left font-semibold">Notas</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse ($case->actions as $a)
                                        <tr>
                                            <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                                                {{ $a->performed_at->format('Y-m-d H:i') }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <code class="text-xs bg-gray-100 rounded px-1.5 py-0.5">{{ $a->action_type->value }}</code>
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-700">
                                                @if ($a->from_status)
                                                    {{ $a->from_status->label() }}
                                                @endif
                                                @if ($a->from_status && $a->to_status) → @endif
                                                @if ($a->to_status)
                                                    <span class="font-semibold">{{ $a->to_status->label() }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-gray-700">{{ $a->user?->name ?? '—' }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $a->description }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-3 py-6 text-center text-gray-500">Sin actuaciones registradas.</td>
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

    {{-- Modal de transición --}}
    @if ($showTransition)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            x-data x-on:keydown.escape.window="$wire.closeTransition()">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full" x-on:click.outside="$wire.closeTransition()">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Mover el caso</h3>
                    <button wire:click="closeTransition" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <form wire:submit="saveTransition" class="p-6 space-y-4">
                    <div class="text-sm text-gray-600">
                        Estado actual: <x-disciplinary.status-badge :status="$case->current_status" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nuevo estado</label>
                        <select wire:model="newStatus"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">— Seleccionar —</option>
                            @foreach ($allowedTransitions as $t)
                                <option value="{{ $t->value }}">{{ $t->label() }}</option>
                            @endforeach
                        </select>
                        @error('newStatus') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Programado para (opcional)</label>
                            <input type="datetime-local" wire:model="scheduledAt"
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Plazo (opcional)</label>
                            <input type="date" wire:model="deadlineAt"
                                class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nota</label>
                        <textarea wire:model="note" rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm text-sm"
                            placeholder="Detalles, observaciones, motivo..."></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeTransition"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                            Aplicar transición
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
