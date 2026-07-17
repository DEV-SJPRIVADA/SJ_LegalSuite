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
@endphp

<p class="ogj-03-underline">Faltas disciplinarias:</p>

<ul class="ogj-03-list">
    <li>
        Artículo 66, numeral
        @if ($blankForDownload ?? true)
            {!! $blank('', 'md') !!}
        @else
            {{ filled($article66Numerals ?? null) ? e($article66Numerals) : '—' }}
        @endif
        , del Reglamento Interno de Trabajo, referente a las obligaciones especiales de los trabajadores
    </li>
    <li>
        Artículo 68, numerales
        @if ($blankForDownload ?? true)
            {!! $blank('', 'md') !!}
        @else
            {{ filled($article68Numerals ?? null) ? e($article68Numerals) : '—' }}
        @endif
        , del Reglamento Interno de Trabajo, referente a las prohibiciones de los trabajadores.
    </li>
    <li>
        Artículo 76, numerales
        @if ($blankForDownload ?? true)
            {!! $blank('', 'md') !!}
        @else
            {{ filled($article76Numerals ?? null) ? e($article76Numerals) : '—' }}
        @endif
        , del Reglamento Interno de Trabajo, referente a las faltas graves
    </li>
</ul>
