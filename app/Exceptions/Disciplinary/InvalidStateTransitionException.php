<?php

namespace App\Exceptions\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    public static function notAllowed(CaseStatus $from, CaseStatus $to): self
    {
        return new self(sprintf(
            'Transición no permitida: %s → %s',
            $from->label(),
            $to->label(),
        ));
    }

    /** Etapa A: exige al menos una respuesta de planeación en el hilo de agenda antes de citación. */
    public static function agendaPlanningReplyRequired(): self
    {
        return new self(
            'No puede pasar a citación: planeación aún no ha respondido en el hilo de solicitud de agenda (o no hay hilo iniciado por el abogado).'
        );
    }
}
