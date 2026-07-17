@php
    $guidePattern = $guidePattern ?? static fn (string $size): string => match ($size) {
        'sm' => '_ _ _ _ _ _',
        'lg' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        default => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
    };

    $blank = $blank ?? static function (string $value, string $size = 'md') use ($guidePattern): string {
        if (filled($value)) {
            return e($value);
        }

        return '<span class="ogj-03-guide ogj-03-guide-'.$size.'" aria-hidden="true">'.$guidePattern($size).'</span>';
    };

    $showLead = (bool) ($chargesShowLead ?? true);
    $showTail = (bool) ($chargesShowTail ?? true);
    $isContinuation = (bool) ($chargesIsContinuation ?? false);
    $chunk = trim((string) ($chargesChunk ?? ($chargesDescription ?? '')));
@endphp

@if ($showLead)
    <p class="ogj-03-section-title">Formulación de cargos</p>
@elseif ($isContinuation)
    <p class="ogj-03-section-title">Formulación de cargos (continuación)</p>
@endif

<p class="ogj-03-justify">
    @if ($showLead)
        <span class="ogj-03-underline">Conductas posibles de sanción:</span>
        El presunto incumplimiento de sus obligaciones laborales. Según el informe disciplinario del
        {!! $blank($informeReportDate ?? '', 'sm') !!}
        se reporta que el día
        {!! $blank($breachDate ?? '', 'sm') !!}:
    @endif

    @if ($blankForDownload ?? true)
        @if ($showLead)
            <span class="ogj-03-guide ogj-03-guide-lg" aria-hidden="true">{{ $guidePattern('lg') }}</span>.
        @endif
    @elseif ($chunk !== '')
        {{ $chunk }}@if ($showTail).@endif
    @elseif ($showLead)
        —.
    @endif

    @if ($showTail)
        Estos hechos de comprobarse podrían constituir faltas graves y grave incumplimiento de las obligaciones contractuales, legales y reglamentarias, vulnerando lo dispuesto en el Reglamento de Trabajo y contrato laboral.
    @endif
</p>
