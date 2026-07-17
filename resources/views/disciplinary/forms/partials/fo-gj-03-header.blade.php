@props([
    'logoSrc' => '',
    'pageLine' => '',
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
            <td class="ogj-title">CITACIÓN A DILIGENCIA DISCIPLINARIA</td>
            <td class="ogj-meta">
                <table class="ogj-meta-grid" role="presentation">
                    <tr><td class="ogj-meta-code">FO-GJ-03</td></tr>
                    <tr><td>Octubre de 2023</td></tr>
                    <tr><td>Versión 03</td></tr>
                    <tr>
                        <td class="ogj-03-page-line">
                            {{-- Dompdf: canvas pinta “Página N de M” en esta 4.ª fila. --}}
                            @if (filled($pageLine))
                                {{ $pageLine }}
                            @else
                                <span class="ogj-03-page-line-print">&nbsp;</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </tbody>
</table>
