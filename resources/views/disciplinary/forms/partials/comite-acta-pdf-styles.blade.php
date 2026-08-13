@include('disciplinary.forms.partials.official-letter-pdf-styles')
{{-- Serif embebida (métricas tipo Times) para acta de comité en Hostinger. --}}
<style>
{!! \App\Support\Pdf\EmbeddedPdfFont::serifFontFaceCss() !!}
    html, body {
        margin: 0;
        padding: 0;
        background: #fff;
        font-family: '{{ \App\Support\Pdf\EmbeddedPdfFont::FAMILY_SERIF }}', 'Times New Roman', Times, serif;
    }
    .comite-body {
        font-family: '{{ \App\Support\Pdf\EmbeddedPdfFont::FAMILY_SERIF }}', 'Times New Roman', Times, serif;
        font-size: 12pt;
        line-height: 1;
        color: #111;
    }
    .comite-body p { margin: 0; }
    .comite-body p.comite-opening { margin-bottom: 2em; }
    .comite-body p.comite-company { margin-bottom: 1em; font-weight: 700; }
    .comite-body p.comite-meta-caso { margin-bottom: 1em; }
    .comite-body p.comite-meta-asunto { margin-bottom: 1em; }
    .comite-meta strong { font-weight: 700; }
    .comite-narrative {
        margin: 0 0 1em;
        line-height: 1;
        text-align: justify;
        white-space: pre-wrap;
    }
    .comite-blank-line { color: #444; letter-spacing: 0.02em; }
    .comite-signatures-heading { margin: 0; }
    .comite-signatures {
        margin-top: 0.65rem;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem 1.35rem;
    }
    .comite-signature-block { text-align: left; font-size: 11pt; }
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
        padding: 1.35in 0.58in 1.42in 0.58in;
    }
    @else
    @page { size: Letter; margin: 0.45in; }
    @endif
</style>
