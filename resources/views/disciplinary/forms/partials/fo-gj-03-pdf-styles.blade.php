@include('disciplinary.forms.partials.official-letter-pdf-styles')
<style>
    /*
     * FO-GJ-03 páginas Letter explícitas (FoGj03DocumentPaginator):
     * cada .ogj-page incluye encabezado HTML + trozo de cuerpo.
     * Sin position:fixed: estable en Dompdf/Hostinger.
     */
    @page { size: Letter; margin: 0; }
    .ogj-page-break { page-break-before: always; break-before: page; }
    .ogj-03-doc .ogj-page.ogj-03-page {
        width: 7.5in;
        max-width: 7.5in;
        margin: 0.5in;
        padding: 0;
    }
    /* Cabecera: logo/título/meta alineados a 4 filas meta (~76px). */
    .ogj-03-doc .ogj-head-grid > tbody > tr > td {
        height: 76px;
        min-height: 76px;
        vertical-align: middle;
    }
    .ogj-03-doc .ogj-meta {
        vertical-align: top;
        padding: 0 !important;
    }
    .ogj-03-doc .ogj-meta-grid {
        height: 76px;
    }
    .ogj-03-doc .ogj-meta-grid td {
        height: 19px;
        max-height: 19px;
        padding: 2px 6px;
        line-height: 14px;
        overflow: hidden;
        vertical-align: middle;
    }
    .ogj-03-doc .ogj-meta-grid tr:last-child td {
        border-bottom: none;
    }
    .ogj-03-doc .ogj-logo-cell img {
        max-height: 52px;
    }
    .ogj-03-flow {
        padding-top: 6px;
    }
    .ogj-03-flow p {
        text-align: justify;
        margin: 0 0 3px;
    }
    .ogj-03-flow .ogj-03-ref p,
    .ogj-03-flow .ogj-03-recipient p {
        text-align: left;
    }
    .ogj-03-flow .ogj-03-section-title {
        text-align: center;
        margin: 4px 0 3px;
    }
    .ogj-03-flow .ogj-03-closing-block p {
        text-align: left;
    }
    .ogj-03-doc { font-size: var(--ogj-font-body); color: #000; line-height: 1.22; }
    .ogj-03-closing-block {
        margin-top: 8px;
        /* Único bloque atómico: no partir firmas entre hojas físicas Dompdf. */
        page-break-inside: avoid;
        break-inside: avoid;
    }
    .ogj-03-closing-block .ogj-03-signatures,
    .ogj-03-closing-block .ogj-03-witnesses {
        page-break-inside: avoid;
        break-inside: avoid;
    }
    .ogj-03-signature-slot-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .ogj-03-signature-slot {
        height: 36px;
        vertical-align: bottom;
        padding: 0;
    }
    .ogj-03-signature-slot-table .ogj-03-sign-line {
        border-bottom: 1px solid #000;
        height: 1px;
        padding: 0;
        margin: 0;
        line-height: 1px;
    }
    .ogj-03-signature-img {
        display: block;
        max-height: 36px;
        max-width: 180px;
        margin: 0;
    }
    .ogj-03-doc .ogj-03-signatures {
        margin-top: 8px;
    }
    .ogj-03-page-line {
        display: block;
        height: 14px;
        min-height: 14px;
        line-height: 14px;
        overflow: hidden;
    }
</style>
