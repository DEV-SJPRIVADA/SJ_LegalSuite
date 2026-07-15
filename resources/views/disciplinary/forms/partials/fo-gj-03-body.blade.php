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
    'pagePlan' => null,
])

@php
    use App\Support\Disciplinary\FoGj03PagePlanner;

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

    $pages = $pagePlan ?? app(FoGj03PagePlanner::class)->plan([
        'blankForDownload' => (bool) $blankForDownload,
        'chargesDescription' => (string) $chargesDescription,
        'article66Numerals' => (string) $article66Numerals,
        'article68Numerals' => (string) $article68Numerals,
        'article76Numerals' => (string) $article76Numerals,
        'locationText' => (string) $locationText,
        'evidenceType' => (string) $evidenceType,
        'witnesses' => is_array($witnesses) ? $witnesses : [],
    ]);

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

<div class="ogj-wrap ogj-03-doc">
    @foreach ($pages as $page)
        <div @class(['ogj-page', 'ogj-page-break' => ! $loop->first])>
            @include('disciplinary.forms.partials.fo-gj-03-header', [
                'logoSrc' => $logoSrc,
                'pageLine' => $page['pageLine'],
            ])

            @if (($page['sections'] ?? []) !== [])
                @include('disciplinary.forms.partials.fo-gj-03-content', $contentProps + [
                    'sections' => $page['sections'],
                ])
            @endif

            @if ($page['showClosing'])
                @include('disciplinary.forms.partials.fo-gj-03-closing-signatures', $closingProps)
            @endif
        </div>
    @endforeach
</div>
