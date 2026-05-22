@props([
    'blankForDownload' => true,
    'fecha' => '',
    'expedienteGj' => '',
    'workerName' => '',
    'workerDocument' => '',
    'workerPosition' => '',
    'hearingDay' => '',
    'hearingTime' => '',
    'conductMonth' => '',
    'conductDays' => '',
    'informeSignedBy' => '',
])

@php
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
        <p>GJ- {!! $blank($expedienteGj, 'lg') !!}</p>
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
        horas, en las instalaciones de la empresa SJ Seguridad Privada Ltda. en Cali en la dirección Av. 4 Nte. #26N - 39 B/ San Vicente con el fin de ejercer su derecho a la defensa para ser escuchado en razón a la apertura del proceso disciplinario.
    </p>

    <p class="ogj-03-section-title">Formulación de cargos</p>

    <p>
        <span class="ogj-03-underline">Conductas posibles de sanción:</span>
        los presuntos incumplimientos a consignas, en el mes de
        @if (filled($conductMonth))
            {{ $conductMonth }}
        @else
            <span class="ogj-03-guide ogj-03-guide-sm" aria-hidden="true">{{ $guidePattern('sm') }}</span>
        @endif
        , los días
        @if (filled($conductDays))
            {{ $conductDays }}
        @else
            <span class="ogj-03-guide ogj-03-guide-md" aria-hidden="true">{{ $guidePattern('md') }}</span>
        @endif
        del presente año, fechas en las cuales, usted no diligencio en el libro de la minuta por las rondas realizadas al dispositivo durante su jornada laboral de dichas fechas anteriormente indicadas sin autorización alguna. Hecho que está comprendido:
    </p>

    <p class="ogj-03-underline">Faltas disciplinarias:</p>

    <ul class="ogj-03-list">
        <li>Artículo 66, numeral 1, 3, 4, 6, 8, 9, 20, 29, 30, 39, 41, 42, del Reglamento Interno de Trabajo, referente a las obligaciones especiales de los trabajadores</li>
        <li>Artículo 68, numerales 10, 34, parágrafo primero numeral 4 y 5 del Reglamento Interno de Trabajo, referente a las prohibiciones de los trabajadores.,</li>
        <li>Artículo 76, numerales 3, 12, 15, 22, 25, 36, 64, 98, 103, 112, del Reglamento Interno de Trabajo, referente a las faltas graves</li>
    </ul>

    <p>Los elementos probatorios que dan lugar al inicio del proceso disciplinario radican en:</p>

    <ul class="ogj-03-list">
        <li>Informes Disciplinarios suscrito por {!! $blank($informeSignedBy, 'md') !!}</li>
    </ul>

    <p>
        Se corre traslado al trabajador de todas y cada una de las pruebas que fundamentan los cargos formulados. Se le hace saber que, el llamamiento a la diligencia de descargos no es propia de sanción disciplinaria, por el contrario, con ella buscamos garantizar el debido proceso, el derecho a la contradicción y a la defensa, conforme lo cual, podrá usted asistir con dos (02) testigos, controvertir las pruebas en su contra y allegar las pruebas que considere pertinentes informando por escrito al correo relacioneslaborales@sjsp.com.co con mínimo dos (02) horas de anticipación a la diligencia. En caso de tener alguna situación que imposibilite su presencia, deberá remitir dentro de los dos (2) días hábiles siguientes, la debida excusa para fijar nueva fecha, de lo contrario se entiende su renuncia al derecho a la defensa y se tendrán por cierto los hechos que motivaron la apertura del presente proceso disciplinario.
    </p>

    <table class="ogj-03-signatures" role="presentation">
        <tr>
            <td>
                <p>Cordialmente;</p>
                <div class="ogj-03-sign-line"></div>
                <p>Nombre:</p>
                <p>Analista de Relaciones Laborales</p>
                <p>SJ Seguridad Privada Ltda</p>
            </td>
            <td>
                <p>Recibido por;</p>
                <div class="ogj-03-sign-line"></div>
                <p>Nombre:</p>
                <p>Cargo:</p>
            </td>
        </tr>
    </table>
</div>
