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
                            {{-- Dompdf: página física vía canvas PAGE_NUM/PAGE_COUNT. Pantalla/Browsershot: counters. --}}
                            @if (filled($pageLine))
                                {{ $pageLine }}
                            @else
                                <span class="ogj-03-page-line-print"></span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </tbody>
</table>
