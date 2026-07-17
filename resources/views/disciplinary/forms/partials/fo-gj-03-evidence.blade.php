@php
    $guidePattern = $guidePattern ?? static fn (string $size): string => match ($size) {
        'sm' => '_ _ _ _ _ _',
        'lg' => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
        default => '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _',
    };

    $blank = $blank ?? static function (string $value, string $size = 'md') use ($guidePattern): string {
        if (filled($value)) {
            return e($value);
        }

        return '<span class="ogj-03-guide ogj-03-guide-'.$size.'" aria-hidden="true">'.$guidePattern($size).'</span>';
    };
@endphp

<p>Los elementos probatorios que dan lugar al inicio del proceso disciplinario radican en:</p>

<ul class="ogj-03-list">
    <li>
        Informes Disciplinarios
        @if ($blankForDownload ?? true)
            del {!! $blank($informeReportDate ?? '', 'sm') !!}
        @elseif (filled($informeReportDate ?? null))
            del {{ $informeReportDate }}
        @endif
    </li>
</ul>

<p>
    Se corre traslado al trabajador de todas y cada una de las pruebas que fundamentan los cargos formulados. Se le hace saber que, el llamamiento a la diligencia de descargos no es propia de sanción disciplinaria, por el contrario, con ella buscamos garantizar el debido proceso, el derecho a la contradicción y a la defensa, conforme lo cual, podrá usted asistir con dos (02) testigos, controvertir las pruebas en su contra y allegar las pruebas que considere pertinentes informando por escrito al correo relacioneslaborales@sjsp.com.co con mínimo dos (02) horas de anticipación a la diligencia. En caso de tener alguna situación que imposibilite su presencia, deberá remitir dentro de los dos (2) días hábiles siguientes, la debida excusa para fijar nueva fecha, de lo contrario se entiende su renuncia al derecho a la defensa y se tendrán por cierto los hechos que motivaron la apertura del presente proceso disciplinario.
</p>
