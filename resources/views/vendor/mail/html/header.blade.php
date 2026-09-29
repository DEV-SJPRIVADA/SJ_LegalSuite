@props(['url'])
@php
    $logoUrl = \App\Support\Disciplinary\DisciplinaryAssets::logoPublicUrl();
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: block; text-decoration: none;">
<div class="sj-mail-brand">
@if (is_file(public_path(\App\Support\Disciplinary\DisciplinaryAssets::LOGO_RELATIVE_PATH)))
<img src="{{ $logoUrl }}" alt="{{ config('app.name', 'SJ LegalSuite') }}" width="72" height="72" style="display: block; margin: 0 auto 12px; height: 72px; width: auto; max-height: 72px; border: 0;">
@endif
<span class="sj-mail-brand-title">{{ config('app.name', 'SJ LegalSuite') }}</span>
<span class="sj-mail-brand-accent">Seguridad Jurídica</span>
</div>
</a>
</td>
</tr>
