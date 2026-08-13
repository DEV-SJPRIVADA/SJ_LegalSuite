<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Aportar documentación · {{ config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Figtree, ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: linear-gradient(135deg,#f8fafc,#e2e8f0); color: #0f172a; min-height: 100vh; }
        .wrap { max-width: 48rem; margin: 0 auto; padding: 2.5rem 1rem; }
        .brand { text-align: center; margin-bottom: 1.5rem; }
        .brand img { height: 4rem; width: auto; }
        .brand .name { margin: .5rem 0 0; font-weight: 600; font-size: .9rem; color: #334155; }
        .brand .sub { margin: .15rem 0 0; font-size: .7rem; letter-spacing: .12em; text-transform: uppercase; color: #64748b; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 25px rgba(15,23,42,.06); margin-bottom: 1.25rem; }
        h1 { font-size: 1.25rem; margin: 0 0 .75rem; }
        h2 { font-size: 1rem; margin: 0 0 .75rem; }
        p { margin: .35rem 0; font-size: .9rem; color: #475569; line-height: 1.45; }
        .hint { background: #eef2ff; color: #312e81; border-radius: .6rem; padding: .65rem .8rem; font-size: .875rem; }
        .muted { font-size: .75rem; color: #64748b; }
        .err { color: #dc2626; font-size: .8rem; margin-top: .35rem; }
        .ok { color: #047857; font-size: .8rem; font-weight: 600; }
        label { display: block; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #64748b; margin-bottom: .4rem; }
        input[type=file] { width: 100%; border: 1px solid #cbd5e1; border-radius: .6rem; padding: .55rem .7rem; background: #fff; font-size: .875rem; }
        .btn { display: inline-block; border: 0; border-radius: .6rem; padding: .65rem 1rem; font-size: .875rem; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:disabled { opacity: .55; cursor: wait; }
        .btn-dark { background: #1e293b; color: #fff; }
        .btn-ghost { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .actions { display: flex; flex-wrap: wrap; gap: .6rem; justify-content: center; margin-top: 1rem; }
        .center { text-align: center; }
        .check { width: 3.5rem; height: 3.5rem; border-radius: 999px; background: #d1fae5; color: #047857; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; margin: 0 auto 1rem; }
        .list { list-style: none; padding: 0; margin: 0; }
        .list li { padding: .75rem 0; border-top: 1px solid #f1f5f9; }
        .list li:first-child { border-top: 0; }
        .badge { display: inline-block; margin-top: .3rem; font-size: .7rem; padding: .15rem .5rem; border-radius: 999px; }
        .badge-pendiente { background: #fef3c7; color: #92400e; }
        .badge-aprobado { background: #d1fae5; color: #065f46; }
        .badge-rechazado { background: #ffe4e6; color: #9f1239; }
        .badge-reemplazado { background: #f1f5f9; color: #475569; }
        .warn { background: #fffbeb; color: #92400e; border-radius: .6rem; padding: .65rem .8rem; font-size: .875rem; }
        a.link { color: #4f46e5; font-weight: 600; font-size: .75rem; }
    </style>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
</head>
<body>
<div class="wrap">
    <div class="brand">
        <a href="/"><x-application-logo class="h-16 w-auto mx-auto" style="height:4rem;width:auto;margin:0 auto;display:block;" /></a>
        <p class="name">SJ LegalSuite</p>
        <p class="sub">Aporte de documentación · Licitaciones</p>
    </div>

    @if ($enviado)
        <div class="card center">
            <div class="check">✓</div>
            <h1>¡Documento enviado!</h1>
            <p>El archivo <strong>{{ $archivoEnviado }}</strong> fue recibido correctamente.</p>
            <p>Se avisará a quien solicitó la documentación para que lo revise.</p>
            <p class="muted">Ya puede cerrar esta pestaña. Si el navegador no la cierra solo, ciérrela manualmente.</p>
            <div class="actions">
                <button type="button" class="btn btn-dark" onclick="try{window.close()}catch(e){}">Cerrar ventana</button>
                <a class="btn btn-ghost" href="{{ route('licitaciones.aportacion', $invitado->token) }}">Enviar otro documento</a>
            </div>
        </div>
        <script>setTimeout(function(){ try{window.close()}catch(e){} }, 6000);</script>
    @else
        <div class="card">
            <h1>Documentación solicitada</h1>
            <p>
                Hola{{ $invitado->nombre ? ' '.$invitado->nombre : '' }},
                se le pide anexar documentos para la solicitud
                <strong>{{ $solicitud->numero_radicado }}</strong> — {{ $solicitud->nombre }}.
            </p>
            @if ($solicitud->licitacion)
                <p><strong>Proceso:</strong> {{ $solicitud->licitacion->numero_proceso }}
                    @if ($solicitud->licitacion->entidad_contratante)
                        — {{ $solicitud->licitacion->entidad_contratante }}
                    @endif
                </p>
            @endif
            @if ($solicitud->descripcion)
                <p><strong>Detalle:</strong> {{ $solicitud->descripcion }}</p>
            @endif
            @if ($invitado->mensaje)
                <p class="hint"><strong>Indicaciones:</strong> {{ $invitado->mensaje }}</p>
            @endif
            <p class="muted">Fecha límite: {{ $solicitud->fecha_limite?->format('d/m/Y') ?? '—' }}</p>
        </div>

        <div class="card">
            <h2>{{ request('corregir') ? 'Subir corrección' : 'Subir documento' }}</h2>
            <form
                method="POST"
                action="{{ route('licitaciones.aportacion.store', $invitado->token) }}"
                enctype="multipart/form-data"
                onsubmit="var b=document.getElementById('btn-enviar'); b.disabled=true; b.innerText='Enviando…';"
            >
                @csrf
                @if (request('corregir'))
                    <input type="hidden" name="reemplaza_adjunto_id" value="{{ (int) request('corregir') }}">
                    <p class="warn">
                        Está reemplazando un documento que requiere corrección.
                        <a class="link" href="{{ route('licitaciones.aportacion', $invitado->token) }}">Cancelar</a>
                    </p>
                @endif
                <label for="archivo">Archivo</label>
                <input id="archivo" type="file" name="archivo" required accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.jpg,.jpeg,.png">
                <p class="muted">Máximo 50 MB · PDF, Word, Excel, ZIP o imágenes.</p>
                @error('archivo') <p class="err">{{ $message }}</p> @enderror
                <div style="margin-top:1rem">
                    <button id="btn-enviar" type="submit" class="btn btn-primary">Enviar documento</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Sus documentos</h2>
            <ul class="list">
                @forelse ($invitado->adjuntos as $adj)
                    @php
                        $estado = $adj->revision_estado?->value ?? 'pendiente';
                    @endphp
                    <li>
                        <strong>{{ $adj->nombre_archivo }}</strong>
                        <div class="muted">{{ $adj->created_at?->format('d/m/Y H:i') }}</div>
                        <span class="badge badge-{{ $estado }}">{{ $adj->revision_estado?->label() }}</span>
                        @if ($adj->revision_estado === \App\Enums\Licitaciones\DocumentRevisionStatus::Rechazado && $adj->revision_comentario)
                            <p class="err">{{ $adj->revision_comentario }}</p>
                            <a class="link" href="{{ route('licitaciones.aportacion', ['token' => $invitado->token, 'corregir' => $adj->id]) }}">Corregir</a>
                        @endif
                    </li>
                @empty
                    <li class="muted" style="text-align:center;border:0">Aún no ha subido documentos.</li>
                @endforelse
            </ul>
        </div>
    @endif
</div>
</body>
</html>
