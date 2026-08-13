<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FO-GJ-03 · Citación a diligencia (en blanco)</title>
    @include('disciplinary.forms.partials.fo-gj-03-pdf-styles')
</head>
<body>
    @include('disciplinary.forms.partials.fo-gj-03-body', [
        'blankForDownload' => true,
        'logoSrc' => $embeddedLogoSrc ?? '',
    ])
</body>
</html>
