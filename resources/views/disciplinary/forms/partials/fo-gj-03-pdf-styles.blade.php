@include('disciplinary.forms.partials.official-letter-pdf-styles')
<style>
    .ogj-page-break { page-break-before: always; break-before: page; }
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
</style>
