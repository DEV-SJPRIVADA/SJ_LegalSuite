<div class="comite-body">
    @if ($usesLetterhead ?? false)
        <p class="comite-opening">{{ $meetingPlaceLine }}</p>
        <p class="comite-company">{{ $companyLegalName }}</p>
        <p class="comite-meta"><strong>ACTA:</strong> {{ $actaNumber }}</p>
        <p class="comite-meta"><strong>CASO:</strong> {{ $caseNumber }}</p>
        <p class="comite-meta"><strong>ASUNTO:</strong> {{ $actaSubject }}</p>
        <div class="comite-narrative">{{ $decisionNarrative }}</div>
    @else
        <p class="comite-meta">
            <strong>Expediente:</strong> {{ $caseNumber }}
            @if (! empty($meetingPlaceLine))
                &nbsp;·&nbsp;
                <strong>Fecha del comité:</strong> {{ $meetingPlaceLine }}
            @endif
        </p>
        <p class="comite-meta" style="margin-top: 1rem;">
            <strong>Decisión / acuerdo del comité:</strong>
        </p>
        <div class="comite-narrative">{{ $decisionNarrative }}</div>
    @endif

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
                <p class="comite-signature-field"><strong>Nombre:</strong> {{ $attendee['name'] ?? '' }}</p>
                <p class="comite-signature-field"><strong>Cargo:</strong> {{ $attendee['cargo'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
</div>
