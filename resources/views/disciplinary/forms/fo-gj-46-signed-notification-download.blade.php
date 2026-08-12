<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-46 · Llamado de atención firmado</title>
    <style>
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-46"
        headline="Llamado de atención"
        :logo-src="$embeddedLogoSrc ?? null"
        meta-date="Noviembre de 2023"
        meta-version="Versión 02"
        :show-micro="false"
    >
        @include('disciplinary.forms.partials.fo-gj-46-body', [
            'blankForDownload' => false,
            'issuedDateLong' => $issuedDateLong ?? '',
            'caseNumber' => $caseNumber ?? '',
            'workerName' => $workerName ?? '',
            'workerDocument' => $workerDocument ?? '',
            'workerPosition' => $workerPosition ?? '',
            'hearingLeadPhrase' => $hearingLeadPhrase ?? '',
            'postHearingBridge' => $postHearingBridge ?? '',
            'hearingLead' => $hearingLead ?? '',
            'modalityLabel' => $modalityLabel ?? '',
            'hearingDay' => $hearingDay ?? '',
            'hearingMonth' => $hearingMonth ?? '',
            'hearingYear' => $hearingYear ?? '',
            'factsNarrative' => $factsNarrative ?? '',
            'breachDay' => $breachDay ?? '',
            'breachMonth' => $breachMonth ?? '',
            'breachYear' => $breachYear ?? '',
            'articles55' => $articles55 ?? '',
            'articles57' => $articles57 ?? '',
            'articles60' => $articles60 ?? '',
            'signerName' => $signerName ?? '',
            'signerTitle' => $signerTitle ?? '',
            'signatureDataUri' => $signatureDataUri ?? null,
            'evidenceType' => $evidenceType ?? 'signed',
            'workerSignatureDataUri' => $workerSignatureDataUri ?? null,
            'witnesses' => $witnesses ?? [],
            'legalPhrasing' => $legalPhrasing ?? null,
        ])
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
