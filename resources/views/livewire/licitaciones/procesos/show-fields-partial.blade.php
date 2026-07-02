            <div class="rounded-xl bg-white ring-1 ring-slate-200 p-5 dark:bg-white/[0.04] dark:ring-white/10 space-y-2 text-sm">
                <p><span class="font-semibold">Objeto:</span> {{ $licitacion->objeto ?: '—' }}</p>
                <p><span class="font-semibold">Modalidad:</span> {{ $licitacion->modalidad_contratacion ?: '—' }}</p>
                <p><span class="font-semibold">Cuantía:</span> {{ $licitacion->cuantia ?: '—' }}</p>
                <p><span class="font-semibold">Plazo ejecución:</span> {{ $licitacion->plazo_ejecucion ?: '—' }}</p>
                <p><span class="font-semibold">Lugar ejecución:</span> {{ $licitacion->lugar_ejecucion ?: '—' }}</p>
                <p><span class="font-semibold">Medio presentación:</span> {{ $licitacion->medio_presentacion ?: '—' }}</p>
                <p><span class="font-semibold">Participación:</span> {{ $licitacion->participacion_tipo ?: '—' }}</p>
                @if ($licitacion->integrantes_participacion)
                    <p><span class="font-semibold">Integrantes:</span> {{ $licitacion->integrantes_participacion }}</p>
                @endif
                <p><span class="font-semibold">Cierre oferta:</span> {{ $licitacion->fecha_cierre_oferta?->format('d/m/Y') ?? '—' }} {{ $licitacion->hora_cierre_oferta }}</p>
                <p><span class="font-semibold">Estado:</span> {{ $licitacion->estado_proceso ?: '—' }}</p>
                <p><span class="font-semibold">Cumplimos:</span> {{ $licitacion->cumplimos ?: '—' }}</p>
                @if ($licitacion->motivo_no_cumplir)
                    <p><span class="font-semibold">Motivo no cumplir:</span> {{ $licitacion->motivo_no_cumplir }}</p>
                @endif
                <p><span class="font-semibold">Adjudicado:</span> {{ $licitacion->adjudicado ?: ($licitacion->resultado ?: '—') }}</p>
                @if ($licitacion->motivo_perdida)
                    <p><span class="font-semibold">Motivo pérdida:</span> {{ $licitacion->motivo_perdida }}</p>
                @endif
                <p><span class="font-semibold">Responsable:</span> {{ $licitacion->responsablePrincipal?->name }}</p>
                @if ($licitacion->enlace_proceso)
                    <p><a href="{{ $licitacion->enlace_proceso }}" target="_blank" rel="noopener" class="text-indigo-600 underline">Abrir enlace del proceso</a></p>
                @endif
            </div>
