<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FO-GJ-42 · Acta de diligencia (en blanco)</title>
    <style>
        @page { size: Letter; margin: 0.45in; }
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
    <x-disciplinary.forms.official-letter-pdf-shell
        code="FO-GJ-42"
        headline="Acta de diligencia disciplinaria"
        phase="Fase C · Diligencia y acta — constancia del desarrollo de la diligencia y levantamiento del acta."
        :logo-src="$embeddedLogoSrc"
    >
        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="2">Encabezado del acta</th>
                </tr>
                <tr>
                    <td class="ogj-lbl">Ciudad y fecha de la diligencia</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Expediente / radicado</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Hora inicio — hora fin</td>
                    <td class="ogj-line"></td>
                </tr>
            </table>
        </div>

        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="2">Asistentes</th>
                </tr>
                <tr>
                    <td class="ogj-lbl">Trabajador (nombre y C.C.)</td>
                    <td class="ogj-line"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Delegados / acompañantes</td>
                    <td class="ogj-line-tall"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Comisión / quien conduce</td>
                    <td class="ogj-line-tall"></td>
                </tr>
            </table>
        </div>

        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="1">Desarrollo de la diligencia (hechos relevantes)</th>
                </tr>
                <tr>
                    <td class="ogj-line-tall" style="height: 140px; min-height: 140px"></td>
                </tr>
            </table>
        </div>

        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="2">Cierre</th>
                </tr>
                <tr>
                    <td class="ogj-lbl">Decisiones o constancias</td>
                    <td class="ogj-line-tall"></td>
                </tr>
                <tr>
                    <td class="ogj-lbl">Próximos pasos (si aplica)</td>
                    <td class="ogj-line"></td>
                </tr>
            </table>
        </div>

        <div class="ogj-block">
            <table class="ogj-tbl" role="presentation">
                <tr>
                    <th colspan="3">Firmas</th>
                </tr>
                <tr>
                    <td style="width:33%" class="ogj-lbl">Trabajador</td>
                    <td style="width:33%" class="ogj-lbl">Presidente / secretario</td>
                    <td style="width:34%" class="ogj-lbl">Testigo (opcional)</td>
                </tr>
                <tr>
                    <td class="ogj-line-tall"></td>
                    <td class="ogj-line-tall"></td>
                    <td class="ogj-line-tall"></td>
                </tr>
            </table>
        </div>
    </x-disciplinary.forms.official-letter-pdf-shell>
</body>
</html>
