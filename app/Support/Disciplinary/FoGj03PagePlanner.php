<?php

namespace App\Support\Disciplinary;

/**
 * Reparte FO-GJ-03 en páginas Letter con encabezado en cada hoja planificada.
 *
 * Regla dura frente a Dompdf: una sola `.ogj-page` no puede “rebalsar” a una hoja PDF
 * intermediaria sin encabezado. Por eso el cuerpo se empaca en páginas planificadas y el
 * cierre solo comparte hoja si cuerpo+firmas caben bajo un techo seguro; si no, el cierre
 * va en su propia `.ogj-page` (con header).
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

    /** Capacidad de empaque del cuerpo (bajo encabezado, caja 7.5in). */
    private const PAGE_UNITS = 78;

    /**
     * Techo cuerpo+cierre en la misma `.ogj-page`. Por encima Dompdf rebalsa
     * (hoja 2 sin encabezado). Valor menor que PAGE_UNITS a propósito.
     */
    private const MAX_COMBINED_UNITS = 48;

    private const CLOSING_UNITS = 13;

    private const WITNESSES_UNITS = 10;

    private const OPENING_UNITS = 12;

    private const CHARGES_BASE_UNITS = 7;

    private const ARTICLES_UNITS = 8;

    private const EVIDENCE_UNITS = 10;

    private const CHARS_PER_LINE = 74;

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
        $pages = $this->distributeSections($sectionUnits);
        $pages = $this->ensureClosingFits($pages, $this->closingUnits($context));

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
     * Cierre en la misma hoja solo si cuerpo+firmas ≤ techo Dompdf-safe.
     * Si no, hoja planificada propia (con encabezado). Nunca overflow sin header.
     *
     * @param  list<array{sections: list<string>, used: int, showClosing: bool}>  $pages
     * @return list<array{sections: list<string>, used: int, showClosing: bool}>
     */
    private function ensureClosingFits(array $pages, int $closingUnits): array
    {
        if ($pages === []) {
            return [[
                'sections' => self::BODY_SECTIONS,
                'used' => array_sum(array_column($this->buildSectionUnits([]), 'units')),
                'showClosing' => true,
            ]];
        }

        $lastIdx = array_key_last($pages);
        $used = (int) $pages[$lastIdx]['used'];
        $combined = $used + $closingUnits;
        $hasEvidence = in_array(self::SECTION_EVIDENCE, $pages[$lastIdx]['sections'], true);

        // Con bloque de traslado (evidence) la hoja Letter ya va casi llena: firmas
        // siempre en otra `.ogj-page` con encabezado (nunca overflow Dompdf).
        if ($hasEvidence || $combined > self::MAX_COMBINED_UNITS) {
            $pages[] = [
                'sections' => [],
                'used' => 0,
                'showClosing' => true,
            ];

            return $pages;
        }

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
