@props([
    'code' => '',
    'headline' => '',
    'phase' => '',
    /** Data URI o URL del logo (PDF embebido). */
    'logoSrc' => '',
    'metaPageLine' => 'Página 1 de 1',
])

@php
    use Illuminate\Support\Str;
@endphp

@include('disciplinary.forms.partials.official-letter-pdf-styles')

<div class="ogj-wrap">
    <div class="ogj-page">
        <div class="ogj-block">
            <table class="ogj-tbl ogj-head-tbl" role="presentation">
                <colgroup>
                    <col style="width:102px">
                    <col>
                    <col style="width:114px">
                </colgroup>
                <tbody>
                    <tr>
                        <td class="ogj-logo-cell">
                            <div class="ogj-logo-ring">
                                <img src="{{ $logoSrc }}" alt="SJ Seguridad">
                            </div>
                        </td>
                        <td class="ogj-title">{{ Str::upper($headline) }}</td>
                        <td class="ogj-meta">
                            <table role="presentation">
                                <tr><td><div class="ogj-code">{{ $code }}</div></td></tr>
                                <tr><td>Mayo de 2024</td></tr>
                                <tr><td>Versión 04</td></tr>
                                <tr><td>{{ $metaPageLine }}</td></tr>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if (filled($phase))
            <p class="ogj-phase">{{ $phase }}</p>
        @endif

        {{ $slot }}

        <p class="ogj-micro">{{ $code }} — Uso interno SJ Seguridad — Plantilla en blanco (completar según normativa interna).</p>
    </div>
</div>
