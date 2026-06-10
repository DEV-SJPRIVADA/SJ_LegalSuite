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
    'lawyerRole' => 'Analista de relaciones laborales y cumplimiento SJ Seguridad Privada Ltda.',
    'signatureDataUri' => null,
])

@php
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

    $manifestationText = match ($workerManifestation) {
        'yes' => 'SI QUIERO RESPONDER.',
        'no' => 'NO DESEA RESPONDER.',
        default => '',
    };

    $questionItems = collect($questions)->map(fn ($q) => is_array($q) ? (string) ($q['text'] ?? '') : (string) $q)->filter(fn ($t) => filled($t))->values();
    $page1Questions = $questionItems->take(3);
    $page2Questions = $questionItems->slice(3)->values();
@endphp

<div class="ogj-wrap ogj-04-body">
    <div class="ogj-page">
        @include('disciplinary.forms.partials.fo-gj-04-header', ['logoSrc' => $logoSrc, 'pageLine' => 'Página 1 de 2'])

        <div class="ogj-04-id">
            <p>NOMBRE. {!! $blank($workerName, 'lg') !!}</p>
            <p>CÉDULA. {!! $blank($workerDocument, 'md') !!}</p>
            <p>CARGO. {!! $blank($workerPosition, 'md') !!}</p>
        </div>

        <p>
            En Santiago de Cali, a los {!! $blank($openingDay, 'sm') !!} días del mes de {!! $blank($openingMonth, 'md') !!}
            de {!! $blank($openingYear, 'sm') !!} en las instalaciones de la compañía SJ Seguridad Privada Ltda., siendo las
            {!! $blank($openingTime, 'sm') !!} horas:
        </p>

        <p><strong>EN REPRESENTACIÓN EL EMPLEADOR:</strong> {!! $blank($lawyerName, 'lg') !!}</p>

        <p>
            De otra parte, en cumplimiento de la citación previa a esta diligencia, enviada mediante comunicación escrita,
            con los respectivos soportes del proceso disciplinario y garantizando su derecho a la defensa y al debido proceso:
        </p>

        <p><strong>EL TRABAJADOR:</strong> {!! $blank($workerName, 'lg') !!}</p>

        <p>
            Con el fin de ser escuchado en diligencia disciplinaria sobre la presunta falta cometida el día
            {!! $blank($breachDay, 'sm') !!} de {!! $blank($breachMonth, 'md') !!} del {!! $blank($breachYear, 'sm') !!},
            fecha en la cual, usted:
            @if ($blankForDownload)
                <span class="ogj-04-guide ogj-04-guide-lg" aria-hidden="true">{{ $guide('xl') }}</span>
            @elseif (filled($chargesDescription))
                {{ $chargesDescription }}
            @else
                —
            @endif
            comprendido como incumplimiento en el Reglamento Interno de Trabajo.
        </p>

        <p>
            Con base a lo anterior se requiere de sus explicaciones y para ello, esta acta se desarrolla en los siguientes términos:
        </p>

        <p class="ogj-04-list-num">1. Su asistencia a esta diligencia es de carácter meramente administrativo laboral y de manera voluntaria.</p>
        <p class="ogj-04-list-num">2. En garantía de su Derecho de Defensa y Debido Proceso tiene derecho a no declarar contra sí mismo, por lo que está en libertad de responder o no responder a los cargos que se le imputarán y hechos que se le expondrán.</p>
        <p class="ogj-04-list-num">3. Si decide responder, se le pide que lo haga de manera espontánea, concreta y fiel con la realidad de los hechos tal como a su forma de ser sucedieron, aceptando o no aceptando los cargos que se le imputarán, o, dando las explicaciones que considere, pudiendo solicitar las pruebas que tiendan a justificar, atenuar, o demostrar su no participación en los hechos que se le expondrán como soporte de dichos cargos.</p>
        <p class="ogj-04-list-num">4. Una vez iniciada esta diligencia, en cualquier momento podrá darla por terminada manifestado que no continuará respondiendo, por lo que esta quedará en el estado en que se encuentre, sin que pueda retirar, aclarar o adicionar lo que hasta ese instante hubiese manifestado.</p>
        <p class="ogj-04-list-num">5. Si por cualquier motivo se negare a firmar el acta de esta diligencia, EL EMPLEADOR recurrirá a dos (2) trabajadores testigos que darán fe con su firma de la veracidad de tal situación.</p>

        <p>
            Una vez enterado y entiendo perfectamente sus derechos, EL TRABAJADOR, manifestó:
            @if ($blankForDownload)
                <span class="ogj-04-guide" aria-hidden="true">{{ $guide('md') }}</span>
            @elseif (filled($manifestationText))
                <strong>{{ $manifestationText }}</strong>
            @else
                <span class="ogj-04-guide" aria-hidden="true">{{ $guide('md') }}</span>
            @endif
        </p>

        <p>
            De esta forma, obedeciendo los lineamientos establecidos en el contrato de trabajo, el reglamento interno de
            trabajo y el Código Sustantivo del Trabajo, se procederá a escuchar la versión libre del trabajador y a efectuar
            el cuestionario relacionado con los hechos que generaron la realización del presente proceso disciplinario:
        </p>

        @if ($page1Questions->isNotEmpty())
            @foreach ($page1Questions as $index => $questionText)
                <div class="ogj-04-question">
                    <p class="ogj-04-question-title">{{ $index + 1 }}. PREGUNTA: {{ $questionText }}</p>
                    <p class="ogj-04-question-title">R:</p>
                    <div class="ogj-04-answer-line"></div>
                    <div class="ogj-04-answer-line"></div>
                </div>
            @endforeach
        @elseif ($blankForDownload)
            <p>(…)</p>
        @endif
    </div>

    <div class="ogj-page ogj-page-break">
        @include('disciplinary.forms.partials.fo-gj-04-header', ['logoSrc' => $logoSrc, 'pageLine' => 'Página 2 de 2'])

        @if ($page2Questions->isNotEmpty())
            @foreach ($page2Questions as $index => $questionText)
                <div class="ogj-04-question">
                    <p class="ogj-04-question-title">{{ $page1Questions->count() + $index + 1 }}. PREGUNTA: {{ $questionText }}</p>
                    <p class="ogj-04-question-title">R:</p>
                    <div class="ogj-04-answer-line"></div>
                    <div class="ogj-04-answer-line"></div>
                </div>
            @endforeach
        @elseif ($blankForDownload)
            <p>(…)</p>
        @endif

        <p>
            No siendo otro el motivo de la diligencia, siendo las {!! $blank($closingTime, 'sm') !!} horas se da por ésta finalizada, en constancia
            firman las partes intervinientes.
        </p>

        <table class="ogj-04-signatures" role="presentation">
            <tr>
                <td>
                    <p><strong>REPRESENTANTE DEL EMPLEADOR:</strong></p>
                    <div class="ogj-04-signature-slot">
                        @if ($signatureDataUri)
                            <img src="{{ $signatureDataUri }}" alt="Firma empleador" class="ogj-04-signature-img">
                        @endif
                    </div>
                    <div class="ogj-04-sign-line"></div>
                    <p><strong>Nombre:</strong> {!! $blank($lawyerName, 'md') !!}</p>
                    <p>{{ $lawyerRole }}</p>
                </td>
                <td>
                    <p><strong>TRABAJADOR,</strong></p>
                    <div class="ogj-04-signature-slot"></div>
                    <div class="ogj-04-sign-line"></div>
                    <p><strong>Nombre:</strong> {!! $blank($workerName, 'md') !!}</p>
                    <p><strong>C.C</strong> {!! $blank($workerDocument, 'md') !!}</p>
                </td>
            </tr>
        </table>
    </div>
</div>
