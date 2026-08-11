<?php

namespace App\Services\Disciplinary;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\Fault;
use App\Services\Settings\CitationFaultTemplateService;
use Illuminate\Support\Collection;

class FoGj03CitationArticleResolver
{
    public function __construct(
        private readonly CitationFaultTemplateService $templates,
    ) {}

    /**
     * @return list<array{article_number: string, numerals: string}>
     */
    public function resolveForCase(DisciplinaryCase $case): array
    {
        $case->loadMissing('faults');

        $faults = $case->faults;
        if ($faults->isEmpty()) {
            return [];
        }

        if ($faults->count() === 1) {
            return $this->formBlocksFromTemplateBlocks(
                $this->templates->templateBlocksForFault($faults->first()),
            );
        }

        return $this->unionArticlesWithoutNumerals($faults);
    }

    /**
     * @param  list<array{article_number: string, numerals: list<string>}>  $blocks
     * @return list<array{article_number: string, numerals: string}>
     */
    public function formBlocksFromTemplateBlocks(array $blocks): array
    {
        $result = [];
        foreach ($blocks as $block) {
            $articleNumber = trim((string) ($block['article_number'] ?? ''));
            if ($articleNumber === '') {
                continue;
            }

            $numerals = array_values(array_filter(array_map(
                static fn (mixed $code): string => trim((string) $code),
                $block['numerals'] ?? [],
            )));

            $result[] = [
                'article_number' => $articleNumber,
                'numerals' => $this->formatNumeralsForForm($numerals),
            ];
        }

        return $result;
    }

    /**
     * @param  list<array{article_number?: string, numerals?: string}>  $blocks
     * @return list<array{article_number: string, numerals: string, clause_suffix?: string|null}>
     */
    public function normalizeSavedBlocks(array $blocks): array
    {
        $result = [];
        foreach ($blocks as $block) {
            $articleNumber = trim((string) ($block['article_number'] ?? ''));
            if ($articleNumber === '') {
                continue;
            }

            $result[] = [
                'article_number' => $articleNumber,
                'numerals' => trim((string) ($block['numerals'] ?? '')),
                'clause_suffix' => $block['clause_suffix'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * @param  list<string>  $numerals
     */
    public function formatNumeralsForForm(array $numerals): string
    {
        return implode(', ', $numerals);
    }

    /**
     * @return list<array{article_number: string, numerals: string}>
     */
    private function unionArticlesWithoutNumerals(Collection $faults): array
    {
        $articleNumbers = [];

        foreach ($faults as $fault) {
            foreach ($this->templates->templateBlocksForFault($fault) as $block) {
                $number = trim((string) ($block['article_number'] ?? ''));
                if ($number !== '') {
                    $articleNumbers[$number] = true;
                }
            }
        }

        if ($articleNumbers === []) {
            return [];
        }

        $sorted = array_keys($articleNumbers);
        usort($sorted, static fn (string $a, string $b): int => (int) $a <=> (int) $b);

        return array_map(
            static fn (string $number): array => ['article_number' => $number, 'numerals' => ''],
            $sorted,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{article_number: string, numerals: string}>
     */
    public function blocksFromPayload(array $payload): array
    {
        if (! empty($payload['statute_articles']) && is_array($payload['statute_articles'])) {
            return array_map(
                static fn (array $block): array => [
                    'article_number' => (string) ($block['article_number'] ?? ''),
                    'numerals' => (string) ($block['numerals'] ?? ''),
                ],
                $payload['statute_articles'],
            );
        }

        $legacy = [];
        foreach ([
            66 => 'article_66_numerals',
            68 => 'article_68_numerals',
            76 => 'article_76_numerals',
        ] as $number => $key) {
            $numerals = trim((string) ($payload[$key] ?? ''));
            if ($numerals !== '') {
                $legacy[] = [
                    'article_number' => (string) $number,
                    'numerals' => $numerals,
                ];
            }
        }

        return $legacy;
    }
}
