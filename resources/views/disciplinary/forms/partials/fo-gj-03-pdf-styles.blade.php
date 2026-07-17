@include('disciplinary.forms.partials.official-letter-pdf-styles')
<style>
    /*
     * FO-GJ-03 continuo: @page reserva márgenes; letterhead position:fixed se repite
     * en cada página física Dompdf. Números: canvas (Dompdf) / counters (Chrome).
     * Nota: thead con una sola celda alta NO repite el header en Dompdf.
     */
    @page { size: Letter; margin: 0.5in; }
@media screen {
        .ogj-03-page-line-print::after {
            content: "Página";
        }
    }
    @media print {
        .ogj-03-page-line-print::after {
            content: "Página " counter(page) " de " counter(pages);
        }
    }
    .ogj-03-doc[data-sj-pdf-flow="fo-gj-03"] .ogj-page.ogj-03-page {
        width: auto;
        max-width: none;
        margin: 0;
        padding: 0;
    }
    .ogj-03-letterhead {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
    }
    .ogj-03-flow {
        /* Reserva bajo letterhead fixed (logo + meta 4 filas); evita que Fecha se pegue al borde. */
        padding-top: 96px;
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
        min-height: 1.2em;
    }
</style>
