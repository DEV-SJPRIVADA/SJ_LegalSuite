<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-04 · Acta de diligencia (en blanco)</title>
    @include('disciplinary.forms.partials.fo-gj-04-pdf-styles')
</head>
<body>
    @include('disciplinary.forms.partials.fo-gj-04-body', [
        'blankForDownload' => true,
        'logoSrc' => $embeddedLogoSrc ?? '',
    ])
</body>
</html>
