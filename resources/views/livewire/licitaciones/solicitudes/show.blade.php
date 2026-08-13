@php
    $field = 'w-full rounded-lg border border-slate-300 bg-white text-sm dark:border-white/15 dark:bg-dash-lift dark:text-slate-100';
    $label = 'block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5 dark:text-slate-400';
@endphp
<div>
    @push('module-nav')
        <x-licitaciones.nav />
    @endpush

    <div class="py-6 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('success'))<div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-200">{{ session('success') }}</div>@endif

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
                    <p><strong>Creada por:</strong> {{ $solicitud->creador?->name }}</p>
                    <p><strong>Notificar a:</strong> {{ $solicitud->email_notificacion ?: ($solicitud->creador?->email ?? '—') }}</p>
                    @can('manageInvitados', $solicitud)
                        <form wire:submit="guardarEmailNotificacion" class="flex flex-wrap gap-2 items-end pt-2">
                            <div class="flex-1 min-w-[220px]">
                                <label class="{{ $label }}">Correo para notificaciones</label>
                                <input type="email" wire:model="emailNotificacionEdit" class="{{ $field }}" placeholder="soporte.admin@sjsp.com.co">
                                @error('emailNotificacionEdit')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="px-3 py-2 bg-slate-700 text-white rounded-lg text-xs font-semibold">Guardar correo</button>
                        </form>
                    @endcan
                    <p><strong>Tipo:</strong> {{ $solicitud->tipo_solicitud?->label() }} · {{ $solicitud->tipo_peticion?->label() }}</p>
                    <p><strong>Vence:</strong> {{ $solicitud->fecha_limite?->format('d/m/Y') }}</p>
                    <p><strong>Descripción:</strong> {{ $solicitud->descripcion ?: '—' }}</p>
                    @if ($solicitud->licitacion)
                        <p><strong>Licitación:</strong>
                            <a href="{{ route('licitaciones.procesos.show', $solicitud->licitacion) }}" wire:navigate class="text-indigo-600 font-semibold">
                                {{ $solicitud->licitacion->numero_proceso }} — {{ $solicitud->licitacion->entidad_contratante }}
                            </a>
                        </p>
                    @endif
                </div>

                @can('manageInvitados', $solicitud)
                    <div class="rounded-xl ring-1 ring-slate-200 p-5 dark:ring-white/10 dark:bg-white/[0.04]">
                        <h2 class="font-semibold mb-1 dark:text-white">Aportantes (correos)</h2>
                        <p class="text-xs text-slate-500 mb-3 dark:text-slate-400">
                            Agregue correos de las personas que deben anexar la documentación. Recibirán un enlace para subir archivos sin crear cuenta.
                        </p>
                        <form wire:submit="invitar" class="space-y-3 mb-4">
                            <div>
                                <label class="{{ $label }}">Correos</label>
                                <textarea wire:model="invitadosTexto" rows="2" class="{{ $field }}" placeholder="correo1@empresa.com, correo2@empresa.com"></textarea>
                                @error('invitadosTexto')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $label }}">Indicaciones de documentación (opcional)</label>
                                <textarea wire:model="mensajeInvitacion" rows="2" class="{{ $field }}" placeholder="Ej.: Certificado de existencia, RUT, experiencia específica…"></textarea>
                            </div>
                            <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold">Invitar y notificar</button>
                        </form>

                        <ul class="divide-y dark:divide-white/10 text-sm">
                            @forelse ($solicitud->invitados as $inv)
                                <li class="py-3 space-y-2">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p class="font-medium dark:text-slate-100">{{ $inv->email }}</p>
                                            <p class="text-xs text-slate-500">
                                                Notificado: {{ $inv->notificado_at?->format('d/m/Y H:i') ?? '—' }}
                                                @if ($inv->ultimo_acceso_at)
                                                    · Acceso: {{ $inv->ultimo_acceso_at->format('d/m/Y H:i') }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="flex gap-2 text-xs font-semibold">
                                            <a href="{{ $inv->portalUrl() }}" target="_blank" class="text-slate-500 hover:text-indigo-600">Ver enlace</a>
                                            <button type="button" wire:click="reenviarInvitacion({{ $inv->id }})" class="text-indigo-600">Reenviar</button>
                                            <button type="button" wire:click="eliminarInvitado({{ $inv->id }})" wire:confirm="¿Eliminar aportante?" class="text-rose-600">Eliminar</button>
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-slate-50 dark:bg-white/5 px-3 py-2 space-y-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Documentos enviados</p>
                                        @forelse ($inv->adjuntos->sortByDesc('created_at') as $adj)
                                            <div class="flex flex-wrap items-start justify-between gap-2 border-t border-slate-200/70 dark:border-white/10 pt-2 first:border-0 first:pt-0">
                                                <div>
                                                    <a href="{{ route('licitaciones.adjuntos.file', $adj) }}" target="_blank" class="text-indigo-600 font-medium">{{ $adj->nombre_archivo }}</a>
                                                    <p class="text-xs text-slate-500">{{ $adj->created_at?->format('d/m/Y H:i') }}</p>
                                                    <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full {{ $adj->revision_estado?->badgeClass() }}">
                                                        {{ $adj->revision_estado?->label() }}
                                                    </span>
                                                    @if ($adj->revision_comentario)
                                                        <p class="text-xs text-rose-700 dark:text-rose-300 mt-1">{{ $adj->revision_comentario }}</p>
                                                    @endif
                                                </div>
                                                @can('reviewDocument', $solicitud)
                                                    @if ($adj->revision_estado === \App\Enums\Licitaciones\DocumentRevisionStatus::Pendiente)
                                                        <div class="flex flex-col gap-1 text-xs font-semibold">
                                                            <button type="button" wire:click="aprobarDocumento({{ $adj->id }})" class="text-emerald-700 dark:text-emerald-300 text-left">Aprobar OK</button>
                                                            <button type="button" wire:click="abrirRechazo({{ $adj->id }})" class="text-rose-600 text-left">Solicitar corrección</button>
                                                        </div>
                                                    @elseif (in_array($adj->revision_estado, [\App\Enums\Licitaciones\DocumentRevisionStatus::Aprobado, \App\Enums\Licitaciones\DocumentRevisionStatus::Rechazado], true))
                                                        <button type="button" wire:click="reenviarResultadoRevision({{ $adj->id }})" class="text-xs font-semibold text-indigo-600">Reenviar correo resultado</button>
                                                    @endif
                                                @endcan
                                            </div>
                                            @if ($revisandoAdjuntoId === $adj->id)
                                                <form wire:submit="rechazarDocumento" class="space-y-2">
                                                    <textarea wire:model="revisionComentario" rows="2" class="{{ $field }}" placeholder="Indique qué debe corregir…"></textarea>
                                                    @error('revisionComentario')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                                    <div class="flex gap-2">
                                                        <button type="submit" class="px-2 py-1 bg-rose-600 text-white rounded text-xs">Enviar corrección</button>
                                                        <button type="button" wire:click="cancelarRechazo" class="text-xs text-slate-500">Cancelar</button>
                                                    </div>
                                                </form>
                                            @endif
                                        @empty
                                            <p class="text-xs text-slate-500">Aún no ha enviado documentos.</p>
                                        @endforelse
                                    </div>
                                </li>
                            @empty
                                <li class="py-3 text-slate-500">Sin aportantes invitados.</li>
                            @endforelse
                        </ul>
                    </div>
                @endcan

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
                    <h2 class="font-semibold mb-3 dark:text-white">Documentos</h2>
                    <ul class="text-sm space-y-4 mb-3">
                        @forelse ($solicitud->adjuntos->sortByDesc('created_at') as $adj)
                            <li class="border-b border-slate-100 dark:border-white/10 pb-3 last:border-0">
                                <a href="{{ route('licitaciones.adjuntos.file', $adj) }}" target="_blank" class="text-indigo-600 font-medium">{{ $adj->nombre_archivo }}</a>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $adj->uploaderLabel() }} · {{ $adj->created_at?->format('d/m/Y H:i') }}
                                </p>
                                @if ($adj->invitado_id)
                                    <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full {{ $adj->revision_estado?->badgeClass() }}">
                                        {{ $adj->revision_estado?->label() }}
                                    </span>
                                    @if ($adj->revision_comentario)
                                        <p class="text-xs text-rose-700 dark:text-rose-300 mt-1">{{ $adj->revision_comentario }}</p>
                                    @endif
                                    @can('reviewDocument', $solicitud)
                                        @if ($adj->revision_estado === \App\Enums\Licitaciones\DocumentRevisionStatus::Pendiente)
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <button type="button" wire:click="aprobarDocumento({{ $adj->id }})" class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Aprobar OK</button>
                                                <button type="button" wire:click="abrirRechazo({{ $adj->id }})" class="text-xs font-semibold text-rose-600">Solicitar corrección</button>
                                            </div>
                                            @if ($revisandoAdjuntoId === $adj->id)
                                                <form wire:submit="rechazarDocumento" class="mt-2 space-y-2">
                                                    <textarea wire:model="revisionComentario" rows="2" class="{{ $field }}" placeholder="Indique qué debe corregir…"></textarea>
                                                    @error('revisionComentario')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                                    <div class="flex gap-2">
                                                        <button type="submit" class="px-2 py-1 bg-rose-600 text-white rounded text-xs">Enviar corrección</button>
                                                        <button type="button" wire:click="cancelarRechazo" class="text-xs text-slate-500">Cancelar</button>
                                                    </div>
                                                </form>
                                            @endif
                                        @elseif (in_array($adj->revision_estado, [\App\Enums\Licitaciones\DocumentRevisionStatus::Aprobado, \App\Enums\Licitaciones\DocumentRevisionStatus::Rechazado], true))
                                            <button type="button" wire:click="reenviarResultadoRevision({{ $adj->id }})" class="mt-2 text-xs font-semibold text-indigo-600">Reenviar correo resultado</button>
                                        @endif
                                    @endcan
                                @endif
                            </li>
                        @empty
                            <li class="text-slate-500">Sin documentos.</li>
                        @endforelse
                    </ul>
                    @can('uploadDocument', $solicitud)
                        <form wire:submit="uploadAdjunto" class="pt-2 border-t dark:border-white/10">
                            <p class="text-xs text-slate-500 mb-1">Adjunto interno (sin revisión de aportante)</p>
                            <input type="file" wire:model="nuevoAdjunto" class="text-sm mb-2">
                            <button type="submit" class="text-sm text-indigo-600 font-semibold">Subir archivo</button>
                        </form>
                    @endcan
                </div>
                <div class="rounded-xl ring-1 ring-slate-200 p-5 dark:ring-white/10 dark:bg-white/[0.04]">
                    <h2 class="font-semibold mb-3 dark:text-white">Historial</h2>
                    <ul class="text-xs space-y-2 text-slate-600 dark:text-slate-400">
                        @foreach ($solicitud->historial as $h)
                            <li>{{ $h->created_at?->format('d/m/Y H:i') }} — {{ $h->usuario?->name ?? 'Aportante externo' }}: {{ $h->accion }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
