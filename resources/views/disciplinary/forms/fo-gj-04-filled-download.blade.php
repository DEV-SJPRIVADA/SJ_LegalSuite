<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-04 · Acta de diligencia</title>
    @include('disciplinary.forms.partials.fo-gj-04-pdf-styles')
</head>
<body>
    @include('disciplinary.forms.partials.fo-gj-04-body', [
        'blankForDownload' => false,
        'logoSrc' => $embeddedLogoSrc ?? '',
        'workerName' => $workerName ?? '',
        'workerDocument' => $workerDocument ?? '',
        'workerPosition' => $workerPosition ?? '',
        'openingDay' => $openingDay ?? '',
        'openingMonth' => $openingMonth ?? '',
        'openingYear' => $openingYear ?? '',
        'openingTime' => $openingTime ?? '',
        'lawyerName' => $lawyerName ?? '',
        'lawyerRole' => $lawyerRole ?? '',
        'breachDay' => $breachDay ?? '',
        'breachMonth' => $breachMonth ?? '',
        'breachYear' => $breachYear ?? '',
        'chargesDescription' => $chargesDescription ?? '',
        'workerManifestation' => $workerManifestation ?? '',
        'closingTime' => $closingTime ?? '',
        'questions' => $questions ?? [],
        'questionPages' => $questionPages ?? null,
        'signatureDataUri' => $signatureDataUri ?? null,
    ])
</body>
</html>
