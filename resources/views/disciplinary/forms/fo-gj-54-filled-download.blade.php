<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-54 · Reprogramación diligencia</title>
    <style>
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
            'informeReportDateLong' => $informeReportDateLong ?? '',
            'chargesDescription' => $chargesDescription ?? '',
            'rescheduleCausePhrase' => $rescheduleCausePhrase ?? '',
            'newHearingDay' => $newHearingDay ?? '',
            'newHearingMonth' => $newHearingMonth ?? '',
            'newHearingYear' => $newHearingYear ?? '',
            'newHearingTime' => $newHearingTime ?? '',
            'modality' => $modality ?? 'presencial',
            'modalityLocationText' => $modalityLocationText ?? '',
            'employerName' => $employerName ?? '',
            'signatureDataUri' => $signatureDataUri ?? null,
            'legalPhrasing' => $legalPhrasing ?? null,
        ])
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
