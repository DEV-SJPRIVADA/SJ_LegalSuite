<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta de comité disciplinario</title>
    @include('disciplinary.forms.partials.official-letter-pdf-styles')
    <style>
        html, body { margin: 0; padding: 0; background: #fff; }
        .comite-body { font-size: var(--ogj-font-body); line-height: 1.45; color: #111; }
        .comite-opening { margin: 0 0 0.85rem; }
        .comite-company { margin: 0 0 0.85rem; font-weight: 700; }
        .comite-meta { margin: 0 0 0.45rem; }
        .comite-meta strong { font-weight: 700; }
        .comite-narrative {
            margin: 0.85rem 0 1rem;
            text-align: justify;
            white-space: pre-wrap;
        }
        .comite-signatures-heading { margin-top: 1rem; }
        .comite-signatures {
            margin-top: 0.65rem;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem 1.35rem;
        }
        .comite-signature-block { text-align: left; font-size: var(--ogj-font-meta); }
        .comite-signature-label { margin: 0 0 0.2rem; font-weight: 700; }
        .comite-signature-slot {
            height: 44px;
            display: flex;
            align-items: flex-end;
            justify-content: flex-start;
            margin-bottom: 0.15rem;
        }
        .comite-signature-slot img { max-height: 40px; max-width: 90%; object-fit: contain; }
        .comite-signature-line {
            border-top: 1px solid #111;
            margin: 0 0 0.35rem;
            min-height: 0;
        }
        .comite-signature-field { margin: 0 0 0.15rem; }
        @if (! empty($letterheadBackgroundSrc))
        @page { size: Letter; margin: 0; }
        html, body { width: 8.5in; height: 11in; }
        .ogj-letterhead-sheet {
            position: relative;
            width: 8.5in;
            height: 11in;
            margin: 0;
            padding: 0;
            overflow: hidden;
            box-sizing: border-box;
        }
        .ogj-letterhead-sheet__bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 8.5in;
            height: 11in;
            object-fit: fill;
            z-index: 0;
        }
        .ogj-letterhead-sheet__content {
            position: relative;
            z-index: 1;
            box-sizing: border-box;
            width: 8.5in;
            height: 11in;
            padding: 2.12in 0.58in 1.42in 0.58in;
        }
        @else
        @page { size: Letter; margin: 0.45in; }
        @endif
    </style>
</head>
<body>
@php
    $comiteContent = view('disciplinary.forms.partials.comite-acta-body', [
        'caseNumber' => $caseNumber ?? '',
        'actaNumber' => $actaNumber ?? ($caseNumber ?? ''),
        'actaSubject' => $actaSubject ?? 'Comité para toma de decisión.',
        'companyLegalName' => $companyLegalName ?? 'SJ SEGURIDAD PRIVADA LTDA',
        'meetingPlaceLine' => $meetingPlaceLine ?? '',
        'decisionNarrative' => $decisionNarrative ?? '',
        'attendees' => $attendees ?? [],
        'usesLetterhead' => ! empty($letterheadBackgroundSrc),
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
        code="ACTA-COMITÉ"
        headline="Acta de comité disciplinario"
        phase="Inasistencia a diligencia disciplinaria"
        :logo-src="$embeddedLogoSrc ?? null"
        :meta-date="$meetingDateLong ?? ''"
        :show-micro="false"
    >
        {!! $comiteContent !!}
    </x-disciplinary.forms.official-letter-pdf-shell>
@endif
</body>
</html>
