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
}
