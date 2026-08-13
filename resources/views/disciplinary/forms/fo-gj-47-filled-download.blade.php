<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-47 · Suspensión disciplinaria</title>
    <style>
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    @include('disciplinary.forms.partials.fo-gj-47-body', [
        'blankForDownload' => false,
        'issuedDateLong' => $issuedDateLong ?? '',
        'caseNumber' => $caseNumber ?? '',
        'workerName' => $workerName ?? '',
        'workerDocument' => $workerDocument ?? '',
        'workerPosition' => $workerPosition ?? '',
        'openingSalutation' => $openingSalutation ?? '',
        'openingNarrative' => $openingNarrative ?? '',
        'daysPhrase' => $daysPhrase ?? '',
        'notifyWorkerPhrase' => $notifyWorkerPhrase ?? '',
        'startLong' => $startLong ?? '',
        'endLong' => $endLong ?? '',
        'returnLong' => $returnLong ?? '',
        'articles55' => $articles55 ?? '',
        'articles57' => $articles57 ?? '',
        'articles60' => $articles60 ?? '',
        'signerName' => $signerName ?? '',
        'signerTitle' => $signerTitle ?? '',
        'signatureDataUri' => $signatureDataUri ?? null,
        'legalPhrasing' => $legalPhrasing ?? null,
        'embeddedLogoSrc' => $embeddedLogoSrc ?? null,
    ])
</body>
</html>
