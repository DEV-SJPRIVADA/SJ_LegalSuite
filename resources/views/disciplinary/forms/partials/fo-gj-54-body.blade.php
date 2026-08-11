@props([
    'blankForDownload' => true,
    'fecha' => '',
    'workerName' => '',
    'workerDocument' => '',
    'workerPosition' => '',
    'originalHearingDay' => '',
    'originalHearingMonth' => '',
    'originalHearingYear' => '',
    'originalHearingTime' => '',
    'factsDay' => '',
    'factsMonth' => '',
    'clientSite' => '',
    'shiftStart' => '',
    'shiftEnd' => '',
    'newHearingDay' => '',
    'newHearingMonth' => '',
    'newHearingYear' => '',
    'newHearingTime' => '',
    'newHearingPlace' => '',
    'employerName' => '',
    'signatureDataUri' => null,
    'legalPhrasing' => null,
])

@php
    use App\Support\Disciplinary\WorkerLegalPhrasing;

    $legalPhrasing = $legalPhrasing ?? WorkerLegalPhrasing::masculine();

    $guidePattern = static fn (string $size): string => match ($size) {
        'xs' => '_ _ _',
        'sm' => '_ _ _ _ _ _',
        'md' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        'lg' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        'xl' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
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

<div class="ogj-03-body ogj-54-body">
    <div class="ogj-03-ref">
        <p>Fecha: {!! $blank($fecha, 'lg') !!}</p>
    </div>

    <div class="ogj-03-recipient">
        <p>NOMBRE: {!! $blank($workerName, 'lg') !!}</p>
        <p class="ogj-54-recipient-cedula">CÉDULA: {!! $blank($workerDocument, 'md') !!}</p>
        <p>CARGO: {!! $blank($workerPosition, 'md') !!}</p>
    </div>

    <p>{{ $legalPhrasing->foGj54OpeningSalutation() }}</p>

    <p>
        Para el día
        {!! $blank($originalHearingDay, 'xs') !!}
        de
        {!! $blank($originalHearingMonth, 'sm') !!}
        de
        {!! $blank($originalHearingYear, 'sm') !!}
        a las
        {!! $blank($originalHearingTime, 'sm') !!}
        {{ $legalPhrasing->foGj54ScheduledHearingPhrase() }}
        {!! $blank($factsDay, 'xs') !!}
        de
        {!! $blank($factsMonth, 'sm') !!}
        del presente año, cuando presuntamente usted no presentó a laborar a las instalaciones del cliente
        {!! $blank($clientSite, 'xl') !!}
        a su turno laboral de
        {!! $blank($shiftStart, 'sm') !!}
        a
        {!! $blank($shiftEnd, 'sm') !!}
        horas, generando así, traumatismo en la operación al no haber reportado a tiempo el cambio de turno establecido.
    </p>

    <p>
        Dando cumplimiento al debido proceso me permito informarle que por temas operativos de la compañía la diligencia disciplinaria será reprogramada para el día
        {!! $blank($newHearingDay, 'xs') !!}
        de
        {!! $blank($newHearingMonth, 'sm') !!}
        de
        {!! $blank($newHearingYear, 'sm') !!}
        a las
        {!! $blank($newHearingTime, 'sm') !!}
        horas,
        {!! $blank($newHearingPlace, 'xl') !!}
        con el fin de ejercer su derecho a la defensa para {{ $legalPhrasing->foGj03DefenseHearingPhrase() }} en razón a la apertura del proceso disciplinario.
    </p>

    <p>De conformidad en lo anterior, se firma por quienes intervienen:</p>

    <table class="ogj-03-signatures" role="presentation">
        <tr>
            <td>
                <p>Representantes del empleador;</p>
                <div class="ogj-03-sign-line">
                    @if (! $blankForDownload && filled($signatureDataUri))
                        <img src="{{ $signatureDataUri }}" alt="Firma empleador" class="ogj-03-signature-img">
                    @endif
                </div>
                <p>Nombre: {!! $blank($employerName, 'md') !!}</p>
                <p>Área Jurídica – SJ Seguridad Privada Ltda.</p>
            </td>
            <td>
                <p>{{ $legalPhrasing->foGj54SignatureSectionLabel() }}</p>
                <div class="ogj-03-sign-line"></div>
                <p>Nombre: {!! $blank($workerName, 'md') !!}</p>
                <p>Cargo: {!! $blank($workerPosition, 'md') !!}</p>
            </td>
        </tr>
    </table>
</div>
