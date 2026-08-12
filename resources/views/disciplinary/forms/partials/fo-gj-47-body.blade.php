@props([
    'blankForDownload' => true,
    'issuedDateLong' => '',
    'caseNumber' => '',
    'workerName' => '',
    'workerDocument' => '',
    'workerPosition' => '',
    'openingSalutation' => '',
    'openingNarrative' => '',
    'daysPhrase' => '',
    'notifyWorkerPhrase' => '',
    'startLong' => '',
    'endLong' => '',
    'returnLong' => '',
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
    'embeddedLogoSrc' => null,
])

@php
    use App\Support\Disciplinary\WorkerLegalPhrasing;

    $legalPhrasing = $legalPhrasing instanceof WorkerLegalPhrasing
        ? $legalPhrasing
        : WorkerLegalPhrasing::masculine();

    $openingSalutation = filled($openingSalutation)
        ? (string) $openingSalutation
        : $legalPhrasing->foGj47OpeningSalutation();

    $notifyWorkerPhrase = filled($notifyWorkerPhrase)
        ? (string) $notifyWorkerPhrase
        : $legalPhrasing->foGj47NotifyWorkerPhrase();

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

    $logoSrc = $embeddedLogoSrc ?? '';
@endphp

@include('disciplinary.forms.partials.official-letter-pdf-styles')
<style>
    .ogj-page-break { page-break-before: always; break-before: page; }
    .ogj-47-resolve { margin: 0.65rem 0 0.35rem; font-weight: 700; }
    .ogj-47-item { margin: 0.35rem 0 0.55rem; }
</style>

<div class="ogj-wrap ogj-47-doc">
    {{-- Página 1 --}}
    <div class="ogj-page">
        <table class="ogj-tbl ogj-head-grid" role="presentation">
            <colgroup>
                <col style="width:25%">
                <col style="width:50%">
                <col style="width:25%">
            </colgroup>
            <tbody>
                <tr>
                    <td class="ogj-logo-cell">
                        @if (filled($logoSrc))
                            <img src="{{ $logoSrc }}" alt="SJ Seguridad">
                        @endif
                    </td>
                    <td class="ogj-title">SUSPENSIÓN DISCIPLINARIA</td>
                    <td class="ogj-meta">
                        <table class="ogj-meta-grid" role="presentation">
                            <tr><td class="ogj-meta-code">FO-GJ-47</td></tr>
                            <tr><td>Noviembre de 2023</td></tr>
                            <tr><td>Versión 02</td></tr>
                            <tr><td>Página 1 de 2</td></tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="ogj-03-body ogj-47-body">
            <p>{!! $blank($issuedDateLong, 'lg') !!}</p>
            <p><strong>{!! $blank($caseNumber, 'md') !!}</strong></p>

            <div class="ogj-03-recipient" style="margin-top:0.75rem;">
                <p><strong>NOMBRE:</strong> {!! $blank($workerName, 'lg') !!}</p>
                <p><strong>CEDULA:</strong> {!! $blank($workerDocument, 'md') !!}</p>
                <p><strong>CARGO:</strong> {!! $blank($workerPosition, 'md') !!}</p>
            </div>

            <p style="margin-top:0.75rem;">
                <strong>Asunto:</strong> cierre de proceso disciplinario – suspensión de contrato laboral
            </p>

            <p style="margin-top:0.65rem;">{{ $openingSalutation }}</p>

            <p class="ogj-03-justify" style="margin-top:0.65rem;">
                @if ($blankForDownload)
                    <span class="ogj-03-guide ogj-03-guide-xl" aria-hidden="true">{{ $guidePattern('xl') }}</span>
                    <span class="ogj-03-guide ogj-03-guide-xl" aria-hidden="true">{{ $guidePattern('xl') }}</span>
                @elseif (filled($openingNarrative))
                    {!! nl2br(e($openingNarrative)) !!}
                @else
                    —
                @endif
            </p>

            <p class="ogj-47-resolve ogj-03-justify">
                Conforme a lo anterior y en aplicación a las sanciones disciplinarias, esta dirección RESOLVIÓ:
            </p>

            <p class="ogj-47-item ogj-03-justify">
                <strong>PRIMERO:</strong> Suspensión de su cargo, por el término de
                <strong>{!! $blank($daysPhrase, 'md') !!}</strong>.
            </p>

            <p class="ogj-47-item ogj-03-justify">
                <strong>SEGUNDO:</strong> {{ $notifyWorkerPhrase }} que la presente decisión se cumplirá a partir del
                <strong>{!! $blank($startLong, 'md') !!}</strong>
                hasta el
                <strong>{!! $blank($endLong, 'md') !!}</strong>,
                debiendo retomar a labores el próximo
                <strong>{!! $blank($returnLong, 'md') !!}</strong>.
            </p>

            <p class="ogj-03-justify" style="margin-top:0.75rem;">
                {{ $legalPhrasing->foGj47SuspensionEffectParagraph() }}
            </p>

            <p class="ogj-03-justify">
                {{ $legalPhrasing->foGj47FactsAnalysisParagraph() }}
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
                {{ $legalPhrasing->foGj47PostArticlesClosingParagraph() }}
            </p>

            <p class="ogj-03-justify">
                {{ $legalPhrasing->foGj47AppealParagraph() }}
            </p>
        </div>
    </div>

    {{-- Página 2 --}}
    <div class="ogj-page ogj-page-break">
        <table class="ogj-tbl ogj-head-grid" role="presentation">
            <colgroup>
                <col style="width:25%">
                <col style="width:50%">
                <col style="width:25%">
            </colgroup>
            <tbody>
                <tr>
                    <td class="ogj-logo-cell">
                        @if (filled($logoSrc))
                            <img src="{{ $logoSrc }}" alt="SJ Seguridad">
                        @endif
                    </td>
                    <td class="ogj-title">SUSPENSIÓN DISCIPLINARIA</td>
                    <td class="ogj-meta">
                        <table class="ogj-meta-grid" role="presentation">
                            <tr><td class="ogj-meta-code">FO-GJ-47</td></tr>
                            <tr><td>Noviembre de 2023</td></tr>
                            <tr><td>Versión 02</td></tr>
                            <tr><td>Página 2 de 2</td></tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="ogj-03-body ogj-47-body">
            <table class="ogj-03-signatures" role="presentation" style="margin-top:1.75rem;">
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
    </div>
</div>
