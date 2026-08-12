<?php

namespace App\Support\Disciplinary;

/**
 * Artículos/numerales de decisión tomados del FO-GJ-03 (misma fuente de verdad).
 *
 * @phpstan-type StatuteBlock array{article_number: string, numerals: string, label?: string}
 */
final class DecisionStatuteArticles
{
    /** @var array<string, string> */
    private const KNOWN_LABELS = [
        '55' => 'Obligaciones especiales',
        '57' => 'Prohibiciones',
        '60' => 'Faltas graves',
    ];

    /**
     * @param  array<string, mixed>  $fo03Payload
     * @param  array<string, mixed>  $decisionPayload
     * @return list<StatuteBlock>
     */
    public static function resolve(array $fo03Payload, array $decisionPayload = []): array
    {
        $fromDecision = self::normalizeBlocks($decisionPayload['statute_articles'] ?? null);
        if ($fromDecision !== [] && self::allHaveNumerals($fromDecision)) {
            return self::withLabels($fromDecision);
        }

        $fromFo03 = self::fromFo03Payload($fo03Payload);
        if ($fromFo03 !== []) {
            // Mezcla: conserva numerales ya editados en decisión si el artículo coincide.
            if ($fromDecision !== []) {
                $byNumber = [];
                foreach ($fromDecision as $block) {
                    if (trim($block['numerals']) !== '') {
                        $byNumber[$block['article_number']] = $block['numerals'];
                    }
                }
                foreach ($fromFo03 as $i => $block) {
                    $num = $byNumber[$block['article_number']] ?? '';
                    if ($num !== '') {
                        $fromFo03[$i]['numerals'] = $num;
                    }
                }
            }

            return self::withLabels($fromFo03);
        }

        // Solo legacy 55/57/60 si ya hay numerales guardados (nunca inventar vacíos).
        $legacy = self::legacyThreeArticles($fo03Payload, $decisionPayload);
        $withNumerals = array_values(array_filter(
            $legacy,
            static fn (array $b): bool => trim($b['numerals']) !== '',
        ));

        return $withNumerals !== [] ? $withNumerals : [];
    }

    /**
     * Misma lectura que FoGj03CitationArticleResolver::blocksFromPayload
     * (statute_articles / articles + claves legacy article_*_numerals).
     *
     * @param  array<string, mixed>  $fo03Payload
     * @return list<StatuteBlock>
     */
    public static function fromFo03Payload(array $fo03Payload): array
    {
        $direct = self::normalizeBlocks($fo03Payload['statute_articles'] ?? $fo03Payload['articles'] ?? null);
        if ($direct !== []) {
            return $direct;
        }

        // Claves legacy alineadas con FoGj03CitationArticleResolver::blocksFromPayload.
        $legacy = [];
        foreach ([
            66 => 'article_66_numerals',
            68 => 'article_68_numerals',
            76 => 'article_76_numerals',
        ] as $number => $key) {
            $numerals = trim((string) ($fo03Payload[$key] ?? ''));
            if ($numerals !== '') {
                $legacy[] = [
                    'article_number' => (string) $number,
                    'numerals' => $numerals,
                ];
            }
        }

        return self::normalizeBlocks($legacy);
    }

    /**
     * @param  list<StatuteBlock>  $blocks
     */
    public static function numeralsFor(array $blocks, string $articleNumber): string
    {
        foreach ($blocks as $block) {
            if ($block['article_number'] === $articleNumber) {
                return $block['numerals'];
            }
        }

        return '';
    }

    /**
     * @param  mixed  $raw
     * @return list<StatuteBlock>
     */
    public static function normalizeBlocks(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $block) {
            if (! is_array($block)) {
                continue;
            }
            $number = trim((string) ($block['article_number'] ?? $block['number'] ?? ''));
            if ($number === '') {
                continue;
            }
            $numerals = $block['numerals'] ?? '';
            if (is_array($numerals)) {
                $numerals = implode(', ', array_map(
                    static fn ($n) => trim((string) $n),
                    array_filter($numerals, static fn ($n) => trim((string) $n) !== ''),
                ));
            } else {
                $numerals = trim((string) $numerals);
            }

            $label = self::KNOWN_LABELS[$number] ?? trim((string) ($block['label'] ?? ''));

            $entry = [
                'article_number' => $number,
                'numerals' => $numerals,
            ];
            if ($label !== '') {
                $entry['label'] = $label;
            }

            $out[] = $entry;
        }

        return array_values($out);
    }

    /**
     * @param  list<array{article_number?: mixed, numerals?: mixed}>  $input
     * @return list<StatuteBlock>
     */
    public static function normalizeInput(array $input): array
    {
        return self::normalizeBlocks($input);
    }

    /**
     * @param  list<StatuteBlock>  $blocks
     * @return list<string>
     */
    public static function missingRequirements(array $blocks): array
    {
        if ($blocks === []) {
            return ['artículos y numerales del FO-GJ-03'];
        }

        $missing = [];
        foreach ($blocks as $block) {
            if (trim($block['numerals']) === '') {
                $missing[] = 'numerales del artículo '.$block['article_number'];
            }
        }

        return $missing;
    }

    /**
     * @param  list<StatuteBlock>  $blocks
     */
    public static function allHaveNumerals(array $blocks): bool
    {
        if ($blocks === []) {
            return false;
        }

        foreach ($blocks as $block) {
            if (trim($block['numerals']) === '') {
                return false;
            }
        }

        return true;
    }

    public static function lineLabel(string $articleNumber, ?string $label = null): string
    {
        $known = $label ?: (self::KNOWN_LABELS[$articleNumber] ?? '');
        if ($known !== '') {
            return 'Artículo '.$articleNumber.' ('.$known.')';
        }

        return 'Artículo '.$articleNumber;
    }

    /**
     * @param  list<StatuteBlock>  $blocks
     * @return list<StatuteBlock>
     */
    private static function withLabels(array $blocks): array
    {
        return array_map(static function (array $block): array {
            $number = $block['article_number'];
            if (! isset($block['label']) || $block['label'] === '') {
                $known = self::KNOWN_LABELS[$number] ?? '';
                if ($known !== '') {
                    $block['label'] = $known;
                }
            }

            return $block;
        }, $blocks);
    }

    /**
     * @param  array<string, mixed>  $fo03Payload
     * @param  array<string, mixed>  $decisionPayload
     * @return list<StatuteBlock>
     */
    private static function legacyThreeArticles(array $fo03Payload, array $decisionPayload): array
    {
        $blocks = [];
        foreach (['55', '57', '60'] as $number) {
            $fromDecision = trim((string) ($decisionPayload['articles_'.$number] ?? ''));
            $fromFo03 = self::numeralsFor(self::fromFo03Payload($fo03Payload), $number);
            $numerals = $fromDecision !== '' ? $fromDecision : $fromFo03;
            $blocks[] = [
                'article_number' => $number,
                'numerals' => $numerals,
                'label' => self::KNOWN_LABELS[$number],
            ];
        }

        return $blocks;
    }
}
