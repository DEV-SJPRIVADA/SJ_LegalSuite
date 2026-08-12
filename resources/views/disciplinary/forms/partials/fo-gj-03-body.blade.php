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
    'statuteArticles' => [],
    'additionalEvidenceItems' => [],
    'signerName' => '',
    'signerRole' => '',
    'signatureDataUri' => null,
    'workerSignatureDataUri' => null,
    'evidenceType' => 'signed',
    'witnesses' => [],
    'documentPages' => null,
    'legalPhrasing' => null,
])

@php
    use App\Support\Disciplinary\FoGj03DocumentPaginator;
    use App\Support\Disciplinary\WorkerLegalPhrasing;

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

    $legalPhrasing = $legalPhrasing ?? WorkerLegalPhrasing::masculine();
    $additionalEvidenceItems = is_array($additionalEvidenceItems) ? $additionalEvidenceItems : [];

    $pages = $documentPages ?? app(FoGj03DocumentPaginator::class)->plan([
        'chargesDescription' => (string) $chargesDescription,
        'statuteArticles' => is_array($statuteArticles) ? $statuteArticles : [],
        'additionalEvidenceItems' => $additionalEvidenceItems,
        'locationText' => (string) $locationText,
        'blankForDownload' => (bool) $blankForDownload,
        'evidenceType' => (string) $evidenceType,
        'witnesses' => is_array($witnesses) ? $witnesses : [],
        'legalPhrasing' => $legalPhrasing,
    ]);

    $sharedHelpers = ['guidePattern' => $guidePattern, 'blank' => $blank];

    $openingProps = array_merge($sharedHelpers, compact(
        'blankForDownload',
        'fecha',
        'caseNumber',
        'expedienteGj',
        'workerName',
        'workerDocument',
        'workerPosition',
        'hearingDay',
        'hearingTime',
        'locationText',
        'legalPhrasing',
    ));

    $chargesBaseProps = array_merge($sharedHelpers, compact(
        'blankForDownload',
        'informeReportDate',
        'breachDate',
        'chargesDescription',
    ));

    $articlesProps = array_merge($sharedHelpers, compact(
        'blankForDownload',
        'statuteArticles',
    ));

    $evidenceProps = array_merge($sharedHelpers, compact(
        'blankForDownload',
        'informeReportDate',
        'legalPhrasing',
        'additionalEvidenceItems',
    ));

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

{{-- Páginas Letter explícitas: encabezado HTML en cada hoja (estable en Dompdf). --}}
<div class="ogj-wrap ogj-03-doc">
    @foreach ($pages as $page)
        <div @class(['ogj-page', 'ogj-03-page', 'ogj-page-break' => ! $loop->first])>
            @include('disciplinary.forms.partials.fo-gj-03-header', [
                'logoSrc' => $logoSrc,
                'pageLine' => $page['pageLine'],
            ])

            <div class="ogj-03-flow">
                @if ($page['showOpening'])
                    @include('disciplinary.forms.partials.fo-gj-03-opening', $openingProps)
                @endif

                @if ($page['showCharges'])
                    @include('disciplinary.forms.partials.fo-gj-03-charges', array_merge($chargesBaseProps, [
                        'chargesShowLead' => $page['chargesShowLead'],
                        'chargesIsContinuation' => $page['chargesIsContinuation'],
                        'chargesChunk' => $page['chargesChunk'],
                        'chargesShowTail' => $page['chargesShowTail'],
                    ]))
                @endif

                @if ($page['showArticles'])
                    @include('disciplinary.forms.partials.fo-gj-03-articles', $articlesProps)
                @endif

                @if ($page['showEvidence'])
                    @include('disciplinary.forms.partials.fo-gj-03-evidence', array_merge($evidenceProps, [
                        'evidenceShowLead' => $page['evidenceShowLead'],
                        'evidenceIsContinuation' => $page['evidenceIsContinuation'],
                        'evidenceChunk' => $page['evidenceChunk'],
                    ]))
                @endif

                @if ($page['showClosing'])
                    @include('disciplinary.forms.partials.fo-gj-03-closing-signatures', $closingProps)
                @endif
            </div>
        </div>
    @endforeach
</div>
