<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-GJ-51 · Informe disciplinario</title>
    <style>
        @page { size: Letter; margin: 0.45in; }
        html, body { margin: 0; padding: 0; background: #fff; }
    </style>
</head>
<body>
<x-disciplinary.forms.fo-gj-51-preview
    :use-auth-preparer="false"
    :render-as-pdf="true"
    :worker-name="$workerName"
    :worker-document="$workerDocument"
    :city="$city"
    :shift="$shift"
    :position="$position"
    :fault-other-detail="$faultOtherDetail"
    :observations="$observations"
    :preparer-name="$preparerName"
    :preparer-role="$preparerRole"
    :preparer-signature="$preparerSignature"
    :report-day="$reportDay"
    :report-month="$reportMonth"
    :report-year="$reportYear"
    :fault-left-checked="$faultLeftChecked"
    :fault-right-checked="$faultRightChecked"
    :fault-other-checked="$faultOtherChecked"
    :jur-pd="$jurPd"
    :entrega-gh="$entregaGh"
    :jur-dd="$jurDd"
    :jur-mm="$jurMm"
    :jur-yyyy="$jurYyyy"
    :logo-src="$embeddedLogoSrc"
/>
</body>
</html>
