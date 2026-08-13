@props([
    'blankForDownload' => true,
    'issuedDateLong' => '',
    'caseNumber' => '',
    'workerName' => '',
    'workerDocument' => '',
    'workerPosition' => '',
    'openingSalutation' => '',
    'bodyParagraph' => '',
    'resolutiveFirst' => '',
    'resolutiveSecond' => '',
    'signerName' => '',
    'signerTitle' => '',
    'signatureDataUri' => null,
    'evidenceType' => 'signed',
    'workerSignatureDataUri' => null,
    'witnesses' => [],
    'legalPhrasing' => null,
    'workerSignatureLead' => '',
])

@php
    use App\Support\Disciplinary\WorkerLegalPhrasing;

    $legalPhrasing = $legalPhrasing instanceof WorkerLegalPhrasing
        ? $legalPhrasing
        : WorkerLegalPhrasing::masculine();

    $openingSalutation = filled($openingSalutation)
        ? (string) $openingSalutation
        : $legalPhrasing->foGj45OpeningSalutation();

    $workerSignatureLead = filled($workerSignatureLead)
        ? (string) $workerSignatureLead
        : $legalPhrasing->foGj45WorkerSignatureLead();

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

<div class="ogj-03-body ogj-45-body">
    <p>{!! $blank($issuedDateLong, 'lg') !!}</p>
    @if (filled($caseNumber) || $blankForDownload)
        <p><strong>{!! $blank($caseNumber, 'md') !!}</strong></p>
    @endif

    <div class="ogj-03-recipient" style="margin-top:0.75rem;">
        <p><strong>NOMBRE.</strong> {!! $blank($workerName, 'lg') !!}</p>
        <p><strong>CEDULA.</strong> {!! $blank($workerDocument, 'md') !!}</p>
        <p><strong>CARGO.</strong> {!! $blank($workerPosition, 'md') !!}</p>
    </div>

    <p style="margin-top:0.85rem;">{{ $openingSalutation }}</p>

    <p class="ogj-03-justify" style="margin-top:0.75rem;">
        @if ($blankForDownload)
            <span class="ogj-03-guide ogj-03-guide-xl" aria-hidden="true">{{ $guidePattern('xl') }}</span>
            <span class="ogj-03-guide ogj-03-guide-xl" aria-hidden="true">{{ $guidePattern('xl') }}</span>
        @elseif (filled($bodyParagraph))
            {!! nl2br(e($bodyParagraph)) !!}
        @else
            —
        @endif
    </p>

    <p class="ogj-03-justify" style="margin-top:0.85rem;">
        <strong>PRIMERO:</strong> {!! $blank($resolutiveFirst, 'xl') !!}
    </p>
    <p class="ogj-03-justify" style="margin-top:0.35rem;">
        <strong>SEGUNDO:</strong> {!! $blank($resolutiveSecond, 'xl') !!}
    </p>

    <p class="ogj-03-justify" style="margin-top:0.85rem;">
        La anterior decisión da por terminada la investigación disciplinaria en su contra, teniendo en cuenta que no existe causal que amerite la aplicación de sanción disciplinaria.
    </p>

    <p class="ogj-03-justify" style="margin-top:0.75rem;">
        Nuestro propósito es continuar contando con su colaboración para la prestación de un adecuado servicio, y que nos acompañe en el continuo proceso de crecimiento, procurando trabajar en ello a partir del cumplimiento de procedimientos, que, como el presente, permitan dar cumplimiento a las consignas que caracterizan la excelencia de la empresa.
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
                <p><strong>Nombre:</strong> {!! $blank($signerName, 'md') !!}</p>
                <p><strong>{!! $blank($signerTitle, 'md') !!}</strong></p>
                <p>SJ Seguridad Privada Ltda.</p>
            </td>
            <td style="width:50%; vertical-align:top;">
                <p>{{ $workerSignatureLead }}</p>
                <div class="ogj-03-sign-line">
                    @if (! $blankForDownload && ($evidenceType ?? 'signed') === 'signed' && filled($workerSignatureDataUri ?? null))
                        <img src="{{ $workerSignatureDataUri }}" alt="Firma del trabajador" class="ogj-03-signature-img">
                    @elseif (! $blankForDownload && ($evidenceType ?? '') === 'refused_witnesses')
                        <p style="margin:0;"><strong>Se niega a firmar</strong></p>
                    @endif
                </div>
                <p><strong>Nombre:</strong> {!! $blank($workerName, 'md') !!}</p>
                <p><strong>Cargo:</strong> {!! $blank($workerPosition, 'md') !!}</p>
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
