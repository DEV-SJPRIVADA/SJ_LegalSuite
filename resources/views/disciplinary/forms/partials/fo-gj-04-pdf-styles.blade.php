@include('disciplinary.forms.partials.official-letter-pdf-styles')
<style>
    /* Inset solo vía @page. Sin padding en .ogj-page (Dompdf + border-box falla y corta la derecha). */
    .ogj-page-break { page-break-before: always; break-before: page; }
    .ogj-04-body { font-size: var(--ogj-font-body); line-height: 1.35; color: #000; }
    .ogj-04-body p { margin: 0 0 7px; text-align: justify; }
    .ogj-04-id p { margin: 0 0 3px; text-align: left; font-weight: bold; }
    .ogj-04-id-last { margin-bottom: 0 !important; }
    .ogj-04-id { margin-bottom: 12px; }
    .ogj-04-opening { margin-top: 0; }
    .ogj-04-party-indent {
        position: relative;
        margin: 0 0 7px;
        padding-left: 28px;
        text-align: left;
    }
    .ogj-04-party-indent::before {
        content: '\2022';
        position: absolute;
        left: 12px;
    }
    .ogj-04-party-indent--break-after {
        margin-bottom: 1.35em;
    }
    .ogj-04-guide-lg { display: inline; }
    .ogj-04-guide {
        display: inline;
        color: #b5b5b5;
        font-weight: normal;
        letter-spacing: 0.06em;
    }
    .ogj-04-question { margin: 8px 0 10px; }
    .ogj-04-list-num--continuation { padding-left: 14px; }
    .ogj-04-closing-text { margin: 6px 0 0; }
    /* Solo la tabla de firmas es atómica (mismo contrato FO-GJ-03). */
    .ogj-04-closing-block {
        margin-top: 8px;
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .ogj-04-closing-block .ogj-04-signatures {
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .ogj-04-question-title { font-weight: bold; margin: 0 0 4px; text-align: left; }
    .ogj-04-question-answer {
        margin: 0 0 4px;
        text-align: left;
    }
    .ogj-04-question-answer strong { font-weight: bold; }
    .ogj-04-answer-inline {
        font-weight: normal;
        white-space: pre-wrap;
    }
    .ogj-04-signatures {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
        table-layout: fixed;
    }
    .ogj-04-signatures td { width: 50%; vertical-align: top; padding: 0 10px 0 0; font-size: var(--ogj-font-body); }
    .ogj-04-signatures td:last-child { padding: 0 0 0 10px; }
    .ogj-04-signatures p { margin: 0 0 3px; text-align: left; }
    .ogj-04-signature-slot { min-height: 44px; display: flex; align-items: flex-end; }
    .ogj-04-signature-img { max-height: 44px; max-width: 180px; object-fit: contain; }
    .ogj-04-sign-line { border-bottom: 1px solid #000; margin: 4px 0 6px; }
    .ogj-04-list-num { margin: 0 0 6px; padding-left: 14px; text-align: justify; }
</style>
