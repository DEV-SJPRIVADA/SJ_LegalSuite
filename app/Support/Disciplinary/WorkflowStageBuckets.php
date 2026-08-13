<?php

namespace App\Support\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\StageType;

/**
 * Agrupación A–F del flujo disciplinario (misma lógica en dashboard y listado).
 */
final class WorkflowStageBuckets
{
    public const CLOSED_KEY = 'cerrados';

    /**
     * @return list<array{letter: string, title: string, types: list<StageType>}>
     */
    public static function definitions(): array
    {
        return [
            [
                'letter' => 'A',
                'title' => 'Informe disciplinario',
                'types' => [StageType::INFORME],
            ],
            [
                'letter' => 'B',
                'title' => 'Citación a diligencia',
                'types' => [StageType::CITACION, StageType::REPROGRAMACION, StageType::JUSTIFICACION],
            ],
            [
                'letter' => 'C',
                'title' => 'Diligencia y acta',
                'types' => [StageType::COMITE, StageType::DILIGENCIA],
            ],
            [
                'letter' => 'D',
                'title' => 'Decisión / cierre',
                'types' => [StageType::DECISION],
            ],
            [
                'letter' => 'E',
                'title' => 'Apelación',
                'types' => [StageType::APELACION],
            ],
            [
                'letter' => 'F',
                'title' => 'Segunda instancia',
                'types' => [StageType::SEGUNDA_INSTANCIA],
            ],
        ];
    }

    /**
     * @return list<StageType>
     */
    public static function typesForLetter(string $letter): array
    {
        $letter = strtoupper($letter);

        foreach (self::definitions() as $def) {
            if ($def['letter'] === $letter) {
                return $def['types'];
            }
        }

        return [];
    }

    public static function titleForLetter(string $letter): ?string
    {
        $letter = strtoupper($letter);

        foreach (self::definitions() as $def) {
            if ($def['letter'] === $letter) {
                return $def['title'];
            }
        }

        return null;
    }

    public static function letterForStageType(?StageType $type): ?string
    {
        if ($type === null) {
            return null;
        }

        foreach (self::definitions() as $def) {
            if (in_array($type, $def['types'], true)) {
                return $def['letter'];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function closedStatusValues(): array
    {
        return [
            CaseStatus::FINALIZADO->value,
            CaseStatus::ARCHIVADO->value,
        ];
    }

    /**
     * Clases Tailwind para la letra de etapa (dark dashboard).
     *
     * @return array<string, string>
     */
    public static function letterColorClasses(): array
    {
        return [
            'A' => 'text-indigo-400',
            'B' => 'text-orange-400',
            'C' => 'text-cyan-400',
            'D' => 'text-fuchsia-400',
            'E' => 'text-pink-400',
            'F' => 'text-emerald-400',
        ];
    }

    public static function normalizeFilterKey(string $key): string
    {
        $key = trim($key);

        if ($key === '') {
            return '';
        }

        if (strtolower($key) === self::CLOSED_KEY) {
            return self::CLOSED_KEY;
        }

        return strtoupper($key);
    }

    public static function isValidFilterKey(string $key): bool
    {
        $normalized = self::normalizeFilterKey($key);

        if ($normalized === '') {
            return true;
        }

        if ($normalized === self::CLOSED_KEY) {
            return true;
        }

        return self::titleForLetter($normalized) !== null;
    }
}
