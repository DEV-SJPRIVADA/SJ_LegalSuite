@props([
    'blankForDownload' => true,
    'logoSrc' => '',
    'workerName' => '',
    'workerDocument' => '',
    'workerPosition' => '',
    'openingDay' => '',
    'openingMonth' => '',
    'openingYear' => '',
    'openingTime' => '',
    'lawyerName' => '',
    'breachDay' => '',
    'breachMonth' => '',
    'breachYear' => '',
    'chargesDescription' => '',
    'workerManifestation' => '',
    'closingTime' => '',
    'questions' => [],
    'questionPages' => null,
    'lawyerRole' => 'Analista de relaciones laborales y cumplimiento SJ Seguridad Privada Ltda.',
    'signatureDataUri' => null,
    'workerSignatureDataUri' => null,
])

@php
    use App\Support\Disciplinary\FoGj04PagePlanner;

    $guide = static fn (string $size = 'md') => match ($size) {
        'sm' => '_ _ _ _ _',
        'lg' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        'xl' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        default => '_ _ _ _ _ _ _ _ _ _ _ _',
    };

    $blank = static function (?string $value, string $size = 'md') use ($guide): string {
        if (filled($value)) {
            return e($value);
        }

        return '<span class="ogj-04-guide" aria-hidden="true">'.$guide($size).'</span>';
    };

    $questionItems = collect($questions)->map(function ($q) {
        if (! is_array($q)) {
            return null;
        }

        $question = trim((string) ($q['question'] ?? $q['text'] ?? ''));
        if ($question === '') {
            return null;
        }

        return [
            'question' => $question,
            'answer' => trim((string) ($q['answer'] ?? '')),
        ];
    })->filter()->values()->all();

    $pages = $questionPages ?? app(FoGj04PagePlanner::class)->plan([
        'questions' => $questionItems,
        'chargesDescription' => (string) $chargesDescription,
        'blankForDownload' => (bool) $blankForDownload,
    ]);

    $sharedIntroProps = compact(
        'blankForDownload',
        'workerName',
        'workerDocument',
        'workerPosition',
        'openingDay',
        'openingMonth',
        'openingYear',
        'openingTime',
        'lawyerName',
        'breachDay',
        'breachMonth',
        'breachYear',
        'chargesDescription',
        'workerManifestation',
    ) + ['guide' => $guide, 'blank' => $blank];

    $sharedClosingProps = compact(
        'blankForDownload',
        'closingTime',
        'lawyerName',
        'lawyerRole',
        'workerName',
        'workerDocument',
        'signatureDataUri',
        'workerSignatureDataUri',
    ) + ['blank' => $blank];
@endphp

<div class="ogj-wrap ogj-04-body">
    @foreach ($pages as $page)
        <div @class(['ogj-page', 'ogj-page-break' => ! $loop->first])>
            @include('disciplinary.forms.partials.fo-gj-04-header', [
                'logoSrc' => $logoSrc,
                'pageLine' => $page['pageLine'],
            ])

            @if (
                ($page['showIntroLead'] ?? false)
                || ($page['showCharges'] ?? false)
                || ($page['showTermsLead'] ?? false)
                || (($page['termNumbers'] ?? []) !== [])
                || ($page['showIntroTail'] ?? false)
            )
                @include('disciplinary.forms.partials.fo-gj-04-intro', $sharedIntroProps + [
                    'showIntroLead' => (bool) ($page['showIntroLead'] ?? false),
                    'showCharges' => (bool) ($page['showCharges'] ?? false),
                    'chargesShowLead' => (bool) ($page['chargesShowLead'] ?? false),
                    'chargesIsContinuation' => (bool) ($page['chargesIsContinuation'] ?? false),
                    'chargesChunk' => (string) ($page['chargesChunk'] ?? ''),
                    'chargesShowTail' => (bool) ($page['chargesShowTail'] ?? false),
                    'showTermsLead' => (bool) ($page['showTermsLead'] ?? false),
                    'termNumbers' => $page['termNumbers'] ?? [],
                    'showIntroTail' => (bool) ($page['showIntroTail'] ?? false),
                ])
            @endif

            @if ($blankForDownload && ($page['showIntroLead'] ?? false) && ($page['questions'] ?? []) === [])
                <p>(…)</p>
            @endif

            @foreach ($page['questions'] as $item)
                @include('disciplinary.forms.partials.fo-gj-04-question-item', [
                    'blankForDownload' => $blankForDownload,
                    'number' => $item['number'],
                    'questionText' => $item['question'],
                    'answerText' => $item['answer'],
                    'guide' => $guide,
                ])
            @endforeach

            @if (($page['showClosingText'] ?? false) || ($page['showClosing'] ?? false))
                @include('disciplinary.forms.partials.fo-gj-04-closing-signatures', $sharedClosingProps + [
                    'showClosingText' => (bool) ($page['showClosingText'] ?? false),
                    'showClosing' => (bool) ($page['showClosing'] ?? false),
                ])
            @endif
        </div>
    @endforeach
</div>
