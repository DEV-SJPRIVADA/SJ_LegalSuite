@props([
    'blankForDownload' => true,
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
    'conductMonth' => '',
    'conductDays' => '',
])

@php
    $displayCaseNumber = filled($caseNumber) ? $caseNumber : (filled($expedienteGj) ? 'GJ-PD:'.$expedienteGj : '');

    $guidePattern = static fn (string $size): string => match ($size) {
        'sm' => '_ _ _ _ _ _',
        'lg' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        default => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
    };

    $blank = static function (string $value, string $size = 'md') use ($guidePattern): string {
        if (filled($value)) {
            return e($value);
        }

        $pattern = $guidePattern($size);

        return '<span class="ogj-03-guide ogj-03-guide-'.$size.'" aria-hidden="true">'.$pattern.'</span>';
    };
@endphp

<div class="ogj-03-body">
    <div class="ogj-03-ref">
        <p>Fecha: {!! $blank($fecha, 'lg') !!}</p>
        <p>{!! $blank($displayCaseNumber, 'lg') !!}</p>
    </div>

    <div class="ogj-03-recipient">
        <p>NOMBRE. {!! $blank($workerName, 'lg') !!}</p>
        <p>CÉDULA. {!! $blank($workerDocument, 'md') !!}</p>
        <p>CARGO. {!! $blank($workerPosition, 'md') !!}</p>
    </div>

    <p>Respetado trabajador;</p>

    <p>
        Dando cumplimiento al debido proceso, me permito citarlo el día
        {!! $blank($hearingDay, 'lg') !!}
        a las
        {!! $blank($hearingTime, 'sm') !!}
        horas,
        @if ($blankForDownload)
            en las instalaciones de la empresa SJ Seguridad Privada Ltda. en Cali en la dirección Av. 4 Nte. #26N - 39 B/ San Vicente
        @else
            {{ filled($locationText) ? e($locationText) : '—' }}
        @endif
        con el fin de ejercer su derecho a la defensa para ser escuchado en razón a la apertura del proceso disciplinario.
    </p>

    <p class="ogj-03-section-title">Formulación de cargos</p>

    <p class="ogj-03-justify">
        <span class="ogj-03-underline">Conductas posibles de sanción:</span>
        El presunto incumplimiento de sus obligaciones laborales. Según el informe disciplinario del
        {!! $blank($informeReportDate, 'sm') !!}
        se reporta que el día
        {!! $blank($breachDate, 'sm') !!}:
        @if ($blankForDownload)
            <span class="ogj-03-guide ogj-03-guide-lg" aria-hidden="true">{{ $guidePattern('lg') }}</span>.
        @elseif (filled($chargesDescription))
            {{ $chargesDescription }}.
        @else
            —.
        @endif
        Estos hechos de comprobarse podrían constituir faltas graves y grave incumplimiento de las obligaciones contractuales, legales y reglamentarias, vulnerando lo dispuesto en el Reglamento de Trabajo y contrato laboral.
    </p>

    <p class="ogj-03-underline">Faltas disciplinarias:</p>

    <ul class="ogj-03-list">
        <li>
            Artículo 66, numeral
            @if ($blankForDownload)
                1, 3, 4, 6, 8, 9, 20, 29, 30, 39, 41, 42
            @else
                {{ filled($article66Numerals) ? e($article66Numerals) : '—' }}
            @endif
            , del Reglamento Interno de Trabajo, referente a las obligaciones especiales de los trabajadores
        </li>
        <li>
            Artículo 68, numerales
            @if ($blankForDownload)
                10, 34
            @else
                {{ filled($article68Numerals) ? e($article68Numerals) : '—' }}
            @endif
            , del Reglamento Interno de Trabajo, referente a las prohibiciones de los trabajadores.
        </li>
        <li>
            Artículo 76, numerales
            @if ($blankForDownload)
                3, 12, 15, 22, 25, 36, 64, 98, 103, 112
            @else
                {{ filled($article76Numerals) ? e($article76Numerals) : '—' }}
            @endif
            , del Reglamento Interno de Trabajo, referente a las faltas graves
        </li>
    </ul>

    <p>Los elementos probatorios que dan lugar al inicio del proceso disciplinario radican en:</p>

    <ul class="ogj-03-list">
        <li>
            Informes Disciplinarios
            @if ($blankForDownload)
                del {!! $blank($informeReportDate, 'sm') !!}
            @elseif (filled($informeReportDate))
                del {{ $informeReportDate }}
            @endif
        </li>
    </ul>

    <p>
        Se corre traslado al trabajador de todas y cada una de las pruebas que fundamentan los cargos formulados. Se le hace saber que, el llamamiento a la diligencia de descargos no es propia de sanción disciplinaria, por el contrario, con ella buscamos garantizar el debido proceso, el derecho a la contradicción y a la defensa, conforme lo cual, podrá usted asistir con dos (02) testigos, controvertir las pruebas en su contra y allegar las pruebas que considere pertinentes informando por escrito al correo relacioneslaborales@sjsp.com.co con mínimo dos (02) horas de anticipación a la diligencia. En caso de tener alguna situación que imposibilite su presencia, deberá remitir dentro de los dos (2) días hábiles siguientes, la debida excusa para fijar nueva fecha, de lo contrario se entiende su renuncia al derecho a la defensa y se tendrán por cierto los hechos que motivaron la apertura del presente proceso disciplinario.
    </p>

    <table class="ogj-03-signatures" role="presentation">
        <tr>
            <td><p>Cordialmente;</p></td>
            <td><p>Recibido por;</p></td>
        </tr>
        <tr class="ogj-03-signatures-slot-row">
            <td class="ogj-03-signature-slot">
                @if (! $blankForDownload && filled($signatureDataUri))
                    <img src="{{ $signatureDataUri }}" alt="Firma" class="ogj-03-signature-img">
                @endif
            </td>
            <td class="ogj-03-signature-slot"></td>
        </tr>
        <tr class="ogj-03-signatures-line-row">
            <td><div class="ogj-03-sign-line"></div></td>
            <td><div class="ogj-03-sign-line"></div></td>
        </tr>
        <tr>
            <td><p>Nombre:@if (filled($signerName)) {{ e($signerName) }}@endif</p></td>
            <td><p>Nombre:</p></td>
        </tr>
        <tr>
            <td><p>{{ filled($signerRole) ? e($signerRole) : 'Analista de Relaciones Laborales' }}</p></td>
            <td><p>Cargo:</p></td>
        </tr>
        <tr>
            <td><p>SJ Seguridad Privada Ltda</p></td>
            <td></td>
        </tr>
    </table>
</div>
