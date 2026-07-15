<?php

namespace App\Support\Disciplinary;

/**
 * Reparte FO-GJ-03 en páginas Letter con encabezado en cada hoja.
 * Secciones de cuerpo + cierre; “Página N de M” alineado al plan.
 *
 * Constantes conservadoras para --ogj-font-body 12px / caja 7.5in (evita overflow Dompdf).
 */
final class FoGj03PagePlanner
{
    public const SECTION_OPENING = 'opening';

    public const SECTION_CHARGES = 'charges';

    public const SECTION_ARTICLES = 'articles';

    public const SECTION_EVIDENCE = 'evidence';

    /** @var list<string> */
    public const BODY_SECTIONS = [
        self::SECTION_OPENING,
        self::SECTION_CHARGES,
        self::SECTION_ARTICLES,
        self::SECTION_EVIDENCE,
    ];

    private const PAGE_UNITS = 52;

    private const CLOSING_UNITS = 16;

    private const WITNESSES_UNITS = 12;

    private const OPENING_UNITS = 16;

    private const CHARGES_BASE_UNITS = 10;

    private const ARTICLES_UNITS = 12;

    private const EVIDENCE_UNITS = 16;

    private const CHARS_PER_LINE = 70;

    /**
     * @param  array{
     *     chargesDescription?: string,
     *     article66Numerals?: string,
     *     article68Numerals?: string,
     *     article76Numerals?: string,
     *     locationText?: string,
     *     blankForDownload?: bool,
     *     evidenceType?: string,
     *     witnesses?: list<mixed>,
     * }  $context
     * @return list<array{
     *     pageNumber: int,
     *     totalPages: int,
     *     pageLine: string,
     *     sections: list<string>,
     *     showClosing: bool,
     * }>
     */
    public function plan(array $context = []): array
    {
        $sectionUnits = $this->buildSectionUnits($context);
        $costById = [];
        foreach ($sectionUnits as $item) {
            $costById[$item['id']] = $item['units'];
        }

        $pages = $this->distributeSections($sectionUnits);
        $pages = $this->ensureClosingFits($pages, $costById, $this->closingUnits($context));

        return $this->finalizePageMeta($pages);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array{id: string, units: int}>
     */
    public function buildSectionUnits(array $context): array
    {
        $chargesExtra = max(0, $this->estimateTextLines((string) ($context['chargesDescription'] ?? '')) - 1);
        $locationExtra = 0;
        $location = (string) ($context['locationText'] ?? '');
        if ($location !== '' && ! ($context['blankForDownload'] ?? false)) {
            $locationExtra = max(0, $this->estimateTextLines($location) - 2);
        }

        $articlesExtra = max(0, $this->estimateTextLines((string) ($context['article66Numerals'] ?? '')) - 1)
            + max(0, $this->estimateTextLines((string) ($context['article68Numerals'] ?? '')) - 1)
            + max(0, $this->estimateTextLines((string) ($context['article76Numerals'] ?? '')) - 1);

        return [
            ['id' => self::SECTION_OPENING, 'units' => self::OPENING_UNITS + $locationExtra],
            ['id' => self::SECTION_CHARGES, 'units' => self::CHARGES_BASE_UNITS + $chargesExtra],
            ['id' => self::SECTION_ARTICLES, 'units' => self::ARTICLES_UNITS + $articlesExtra],
            ['id' => self::SECTION_EVIDENCE, 'units' => self::EVIDENCE_UNITS],
        ];
    }

    /**
     * @param  list<array{id: string, units: int}>  $sectionUnits
     * @return list<array{sections: list<string>, used: int, showClosing: bool}>
     */
    private function distributeSections(array $sectionUnits): array
    {
        $pages = [];
        $current = [];
        $used = 0;
        $remaining = self::PAGE_UNITS;

        foreach ($sectionUnits as $item) {
            $units = max(1, (int) $item['units']);

            if ($units > $remaining && $current !== []) {
                $pages[] = [
                    'sections' => $current,
                    'used' => $used,
                    'showClosing' => false,
                ];
                $current = [];
                $used = 0;
                $remaining = self::PAGE_UNITS;
            }

            $current[] = $item['id'];
            $used += $units;
            $remaining -= $units;
        }

        if ($current !== []) {
            $pages[] = [
                'sections' => $current,
                'used' => $used,
                'showClosing' => false,
            ];
        }

        return $pages === [] ? [['sections' => [], 'used' => 0, 'showClosing' => false]] : $pages;
    }

    /**
     * @param  list<array{sections: list<string>, used: int, showClosing: bool}>  $pages
     * @param  array<string, int>  $costById
     * @return list<array{sections: list<string>, used: int, showClosing: bool}>
     */
    private function ensureClosingFits(array $pages, array $costById, int $closingUnits): array
    {
        if ($pages === []) {
            return [[
                'sections' => self::BODY_SECTIONS,
                'used' => array_sum($costById),
                'showClosing' => true,
            ]];
        }

        while ($pages !== [] && (self::PAGE_UNITS - $pages[array_key_last($pages)]['used']) < $closingUnits) {
            $lastIdx = array_key_last($pages);
            $lastSections = $pages[$lastIdx]['sections'];

            if ($lastSections === [] || count($lastSections) === 1) {
                $pages[] = [
                    'sections' => [],
                    'used' => 0,
                    'showClosing' => true,
                ];

                return $pages;
            }

            $moved = array_pop($lastSections);
            $movedCost = $costById[$moved] ?? 8;
            $pages[$lastIdx]['sections'] = $lastSections;
            $pages[$lastIdx]['used'] = max(0, $pages[$lastIdx]['used'] - $movedCost);
            $pages[] = [
                'sections' => [$moved],
                'used' => $movedCost,
                'showClosing' => false,
            ];
        }

        $lastIdx = array_key_last($pages);
        $pages[$lastIdx]['showClosing'] = true;

        return $pages;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function closingUnits(array $context): int
    {
        $blank = (bool) ($context['blankForDownload'] ?? false);
        $evidenceType = (string) ($context['evidenceType'] ?? 'signed');
        $hasWitnesses = ! $blank && $evidenceType === 'refused_witnesses';

        return self::CLOSING_UNITS + ($hasWitnesses ? self::WITNESSES_UNITS : 0);
    }

    private function estimateTextLines(string $text): int
    {
        $text = trim($text);
        if ($text === '') {
            return 1;
        }

        $lines = 1;
        foreach (preg_split('/\R/u', $text) ?: [] as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                $lines++;

                continue;
            }

            $lines += max(1, (int) ceil(mb_strlen($segment) / self::CHARS_PER_LINE));
        }

        return max(1, $lines);
    }

    /**
     * @param  list<array{sections: list<string>, used?: int, showClosing: bool}>  $pages
     * @return list<array{
     *     pageNumber: int,
     *     totalPages: int,
     *     pageLine: string,
     *     sections: list<string>,
     *     showClosing: bool,
     * }>
     */
    private function finalizePageMeta(array $pages): array
    {
        $total = count($pages);

        return array_values(array_map(function (array $page, int $index) use ($total) {
            $pageNumber = $index + 1;

            return [
                'pageNumber' => $pageNumber,
                'totalPages' => $total,
                'pageLine' => 'Página '.$pageNumber.' de '.$total,
                'sections' => array_values($page['sections'] ?? []),
                'showClosing' => (bool) ($page['showClosing'] ?? false),
            ];
        }, $pages, array_keys($pages)));
    }
}
