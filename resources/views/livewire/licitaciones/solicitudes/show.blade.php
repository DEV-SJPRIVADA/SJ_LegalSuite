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
            <a href="{{ route('licitaciones.solicitudes.index') }}" wire:navigate class="text-sm font-semibold text-sj-blue">← Solicitudes</a>
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
                    <p><strong>Fecha y hora de límite de entrega:</strong> {{ $solicitud->aportacionDeadlineLabel() }}</p>
                    <p><strong>Descripción:</strong> {{ $solicitud->descripcion ?: '—' }}</p>
                    @if ($solicitud->licitacion)
                        <p><strong>Licitación:</strong>
                            <a href="{{ route('licitaciones.procesos.show', $solicitud->licitacion) }}" wire:navigate class="text-sj-blue font-semibold">
                                {{ $solicitud->licitacion->numero_proceso }} — {{ $solicitud->licitacion->entidad_contratante }}
                            </a>
                        </p>
                    @endif
                </div>

                @can('manageInvitados', $solicitud)
                    <div class="rounded-xl ring-1 ring-slate-200 p-5 dark:ring-white/10 dark:bg-white/[0.04]">
                        <h2 class="font-semibold mb-1 dark:text-white">Aportantes (correos)</h2>
                        <p class="text-xs text-slate-500 mb-3 dark:text-slate-400">
                            Busque funcionarios del directorio corporativo por nombre o correo, o agregue un correo externo. Recibirán un enlace para subir archivos sin crear cuenta.
                        </p>
                        <form wire:submit="invitar" class="space-y-3 mb-4">
                            <div>
                                <label class="{{ $label }}">Buscar funcionario</label>
                                <div class="relative">
                                    <input
                                        type="search"
                                        wire:model.live.debounce.250ms="aportanteBusqueda"
                                        class="{{ $field }}"
                                        placeholder="Buscar por nombre o correo…"
                                        autocomplete="off"
                                        aria-autocomplete="list"
                                    >
                                    @if (mb_strlen(trim($aportanteBusqueda)) >= 2)
                                        <ul class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg dark:border-white/15 dark:bg-dash-ink">
                                            @forelse ($this->aportanteResultados as $user)
                                                <li>
                                                    <button
                                                        type="button"
                                                        wire:click="agregarAportanteUsuario(@js($user['email']), @js($user['name']))"
                                                        class="flex w-full flex-col px-3 py-2 text-left text-sm hover:bg-sj-orange/10 dark:hover:bg-white/10"
                                                    >
                                                        <span class="font-medium text-slate-900 dark:text-white">{{ $user['name'] }}</span>
                                                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $user['email'] }}</span>
                                                    </button>
                                                </li>
                                            @empty
                                                <li class="px-3 py-2 text-xs text-slate-500">Sin coincidencias en el directorio.</li>
                                            @endforelse
                                        </ul>
                                    @endif
                                </div>
                                @error('aportanteBusqueda')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="{{ $label }}">Correo externo (opcional)</label>
                                <div class="flex flex-wrap gap-2">
                                    <input
                                        type="email"
                                        wire:model="aportanteExterno"
                                        class="{{ $field }} min-w-[16rem] flex-1"
                                        placeholder="correo@externo.com"
                                        autocomplete="off"
                                    >
                                    <button type="button" wire:click="agregarAportanteExterno" class="rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white dark:bg-sj-orange dark:text-sj-blue">
                                        Agregar
                                    </button>
                                </div>
                                @error('aportanteExterno')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <p class="{{ $label }}">Seleccionados para invitar</p>
                                @if ($aportantesSeleccionados === [])
                                    <p class="text-xs text-sj-blue dark:text-sj-orange">Ningún aportante seleccionado.</p>
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($aportantesSeleccionados as $aportante)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-sj-orange/10 px-2.5 py-1 text-xs font-medium text-sj-blue ring-1 ring-sj-orange/35 dark:bg-sj-orange/15 dark:text-sj-orange dark:ring-sj-orange/30">
                                                <span>
                                                    {{ $aportante['name'] ?: $aportante['email'] }}
                                                    @if ($aportante['name'])
                                                        <span class="font-normal text-sj-blue/70 dark:text-sj-orange/80">({{ $aportante['email'] }})</span>
                                                    @endif
                                                </span>
                                                <button type="button" wire:click="quitarAportante('{{ $aportante['email'] }}')" class="rounded-full px-1 text-sj-blue hover:bg-sj-orange/15 hover:text-sj-blue dark:hover:bg-white/10" aria-label="Quitar">×</button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @error('aportantesSeleccionados')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                @error('aportantesSeleccionados.*')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $label }}">Indicaciones de documentación (opcional)</label>
                                <textarea wire:model="mensajeInvitacion" rows="2" class="{{ $field }}" placeholder="Ej.: Certificado de existencia, RUT, experiencia específica…"></textarea>
                            </div>
                            <div>
                                <label class="{{ $label }}">Fecha y hora de límite de entrega <span class="normal-case text-rose-600">*</span></label>
                                <input type="datetime-local" wire:model="aportacionLimiteAt" class="{{ $field }} max-w-xs">
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Fecha y hora máximas para que los aportantes suban los documentos. Si no envían a tiempo, el sistema les enviará recordatorios automáticos.
                                </p>
                                @error('aportacionLimiteAt')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            @can('uploadDocument', $solicitud)
                                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50/80 p-3 dark:border-white/15 dark:bg-white/[0.03]">
                                    <label class="{{ $label }}">Anexos del requerimiento</label>
                                    <p class="mb-2 text-xs text-slate-500 dark:text-slate-400">
                                        Documentos o formatos que la abogada adjunta al requerimiento para que los aportantes los diligencien o consideren.
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="file" wire:model="nuevoAdjunto" class="text-sm">
                                        <button type="button" wire:click="uploadAdjunto" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white dark:bg-sj-orange dark:text-sj-blue">
                                            Subir anexo
                                        </button>
                                    </div>
                                    @error('nuevoAdjunto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    <div wire:loading wire:target="nuevoAdjunto,uploadAdjunto" class="mt-1 text-xs text-slate-500">Subiendo…</div>
                                </div>
                            @endcan
                            <button type="submit" class="px-3 py-2 bg-sj-blue text-white rounded-lg text-sm font-semibold">Invitar y notificar</button>
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
                                                @if ($inv->ultimo_recordatorio_at)
                                                    · Último recordatorio: {{ $inv->ultimo_recordatorio_at->format('d/m/Y H:i') }}
                                                @endif
                                                @if (! $inv->hasUploadedDocuments())
                                                    · <span class="font-semibold text-amber-700 dark:text-amber-300">Pendiente de envío</span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="flex gap-2 text-xs font-semibold">
                                            <a href="{{ $inv->portalUrl() }}" target="_blank" class="text-slate-500 hover:text-sj-orange">Ver enlace</a>
                                            <button type="button" wire:click="reenviarInvitacion({{ $inv->id }})" class="text-sj-blue">Reenviar</button>
                                            <button type="button" wire:click="eliminarInvitado({{ $inv->id }})" wire:confirm="¿Eliminar aportante?" class="text-rose-600">Eliminar</button>
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-slate-50 dark:bg-white/5 px-3 py-2 space-y-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Documentos enviados</p>
                                        @forelse ($inv->adjuntos->sortByDesc('created_at') as $adj)
                                            <div class="flex flex-wrap items-start justify-between gap-2 border-t border-slate-200/70 dark:border-white/10 pt-2 first:border-0 first:pt-0">
                                                <div>
                                                    <a href="{{ route('licitaciones.adjuntos.file', $adj) }}" target="_blank" class="text-sj-blue font-medium">{{ $adj->nombre_archivo }}</a>
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
                                                        <button type="button" wire:click="reenviarResultadoRevision({{ $adj->id }})" class="text-xs font-semibold text-sj-blue">Reenviar correo resultado</button>
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
                            <button type="submit" class="px-3 py-2 bg-sj-blue text-white rounded-lg text-sm">Actualizar</button>
                        </form>
                    </div>
                @endcan
            </div>

            <div class="space-y-6">
                <div class="rounded-xl ring-1 ring-slate-200 p-5 dark:ring-white/10 dark:bg-white/[0.04]">
                    <h2 class="font-semibold mb-3 dark:text-white">Documentos</h2>
                    <ul class="text-sm space-y-4">
                        @forelse ($solicitud->adjuntos->sortByDesc('created_at') as $adj)
                            <li class="border-b border-slate-100 dark:border-white/10 pb-3 last:border-0">
                                <a href="{{ route('licitaciones.adjuntos.file', $adj) }}" target="_blank" class="text-sj-blue font-medium">{{ $adj->nombre_archivo }}</a>
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
                                            <button type="button" wire:click="reenviarResultadoRevision({{ $adj->id }})" class="mt-2 text-xs font-semibold text-sj-blue">Reenviar correo resultado</button>
                                        @endif
                                    @endcan
                                @endif
                            </li>
                        @empty
                            <li class="text-slate-500">Sin documentos.</li>
                        @endforelse
                    </ul>
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
