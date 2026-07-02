@php
    $guide = $guide ?? static fn (string $size = 'md') => '_ _ _ _ _ _ _ _ _ _ _ _';
@endphp

<div class="ogj-04-question">
    <p class="ogj-04-question-title">{{ $number }}. PREGUNTA: {{ $questionText }}</p>
    <p class="ogj-04-question-answer"><strong>R:</strong>@if ($blankForDownload)<span class="ogj-04-guide" aria-hidden="true"> {{ $guide('lg') }}</span>@elseif (filled($answerText)) <span class="ogj-04-answer-inline">{{ $answerText }}</span>@else<span class="ogj-04-guide" aria-hidden="true"> {{ $guide('lg') }}</span>@endif</p>
</div>
