{{-- Estilos compartidos para plantillas FO-GJ en PDF (Letter, Browsershot). --}}
{{-- Tipografías Liberation embebidas (SjPdfSans): no dependen de Arial del SO / Hostinger. --}}
<style>
{!! \App\Support\Pdf\EmbeddedPdfFont::sansFontFaceCss() !!}
    :root {
        --ogj-font-body: 12px;
        --ogj-font-meta: 11px;
        --ogj-font-title: 13px;
        --ogj-font-micro: 10px;
        --ogj-font-family: '{{ \App\Support\Pdf\EmbeddedPdfFont::FAMILY_SANS }}', Arial, Helvetica, sans-serif;
    }
    /*
     * Dompdf-safe Letter (8.5in): no usar width:100% + padding (ignora border-box → corta la derecha).
     * Caja útil 7.5in + margen 0.5in; @page margin 0 para no doblar.
     */
    @page { size: Letter; margin: 0; }
    html, body { margin: 0; padding: 0; background: #fff; font-family: var(--ogj-font-family); }
    .ogj-wrap {
        width: auto;
        max-width: none;
        min-width: 0;
        margin: 0;
        padding: 0;
        font-family: var(--ogj-font-family);
        font-size: var(--ogj-font-body);
        line-height: 1.25;
        color: #000;
        background: #fff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .ogj-page {
        width: 7.5in;
        margin: 0.5in;
        padding: 0;
        background: #fff;
        min-height: 0;
    }
    .ogj-letter-screen-scaler {
        display: flex;
        justify-content: center;
        padding: 0.5rem 0 1rem;
    }
    .ogj-letter-screen-sheet {
        width: 8.5in;
        min-height: 11in;
        box-sizing: border-box;
        background: #fff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        transform-origin: top center;
    }
    .ogj-page--screen-preview {
        width: 8.5in;
        min-height: 11in;
        box-sizing: border-box;
        padding: 0.5in;
    }
    .ogj-letter-screen-sheet .ogj-page {
        /* Solo pantalla: simula el inset de @page letter. */
        padding: 0.5in;
        box-sizing: border-box;
    }
    .ogj-block {
        border: 1px solid #000;
        margin-bottom: 10px;
        box-sizing: border-box;
        background: #fff;
    }
    .ogj-tbl {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .ogj-tbl td,
    .ogj-tbl th {
        border: 1px solid #000;
        padding: 5px 7px;
        vertical-align: top;
        box-sizing: border-box;
    }
    .ogj-tbl th {
        background: #e8e8e8;
        font-weight: bold;
        text-transform: uppercase;
        font-size: var(--ogj-font-meta);
        text-align: left;
    }
    .ogj-lbl {
        font-weight: bold;
        width: 26%;
        background: #f3f3f3;
        font-size: var(--ogj-font-meta);
        text-transform: uppercase;
    }
    .ogj-line {
        min-height: 26px;
        height: 26px;
    }
    .ogj-line-tall {
        min-height: 120px;
        height: 120px;
        vertical-align: top;
    }
    .ogj-code {
        font-weight: bold;
        font-size: var(--ogj-font-title);
    }
    .ogj-head-grid {
        margin-bottom: 4px;
    }
    .ogj-head-grid > tbody > tr > td {
        vertical-align: middle;
        padding: 0;
    }
    .ogj-title {
        font-weight: bold;
        font-size: var(--ogj-font-title);
        text-align: center;
        vertical-align: middle;
        padding: 10px 8px !important;
        text-transform: uppercase;
        width: 50%;
    }
    .ogj-meta {
        width: 25%;
        max-width: 25%;
        padding: 0 !important;
        vertical-align: top;
    }
    .ogj-meta-grid {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        font-size: var(--ogj-font-meta);
    }
    .ogj-meta-grid td {
        border: none;
        border-bottom: 1px solid #000;
        padding: 4px 6px;
        text-align: left;
        font-weight: normal;
        background: #fff;
    }
    .ogj-meta-grid tr:last-child td {
        border-bottom: none;
    }
    .ogj-meta-grid td.ogj-meta-code {
        font-weight: bold;
        font-size: var(--ogj-font-body);
    }
    .ogj-phase {
        font-size: var(--ogj-font-meta);
        margin: 0 0 8px;
        padding: 5px 8px;
        border: 1px solid #666;
        background: #fafafa;
    }
    .ogj-logo-cell {
        width: 25%;
        max-width: 25%;
        text-align: center;
        vertical-align: middle;
        padding: 6px !important;
    }
    .ogj-logo-cell img {
        max-width: 88px;
        max-height: 56px;
        width: auto;
        height: auto;
        object-fit: contain;
        display: block;
        margin: 0 auto;
    }
    .ogj-micro {
        font-size: var(--ogj-font-micro);
        text-align: center;
        margin-top: 10px;
        color: #333;
    }
    /* FO-GJ-03 · Citación a diligencia disciplinaria */
    .ogj-03-body {
        font-size: var(--ogj-font-body);
        line-height: 1.35;
        color: #000;
        margin-top: 12px;
    }
    .ogj-03-body p {
        margin: 0 0 4px;
        text-align: justify;
    }
    .ogj-03-ref {
        margin-bottom: 4px;
    }
    .ogj-03-ref p {
        margin: 0 0 3px;
        text-align: left;
    }
    .ogj-03-guide {
        display: inline;
        color: #b5b5b5;
        font-weight: normal;
        letter-spacing: 0.08em;
        white-space: nowrap;
    }
    .ogj-03-guide-sm {
        letter-spacing: 0.06em;
    }
    .ogj-03-guide-md {
        letter-spacing: 0.07em;
    }
    .ogj-03-guide-lg {
        letter-spacing: 0.08em;
    }
    .ogj-03-guide-xl {
        letter-spacing: 0.05em;
    }
    .ogj-03-guide-xxl {
        letter-spacing: 0.04em;
    }
    /* FO-GJ-44 · Constancia de inasistencia */
    .ogj-44-subject {
        margin: 0 0 10px !important;
        text-align: left;
    }
    .ogj-44-omission-guide {
        display: block;
        margin: 4px 0 6px;
        line-height: 1.5;
    }
    .ogj-44-employer-sign {
        margin-top: 16px;
        max-width: 55%;
    }
    .ogj-44-employer-sign p {
        margin: 0 0 4px;
        text-align: left;
    }
    .ogj-44-witnesses {
        margin-top: 20px;
    }
    /* FO-GJ-54 · Reprogramación a diligencia disciplinaria */
    .ogj-54-recipient-cedula {
        padding-bottom: 6px;
        border-bottom: 1px solid #d0d0d0;
        margin-bottom: 6px !important;
    }
    .ogj-03-recipient {
        margin: 4px 0 6px;
    }
    .ogj-03-recipient p {
        margin: 0 0 2px;
        text-align: left;
        font-weight: bold;
    }
    .ogj-03-section-title {
        text-align: center;
        font-weight: bold;
        margin: 6px 0 4px;
    }
    .ogj-03-justify {
        text-align: justify;
        margin: 0 0 4px;
    }
    .ogj-03-underline {
        font-weight: bold;
        text-decoration: underline;
    }
    .ogj-03-list {
        margin: 0 0 4px 0;
        padding-left: 18px;
    }
    .ogj-03-list li {
        margin-bottom: 2px;
        text-align: justify;
    }
    .ogj-03-signatures {
        width: 100%;
        border-collapse: collapse;
        margin-top: 18px;
        table-layout: fixed;
    }
    /* Cierre junto: planner FO-GJ-03 mueve firmas de página; CSS refuerza. */
    .ogj-03-closing-block {
        page-break-inside: avoid;
        break-inside: avoid;
    }
    .ogj-03-signatures td {
        width: 50%;
        vertical-align: top;
        padding: 0 12px 0 0;
        font-size: var(--ogj-font-body);
    }
    .ogj-03-signatures td:last-child {
        padding: 0 0 0 12px;
    }
    .ogj-03-signatures p {
        margin: 0 0 4px;
        text-align: left;
    }
    .ogj-03-signatures-capture-row td {
        vertical-align: bottom;
    }
    /* Tablas (no flex): Dompdf calcula altura de forma fiable. */
    .ogj-03-signature-slot-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .ogj-03-signature-slot {
        height: 44px;
        vertical-align: bottom;
        padding: 0;
    }
    .ogj-03-signature-img {
        display: block;
        max-height: 44px;
        max-width: 180px;
        margin: 0;
    }
    .ogj-03-sign-line {
        border-bottom: 1px solid #000;
        height: 1px;
        padding: 0;
        margin: 0 0 6px;
        line-height: 1px;
    }
    .ogj-03-refusal-text {
        margin: 0 0 1px;
        padding: 0;
        font-size: var(--ogj-font-body);
        font-style: italic;
        text-align: left;
    }
    .ogj-03-witnesses {
        margin-top: 20px;
    }
    .ogj-03-witnesses td {
        vertical-align: top;
        padding-right: 12px;
    }
    .ogj-03-witnesses td:last-child {
        padding-right: 0;
        padding-left: 12px;
    }
</style>
