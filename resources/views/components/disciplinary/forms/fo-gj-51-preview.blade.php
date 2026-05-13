{{--
  FO-GJ-51 — grillas separadas con espacio entre bloques (como el formato impreso original).
--}}
@props([
    'workerName' => '',
    'workerDocument' => '',
    /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, array{code: string, name: string}>> */
    'municipalitiesGrouped' => null,
    'city' => '',
    'shift' => '',
    'position' => '',
    'faultOtherDetail' => '',
    'observations' => '',
    'useAuthPreparer' => true,
    'preparerName' => '',
    'preparerRole' => '',
    'preparerSignature' => '',
    'reportDay' => null,
    'reportMonth' => null,
    'reportYear' => null,
    'metaPageLine' => 'Página 1 de 1',
    /** Plantilla sin datos de sesión ni fecha automática (descarga manual). */
    'blankForDownload' => false,
    /** Logo embebido (data URI) para PDF vía Browsershot; si es null se usa asset(). */
    'logoSrc' => null,
    /** @var list<string> */
    'faultLeftChecked' => [],
    /** @var list<string> */
    'faultRightChecked' => [],
    'faultOtherChecked' => false,
    'jurPd' => '',
    'entregaGh' => '',
    'jurDd' => '',
    'jurMm' => '',
    'jurYyyy' => '',
    'renderAsPdf' => false,
])

@php
    use App\Models\ColombianMunicipality;
    use App\Support\Disciplinary\DisciplinaryAssets;
    use App\Support\Disciplinary\FoGj51Catalog;
    use Illuminate\Support\Carbon;

    $municipalitiesGrouped = $municipalitiesGrouped instanceof \Illuminate\Support\Collection
        ? $municipalitiesGrouped
        : ColombianMunicipality::groupedByDepartmentForSelect();

    $faultLeft = FoGj51Catalog::faultLeft();
    $faultRight = FoGj51Catalog::faultRight();

    $resolvedLogo = filled((string) $logoSrc) ? $logoSrc : DisciplinaryAssets::logoPublicUrl();

    $observationsText = trim((string) $observations);
    $user = auth()->user();

    if ($blankForDownload) {
        $resolvedPreparerName = '';
        $resolvedPreparerRole = '';
        $resolvedReportDay = '';
        $resolvedReportMonth = '';
        $resolvedReportYear = '';
    } elseif ($useAuthPreparer && $user) {
        $resolvedPreparerName = $user->name;
        $resolvedPreparerRole = filled($user->position)
            ? $user->position
            : (string) ($user->roles->first()?->name ?? '');
        $now = Carbon::now()->locale('es');
        $resolvedReportDay = $reportDay ?? $now->format('d');
        $resolvedReportMonth = $reportMonth ?? $now->format('m');
        $resolvedReportYear = $reportYear ?? $now->format('Y');
    } else {
        $resolvedPreparerName = $preparerName;
        $resolvedPreparerRole = $preparerRole;
        $now = Carbon::now()->locale('es');
        $resolvedReportDay = $reportDay ?? $now->format('d');
        $resolvedReportMonth = $reportMonth ?? $now->format('m');
        $resolvedReportYear = $reportYear ?? $now->format('Y');
    }

    $preparerFieldsReadonly = ! $blankForDownload && $useAuthPreparer && $user;

    $faultRightCount = count($faultRight);
    $faultRows = max(count($faultLeft), $faultRightCount + 1);
@endphp

