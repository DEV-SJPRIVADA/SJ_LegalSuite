<?php

namespace App\Support\Disciplinary;

enum FoGj46HearingLead: string
{
    /** Hubo diligencia (trabajador asistió). */
    case Surtida = 'surtida';

    /** No asistió: solo fue citado. */
    case Citado = 'citado';

    public function label(): string
    {
        return match ($this) {
            self::Surtida => 'Una vez surtida la diligencia (asistió)',
            self::Citado => 'Fue citado(a) a una diligencia (no asistió)',
        };
    }

    /**
     * Frase de respaldo (masculino). Preferir {@see WorkerLegalPhrasing::foGj46HearingLeadPhrase()}.
     */
    public function phrase(): string
    {
        return match ($this) {
            self::Surtida => 'y una vez surtida la',
            self::Citado => 'usted fue citado a una',
        };
    }
}
