<div>
    @push('module-nav')
        <x-disciplinary.nav />
    @endpush

    <div class="bg-white border-b border-slate-200 dark:bg-dash-ink/60 dark:border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <p class="text-xs uppercase tracking-widest text-slate-500 font-semibold dark:text-dash-muted">Disciplinarios · Planeación</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">Coordinaciones abiertas</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Este panel muestra solo coordinaciones activas para programación de citación.</p>
        </div>
    </div>

    <div class="py-6 sm:py-8">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-900 ring-1 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-100 dark:ring-emerald-500/30">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <aside class="lg:col-span-4 bg-white rounded-lg ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10">
                    <div class="px-4 py-3 border-b border-slate-200 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Bandeja de coordinación</h2>
                    </div>
                    <div class="divide-y divide-slate-200 dark:divide-white/10">
                        @forelse ($threads as $thread)
                            @php
                                $case = $thread->case;
                                $workerName = trim((string) ($case?->employee?->first_name.' '.$case?->employee?->last_name));
                                $cityLabel = $case?->municipality?->municipality_name ?: ($case?->city ?? '—');
                            @endphp
                            <button type="button" wire:click="selectThread({{ $thread->id }})"
                                class="w-full px-4 py-3 text-left hover:bg-slate-50 dark:hover:bg-white/[0.04] {{ (int) $selectedThread === (int) $thread->id ? 'bg-indigo-50 dark:bg-indigo-500/10' : '' }}">
                                <p class="text-xs font-mono text-slate-600 dark:text-slate-300">{{ $case?->case_number }}</p>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $workerName !== '' ? $workerName : 'Sin trabajador' }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $cityLabel }} · Abierta {{ optional($thread->coordination_started_at)->diffForHumans() }}</p>
                            </button>
                        @empty
                            <div class="px-4 py-8 text-sm text-slate-500 dark:text-slate-400">
                                No hay coordinaciones abiertas.
                            </div>
                        @endforelse
                    </div>
                </aside>

                <section class="lg:col-span-8 bg-white rounded-lg ring-1 ring-slate-200 p-4 sm:p-5 dark:bg-white/[0.04] dark:ring-white/10">
                    @if ($selectedThreadModel)
                        @php
                            $case = $selectedThreadModel->case;
                            $workerName = trim((string) ($case?->employee?->first_name.' '.$case?->employee?->last_name));
                            $cityLabel = $case?->municipality?->municipality_name ?: ($case?->city ?? '—');
                        @endphp

                        <div class="space-y-4">
                            <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                                <p class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">Datos de coordinación</p>
                                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-slate-700 dark:text-slate-200">
                                    <p><span class="font-semibold">Caso:</span> <span class="font-mono">{{ $case?->case_number }}</span></p>
                                    <p><span class="font-semibold">Estado:</span> Abierta</p>
                                    <p><span class="font-semibold">Trabajador:</span> {{ $workerName !== '' ? $workerName : '—' }}</p>
                                    <p><span class="font-semibold">Ciudad:</span> {{ $cityLabel }}</p>
                                </div>
                            </div>

                            <div class="rounded-md border border-slate-200 px-3 py-3 max-h-80 overflow-y-auto dark:border-white/10">
                                <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 mb-2">Conversación</p>
                                <ul class="space-y-3">
                                    @foreach ($selectedThreadModel->messages as $msg)
                                        <li class="rounded-md border border-slate-200 px-3 py-2 dark:border-white/10">
                                            <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $msg->author?->name }}</span>
                                                <span>{{ $msg->created_at?->format('Y-m-d H:i') }}</span>
                                            </div>
                                            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200">{{ $msg->body }}</p>
                                            @if ($msg->normalizedProposedSlots() !== [])
                                                <ul class="mt-2 space-y-1 text-xs text-indigo-700 dark:text-indigo-300">
                                                    @foreach ($msg->normalizedProposedSlots() as $slot)
                                                        <li>• {{ $slot['date'] ?? '—' }} {{ $slot['time'] ?? '' }}{{ !empty($slot['notes']) ? ' · '.$slot['notes'] : '' }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            @if ($msg->attachments->isNotEmpty())
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach ($msg->attachments as $att)
                                                        <a href="{{ route('disciplinary.coordinations.attachments.download', [$selectedThreadModel, $att]) }}"
                                                            class="text-xs font-semibold text-indigo-700 hover:underline dark:text-cyan-300">
                                                            {{ $att->original_name }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="border-t border-slate-200 pt-4 space-y-3 dark:border-white/10">
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">Responder coordinación</p>
                                <textarea wire:model="agendaPlanningBody" rows="2"
                                    class="w-full rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white"
                                    placeholder="Observaciones para el abogado..."></textarea>
                                @foreach ($planningSlots as $i => $slot)
                                    <div class="grid grid-cols-3 gap-2">
                                        <input type="date" wire:model="planningSlots.{{ $i }}.date" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                        <input type="time" wire:model="planningSlots.{{ $i }}.time" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                        <input type="text" wire:model="planningSlots.{{ $i }}.notes" placeholder="Notas" class="rounded-md border-slate-300 text-sm dark:bg-dash-lift dark:border-white/15 dark:text-white">
                                    </div>
                                @endforeach
                                <button type="button" wire:click="addPlanningSlotRow" class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">+ Otra fecha propuesta</button>
                                <input type="file" wire:model="agendaPlanningUploads" multiple accept="image/*,application/pdf" class="text-xs">
                                @error('agendaPlanningBody')
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <button type="button" wire:click="postPlanningReply" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md">Publicar respuesta</button>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">Seleccione una coordinación abierta para responder.</p>
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>
