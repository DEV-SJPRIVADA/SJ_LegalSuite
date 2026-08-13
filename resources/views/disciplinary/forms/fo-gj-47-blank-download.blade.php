<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-47 · Suspensión disciplinaria (en blanco)</title>
    <style>
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    @include('disciplinary.forms.partials.fo-gj-47-body', [
        'blankForDownload' => true,
        'embeddedLogoSrc' => $embeddedLogoSrc ?? null,
    ])
</body>
</html>
