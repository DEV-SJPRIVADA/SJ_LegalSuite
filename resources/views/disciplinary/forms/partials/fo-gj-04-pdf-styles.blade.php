@include('disciplinary.forms.partials.official-letter-pdf-styles')
<style>
    .ogj-page-break { page-break-before: always; break-before: page; }
    .ogj-04-body { font-size: 10px; line-height: 1.35; color: #000; }
    .ogj-04-body p { margin: 0 0 7px; text-align: justify; }
    .ogj-04-id p { margin: 0 0 3px; text-align: left; font-weight: bold; }
    .ogj-04-id-last { margin-bottom: 0 !important; }
    .ogj-04-id { margin-bottom: 12px; }
    .ogj-04-opening { margin-top: 0; }
    .ogj-04-guide-lg { display: inline; }
    .ogj-04-guide {
        display: inline;
        color: #b5b5b5;
        font-weight: normal;
        letter-spacing: 0.06em;
    }
    .ogj-04-question { margin: 8px 0 10px; }
    .ogj-04-question-title { font-weight: bold; margin: 0 0 4px; text-align: left; }
    .ogj-04-answer-line {
        border-bottom: 1px solid #000;
        min-height: 18px;
        margin: 2px 0 0;
    }
    .ogj-04-signatures {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
        table-layout: fixed;
    }
    .ogj-04-signatures td { width: 50%; vertical-align: top; padding: 0 10px 0 0; font-size: 10px; }
    .ogj-04-signatures td:last-child { padding: 0 0 0 10px; }
    .ogj-04-signatures p { margin: 0 0 3px; text-align: left; }
    .ogj-04-signature-slot { min-height: 44px; display: flex; align-items: flex-end; }
    .ogj-04-signature-img { max-height: 44px; max-width: 180px; object-fit: contain; }
    .ogj-04-sign-line { border-bottom: 1px solid #000; margin: 4px 0 6px; }
    .ogj-04-list-num { margin: 0 0 6px; padding-left: 14px; text-align: justify; }
</style>
