@php
    $blankForDownload = $blankForDownload ?? false;
    $blankField = static function (?string $value, string $placeholder) use ($blankForDownload): string {
        if (filled($value)) {
            return e($value);
        }

        return $blankForDownload
            ? '<span class="comite-blank-line">'.$placeholder.'</span>'
            : '';
    };
    $openingLine = filled($meetingPlaceLine ?? '')
        ? e($meetingPlaceLine)
        : ($blankForDownload
            ? '<span class="comite-blank-line">Santiago de Cali, ___ de _____________ de ____</span>'
            : '');
    $narrativeContent = filled($decisionNarrative ?? '')
        ? e($decisionNarrative)
        : ($blankForDownload
            ? '<span class="comite-blank-line">______________________________________________________________________________</span>'."\n"
                .'<span class="comite-blank-line">______________________________________________________________________________</span>'."\n"
                .'<span class="comite-blank-line">______________________________________________________________________________</span>'."\n"
                .'<span class="comite-blank-line">______________________________________________________________________________</span>'
            : '');
@endphp

<div class="comite-body">
    @if ($usesLetterhead ?? false)
        <p class="comite-opening">{!! $openingLine !!}</p>
        <p class="comite-company">{{ $companyLegalName }}</p>
        <p class="comite-meta"><strong>ACTA:</strong> {!! $blankField($actaNumber, '________________') !!}</p>
        <p class="comite-meta comite-meta-caso"><strong>CASO:</strong> {!! $blankField($caseNumber, '________________') !!}</p>
        <p class="comite-meta comite-meta-asunto"><strong>ASUNTO:</strong> {{ $actaSubject }}</p>
    @else
        <p class="comite-meta">
            <strong>Expediente:</strong> {!! $blankField($caseNumber, '________________') !!}
            @if (! empty($meetingPlaceLine) || $blankForDownload)
                &nbsp;·&nbsp;
                <strong>Fecha del comité:</strong> {!! $openingLine ?: $blankField($meetingPlaceLine ?? '', '___ / ___ / ____') !!}
            @endif
        </p>
    @endif

    <p class="comite-meta comite-meta-decision">
        <strong>Decisión / acuerdo del comité:</strong>
    </p>
    <div class="comite-narrative">{!! $narrativeContent !!}</div>

    <p class="comite-meta comite-signatures-heading"><strong>Integrantes del comité:</strong></p>
    <div class="comite-signatures">
        @foreach ($attendees as $attendee)
            <div class="comite-signature-block">
                <p class="comite-signature-label">Firma:</p>
                <div class="comite-signature-slot">
                    @if (! empty($attendee['signature_data_uri']))
                        <img src="{{ $attendee['signature_data_uri'] }}" alt="Firma">
                    @endif
                </div>
                <div class="comite-signature-line" aria-hidden="true"></div>
                <p class="comite-signature-field"><strong>Nombre:</strong> {!! $blankField($attendee['name'] ?? '', '________________________________') !!}</p>
                <p class="comite-signature-field"><strong>Cargo:</strong> {!! $blankField($attendee['cargo'] ?? '', '________________________________') !!}</p>
            </div>
        @endforeach
    </div>
</div>
