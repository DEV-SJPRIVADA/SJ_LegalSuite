<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>ACTA-COMITE · Acta de comité disciplinario (en blanco)</title>
    @include('disciplinary.forms.partials.comite-acta-pdf-styles')
</head>
<body>
    @include('disciplinary.forms.partials.comite-acta-pdf-document', [
        'blankForDownload' => true,
        'caseNumber' => '',
        'actaNumber' => '',
        'actaSubject' => 'Comité para toma de decisión.',
        'companyLegalName' => 'SJ SEGURIDAD PRIVADA LTDA',
        'meetingPlaceLine' => '',
        'meetingDateLong' => '',
        'decisionNarrative' => '',
        'attendees' => [
            ['name' => '', 'cargo' => '', 'signature_data_uri' => null],
            ['name' => '', 'cargo' => '', 'signature_data_uri' => null],
        ],
    ])
</body>
</html>
