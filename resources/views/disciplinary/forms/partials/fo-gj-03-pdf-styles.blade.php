@include('disciplinary.forms.partials.official-letter-pdf-styles')
<style>
    /*
     * FO-GJ-03 continuo (Dompdf):
     * - Laterales: caja 7.5in + 0.5in (no confiar en @page horizontal).
     * - Los cuatro lados: caja 7.5in con margin 0.5in (Dompdf ignora @page
     *   de forma inconsistente cuando hay position:fixed).
     * - El cuerpo reserva la altura del header para no montarse sobre él.
     * - Meta: 4 filas de altura fija para que “Página N de M” quede dentro del marco.
     */
    @page { size: Letter; margin: 0; }
    @media print {
        .ogj-03-page-line-print::after {
            content: "Página " counter(page) " de " counter(pages);
        }
    }
    .ogj-03-doc[data-sj-pdf-flow="fo-gj-03"] .ogj-page.ogj-03-page {
        width: 7.5in;
        max-width: 7.5in;
        margin: 0.5in;
        padding: 0;
    }
    .ogj-03-letterhead {
        position: fixed;
        top: 0.5in;
        left: 0.5in;
        width: 7.5in;
    }
    .ogj-03-flow {
        padding-top: 94px;
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
    .ogj-03-flow p {
        text-align: justify;
    }
    .ogj-03-flow .ogj-03-ref p,
    .ogj-03-flow .ogj-03-recipient p {
        text-align: left;
    }
    .ogj-03-flow .ogj-03-section-title {
        text-align: center;
    }
    .ogj-03-flow .ogj-03-closing-block p {
        text-align: left;
    }
    .ogj-03-doc { font-size: var(--ogj-font-body); color: #000; }
    .ogj-03-closing-block {
        margin-top: 14px;
        page-break-inside: avoid;
        break-inside: avoid;
    }
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
    .ogj-03-signature-slot-table .ogj-03-sign-line {
        border-bottom: 1px solid #000;
        height: 1px;
        padding: 0;
        margin: 0;
        line-height: 1px;
    }
    .ogj-03-signature-img {
        display: block;
        max-height: 44px;
        max-width: 180px;
        margin: 0;
    }
    .ogj-03-page-line {
        display: block;
        height: 14px;
        min-height: 14px;
        line-height: 14px;
        overflow: hidden;
    }
</style>
