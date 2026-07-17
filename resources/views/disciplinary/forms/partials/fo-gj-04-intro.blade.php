@php
    $guide = $guide ?? static fn (string $size = 'md') => match ($size) {
        'sm' => '_ _ _ _ _',
        'lg' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        'xl' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        default => '_ _ _ _ _ _ _ _ _ _ _ _',
    };
    $blank = $blank ?? static function (?string $value, string $size = 'md') use ($guide): string {
        if (filled($value)) {
            return e($value);
        }

        return '<span class="ogj-04-guide" aria-hidden="true">'.$guide($size).'</span>';
    };
    $manifestationText = match ($workerManifestation ?? '') {
        'yes' => 'SI QUIERO RESPONDER.',
        'no' => 'NO DESEA RESPONDER.',
        default => '',
    };

    $showIntroLead = (bool) ($showIntroLead ?? true);
    $showCharges = (bool) ($showCharges ?? true);
    $chargesShowLead = (bool) ($chargesShowLead ?? true);
    $chargesIsContinuation = (bool) ($chargesIsContinuation ?? false);
    $chargesChunk = (string) ($chargesChunk ?? ($chargesDescription ?? ''));
    $chargesShowTail = (bool) ($chargesShowTail ?? true);
    $showTermsLead = (bool) ($showTermsLead ?? true);
    $termChunks = is_array($termChunks ?? null) ? $termChunks : [];
    $showIntroManifestation = (bool) ($showIntroManifestation ?? true);
    $showIntroQuizLead = (bool) ($showIntroQuizLead ?? true);
@endphp

@if ($showIntroLead)
    <div class="ogj-04-id">
        <p>NOMBRE. {!! $blank($workerName, 'lg') !!}</p>
        <p>CÉDULA. {!! $blank($workerDocument, 'md') !!}</p>
        <p class="ogj-04-id-last">CARGO. {!! $blank($workerPosition, 'md') !!}</p>
    </div>

    <p class="ogj-04-opening">
        En Santiago de Cali, a los {!! $blank($openingDay, 'sm') !!} días del mes de {!! $blank($openingMonth, 'md') !!}
        de {!! $blank($openingYear, 'sm') !!} en las instalaciones de la compañía SJ Seguridad Privada Ltda., siendo las
        {!! $blank($openingTime, 'sm') !!} horas:
    </p>

    <p class="ogj-04-party-indent ogj-04-party-indent--break-after"><strong>EN REPRESENTACIÓN EL EMPLEADOR:</strong> {!! $blank($lawyerName, 'lg') !!}</p>

    <p>
        De otra parte, en cumplimiento de la citación previa a esta diligencia, enviada mediante comunicación escrita,
        con los respectivos soportes del proceso disciplinario y garantizando su derecho a la defensa y al debido proceso:
    </p>

    <p class="ogj-04-party-indent"><strong>EL TRABAJADOR:</strong> {!! $blank($workerName, 'lg') !!}</p>
@endif

@if ($showCharges)
    <p @class(['ogj-04-charges' => true, 'ogj-04-charges--continuation' => $chargesIsContinuation && ! $chargesShowLead])>
        @if ($chargesShowLead)
            Con el fin de ser escuchado en diligencia disciplinaria sobre la presunta falta cometida el día
            {!! $blank($breachDay, 'sm') !!} de {!! $blank($breachMonth, 'md') !!} del {!! $blank($breachYear, 'sm') !!},
            fecha en la cual, usted:
        @endif
        @if ($blankForDownload && $chargesShowLead)
            <span class="ogj-04-guide ogj-04-guide-lg" aria-hidden="true">{{ $guide('xl') }}</span>
        @elseif (filled($chargesChunk))
            {{ $chargesChunk }}
        @elseif ($chargesShowLead)
            —
        @endif
        @if ($chargesShowTail)
            comprendido como incumplimiento en el Reglamento Interno de Trabajo.
        @endif
    </p>
@endif

@if ($showTermsLead)
    <p>
        Con base a lo anterior se requiere de sus explicaciones y para ello, esta acta se desarrolla en los siguientes términos:
    </p>
@endif

@foreach ($termChunks as $termChunk)
    @php
        $termText = trim((string) ($termChunk['text'] ?? ''));
        $isCont = (bool) ($termChunk['isContinuation'] ?? false);
    @endphp
    @if ($termText !== '')
        <p @class(['ogj-04-list-num', 'ogj-04-list-num--continuation' => $isCont])>{{ $termText }}</p>
    @endif
@endforeach

@if ($showIntroManifestation)
    <p>
        Una vez enterado y entiendo perfectamente sus derechos, EL TRABAJADOR, manifestó:
        @if ($blankForDownload)
            <span class="ogj-04-guide" aria-hidden="true">{{ $guide('md') }}</span>
        @elseif (filled($manifestationText))
            <strong>{{ $manifestationText }}</strong>
        @else
            <span class="ogj-04-guide" aria-hidden="true">{{ $guide('md') }}</span>
        @endif
    </p>
@endif

@if ($showIntroQuizLead)
    <p>
        De esta forma, obedeciendo los lineamientos establecidos en el contrato de trabajo, el reglamento interno de
        trabajo y el Código Sustantivo del Trabajo, se procederá a escuchar la versión libre del trabajador y a efectuar
        el cuestionario relacionado con los hechos que generaron la realización del presente proceso disciplinario:
    </p>
@endif
