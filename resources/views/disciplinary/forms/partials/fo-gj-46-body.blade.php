@props([
    'blankForDownload' => true,
    'issuedDateLong' => '',
    'caseNumber' => '',
    'workerName' => '',
    'workerDocument' => '',
    'workerPosition' => '',
    'hearingLeadPhrase' => '',
    'postHearingBridge' => '',
    'hearingLead' => '',
    'modalityLabel' => '',
    'hearingDay' => '',
    'hearingMonth' => '',
    'hearingYear' => '',
    'factsNarrative' => '',
    'breachDay' => '',
    'breachMonth' => '',
    'breachYear' => '',
    'articles55' => '',
    'articles57' => '',
    'articles60' => '',
    'signerName' => '',
    'signerTitle' => '',
    'signatureDataUri' => null,
    'evidenceType' => 'signed',
    'workerSignatureDataUri' => null,
    'witnesses' => [],
    'legalPhrasing' => null,
])

@php
    use App\Support\Disciplinary\WorkerLegalPhrasing;

    $legalPhrasing = $legalPhrasing instanceof WorkerLegalPhrasing
        ? $legalPhrasing
        : WorkerLegalPhrasing::masculine();

    $hearingLeadEnum = \App\Support\Disciplinary\FoGj46HearingLead::tryFrom((string) $hearingLead);
    $postHearingBridge = filled($postHearingBridge)
        ? (string) $postHearingBridge
        : $legalPhrasing->foGj46PostHearingBridge($hearingLeadEnum);

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

        return '<span class="ogj-03-guide ogj-03-guide-'.$size.'" aria-hidden="true">'.$guidePattern($size).'</span>';
    };
@endphp

<div class="ogj-03-body ogj-46-body">
    <p>{!! $blank($issuedDateLong, 'lg') !!}</p>
    <p><strong>{!! $blank($caseNumber, 'md') !!}</strong></p>

    <div class="ogj-03-recipient" style="margin-top:0.75rem;">
        <p><strong>NOMBRE:</strong> {!! $blank($workerName, 'lg') !!}</p>
        <p><strong>CEDULA:</strong> {!! $blank($workerDocument, 'md') !!}</p>
        <p><strong>CARGO:</strong> {!! $blank($workerPosition, 'md') !!}</p>
    </div>

    <p style="margin-top:0.75rem;">
        <strong>Asunto:</strong> cierre de proceso disciplinario – llamado de atención por escrito.
    </p>

    <p class="ogj-03-justify" style="margin-top:0.85rem;">
        Por medio del presente comunicado, me permito informarle que, en el marco del proceso disciplinario adelantado en su contra
        {!! $blank($hearingLeadPhrase, 'md') !!}
        diligencia disciplinaria de manera
        {!! $blank($modalityLabel, 'sm') !!}
        el día
        {!! $blank($hearingDay, 'xs') !!}
        de
        {!! $blank($hearingMonth, 'sm') !!}
        del
        {!! $blank($hearingYear, 'sm') !!},
        {{ $postHearingBridge }}
        {!! $blank($breachDay, 'xs') !!}
        de
        {!! $blank($breachMonth, 'sm') !!}
        de
        {!! $blank($breachYear, 'sm') !!}
        @if ($blankForDownload)
            <span class="ogj-03-guide ogj-03-guide-xl" aria-hidden="true">{{ $guidePattern('xl') }}</span>
        @elseif (filled($factsNarrative))
            {!! nl2br(e($factsNarrative)) !!}
        @else
            —
        @endif
        En consecuencia, y en aplicación de las sanciones disciplinarias correspondientes, esta Dirección ha resuelto imponerle un: <strong>LLAMADO DE ATENCIÓN ESCRITO</strong>.
    </p>

    <p class="ogj-03-justify" style="margin-top:0.75rem;">
        <strong>Fundamento jurídico:</strong> Esta decisión se fundamenta en el Reglamento de Trabajo, en especial:
    </p>
    <ul class="ogj-03-justify" style="margin:0.35rem 0 0.75rem 1.25rem; padding:0;">
        <li>Artículo 55 (Obligaciones especiales): numerales {!! $blank($articles55, 'lg') !!}.</li>
        <li>Artículo 57 (Prohibiciones): numerales {!! $blank($articles57, 'lg') !!}.</li>
        <li>Artículo 60 (Faltas graves): numerales {!! $blank($articles60, 'lg') !!}.</li>
    </ul>

    <p class="ogj-03-justify">
        {{ $legalPhrasing->foGj46ExhortationParagraph1() }}
    </p>

    <p class="ogj-03-justify">
        {{ $legalPhrasing->foGj46ExhortationParagraph2() }}
    </p>

    <table class="ogj-03-signatures" role="presentation" style="margin-top:1.5rem;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <p>Cordialmente,</p>
                <div class="ogj-03-sign-line">
                    @if (! $blankForDownload && filled($signatureDataUri))
                        <img src="{{ $signatureDataUri }}" alt="Firma emisor" class="ogj-03-signature-img">
                    @endif
                </div>
                <p><strong>{!! $blank($signerName, 'md') !!}</strong></p>
                <p>{!! $blank($signerTitle, 'md') !!}</p>
                <p>SJ Seguridad Privada Ltda.</p>
            </td>
            <td style="width:50%; vertical-align:top;">
                <p>Recibido por,</p>
                <div class="ogj-03-sign-line">
                    @if (! $blankForDownload && ($evidenceType ?? 'signed') === 'signed' && filled($workerSignatureDataUri ?? null))
                        <img src="{{ $workerSignatureDataUri }}" alt="Firma del trabajador" class="ogj-03-signature-img">
                    @elseif (! $blankForDownload && ($evidenceType ?? '') === 'refused_witnesses')
                        <p style="margin:0;"><strong>Se niega a firmar</strong></p>
                    @endif
                </div>
                <p><strong>{!! $blank($workerName, 'md') !!}</strong></p>
                <p>{!! $blank($workerPosition, 'md') !!}</p>
                <p>Fecha de recibido: ________________</p>
                @if (! $blankForDownload && ($evidenceType ?? '') === 'refused_witnesses' && is_array($witnesses ?? null))
                    @foreach ($witnesses as $witness)
                        <div style="margin-top:0.75rem;">
                            @if (filled($witness['signatureDataUri'] ?? null))
                                <img src="{{ $witness['signatureDataUri'] }}" alt="Firma testigo" class="ogj-03-signature-img" style="max-height:2.5rem;">
                            @endif
                            <p style="margin:0.15rem 0 0;"><strong>{{ $witness['name'] ?? '' }}</strong></p>
                            <p style="margin:0;">C.C. {{ $witness['document'] ?? '' }}</p>
                        </div>
                    @endforeach
                @endif
            </td>
        </tr>
    </table>
</div>
