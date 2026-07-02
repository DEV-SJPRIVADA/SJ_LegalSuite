<?php

namespace App\Support\Disciplinary;

use App\Enums\Disciplinary\Decision;

/**
 * Agrupa tipos de decisión disciplinaria en ramas operativas de Etapa D.
 */
final class DecisionBranch
{
    public const SUSPENSION = 'suspension';

    public const NOTICE = 'notice';

    public const TERMINATION = 'termination';

    public static function forDecision(?Decision $decision): ?string
    {
        if ($decision === null) {
            return null;
        }

        return match ($decision) {
            Decision::SUSPENSION => self::SUSPENSION,
            Decision::TERMINACION_CONTRATO => self::TERMINATION,
            Decision::AMONESTACION_VERBAL,
            Decision::AMONESTACION_ESCRITA,
            Decision::ABSUELTO,
            Decision::ARCHIVADO => self::NOTICE,
            default => null,
        };
    }

    public static function label(string $branch): string
    {
        return match ($branch) {
            self::SUSPENSION => 'Suspensión',
            self::NOTICE => 'Llamado de atención / recordatorio / archivo',
            self::TERMINATION => 'Terminación de contrato',
            default => 'Decisión disciplinaria',
        };
    }

    public static function requiresSuspensionDates(string $branch): bool
    {
        return in_array($branch, [self::SUSPENSION, self::TERMINATION], true);
    }

    public static function requiresHrReview(string $branch): bool
    {
        return $branch === self::TERMINATION;
    }

    /** @return list<Decision> */
    public static function choicesForBranch(string $branch): array
    {
        return match ($branch) {
            self::SUSPENSION => [Decision::SUSPENSION],
            self::TERMINATION => [Decision::TERMINACION_CONTRATO],
            self::NOTICE => [
                Decision::AMONESTACION_VERBAL,
                Decision::AMONESTACION_ESCRITA,
                Decision::ABSUELTO,
                Decision::ARCHIVADO,
            ],
            default => [],
        };
    }
}
