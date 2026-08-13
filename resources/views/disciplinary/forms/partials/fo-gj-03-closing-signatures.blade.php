@php
    $blankForDownload = (bool) ($blankForDownload ?? true);
    $evidenceType = (string) ($evidenceType ?? 'signed');
    $witnesses = is_array($witnesses ?? null) ? $witnesses : [];
@endphp

<div class="ogj-03-closing-block">
    <table class="ogj-03-signatures" role="presentation">
        <tr>
            <td><p>Cordialmente;</p></td>
            <td><p>Recibido por;</p></td>
        </tr>
        <tr class="ogj-03-signatures-capture-row">
            <td>
                <table class="ogj-03-signature-slot-table" role="presentation">
                    <tr>
                        <td class="ogj-03-signature-slot">
                            @if (! $blankForDownload && filled($signatureDataUri ?? null))
                                <img src="{{ $signatureDataUri }}" alt="Firma" class="ogj-03-signature-img">
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="ogj-03-sign-line"></td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="ogj-03-signature-slot-table" role="presentation">
                    <tr>
                        <td class="ogj-03-signature-slot">
                            @if (! $blankForDownload && $evidenceType === 'refused_witnesses')
                                <p class="ogj-03-refusal-text">Se niega a firmar</p>
                            @elseif (! $blankForDownload && filled($workerSignatureDataUri ?? null))
                                <img src="{{ $workerSignatureDataUri }}" alt="Firma del trabajador" class="ogj-03-signature-img">
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="ogj-03-sign-line"></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td><p>Nombre:@if (filled($signerName ?? null)) {{ e($signerName) }}@endif</p></td>
            <td>
                <p>Nombre:
                    @if (! $blankForDownload && filled($workerName ?? null) && in_array($evidenceType, ['signed', 'refused_witnesses'], true))
                        {{ e($workerName) }}
                    @endif
                </p>
                @if (! $blankForDownload && $evidenceType === 'signed' && filled($workerDocument ?? null))
                    <p>C.C. {{ e($workerDocument) }}</p>
                @endif
            </td>
        </tr>
        <tr>
            <td><p>{{ filled($signerRole ?? null) ? e($signerRole) : 'Analista de Relaciones Laborales' }}</p></td>
            <td>
                <p>Cargo:
                    @if (! $blankForDownload && filled($workerPosition ?? null) && in_array($evidenceType, ['signed', 'refused_witnesses'], true))
                        {{ e($workerPosition) }}
                    @endif
                </p>
            </td>
        </tr>
        <tr>
            <td><p>SJ Seguridad Privada Ltda</p></td>
            <td></td>
        </tr>
    </table>

    @if (! $blankForDownload && $evidenceType === 'refused_witnesses')
        <table class="ogj-03-signatures ogj-03-witnesses" role="presentation">
            <tr>
                @foreach (array_slice($witnesses, 0, 2) as $witness)
                    <td>
                        <p>Testigo</p>
                        <table class="ogj-03-signature-slot-table" role="presentation">
                            <tr>
                                <td class="ogj-03-signature-slot">
                                    @if (filled($witness['signatureDataUri'] ?? null))
                                        <img src="{{ $witness['signatureDataUri'] }}" alt="Firma testigo" class="ogj-03-signature-img">
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="ogj-03-sign-line"></td>
                            </tr>
                        </table>
                        <p>Nombre:@if (filled($witness['name'] ?? null)) {{ e($witness['name']) }}@endif</p>
                        <p>Cédula:@if (filled($witness['document'] ?? null)) {{ e($witness['document']) }}@endif</p>
                    </td>
                @endforeach
                @for ($i = count($witnesses); $i < 2; $i++)
                    <td>
                        <p>Testigo</p>
                        <table class="ogj-03-signature-slot-table" role="presentation">
                            <tr>
                                <td class="ogj-03-signature-slot"></td>
                            </tr>
                            <tr>
                                <td class="ogj-03-sign-line"></td>
                            </tr>
                        </table>
                        <p>Nombre:</p>
                        <p>Cédula:</p>
                    </td>
                @endfor
            </tr>
        </table>
    @endif
</div>
