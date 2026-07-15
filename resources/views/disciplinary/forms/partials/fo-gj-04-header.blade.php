@props([
    'logoSrc' => '',
    'pageLine' => 'Página 1 de 2',
])

<table class="ogj-tbl ogj-head-grid" role="presentation">
    <colgroup>
        <col style="width:25%">
        <col style="width:50%">
        <col style="width:25%">
    </colgroup>
    <tbody>
        <tr>
            <td class="ogj-logo-cell">
                <img src="{{ $logoSrc }}" alt="SJ Seguridad">
            </td>
            <td class="ogj-title">ACTA DE DILIGENCIA DISCIPLINARIA</td>
            <td class="ogj-meta">
                <table class="ogj-meta-grid" role="presentation">
                    <tr><td class="ogj-meta-code">FO-GJ-04</td></tr>
                    <tr><td>Septiembre de 2023</td></tr>
                    <tr><td>Versión 02</td></tr>
                    <tr><td>{{ $pageLine }}</td></tr>
                </table>
            </td>
        </tr>
    </tbody>
</table>
