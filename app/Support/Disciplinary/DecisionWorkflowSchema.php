<?php

namespace App\Support\Disciplinary;

use Illuminate\Support\Facades\Schema;

/**
 * Comprueba si la migración de Etapa D está aplicada en disciplinary_cases.
 */
final class DecisionWorkflowSchema
{
    public static function isReady(): bool
    {
        return Schema::hasColumn('disciplinary_cases', 'decision_evidence_uploaded_at');
    }
}
