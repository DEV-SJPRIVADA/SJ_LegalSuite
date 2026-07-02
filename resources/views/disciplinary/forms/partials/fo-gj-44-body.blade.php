@props([
    'blankForDownload' => true,
    'fecha' => '',
    'workerName' => '',
    'citationSinceDay' => '',
    'citationSinceMonth' => '',
    'citationSinceYearSuffix' => '',
    'hearingDay' => '',
    'hearingMonth' => '',
    'hearingYearSuffix' => '',
    'hearingTime' => '',
    'allegedOmission' => '',
    'workerPosition' => '',
    'signDay' => '',
    'signMonth' => '',
    'signYearSuffix' => '',
    'signTime' => '',
    'employerName' => '',
    'signatureDataUri' => null,
    'witness1Name' => '',
    'witness1Cargo' => '',
    'witness1Date' => '',
    'witness2Name' => '',
    'witness2Cargo' => '',
    'witness2Date' => '',
])

@php
    $guidePattern = static fn (string $size): string => match ($size) {
        'xs' => '_ _ _',
        'sm' => '_ _ _ _ _ _',
        'md' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        'lg' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        'xl' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        'xxl' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
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

<div class="ogj-03-body ogj-44-body">
    <div class="ogj-03-ref">
        <p>Fecha: {!! $blank($fecha, 'lg') !!}</p>
    </div>

    <p class="ogj-44-subject"><strong>Asunto:</strong> constancia de inasistencia a diligencia disciplinaria</p>

    <p>
        Se deja expresa constancia, que a pesar de haberse realizado la citación en debida forma a el Sr
        {!! $blank($workerName, 'lg') !!}
        desde el pasado
        {!! $blank($citationSinceDay, 'xs') !!}
        de
        {!! $blank($citationSinceMonth, 'sm') !!}
        de 202
        {!! $blank($citationSinceYearSuffix, 'xs') !!}
        , no compareció, ni justificó su inasistencia, a la toma de la diligencia disciplinaria programada para el día
        {!! $blank($hearingDay, 'xs') !!}
        de
        {!! $blank($hearingMonth, 'sm') !!}
        de 202
        {!! $blank($hearingYearSuffix, 'xs') !!}
        a las
        {!! $blank($hearingTime, 'sm') !!}
        horas con el fin de ser escuchado sobre la presunta omisión de:
        @if (filled($allegedOmission))
            {{ $allegedOmission }}
        @else
            <span class="ogj-44-omission-guide ogj-03-guide ogj-03-guide-xxl" aria-hidden="true">{{ $guidePattern('xxl') }}</span>
        @endif
        , desentendiendo su responsabilidad ante su cargo como
        {!! $blank($workerPosition, 'lg') !!}
        , siendo este, un incumplimiento a sus deberes y/o obligaciones establecidas en el Reglamento Interno, contrato laboral, Código Sustantivo de Trabajo, consignas generales y particulares, procedimientos internos, entre otros.
    </p>

    <p>
        Conforme a lo anterior, se firma a los
        {!! $blank($signDay, 'xs') !!}
        días del mes de
        {!! $blank($signMonth, 'sm') !!}
        de 202
        {!! $blank($signYearSuffix, 'xs') !!}
        , siendo las
        {!! $blank($signTime, 'sm') !!}
        horas
    </p>

    <div class="ogj-44-employer-sign">
        <p>Representante del Empleador;</p>
        <div class="ogj-03-sign-line">
            @if (! $blankForDownload && filled($signatureDataUri))
                <img src="{{ $signatureDataUri }}" alt="Firma empleador" class="ogj-03-signature-img">
            @endif
        </div>
        <p><strong>Nombre:</strong> {!! $blank($employerName, 'lg') !!}</p>
        <p>Área Jurídica -SJ Seguridad Privada Ltda.</p>
    </div>

    <table class="ogj-03-signatures ogj-44-witnesses" role="presentation">
        <tr>
            <td>
                <p>Testigo 1,</p>
                <div class="ogj-03-sign-line"></div>
                <p>Nombre: {!! $blank($witness1Name, 'md') !!}</p>
                <p>Cargo: {!! $blank($witness1Cargo, 'md') !!}</p>
                <p>Fecha: {!! $blank($witness1Date, 'md') !!}</p>
            </td>
            <td>
                <p>Testigo 2,</p>
                <div class="ogj-03-sign-line"></div>
                <p>Nombre: {!! $blank($witness2Name, 'md') !!}</p>
                <p>Cargo: {!! $blank($witness2Cargo, 'md') !!}</p>
                <p>Fecha: {!! $blank($witness2Date, 'md') !!}</p>
            </td>
        </tr>
    </table>
</div>
