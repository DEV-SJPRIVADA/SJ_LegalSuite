<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FO-GJ-54 · Reprogramación diligencia (en blanco)</title>
    <style>
        @page { size: Letter; margin: 0.45in; }
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-54"
        headline="Reprogramación a diligencia disciplinaria"
        :logo-src="$embeddedLogoSrc"
        meta-date="Mayo de 2024"
        meta-version="Versión 01"
        :show-micro="false"
    >
        @include('disciplinary.forms.partials.fo-gj-54-body', ['blankForDownload' => true])
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
