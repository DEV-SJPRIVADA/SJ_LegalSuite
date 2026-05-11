<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FO-GJ-03 · Citación a diligencia (en blanco)</title>
    <style>
        @page { size: Letter; margin: 0.45in; }
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-03"
        headline="Citación a diligencia disciplinaria por escrito"
        phase="Fase B · Citación — citación formal al trabajador para asistencia a la diligencia disciplinaria."
        :logo-src="$embeddedLogoSrc"
    >
        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="2">Identificación del destinatario</th>
                </tr>
                <tr>
                    <td class="ogj-lbl">Ciudad y fecha</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Señor(a)</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">C.C. / identificación</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Cargo / dependencia</td>
                    <td class="ogj-line"></td>
                </tr>
            </table>
        </div>

        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="2">Citación</th>
                </tr>
                <tr>
                    <td class="ogj-lbl">Referencia expediente / radicado</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Asunto</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Fecha, hora y lugar de la diligencia</td>
                    <td class="ogj-line-tall"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Normativa / fundamento (breve)</td>
                    <td class="ogj-line-tall"></td>
                </tr>
            </table>
        </div>

        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="2">Firma quien notifica</th>
                </tr>
                <tr>
                    <td class="ogj-lbl">Nombre completo</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Cargo</td>
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
