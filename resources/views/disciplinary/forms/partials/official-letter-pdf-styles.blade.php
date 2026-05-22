{{-- Estilos compartidos para plantillas FO-GJ en PDF (Letter, Browsershot). --}}
<style>
    @page { size: Letter; margin: 0.45in; }
    html, body { margin: 0; padding: 0; background: #fff; }
    .ogj-wrap {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
        margin: 0 auto;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10px;
        line-height: 1.25;
        color: #000;
        background: #fff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .ogj-page {
        width: 100%;
        box-sizing: border-box;
        padding: 0.38in 0.44in 0.34in;
        background: #fff;
        min-height: 0;
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
        font-size: 9px;
        text-align: left;
    }
    .ogj-lbl {
        font-weight: bold;
        width: 26%;
        background: #f3f3f3;
        font-size: 9px;
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
        font-size: 11px;
    }
    .ogj-head-grid {
        margin-bottom: 10px;
    }
    .ogj-head-grid > tbody > tr > td {
        vertical-align: middle;
        padding: 0;
    }
    .ogj-title {
        font-weight: bold;
        font-size: 11px;
        text-align: center;
        vertical-align: middle;
        padding: 10px 8px !important;
        text-transform: uppercase;
    }
    .ogj-meta {
        width: 114px;
        padding: 0 !important;
        vertical-align: top;
    }
    .ogj-meta-grid {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        font-size: 9px;
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
        font-size: 10px;
    }
    .ogj-phase {
        font-size: 9px;
        margin: 0 0 8px;
        padding: 5px 8px;
        border: 1px solid #666;
        background: #fafafa;
    }
    .ogj-logo-cell {
        width: 102px;
        max-width: 102px;
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
        font-size: 8px;
        text-align: center;
        margin-top: 10px;
        color: #333;
    }
    /* FO-GJ-03 · Citación a diligencia disciplinaria */
    .ogj-03-body {
        font-size: 10px;
        line-height: 1.35;
        color: #000;
        margin-top: 12px;
    }
    .ogj-03-body p {
        margin: 0 0 8px;
        text-align: justify;
    }
    .ogj-03-ref {
        margin-bottom: 10px;
    }
    .ogj-03-ref p {
        margin: 0 0 4px;
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
        margin: 8px 0 12px;
    }
    .ogj-03-recipient p {
        margin: 0 0 3px;
        text-align: left;
        font-weight: bold;
    }
    .ogj-03-section-title {
        text-align: center;
        font-weight: bold;
        margin: 10px 0 8px !important;
    }
    .ogj-03-underline {
        font-weight: bold;
        text-decoration: underline;
    }
    .ogj-03-list {
        margin: 0 0 8px 0;
        padding-left: 18px;
    }
    .ogj-03-list li {
        margin-bottom: 4px;
        text-align: justify;
    }
    .ogj-03-signatures {
        width: 100%;
        border-collapse: collapse;
        margin-top: 18px;
        table-layout: fixed;
    }
    .ogj-03-signatures td {
        width: 50%;
        vertical-align: top;
        padding: 0 12px 0 0;
        font-size: 10px;
    }
    .ogj-03-signatures td:last-child {
        padding: 0 0 0 12px;
    }
    .ogj-03-signatures p {
        margin: 0 0 4px;
        text-align: left;
    }
    .ogj-03-sign-line {
        border-bottom: 1px solid #000;
        min-height: 36px;
        margin: 28px 0 6px;
    }
</style>
