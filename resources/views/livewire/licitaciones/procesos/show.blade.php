<div>
    @push('module-nav')
        <x-licitaciones.nav />
    @endpush

    <div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>@endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-widest text-slate-500">Licitación</p>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $licitacion->numero_proceso ?: 'Sin número de proceso' }}</h1>
                <p class="text-slate-600 dark:text-slate-300">{{ $licitacion->entidad_contratante }}</p>
            </div>
            <a href="{{ route('licitaciones.procesos.index') }}" wire:navigate class="text-sm font-semibold text-indigo-600 dark:text-cyan-400">← Volver al listado</a>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            @include('livewire.licitaciones.procesos.show-fields-partial')

            <div class="rounded-xl bg-white ring-1 ring-slate-200 p-5 dark:bg-white/[0.04] dark:ring-white/10">
                <h2 class="font-semibold mb-3 dark:text-white">Documentos</h2>
                <ul class="space-y-2 text-sm mb-4">
                    @forelse ($licitacion->adjuntos as $adj)
                        <li><a href="{{ route('licitaciones.adjuntos.file', $adj) }}" target="_blank" class="text-indigo-600 dark:text-cyan-400">{{ $adj->nombre_archivo }}</a></li>
                    @empty
                        <li class="text-slate-500">Sin documentos.</li>
                    @endforelse
                </ul>
                @can('uploadDocument', $licitacion)
                    <form wire:submit="uploadAdjunto" class="flex flex-wrap gap-2 items-end">
                        <input type="file" wire:model="nuevoAdjunto" class="text-sm">
                        <button type="submit" class="px-3 py-1.5 text-sm rounded-lg bg-indigo-600 text-white">Subir</button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="rounded-xl bg-white ring-1 ring-slate-200 dark:bg-white/[0.04] dark:ring-white/10 overflow-hidden">
            <div class="px-5 py-3 border-b dark:border-white/10 font-semibold dark:text-white">Solicitudes asociadas</div>
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-white/5"><tr>
                    <th class="px-4 py-2 text-left">Radicado</th><th class="px-4 py-2 text-left">Nombre</th><th class="px-4 py-2 text-left">Estado</th><th class="px-4 py-2 text-right">Ver</th>
                </tr></thead>
                <tbody class="divide-y dark:divide-white/10">
                    @forelse ($licitacion->solicitudes as $sol)
                        <tr>
                            <td class="px-4 py-2">{{ $sol->numero_radicado }}</td>
                            <td class="px-4 py-2">{{ $sol->nombre }}</td>
                            <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded-full {{ $sol->estado?->badgeClass() }}">{{ $sol->estado?->label() }}</span></td>
                            <td class="px-4 py-2 text-right"><a href="{{ route('licitaciones.solicitudes.show', $sol) }}" wire:navigate class="text-indigo-600 font-semibold">Detalle</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Sin solicitudes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
