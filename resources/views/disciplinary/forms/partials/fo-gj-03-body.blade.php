@props([
    'blankForDownload' => true,
    'logoSrc' => '',
    'fecha' => '',
    'caseNumber' => '',
    'expedienteGj' => '',
    'workerName' => '',
    'workerDocument' => '',
    'workerPosition' => '',
    'hearingDay' => '',
    'hearingTime' => '',
    'modality' => 'presencial',
    'locationText' => '',
    'informeReportDate' => '',
    'breachDate' => '',
    'chargesDescription' => '',
    'article66Numerals' => '',
    'article68Numerals' => '',
    'article76Numerals' => '',
    'signerName' => '',
    'signerRole' => '',
    'signatureDataUri' => null,
    'workerSignatureDataUri' => null,
    'evidenceType' => 'signed',
    'witnesses' => [],
])

@php
    $guidePattern = static fn (string $size): string => match ($size) {
        'sm' => '_ _ _ _ _ _',
        'lg' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        default => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
    };

    $blank = static function (string $value, string $size = 'md') use ($guidePattern): string {
        if (filled($value)) {
            return e($value);
        }

        return '<span class="ogj-03-guide ogj-03-guide-'.$size.'" aria-hidden="true">'.$guidePattern($size).'</span>';
    };

    $contentProps = compact(
        'blankForDownload',
        'fecha',
        'caseNumber',
        'expedienteGj',
        'workerName',
        'workerDocument',
        'workerPosition',
        'hearingDay',
        'hearingTime',
        'modality',
        'locationText',
        'informeReportDate',
        'breachDate',
        'chargesDescription',
        'article66Numerals',
        'article68Numerals',
        'article76Numerals',
        'guidePattern',
        'blank',
    );

    $closingProps = compact(
        'blankForDownload',
        'signerName',
        'signerRole',
        'signatureDataUri',
        'workerSignatureDataUri',
        'workerName',
        'workerDocument',
        'workerPosition',
        'evidenceType',
        'witnesses',
    );
@endphp

{{-- Flujo continuo: letterhead position:fixed (Dompdf lo repite); N de M vía canvas. --}}
<div class="ogj-wrap ogj-03-doc" data-sj-pdf-flow="fo-gj-03">
    <div class="ogj-page ogj-03-page">
        <div class="ogj-03-letterhead">
            @include('disciplinary.forms.partials.fo-gj-03-header', [
                'logoSrc' => $logoSrc,
                'pageLine' => '',
            ])
        </div>

        <div class="ogj-03-flow">
            @include('disciplinary.forms.partials.fo-gj-03-content', $contentProps)
            @include('disciplinary.forms.partials.fo-gj-03-closing-signatures', $closingProps)
        </div>
    </div>
</div>
