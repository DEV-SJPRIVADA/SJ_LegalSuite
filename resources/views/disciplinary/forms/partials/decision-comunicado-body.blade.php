@props([
    'blankForDownload' => true,
    'subject' => '',
    'bodyNarrative' => '',
    'workerName' => '',
    'workerDocument' => '',
    'workerPosition' => '',
    'decisionLabel' => '',
    'suspensionStart' => '',
    'suspensionEnd' => '',
    'reliefNotes' => '',
    'showSuspensionDates' => false,
    'showRelief' => false,
    'notificationDate' => '',
    'notificationShift' => '',
    'notificationZone' => '',
    'supervisorName' => '',
    'issuedDate' => '',
    'lawyerName' => '',
    'placeLine' => null,
    'evidenceType' => 'signed',
    'workerSignatureDataUri' => null,
    'witnesses' => [],
])

@php
    $blank = static function (string $value, int $min = 12): string {
        if (filled($value)) {
            return e($value);
        }

        return '<span class="ogj-blank-line" style="min-width:'.($min * 8).'px">&nbsp;</span>';
    };
@endphp

<div class="ogj-decision-body">
    <div class="ogj-03-recipient">
        <p><strong>Señor(a):</strong> {!! $blank($workerName, 20) !!}</p>
        <p><strong>C.C.:</strong> {!! $blank($workerDocument, 14) !!}</p>
        <p><strong>Cargo:</strong> {!! $blank($workerPosition, 16) !!}</p>
    </div>

    <p class="ogj-decision-subject"><strong>Asunto:</strong> {!! $blank($subject, 24) !!}</p>

    <p>Cordial saludo,</p>

    @if ($blankForDownload)
        <p>Por medio del presente comunicado le informamos la decisión adoptada dentro del proceso disciplinario:</p>
        <p><strong>Decisión:</strong> {!! $blank($decisionLabel, 18) !!}</p>
        @if ($showSuspensionDates)
            <p><strong>Periodo de suspensión:</strong> del {!! $blank($suspensionStart, 10) !!} al {!! $blank($suspensionEnd, 10) !!}.</p>
        @endif
        @if ($showRelief)
            <p><strong>Relevo:</strong> {!! $blank($reliefNotes, 30) !!}</p>
        @endif
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
    @else
        <div class="ogj-decision-narrative">{!! nl2br(e($bodyNarrative)) !!}</div>
        @if ($showSuspensionDates && filled($suspensionStart) && filled($suspensionEnd))
            <p><strong>Periodo de suspensión:</strong> del {{ $suspensionStart }} al {{ $suspensionEnd }}.</p>
        @endif
        @if ($showRelief && filled($reliefNotes))
            <p><strong>Relevo:</strong> {{ $reliefNotes }}</p>
        @endif
    @endif

    @if (! $blankForDownload && filled($notificationDate))
        <p class="ogj-decision-meta">
            Notificación programada: {{ $notificationDate }}
            @if (filled($notificationShift)) · Turno: {{ $notificationShift }} @endif
            @if (filled($notificationZone)) · Zona: {{ $notificationZone }} @endif
            @if (filled($supervisorName)) · Supervisor: {{ $supervisorName }} @endif
        </p>
    @endif

    <p>Atentamente,</p>

    <p class="ogj-decision-signature">
        <strong>{!! $blank($lawyerName, 18) !!}</strong><br>
        Departamento Jurídico<br>
        SJ Seguridad Privada Ltda.
    </p>

    @if (! $blankForDownload && filled($issuedDate))
        <p class="ogj-decision-date">{{ $placeLine ?? $issuedDate }}</p>
    @endif

    @if (! $blankForDownload)
        <table class="ogj-03-signature-grid" role="presentation" style="width:100%; margin-top:1.5rem;">
            <tr>
                <td style="width:50%; vertical-align:top;">
                    <p style="margin:0 0 0.25rem;">Recibido por:</p>
                    @if (($evidenceType ?? 'signed') === 'signed' && filled($workerSignatureDataUri ?? null))
                        <img src="{{ $workerSignatureDataUri }}" alt="Firma del trabajador" class="ogj-03-signature-img" style="max-height:3rem;">
                    @elseif (($evidenceType ?? '') === 'refused_witnesses')
                        <p style="margin:0;"><strong>Se niega a firmar</strong></p>
                    @else
                        <p style="margin:0;">&nbsp;</p>
                    @endif
                    <p style="margin:0.5rem 0 0;"><strong>{{ $workerName }}</strong></p>
                    <p style="margin:0;">C.C. {{ $workerDocument }}</p>
                </td>
                @if (($evidenceType ?? '') === 'refused_witnesses' && is_array($witnesses ?? null))
                    @foreach ($witnesses as $witness)
                        <td style="width:25%; vertical-align:top; padding-left:0.5rem;">
                            <p style="margin:0 0 0.25rem;">Testigo:</p>
                            @if (filled($witness['signatureDataUri'] ?? null))
                                <img src="{{ $witness['signatureDataUri'] }}" alt="Firma testigo" class="ogj-03-signature-img" style="max-height:3rem;">
                            @endif
                            <p style="margin:0.5rem 0 0;"><strong>{{ $witness['name'] ?? '' }}</strong></p>
                            <p style="margin:0;">C.C. {{ $witness['document'] ?? '' }}</p>
                        </td>
                    @endforeach
                @endif
            </tr>
        </table>
    @endif
</div>