<style>
    .fo51-wrap {
        /* 100% del área útil: evita desborde horizontal vs @page margin Letter + segunda hoja en blanco en Chrome. */
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
        margin: 0 auto;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10px;
        line-height: 1.25;
        color: #000;
        background: #fff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .fo51-page {
        width: 100%;
        box-sizing: border-box;
        padding: 0.38in 0.44in 0.34in;
        background: #fff;
        /*
          Importante: NO usar min-height: 11in aquí. El PDF ya aplica @page { margin }
          en Letter; forzar 11in dentro del body supera la altura imprimible y Chromium
          agrega una segunda página en blanco.
        */
        min-height: 0;
    }
    /* Cada bloque = una grilla propia + hueco debajo */
    .fo51-block {
        border: 1px solid #000;
        margin-bottom: 11px;
        box-sizing: border-box;
        background: #fff;
    }
    .fo51-page > .fo51-micro {
        margin-bottom: 0;
    }
    .fo51-tbl {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        border-spacing: 0;
        margin: 0;
    }
    .fo51-tbl td,
    .fo51-tbl th {
        border: 1px solid #000;
        vertical-align: middle;
        padding: 3px 5px;
        background: #fff;
        color: #000;
    }
    .fo51-tbl th {
        font-weight: bold;
        font-size: 9px;
        text-align: left;
    }
    .fo51-logo-cell {
        width: 102px;
        max-width: 102px;
        text-align: center;
        vertical-align: middle;
        padding: 6px !important;
    }
    /* Marco rectangular: el PNG corporativo no debe recortarse en círculo (overflow + radius). */
    .fo51-logo-ring {
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #000;
        padding: 4px;
        box-sizing: border-box;
        max-width: 94px;
    }
    .fo51-logo-ring img {
        max-width: 100%;
        max-height: 72px;
        width: auto;
        height: auto;
        object-fit: contain;
        display: block;
    }
    .fo51-title {
        text-align: center;
        font-size: 13px;
        font-weight: bold;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        padding: 10px 8px !important;
        vertical-align: middle !important;
    }
    .fo51-meta {
        width: 112px;
        padding: 0 !important;
        vertical-align: top;
    }
    .fo51-meta table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
    }
    .fo51-meta td {
        border: 1px solid #000;
        padding: 3px 5px;
        text-align: center;
        font-weight: normal;
        text-transform: none;
    }
    .fo51-meta td.fo51-code {
        font-family: ui-monospace, monospace;
        font-weight: bold;
    }
    .fo51-in {
        width: 100%;
        border: none !important;
        outline: none !important;
        background: #fff !important;
        font: inherit;
        padding: 4px 5px;
        margin: 0;
        box-sizing: border-box;
        color: #000;
    }
    .fo51-in:focus {
        outline: 1px dotted #555 !important;
        outline-offset: 1px;
    }
    textarea.fo51-in {
        display: block;
        min-height: 168px;
        line-height: 1.38;
        text-align: justify;
        resize: vertical;
    }
    .fo51-chk {
        width: 13px;
        height: 13px;
        margin: 0;
        flex-shrink: 0;
        accent-color: #000;
    }
    .fo51-lbl-cap {
        font-weight: bold;
        font-size: 9px;
        text-transform: uppercase;
        padding: 5px 5px !important;
        line-height: 1.2;
    }
    .fo51-fault-head {
        font-weight: bold;
        font-size: 9px;
        text-transform: uppercase;
        padding: 6px 6px !important;
        line-height: 1.25;
    }
    .fo51-fault-line {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        padding: 4px 5px;
        line-height: 1.28;
        min-height: 1.35em;
    }
    .fo51-fault-line > span:first-child {
        flex: 1;
        text-align: left;
    }
    .fo51-obs-head {
        font-weight: bold;
        font-size: 9px;
        padding: 6px 7px !important;
        line-height: 1.3;
        border-bottom: 1px solid #000 !important;
        text-transform: uppercase;
    }
    .fo51-obs-head .fo51-obs-sub {
        text-transform: none;
        font-weight: bold;
    }
    .fo51-sign-cap th {
        text-align: center !important;
        text-transform: uppercase;
        font-size: 9px;
        padding: 6px 4px !important;
    }
    .fo51-sign-note td {
        text-align: center !important;
        font-style: italic;
        font-size: 9px;
        padding: 6px 8px !important;
        border-top: 1px solid #000 !important;
    }
    th.fo51-foot-band {
        background: #e0e0e0 !important;
        text-align: center !important;
        font-size: 9px;
        text-transform: uppercase;
        padding: 7px 6px !important;
        letter-spacing: 0.02em;
        border-bottom: 1px solid #000 !important;
    }
    .fo51-micro {
        font-size: 7px;
        color: #555;
        text-align: center;
        padding: 6px 8px 0;
        line-height: 1.25;
        border: none !important;
        margin-top: 2px;
        margin-bottom: 0;
    }
    @media (max-width: 900px) {
        .fo51-wrap {
            min-width: 0;
            width: 100%;
        }
        textarea.fo51-in {
            min-height: 140px;
        }
    }
</style>

