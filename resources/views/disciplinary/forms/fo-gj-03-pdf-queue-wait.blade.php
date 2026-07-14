{{-- Vista mínima para iframe / pestaña: no usa layout de app (evita nav dentro del modal). --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generando FO-GJ-03</title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #f1f5f9; color: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .box { text-align: center; padding: 1.5rem; max-width: 28rem; }
        .spin { width: 2.5rem; height: 2.5rem; border: 3px solid #cbd5e1; border-top-color: #0284c7; border-radius: 999px; animation: s 0.8s linear infinite; margin: 0 auto 1rem; }
        @keyframes s { to { transform: rotate(360deg); } }
        .err { color: #b91c1c; margin-top: 1rem; display: none; white-space: pre-wrap; text-align: left; font-size: 0.875rem; }
        a { color: #1d4ed8; }
    </style>
</head>
<body>
<div class="box">
    <div class="spin" id="spin" aria-hidden="true"></div>
    <p id="msg">
        @if ($intent === 'generate')
            Generando y guardando FO-GJ-03 en el expediente…
        @else
            Generando vista previa FO-GJ-03…
        @endif
        Esto suele tardar unos segundos (cola del servidor).
    </p>
    <p class="err" id="err"></p>
    <p id="back" style="display:none;margin-top:1rem">
        <a href="{{ $caseUrl }}">Volver al expediente</a>
    </p>
</div>
<script>
(function () {
    const statusUrl = @json($statusUrl);
    const downloadUrl = @json($downloadUrl);
    const caseUrl = @json($caseUrl);
    const intent = @json($intent);
    const msg = document.getElementById('msg');
    const err = document.getElementById('err');
    const spin = document.getElementById('spin');
    const back = document.getElementById('back');

    const poll = () => {
        fetch(statusUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((r) => {
                if (!r.ok) throw new Error('No se pudo consultar el estado del PDF.');
                return r.json();
            })
            .then((data) => {
                if (data.status === 'ready') {
                    if (intent === 'generate') {
                        window.location.href = data.redirect_url || caseUrl;
                        return;
                    }
                    window.location.href = downloadUrl;
                    return;
                }
                if (data.status === 'failed') {
                    spin.style.display = 'none';
                    msg.style.display = 'none';
                    err.style.display = 'block';
                    err.textContent = data.error || 'No se pudo generar el PDF.';
                    back.style.display = 'block';
                    return;
                }
                window.setTimeout(poll, 2000);
            })
            .catch(() => window.setTimeout(poll, 3000));
    };
    poll();
})();
</script>
</body>
</html>
