<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-54 · Reprogramación diligencia</title>
    <style>
        @page { size: Letter; margin: 0.45in; }
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-54"
        headline="Reprogramación a diligencia disciplinaria"
        :logo-src="$embeddedLogoSrc ?? null"
        meta-date="Mayo de 2024"
        meta-version="Versión 01"
        :show-micro="false"
    >
        @include('disciplinary.forms.partials.fo-gj-54-body', [
            'blankForDownload' => false,
            'fecha' => $fecha ?? '',
            'workerName' => $workerName ?? '',
            'workerDocument' => $workerDocument ?? '',
            'workerPosition' => $workerPosition ?? '',
            'originalHearingDay' => $originalHearingDay ?? '',
            'originalHearingMonth' => $originalHearingMonth ?? '',
            'originalHearingYear' => $originalHearingYear ?? '',
            'originalHearingTime' => $originalHearingTime ?? '',
            'factsDay' => $factsDay ?? '',
            'factsMonth' => $factsMonth ?? '',
            'clientSite' => $clientSite ?? '',
            'shiftStart' => $shiftStart ?? '',
            'shiftEnd' => $shiftEnd ?? '',
            'newHearingDay' => $newHearingDay ?? '',
            'newHearingMonth' => $newHearingMonth ?? '',
            'newHearingYear' => $newHearingYear ?? '',
            'newHearingTime' => $newHearingTime ?? '',
            'newHearingPlace' => $newHearingPlace ?? '',
            'employerName' => $employerName ?? '',
            'signatureDataUri' => $signatureDataUri ?? null,
        ])
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