<div class="fo51-wrap">
    <div class="fo51-page">
        {{-- 1 · Encabezado --}}
        <div class="fo51-block">
            <table class="fo51-tbl" role="presentation">
                <colgroup>
                    <col style="width:102px">
                    <col>
                    <col style="width:114px">
                </colgroup>
                <tbody>
                    <tr>
                        <td class="fo51-logo-cell">
                            <div class="fo51-logo-ring">
                                <img src="{{ $resolvedLogo }}" alt="SJ Seguridad">
                            </div>
                        </td>
                        <td class="fo51-title">INFORME DISCIPLINARIO</td>
                        <td class="fo51-meta">
                            <table role="presentation">
                                <tr><td class="fo51-code">FO-GJ-51</td></tr>
                                <tr><td>Mayo de 2024</td></tr>
                                <tr><td>Versión 04</td></tr>
                                <tr><td>{{ $metaPageLine }}</td></tr>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- 2 · Fecha --}}
        <div class="fo51-block">
            <table class="fo51-tbl" role="presentation">
                <tr>
                    <td style="width:12%" class="fo51-lbl-cap">FECHA:</td>
                    <td style="width:88%">
                        <span style="display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap">
                            <input type="text" class="fo51-in" name="fo51_report_dd" maxlength="2" inputmode="numeric" value="{{ $resolvedReportDay }}" style="width:2.1rem;text-align:center;border:1px solid #000!important;padding:3px">
                            <span style="font-weight:bold">DD</span>
                            <input type="text" class="fo51-in" name="fo51_report_mm" maxlength="2" inputmode="numeric" value="{{ $resolvedReportMonth }}" style="width:2.1rem;text-align:center;border:1px solid #000!important;padding:3px">
                            <span style="font-weight:bold">MM</span>
                            <input type="text" class="fo51-in" name="fo51_report_yyyy" maxlength="4" inputmode="numeric" value="{{ $resolvedReportYear }}" style="width:3.2rem;text-align:center;border:1px solid #000!important;padding:3px">
                            <span style="font-weight:bold">AAAA</span>
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- 3 · Datos del trabajador --}}
        <div class="fo51-block">
            <table class="fo51-tbl" role="presentation">
                <colgroup>
                    <col style="width:15%">
                    <col style="width:35%">
                    <col style="width:10%">
                    <col style="width:12%">
                    <col style="width:13%">
                    <col style="width:15%">
                </colgroup>
                <tr>
                    <td class="fo51-lbl-cap">NOMBRE DEL TRABAJADOR:</td>
                    <td colspan="3"><input type="text" name="fo51_worker_name" class="fo51-in" value="{{ $workerName }}" autocomplete="off"></td>
                    <td class="fo51-lbl-cap">CC:</td>
                    <td><input type="text" name="fo51_worker_document" class="fo51-in" value="{{ $workerDocument }}" autocomplete="off" inputmode="numeric"></td>
                </tr>
                <tr>
                    <td class="fo51-lbl-cap">CIUDAD:</td>
                    <td>
                        @if ($renderAsPdf ?? false)
                            <span class="fo51-static">{{ $city }}</span>
                        @else
                            <select name="fo51_municipality_code"
                                class="fo51-in fo51-select-mun"
                                style="width:100%;max-width:100%;box-sizing:border-box"
                                @if (! $blankForDownload) required @endif>
                                <option value="">{{ $blankForDownload ? '—' : '— Elija municipio (DIVIPOLA) —' }}</option>
                                @foreach ($municipalitiesGrouped as $deptName => $rows)
                                    <optgroup label="{{ $deptName }}">
                                        @foreach ($rows as $mun)
                                            <option value="{{ $mun['code'] }}" @selected(old('fo51_municipality_code', '') === $mun['code'])>
                                                {{ $mun['name'] }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        @endif
                    </td>
                    <td class="fo51-lbl-cap">TURNO:</td>
                    <td><input type="text" name="fo51_shift" class="fo51-in" value="{{ $shift }}"></td>
                    <td class="fo51-lbl-cap">PUESTO:</td>
                    <td><input type="text" name="fo51_position" class="fo51-in" value="{{ $position }}"></td>
                </tr>
            </table>
        </div>

        {{-- 4 · Faltas (texto + casilla a la derecha como en el papel) --}}
        <div class="fo51-block">
            <table class="fo51-tbl" role="presentation">
                <thead>
                    <tr>
                        <th colspan="4" class="fo51-fault-head">
                            SEÑALE CON UNA EQUIS (X) LA FALTA COMETIDA POR EL COLABORADOR:
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @for ($r = 0; $r < $faultRows; $r++)
                        <tr>
                            <td colspan="2" style="width:50%;padding:0!important">
                                @if (isset($faultLeft[$r]))
                                    <div class="fo51-fault-line">
                                        <span>{{ $faultLeft[$r] }}</span>
                                        <input type="checkbox" name="fo51_fault_left[]" value="{{ $faultLeft[$r] }}" class="fo51-chk" title="Marcar falta" aria-label="Marcar: {{ $faultLeft[$r] }}"
                                            @checked(in_array($faultLeft[$r], $faultLeftChecked, true))>
                                    </div>
                                @endif
                            </td>
                            <td colspan="2" style="width:50%;padding:0!important">
                                @if ($r < $faultRightCount)
                                    <div class="fo51-fault-line">
                                        <span>{{ $faultRight[$r] }}</span>
                                        <input type="checkbox" name="fo51_fault_right[]" value="{{ $faultRight[$r] }}" class="fo51-chk" title="Marcar falta" aria-label="Marcar: {{ $faultRight[$r] }}"
                                            @checked(in_array($faultRight[$r], $faultRightChecked, true))>
                                    </div>
                                @elseif ($r === $faultRightCount)
                                    <div class="fo51-fault-line">
                                        <span style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;flex:1">
                                            <strong>Otros</strong>
                                            <input type="checkbox" name="fo51_fault_other_chk" value="1" class="fo51-chk" title="Otros" aria-label="Otros"
                                                @checked($faultOtherChecked)>
                                            <span>¿Cuál?</span>
                                            <input type="text" name="fo51_fault_other_detail" value="{{ $faultOtherDetail }}" class="fo51-in" style="flex:1;min-width:8rem;border-bottom:1px solid #000!important;padding:2px 4px!important">
                                        </span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        {{-- 5 · Observaciones / hechos --}}
        <div class="fo51-block">
            <table class="fo51-tbl" role="presentation">
                <tr>
                    <td class="fo51-obs-head" colspan="1">
                        OBSERVACIONES / HECHOS <span class="fo51-obs-sub">Explicación del caso concreto y la situación (relación de pruebas)</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0!important;vertical-align:top">
                        <textarea name="fo51_observations" class="fo51-in" rows="10">{{ $observationsText }}</textarea>
                    </td>
                </tr>
            </table>
        </div>

        {{-- 6 · Nombre, cargo, fecha elaboración + leyenda --}}
        <div class="fo51-block">
            <table class="fo51-tbl fo51-sign-cap" role="presentation">
                <thead>
                    <tr>
                        <th style="width:34%">NOMBRE</th>
                        <th style="width:33%">CARGO</th>
                        <th style="width:33%">FIRMA</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:0!important;height:38px">
                            <input type="text" name="fo51_preparer_name" class="fo51-in" @if ($preparerFieldsReadonly) readonly @endif value="{{ $resolvedPreparerName }}" style="height:36px">
                        </td>
                        <td style="padding:0!important">
                            <input type="text" name="fo51_preparer_role" class="fo51-in" @if ($preparerFieldsReadonly) readonly @endif value="{{ $resolvedPreparerRole }}" style="height:36px">
                        </td>
                        <td style="padding:0!important">
                            <input type="text" name="fo51_preparer_signature" class="fo51-in" value="{{ $preparerSignature }}" autocomplete="off" style="height:36px">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="fo51-sign-note">Nombre, cargo y firma de quien elaboró el informe</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- 7 · Gestión jurídica (grilla aparte; barra gris como en el formato base) --}}
        <div class="fo51-block">
            <table class="fo51-tbl" role="presentation">
                <thead>
                    <tr>
                        <th colspan="5" class="fo51-foot-band">ESPACIO EXCLUSIVO PARA GESTIÓN JURÍDICA</th>
                    </tr>
                    <tr>
                        <th class="fo51-lbl-cap" style="text-transform:none;width:28%">JUR-PD-</th>
                        <th class="fo51-lbl-cap" style="text-transform:none;width:32%">ENTREGA A G.H</th>
                        <th class="fo51-lbl-cap" style="width:13%;text-align:center">DD</th>
                        <th class="fo51-lbl-cap" style="width:13%;text-align:center">MM</th>
                        <th class="fo51-lbl-cap" style="width:14%;text-align:center">AAAA</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:0!important;height:36px"><input type="text" name="fo51_jur_pd" class="fo51-in" style="height:34px" value="{{ $jurPd }}"></td>
                        <td style="padding:0!important"><input type="text" name="fo51_entrega_gh" class="fo51-in" style="height:34px" value="{{ $entregaGh }}"></td>
                        <td style="padding:0!important"><input type="text" name="fo51_jur_dd" maxlength="2" class="fo51-in" style="height:34px;text-align:center" value="{{ $jurDd }}"></td>
                        <td style="padding:0!important"><input type="text" name="fo51_jur_mm" maxlength="2" class="fo51-in" style="height:34px;text-align:center" value="{{ $jurMm }}"></td>
                        <td style="padding:0!important"><input type="text" name="fo51_jur_yyyy" maxlength="4" class="fo51-in" style="height:34px;text-align:center" value="{{ $jurYyyy }}"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="fo51-micro">
            FO-GJ-51 - Uso interno SJ Seguridad - Reproducción no autorizada prohibida.
        </p>
    </div>
</div>

@if ($useAuthPreparer && $user && ! $blankForDownload)
    <p style="font-size:10px;color:#64748b;text-align:center;max-width:8.5in;margin:12px auto 0;padding:0 8px">
        Nombre y cargo del elaborador se cargan desde su sesión; la firma se diligencia manualmente.
    </p>
@endif
