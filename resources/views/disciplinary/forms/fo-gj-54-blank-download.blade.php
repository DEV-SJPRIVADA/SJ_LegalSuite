<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FO-GJ-54 · Reprogramación diligencia (en blanco)</title>
    <style>
        @page { size: Letter; margin: 0.45in; }
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-54"
        headline="Reprogramación a diligencia disciplinaria"
        phase="Fase B · Tras justificación aceptada — nueva fecha para la diligencia disciplinaria."
        :logo-src="$embeddedLogoSrc"
    >
        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="2">Referencia</th>
                </tr>
                <tr>
                    <td class="ogj-lbl">Expediente / radicado</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Citación anterior (fecha / ref.)</td>
                    <td class="ogj-line"></td>
                </tr>
            </table>
        </div>

        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="2">Trabajador</th>
                </tr>
                <tr>
                    <td class="ogj-lbl">Nombre completo</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">C.C.</td>
                    <td class="ogj-line"></td>
                </tr>
            </table>
        </div>

        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="2">Reprogramación</th>
                </tr>
                <tr>
                    <td class="ogj-lbl">Motivo / fundamento</td>
                    <td class="ogj-line-tall"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Nueva fecha, hora y lugar</td>
                    <td class="ogj-line-tall"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Observaciones</td>
                    <td class="ogj-line-tall"></td>
                </tr>
            </table>
        </div>

        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="2">Firma quien comunica</th>
                </tr>
                <tr>
                    <td class="ogj-lbl">Nombre y cargo</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Firma y sello</td>
                    <td class="ogj-line-tall"></td>
                </tr>
            </table>
        </div>
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
