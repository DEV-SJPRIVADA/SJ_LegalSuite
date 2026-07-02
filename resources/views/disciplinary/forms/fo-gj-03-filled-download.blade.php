<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-03 · Citación</title>
    <style>
        @page { size: Letter; margin: 0.45in; }
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-03"
        headline="Citación a diligencia disciplinaria"
        :logo-src="$embeddedLogoSrc ?? null"
        meta-date="Octubre de 2023"
        meta-version="Versión 03"
        :show-micro="false"
    >
        @include('disciplinary.forms.partials.fo-gj-03-body', [
            'blankForDownload' => false,
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
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
