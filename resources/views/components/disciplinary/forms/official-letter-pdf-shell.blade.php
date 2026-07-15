@props([
    'code' => '',
    'headline' => '',
    'phase' => '',
    /** Data URI o URL del logo (PDF embebido). */
    'logoSrc' => '',
    'metaPageLine' => 'Página 1 de 1',
    'metaDate' => 'Mayo de 2024',
    'metaVersion' => 'Versión 04',
    'showMicro' => true,
])

@php
    use Illuminate\Support\Str;
@endphp

@include('disciplinary.forms.partials.official-letter-pdf-styles')

<div class="ogj-wrap">
    <div class="ogj-page">
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
                    <td class="ogj-title">{{ Str::upper($headline) }}</td>
                    <td class="ogj-meta">
                        <table class="ogj-meta-grid" role="presentation">
                            <tr><td class="ogj-meta-code">{{ $code }}</td></tr>
                            <tr><td>{{ $metaDate }}</td></tr>
                            <tr><td>{{ $metaVersion }}</td></tr>
                            <tr><td>{{ $metaPageLine }}</td></tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        @if (filled($phase))
            <p class="ogj-phase">{{ $phase }}</p>
        @endif

        {{ $slot }}

        @if ($showMicro)
            <p class="ogj-micro">{{ $code }} — Uso interno SJ Seguridad — Plantilla en blanco (completar según normativa interna).</p>
        @endif
    </div>
</div>
