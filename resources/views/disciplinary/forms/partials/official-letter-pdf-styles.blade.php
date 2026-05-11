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
        text-align: center;
        border: 1px solid #000;
        padding: 4px 6px;
    }
    .ogj-title {
        font-weight: bold;
        font-size: 11px;
        text-align: center;
        vertical-align: middle;
        padding: 8px 10px !important;
    }
    .ogj-meta {
        font-size: 9px;
        text-align: right;
        vertical-align: middle;
    }
    .ogj-meta table {
        width: 100%;
        border-collapse: collapse;
    }
    .ogj-meta td {
        border: none;
        padding: 1px 0;
    }
    .ogj-phase {
        font-size: 9px;
        margin: 0 0 8px;
        padding: 5px 8px;
        border: 1px solid #666;
        background: #fafafa;
    }
    .ogj-head-tbl td {
        border: none !important;
        vertical-align: middle;
    }
    .ogj-logo-cell {
        width: 102px;
        text-align: center;
    }
    .ogj-logo-ring {
        display: inline-block;
        border: 1px solid #000;
        padding: 4px;
    }
    .ogj-logo-ring img {
        max-width: 88px;
        max-height: 56px;
        display: block;
    }
    .ogj-micro {
        font-size: 8px;
        text-align: center;
        margin-top: 10px;
        color: #333;
    }
</style>
