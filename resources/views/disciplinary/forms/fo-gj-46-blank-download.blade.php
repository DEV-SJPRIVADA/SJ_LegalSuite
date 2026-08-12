<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-46 · Llamado de atención (en blanco)</title>
    <style>
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-46"
        headline="Llamado de atención"
        :logo-src="$embeddedLogoSrc ?? null"
        meta-date="Noviembre de 2023"
        meta-version="Versión 02"
        :show-micro="true"
    >
        @include('disciplinary.forms.partials.fo-gj-46-body', [
            'blankForDownload' => true,
        ])
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
