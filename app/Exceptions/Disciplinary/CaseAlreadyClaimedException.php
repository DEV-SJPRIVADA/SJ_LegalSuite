<?php

namespace App\Exceptions\Disciplinary;

use RuntimeException;

/** Otro abogado reclamó el caso antes (asignación atómica falló). */
class CaseAlreadyClaimedException extends RuntimeException {}
