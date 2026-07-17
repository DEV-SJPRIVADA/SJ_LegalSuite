@php
    $guide = $guide ?? static fn (string $size = 'md') => '_ _ _ _ _ _ _ _ _ _ _ _';
    $showTitle = (bool) ($showTitle ?? true);
    $isAnswerContinuation = (bool) ($isAnswerContinuation ?? false);
@endphp

<div class="ogj-04-question">
    @if ($showTitle)
        <p class="ogj-04-question-title">{{ $number }}. PREGUNTA: {{ $questionText }}</p>
    @endif
    {{-- El planner emite título y respuesta como filas distintas: no pintar R: en la fila del título. --}}
    @if (filled($answerText) || $blankForDownload || $isAnswerContinuation || ! $showTitle)
        <p class="ogj-04-question-answer">
            @if (! $isAnswerContinuation)
                <strong>R:</strong>
            @endif
            @if ($blankForDownload && ! $isAnswerContinuation)
                <span class="ogj-04-guide" aria-hidden="true"> {{ $guide('lg') }}</span>
            @elseif (filled($answerText))
                <span class="ogj-04-answer-inline">{{ $isAnswerContinuation ? '' : ' ' }}{{ $answerText }}</span>
            @elseif (! $isAnswerContinuation)
                <span class="ogj-04-guide" aria-hidden="true"> {{ $guide('lg') }}</span>
            @endif
        </p>
    @endif
</div>
