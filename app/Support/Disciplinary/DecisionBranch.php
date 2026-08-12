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

    /** Cierre sin sanción escrita (verbal / absuelto / archivado). Acta FO-GJ-45. */
    public const CLOSURE = 'closure';

    public static function forDecision(?Decision $decision): ?string
    {
        if ($decision === null) {
            return null;
        }

        return match ($decision) {
            Decision::SUSPENSION => self::SUSPENSION,
            Decision::TERMINACION_CONTRATO => self::TERMINATION,
            Decision::AMONESTACION_ESCRITA => self::NOTICE,
            Decision::AMONESTACION_VERBAL,
            Decision::ABSUELTO,
            Decision::ARCHIVADO => self::CLOSURE,
            default => null,
        };
    }

    public static function label(string $branch): string
    {
        return match ($branch) {
            self::SUSPENSION => 'Suspensión',
            self::NOTICE => 'Llamado de atención',
            self::TERMINATION => 'Terminación de contrato',
            self::CLOSURE => 'Cierre sin sanción escrita',
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
            self::NOTICE => [Decision::AMONESTACION_ESCRITA],
            self::CLOSURE => [
                Decision::AMONESTACION_VERBAL,
                Decision::ABSUELTO,
                Decision::ARCHIVADO,
            ],
            default => [],
        };
    }
}
