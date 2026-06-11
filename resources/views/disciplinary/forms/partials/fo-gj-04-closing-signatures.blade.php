@php
    $blank = $blank ?? static function (?string $value, string $size = 'md'): string {
        if (filled($value)) {
            return e($value);
        }

        return '<span class="ogj-04-guide" aria-hidden="true">_ _ _ _ _ _ _ _ _ _ _ _</span>';
    };
@endphp

<div class="ogj-04-closing-block">
    <p>
        No siendo otro el motivo de la diligencia, siendo las {!! $blank($closingTime, 'sm') !!} horas se da por ésta finalizada, en constancia
        firman las partes intervinientes.
    </p>

    <table class="ogj-04-signatures" role="presentation">
        <tr>
            <td>
                <p><strong>REPRESENTANTE DEL EMPLEADOR:</strong></p>
                <div class="ogj-04-signature-slot">
                    @if ($signatureDataUri ?? null)
                        <img src="{{ $signatureDataUri }}" alt="Firma empleador" class="ogj-04-signature-img">
                    @endif
                </div>
                <div class="ogj-04-sign-line"></div>
                <p><strong>Nombre:</strong> {!! $blank($lawyerName, 'md') !!}</p>
                <p>{{ $lawyerRole ?? '' }}</p>
            </td>
            <td>
                <p><strong>TRABAJADOR,</strong></p>
                <div class="ogj-04-signature-slot"></div>
                <div class="ogj-04-sign-line"></div>
                <p><strong>Nombre:</strong> {!! $blank($workerName, 'md') !!}</p>
                <p><strong>C.C</strong> {!! $blank($workerDocument, 'md') !!}</p>
            </td>
        </tr>
    </table>
</div>
