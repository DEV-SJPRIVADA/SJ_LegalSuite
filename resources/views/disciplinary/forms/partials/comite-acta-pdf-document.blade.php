@php
    $blankForDownload = $blankForDownload ?? false;
    $comiteContent = view('disciplinary.forms.partials.comite-acta-body', [
        'caseNumber' => $caseNumber ?? '',
        'actaNumber' => $actaNumber ?? ($caseNumber ?? ''),
        'actaSubject' => $actaSubject ?? 'Comité para toma de decisión.',
        'companyLegalName' => $companyLegalName ?? 'SJ SEGURIDAD PRIVADA LTDA',
        'meetingPlaceLine' => $meetingPlaceLine ?? '',
        'decisionNarrative' => $decisionNarrative ?? '',
        'attendees' => $attendees ?? [],
        'usesLetterhead' => ! empty($letterheadBackgroundSrc),
        'blankForDownload' => $blankForDownload,
    ])->render();
@endphp

@if (! empty($letterheadBackgroundSrc))
    <div class="ogj-letterhead-sheet">
        <img class="ogj-letterhead-sheet__bg" src="{{ $letterheadBackgroundSrc }}" alt="">
        <div class="ogj-letterhead-sheet__content">
            {!! $comiteContent !!}
        </div>
    </div>
@else
    <x-disciplinary.forms.official-letter-pdf-shell
        code="ACTA-COMITE"
        headline="Acta de comité disciplinario"
        phase="Comité disciplinario para decisión"
        :logo-src="$embeddedLogoSrc ?? null"
        :meta-date="$meetingDateLong ?? ''"
        :show-micro="false"
    >
        {!! $comiteContent !!}
    </x-disciplinary.forms.official-letter-pdf-shell>
@endif
