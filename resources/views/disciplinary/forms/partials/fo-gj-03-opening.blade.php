@php
    $displayCaseNumber = filled($caseNumber ?? null)
        ? $caseNumber
        : (filled($expedienteGj ?? null) ? 'GJ-PD:'.$expedienteGj : '');

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

<div class="ogj-03-ref">
    <p>Fecha: {!! $blank($fecha ?? '', 'lg') !!}</p>
    <p>{!! $blank($displayCaseNumber, 'lg') !!}</p>
</div>

<div class="ogj-03-recipient">
    <p>NOMBRE. {!! $blank($workerName ?? '', 'lg') !!}</p>
    <p>CÉDULA. {!! $blank($workerDocument ?? '', 'md') !!}</p>
    <p>CARGO. {!! $blank($workerPosition ?? '', 'md') !!}</p>
</div>

<p>Respetado trabajador;</p>

<p>
    Dando cumplimiento al debido proceso, me permito citarlo el día
    {!! $blank($hearingDay ?? '', 'lg') !!}
    a las
    {!! $blank($hearingTime ?? '', 'sm') !!}
    horas,
    @if ($blankForDownload ?? true)
        en las instalaciones de la empresa SJ Seguridad Privada Ltda. en Cali en la dirección Av. 4 Nte. #26N - 39 B/ San Vicente
    @else
        {{ filled($locationText ?? null) ? e($locationText) : '—' }}
    @endif
    con el fin de ejercer su derecho a la defensa para ser escuchado en razón a la apertura del proceso disciplinario.
</p>
