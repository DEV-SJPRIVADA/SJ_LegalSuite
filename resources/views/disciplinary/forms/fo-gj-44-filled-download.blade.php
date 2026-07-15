<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-44 · Constancia de inasistencia</title>
    <style>
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-44"
        headline="Constancia de inasistencia"
        :logo-src="$embeddedLogoSrc ?? null"
        meta-date="Noviembre de 2023"
        meta-version="Versión 02"
        :show-micro="false"
    >
        @include('disciplinary.forms.partials.fo-gj-44-body', [
            'blankForDownload' => false,
            'fecha' => $fecha ?? '',
            'workerName' => $workerName ?? '',
            'citationSinceDay' => $citationSinceDay ?? '',
            'citationSinceMonth' => $citationSinceMonth ?? '',
            'citationSinceYearSuffix' => $citationSinceYearSuffix ?? '',
            'hearingDay' => $hearingDay ?? '',
            'hearingMonth' => $hearingMonth ?? '',
            'hearingYearSuffix' => $hearingYearSuffix ?? '',
            'hearingTime' => $hearingTime ?? '',
            'allegedOmission' => $allegedOmission ?? '',
            'workerPosition' => $workerPosition ?? '',
            'signDay' => $signDay ?? '',
            'signMonth' => $signMonth ?? '',
            'signYearSuffix' => $signYearSuffix ?? '',
            'signTime' => $signTime ?? '',
            'employerName' => $employerName ?? '',
            'signatureDataUri' => $signatureDataUri ?? null,
            'witness1Name' => $witness1Name ?? '',
            'witness1Cargo' => $witness1Cargo ?? '',
            'witness1Date' => $witness1Date ?? '',
            'witness2Name' => $witness2Name ?? '',
            'witness2Cargo' => $witness2Cargo ?? '',
            'witness2Date' => $witness2Date ?? '',
        ])
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
