{{-- FO-GJ-51 · solo pantalla interactiva (no PDF). Scoped bajo .fo51-interactive --}}
<style>
    @media (max-width: 767px) {
        .fo51-interactive .ogj-page {
            padding: 0.65rem 0.45rem 0.75rem;
        }

        /* Encabezado: logo, título y meta en columna */
        .fo51-interactive .ogj-head-grid,
        .fo51-interactive .ogj-head-grid tbody,
        .fo51-interactive .ogj-head-grid tr {
            display: block;
            width: 100%;
        }

        .fo51-interactive .ogj-head-grid td {
            display: block;
            width: 100% !important;
            max-width: none !important;
            box-sizing: border-box;
        }

        .fo51-interactive .ogj-logo-cell {
            padding: 8px 6px !important;
            border-bottom: none !important;
        }

        .fo51-interactive .ogj-logo-cell img {
            margin: 0 auto;
        }

        .fo51-interactive .ogj-title {
            padding: 8px 6px !important;
            font-size: 12px;
            line-height: 1.3;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .fo51-interactive .ogj-meta {
            padding: 0 !important;
        }

        .fo51-interactive .ogj-meta-grid td {
            padding: 5px 8px;
            font-size: 11px;
        }

        /* Fecha: ancho completo */
        .fo51-interactive .fo51-date-wrap {
            width: 100%;
            max-width: 100%;
        }

        .fo51-interactive .fo51-date-grid .fo51-lbl-cap {
            font-size: 11px;
            white-space: nowrap;
        }

        /* Datos del trabajador: grilla 2×3 en escritorio; celdas apiladas en móvil */
        .fo51-interactive .fo51-block-personal table.fo51-tbl {
            table-layout: auto;
        }

        .fo51-interactive .fo51-block-personal colgroup {
            display: none;
        }

        .fo51-interactive .fo51-block-personal tr {
            display: block;
            width: 100%;
        }

        .fo51-interactive .fo51-block-personal td.fo51-personal-cell {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 4px;
            width: 100% !important;
            box-sizing: border-box;
            border-left: none;
            border-right: none;
            padding: 8px !important;
        }

        .fo51-interactive .fo51-block-personal td.fo51-personal-cell .fo51-inline-lbl {
            font-size: 11px;
            background: #f8fafc;
            padding: 2px 0;
        }

        .fo51-interactive .fo51-block-personal td.fo51-personal-cell .fo51-personal-val {
            width: 100%;
        }

        .fo51-interactive .fo51-block-personal td.fo51-personal-cell .fo51-in,
        .fo51-interactive .fo51-block-personal td.fo51-personal-cell .fo51-static {
            min-height: 40px;
            padding: 8px !important;
            font-size: 14px;
        }

        .fo51-interactive .fo51-block-personal tr:last-child td.fo51-personal-cell:last-child {
            border-bottom: none;
        }

        /* Faltas: una columna */
        .fo51-interactive .fo51-block-faults table.fo51-tbl {
            table-layout: auto;
        }

        .fo51-interactive .fo51-block-faults tbody tr {
            display: block;
            width: 100%;
        }

        .fo51-interactive .fo51-block-faults tbody td {
            display: block;
            width: 100% !important;
            box-sizing: border-box;
        }

        .fo51-interactive .fo51-fault-head {
            font-size: 11px;
            line-height: 1.35;
            padding: 8px !important;
        }

        .fo51-interactive .fo51-fault-line {
            align-items: center;
            gap: 12px;
            padding: 10px 8px;
            min-height: 44px;
        }

        .fo51-interactive .fo51-chk {
            width: 22px;
            height: 22px;
            min-width: 22px;
            min-height: 22px;
            flex-shrink: 0;
        }

        .fo51-interactive .fo51-obs-head {
            font-size: 11px;
            line-height: 1.35;
            padding: 8px !important;
        }

        .fo51-interactive .fo51-obs-head .fo51-obs-sub {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            line-height: 1.3;
        }

        .fo51-interactive textarea.fo51-in {
            min-height: 160px;
            font-size: 14px;
            padding: 8px !important;
        }

        /* Firma elaborador: apilar NOMBRE / CARGO / FIRMA */
        .fo51-interactive .fo51-sign-cap thead {
            display: none;
        }

        .fo51-interactive .fo51-sign-cap tbody tr:first-child {
            display: block;
        }

        .fo51-interactive .fo51-sign-cap tbody tr:first-child td {
            display: block;
            width: 100% !important;
            height: auto !important;
            padding: 0 !important;
            border-bottom: 1px solid #000;
        }

        .fo51-interactive .fo51-sign-cap tbody tr:first-child td::before {
            display: block;
            padding: 6px 8px 2px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .fo51-interactive .fo51-sign-cap tbody tr:first-child td:nth-child(1)::before { content: 'Nombre'; }
        .fo51-interactive .fo51-sign-cap tbody tr:first-child td:nth-child(2)::before { content: 'Cargo'; }
        .fo51-interactive .fo51-sign-cap tbody tr:first-child td:nth-child(3)::before { content: 'Firma'; }

        .fo51-interactive .fo51-sign-cap tbody tr:first-child .fo51-in {
            height: auto !important;
            min-height: 40px;
            font-size: 14px;
            padding: 8px !important;
        }

        /* Gestión jurídica */
        .fo51-interactive .fo51-block-legal table.fo51-tbl {
            table-layout: auto;
        }

        .fo51-interactive .fo51-block-legal thead tr:nth-child(2) {
            display: none;
        }

        .fo51-interactive .fo51-block-legal tbody tr {
            display: block;
            width: 100%;
        }

        .fo51-interactive .fo51-block-legal tbody td {
            display: block;
            width: 100% !important;
            box-sizing: border-box;
            height: auto !important;
            border-left: none;
            border-right: none;
        }

        .fo51-interactive .fo51-block-legal tbody td::before {
            display: block;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            padding: 8px 8px 4px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .fo51-interactive .fo51-block-legal tbody td:nth-child(1)::before { content: 'JUR-PD-'; }
        .fo51-interactive .fo51-block-legal tbody td:nth-child(2)::before { content: 'Entrega a G.H'; }
        .fo51-interactive .fo51-block-legal tbody td:nth-child(3)::before { content: 'Día (DD)'; }
        .fo51-interactive .fo51-block-legal tbody td:nth-child(4)::before { content: 'Mes (MM)'; }
        .fo51-interactive .fo51-block-legal tbody td:nth-child(5)::before { content: 'Año (AAAA)'; }

        .fo51-interactive .fo51-block-legal tbody .fo51-in {
            height: auto !important;
            min-height: 44px;
            font-size: 16px;
            padding: 10px 8px !important;
            text-align: left;
        }

        .fo51-interactive .fo51-block-legal tbody td:nth-child(3) .fo51-in,
        .fo51-interactive .fo51-block-legal tbody td:nth-child(4) .fo51-in,
        .fo51-interactive .fo51-block-legal tbody td:nth-child(5) .fo51-in {
            text-align: center;
        }

        .fo51-interactive .fo51-sign-cap tbody tr.fo51-sign-note-row {
            display: block;
        }

        .fo51-interactive .fo51-sign-cap tbody tr.fo51-sign-note-row td {
            display: block;
            width: 100% !important;
            padding: 8px !important;
        }

        .fo51-interactive .fo51-sign-cap tbody tr:first-child td:nth-child(3) {
            min-height: 88px;
            padding-bottom: 8px !important;
        }

        .fo51-interactive .fo51-signature-capture-inner {
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 8px;
            min-height: 64px;
            padding: 10px 8px 12px;
        }

        .fo51-interactive .fo51-signature-preview {
            flex: 0 0 100%;
            max-height: 56px;
            margin-bottom: 4px;
        }

        .fo51-interactive .fo51-signature-capture-btn {
            font-size: 13px;
            padding: 10px 16px;
            min-height: 44px;
            flex: 1 1 auto;
            max-width: 100%;
        }

        .fo51-interactive .fo51-signature-capture-link {
            font-size: 12px;
            padding: 8px;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
        }

        .fo51-interactive .fo51-helper-note {
            font-size: 12px !important;
            padding: 0 4px !important;
        }
    }
</style>
