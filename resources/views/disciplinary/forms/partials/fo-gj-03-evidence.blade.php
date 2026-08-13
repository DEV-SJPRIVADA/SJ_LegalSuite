@php
    use App\Support\Disciplinary\FoGj03DocumentPaginator;
    use App\Support\Disciplinary\WorkerLegalPhrasing;

    $legalPhrasing = $legalPhrasing ?? WorkerLegalPhrasing::masculine();

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

    $showLead = (bool) ($evidenceShowLead ?? true);
    $chunk = trim((string) ($evidenceChunk ?? ''));
    $fullTraslado = FoGj03DocumentPaginator::evidenceTrasladoText($legalPhrasing);
    $additionalEvidenceItems = collect($additionalEvidenceItems ?? [])
        ->map(static fn (mixed $item): string => is_array($item)
            ? trim((string) ($item['text'] ?? ''))
            : trim((string) $item))
        ->filter()
        ->values()
        ->all();

    // Compat legacy: sin chunk explícito, pintar el párrafo completo.
    if ($chunk === '' && ($evidenceShowLead ?? null) === null && ($evidenceChunk ?? null) === null) {
        $showLead = true;
        $chunk = $fullTraslado;
    }
@endphp

@if ($showLead)
    <p>Los elementos probatorios que dan lugar al inicio del proceso disciplinario radican en:</p>

    <ul class="ogj-03-list">
        <li>
            Informes Disciplinarios
            @if ($blankForDownload ?? true)
                del {!! $blank($informeReportDate ?? '', 'sm') !!}
            @elseif (filled($informeReportDate ?? null))
                del {{ $informeReportDate }}
            @endif
        </li>
        @foreach ($additionalEvidenceItems as $evidenceItem)
            <li>{{ $evidenceItem }}</li>
        @endforeach
    </ul>
@endif

@if ($chunk !== '')
    <p class="ogj-03-justify">{{ $chunk }}</p>
@endif
