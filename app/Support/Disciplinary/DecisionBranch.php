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
            Decision::AMONESTACION_ESCRITA => self::NOTICE,
        };
    }

    public static function label(string $branch): string
    {
        return match ($branch) {
            self::SUSPENSION => 'Suspensión',
            self::NOTICE => 'Llamado de atención',
            self::TERMINATION => 'Terminación de contrato',
            default => 'Decisión disciplinaria',
        };
    }

    public static function requiresSuspensionDates(string $branch): bool
    {
        return $branch === self::SUSPENSION;
    }

    /** Terminación: el abogado carga un PDF único de anexos laborales firmados. */
    public static function requiresLawyerTerminationPackage(string $branch): bool
    {
        return $branch === self::TERMINATION;
    }

    /** @deprecated Use requiresLawyerTerminationPackage — ya no hay cola RRHH. */
    public static function requiresHrReview(string $branch): bool
    {
        return self::requiresLawyerTerminationPackage($branch);
    }

    /** @return list<Decision> */
    public static function choicesForBranch(string $branch): array
    {
        return match ($branch) {
            self::SUSPENSION => [Decision::SUSPENSION],
            self::TERMINATION => [Decision::TERMINACION_CONTRATO],
            self::NOTICE => [Decision::AMONESTACION_ESCRITA],
            default => [],
        };
    }
}
