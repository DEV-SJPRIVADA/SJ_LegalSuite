<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comunicado de decisión · {{ $caseNumber ?? '' }}</title>
    <style>
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-DECISION"
        headline="Comunicado de decisión de sanción o cierre del proceso"
        :logo-src="$embeddedLogoSrc"
        meta-date="{{ $issuedDate ?? '' }}"
        meta-version=""
        :show-micro="false"
    >
        @include('disciplinary.forms.partials.decision-comunicado-body', [
            'blankForDownload' => false,
            'subject' => $subject ?? '',
            'bodyNarrative' => $bodyNarrative ?? '',
            'workerName' => $workerName ?? '',
            'workerDocument' => $workerDocument ?? '',
            'workerPosition' => $workerPosition ?? '',
            'decisionLabel' => $decisionLabel ?? '',
            'suspensionStart' => $suspensionStart ?? '',
            'suspensionEnd' => $suspensionEnd ?? '',
            'reliefNotes' => $reliefNotes ?? '',
            'showSuspensionDates' => $showSuspensionDates ?? false,
            'showRelief' => $showRelief ?? false,
            'notificationDate' => $notificationDate ?? '',
            'notificationShift' => $notificationShift ?? '',
            'notificationZone' => $notificationZone ?? '',
            'supervisorName' => $supervisorName ?? '',
            'issuedDate' => $issuedDate ?? '',
            'lawyerName' => $lawyerName ?? '',
            'placeLine' => $placeLine ?? null,
        ])
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
