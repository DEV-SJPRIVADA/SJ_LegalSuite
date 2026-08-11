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

    $statuteArticles = $statuteArticles ?? [];
    if ($statuteArticles === [] && (($article66Numerals ?? '') !== '' || ($article68Numerals ?? '') !== '' || ($article76Numerals ?? '') !== '')) {
        $statuteArticles = array_values(array_filter([
            filled($article66Numerals ?? null) ? ['article_number' => '66', 'numerals' => (string) $article66Numerals] : null,
            filled($article68Numerals ?? null) ? ['article_number' => '68', 'numerals' => (string) $article68Numerals] : null,
            filled($article76Numerals ?? null) ? ['article_number' => '76', 'numerals' => (string) $article76Numerals] : null,
        ]));
    }
@endphp

<p class="ogj-03-underline">Faltas disciplinarias:</p>

<ul class="ogj-03-list">
    @forelse ($statuteArticles as $article)
        @php
            $articleNumber = (string) ($article['article_number'] ?? '');
            $numerals = (string) ($article['numerals'] ?? '');
            $clause = trim((string) ($article['clause_suffix'] ?? ''));
            $numeralLabel = str_contains($numerals, ',') ? 'numerales' : 'numeral';
        @endphp
        <li>
            Artículo {{ $articleNumber }}, {{ $numeralLabel }}
            @if ($blankForDownload ?? true)
                {!! $blank($numerals, 'md') !!}
            @else
                {{ filled($numerals) ? $numerals : '—' }}
            @endif
            , del Reglamento Interno de Trabajo
            @if ($clause !== '')
                {{ ', '.$clause }}
            @endif
        </li>
    @empty
        <li>
            Artículo
            @if ($blankForDownload ?? true)
                {!! $blank('', 'sm') !!}
            @else
                —
            @endif
            , numeral
            @if ($blankForDownload ?? true)
                {!! $blank('', 'md') !!}
            @else
                —
            @endif
            , del Reglamento Interno de Trabajo
        </li>
    @endforelse
</ul>
