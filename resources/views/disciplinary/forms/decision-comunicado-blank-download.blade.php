<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FO-GJ-DECISION · Comunicado (en blanco)</title>
    <style>
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-DECISION"
        headline="Comunicado de decisión de sanción o cierre del proceso"
        :logo-src="$embeddedLogoSrc"
        meta-date=""
        meta-version="Versión 01"
        :show-micro="false"
    >
        @include('disciplinary.forms.partials.decision-comunicado-body', ['blankForDownload' => true])
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
