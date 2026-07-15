<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-03 · Citación</title>
    @include('disciplinary.forms.partials.fo-gj-03-pdf-styles')
</head>
<body>
    @include('disciplinary.forms.partials.fo-gj-03-body', [
        'blankForDownload' => false,
        'logoSrc' => $embeddedLogoSrc ?? '',
        'fecha' => $fecha ?? '',
        'caseNumber' => $caseNumber ?? '',
        'workerName' => $workerName ?? '',
        'workerDocument' => $workerDocument ?? '',
        'workerPosition' => $workerPosition ?? '',
        'hearingDay' => $hearingDay ?? '',
        'hearingTime' => $hearingTime ?? '',
        'modality' => $modality ?? 'presencial',
        'locationText' => $locationText ?? '',
        'informeReportDate' => $informeReportDate ?? '',
        'breachDate' => $breachDate ?? '',
        'chargesDescription' => $chargesDescription ?? '',
        'article66Numerals' => $article66Numerals ?? '',
        'article68Numerals' => $article68Numerals ?? '',
        'article76Numerals' => $article76Numerals ?? '',
        'signerName' => $signerName ?? '',
        'signerRole' => $signerRole ?? '',
        'signatureDataUri' => $signatureDataUri ?? null,
    ])
</body>
</html>
