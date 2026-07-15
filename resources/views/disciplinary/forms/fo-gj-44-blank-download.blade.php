<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FO-GJ-44 · Constancia de inasistencia (en blanco)</title>
    <style>
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-44"
        headline="Constancia de inasistencia"
        :logo-src="$embeddedLogoSrc"
        meta-date="Noviembre de 2023"
        meta-version="Versión 02"
        :show-micro="false"
    >
        @include('disciplinary.forms.partials.fo-gj-44-body', ['blankForDownload' => true])
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
