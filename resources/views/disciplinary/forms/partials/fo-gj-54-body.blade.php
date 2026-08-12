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
    'informeReportDateLong' => '',
    'chargesDescription' => '',
    'rescheduleCausePhrase' => '',
    'newHearingDay' => '',
    'newHearingMonth' => '',
    'newHearingYear' => '',
    'newHearingTime' => '',
    'modality' => 'presencial',
    'modalityLocationText' => '',
    'employerName' => '',
    'signatureDataUri' => null,
    'legalPhrasing' => null,
])

@php
    use App\Support\Disciplinary\FoGj03Modality;
    use App\Support\Disciplinary\WorkerLegalPhrasing;

    $legalPhrasing = $legalPhrasing ?? WorkerLegalPhrasing::masculine();
    $isVirtual = (string) $modality === FoGj03Modality::Virtual->value;

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
        horas,
        {{ $legalPhrasing->foGj54ScheduledHearingPhrase() }}
    </p>

    <p class="ogj-03-justify">
        De acuerdo con el informe disciplinario del
        {!! $blank($informeReportDateLong, 'lg') !!},
        se reporta que
        @if ($blankForDownload)
            <span class="ogj-03-guide ogj-03-guide-xl" aria-hidden="true">{{ $guidePattern('xl') }}</span>.
        @elseif (filled($chargesDescription))
            {{ $chargesDescription }}
        @else
            —.
        @endif
    </p>

    <p class="ogj-03-justify">
        Dando cumplimiento al debido proceso y en atención a que la citación inicialmente programada no pudo realizarse debido a
        {!! $blank($rescheduleCausePhrase, 'lg') !!},
        me permito informarle que la diligencia disciplinaria será reprogramada para el día
        {!! $blank($newHearingDay, 'xs') !!}
        de
        {!! $blank($newHearingMonth, 'sm') !!}
        de
        {!! $blank($newHearingYear, 'sm') !!}
        a las
        {!! $blank($newHearingTime, 'sm') !!}
        horas,
        @if ($isVirtual)
            de manera virtual a través de la plataforma Microsoft Teams, a la cual podrá acceder mediante el siguiente enlace:
            {!! $blank($modalityLocationText, 'xl') !!}
        @else
            de manera presencial
            {!! $blank($modalityLocationText, 'xl') !!}
        @endif
    </p>

    <p>De conformidad en lo anterior, se firma por quienes intervienen:</p>

    <table class="ogj-03-signatures" role="presentation">
        <tr>
            <td>
                <p>Representante del empleador,</p>
                <div class="ogj-03-sign-line">
                    @if (! $blankForDownload && filled($signatureDataUri))
                        <img src="{{ $signatureDataUri }}" alt="Firma empleador" class="ogj-03-signature-img">
                    @endif
                </div>
                <p>{!! $blank($employerName, 'md') !!}</p>
                <p>Área Jurídica – SJ Seguridad Privada Ltda.</p>
            </td>
            <td>
                <p>{{ $legalPhrasing->foGj54SignatureSectionLabel() }}</p>
                <div class="ogj-03-sign-line"></div>
                <p>{!! $blank($workerName, 'md') !!}</p>
                <p>{!! $blank($workerPosition, 'md') !!}</p>
            </td>
        </tr>
    </table>
</div>
