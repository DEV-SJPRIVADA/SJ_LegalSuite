@php $field = 'w-full rounded-lg border border-slate-300 bg-white text-sm dark:border-white/15 dark:bg-dash-lift dark:text-slate-100'; @endphp
<div>
    @push('module-nav')
        <x-licitaciones.nav />
    @endpush

    <div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif

        <div class="flex justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-xl font-bold dark:text-white">{{ $solicitud->numero_radicado }}</h1>
                <p class="text-slate-600 dark:text-slate-300">{{ $solicitud->nombre }}</p>
            </div>
            <a href="{{ route('licitaciones.solicitudes.index') }}" wire:navigate class="text-sm font-semibold text-indigo-600">← Solicitudes</a>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-xl ring-1 ring-slate-200 p-5 dark:ring-white/10 dark:bg-white/[0.04] text-sm space-y-1">
                    <p><strong>Estado:</strong> <span class="text-xs px-2 py-0.5 rounded-full {{ $solicitud->estado?->badgeClass() }}">{{ $solicitud->estado?->label() }}</span></p>
                    <p><strong>Área:</strong> {{ $solicitud->area_responsable }}</p>
                    <p><strong>Responsable:</strong> {{ $solicitud->usuarioResponsable?->name }}</p>
                    <p><strong>Tipo:</strong> {{ $solicitud->tipo_solicitud?->label() }} · {{ $solicitud->tipo_peticion?->label() }}</p>
                    <p><strong>Vence:</strong> {{ $solicitud->fecha_limite?->format('d/m/Y') }}</p>
                    <p><strong>Descripción:</strong> {{ $solicitud->descripcion ?: '—' }}</p>
                </div>

                @can('update', $solicitud)
                    <div class="rounded-xl ring-1 ring-slate-200 p-5 dark:ring-white/10 dark:bg-white/[0.04]">
                        <h2 class="font-semibold mb-3 dark:text-white">Cambiar estado</h2>
                        <form wire:submit="cambiarEstado" class="flex flex-wrap gap-2 items-end">
                            <select wire:model="nuevoEstado" class="{{ $field }} max-w-xs">
                                @foreach ($estados as $e)<option value="{{ $e->value }}">{{ $e->label() }}</option>@endforeach
                            </select>
                            <input wire:model="comentarioEstado" placeholder="Comentario (opcional)" class="{{ $field }} flex-1 min-w-[200px]">
                            <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm">Actualizar</button>
                        </form>
                    </div>
                @endcan

                <div class="rounded-xl ring-1 ring-slate-200 p-5 dark:ring-white/10 dark:bg-white/[0.04]">
                    <h2 class="font-semibold mb-3 dark:text-white">Comentarios</h2>
                    @foreach ($solicitud->comentarios as $c)
                        <div class="mb-3 text-sm border-b pb-2 dark:border-white/10">
                            <p class="font-semibold">{{ $c->usuario?->name }} <span class="text-slate-500 font-normal">{{ $c->created_at?->format('d/m/Y H:i') }}</span></p>
                            <p>{{ $c->comentario }}</p>
                        </div>
                    @endforeach
                    @can('comment', $solicitud)
                        <form wire:submit="guardarComentario" class="flex gap-2">
                            <input wire:model="nuevoComentario" class="{{ $field }}" placeholder="Escriba un comentario…">
                            <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm shrink-0">Enviar</button>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl ring-1 ring-slate-200 p-5 dark:ring-white/10 dark:bg-white/[0.04]">
                    <h2 class="font-semibold mb-3 dark:text-white">Adjuntos</h2>
                    <ul class="text-sm space-y-2 mb-3">
                        @foreach ($solicitud->adjuntos as $adj)
                            <li><a href="{{ route('licitaciones.adjuntos.file', $adj) }}" target="_blank" class="text-indigo-600">{{ $adj->nombre_archivo }}</a></li>
                        @endforeach
                    </ul>
                    @can('uploadDocument', $solicitud)
                        <form wire:submit="uploadAdjunto"><input type="file" wire:model="nuevoAdjunto" class="text-sm mb-2"><button type="submit" class="text-sm text-indigo-600 font-semibold">Subir archivo</button></form>
                    @endcan
                </div>
                <div class="rounded-xl ring-1 ring-slate-200 p-5 dark:ring-white/10 dark:bg-white/[0.04]">
                    <h2 class="font-semibold mb-3 dark:text-white">Historial</h2>
                    <ul class="text-xs space-y-2 text-slate-600 dark:text-slate-400">
                        @foreach ($solicitud->historial as $h)
                            <li>{{ $h->created_at?->format('d/m/Y H:i') }} — {{ $h->usuario?->name }}: {{ $h->accion }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
