<?php

namespace App\Support\Disciplinary;

/**
 * Reparte FO-GJ-03 en páginas Letter con encabezado en cada hoja planificada.
 *
 * Dompdf NO cabe el cuerpo completo (hasta el párrafo de traslado) + firmas en una
 * sola `.ogj-page`: rebalsa a una hoja física sin header. Reglas:
 * 1) `evidence` nunca comparte hoja con `opening` (corte limpio antes del traslado).
 * 2) El cierre comparte la última hoja solo si cabe en un techo seguro; si no, hoja propia.
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

    /** Capacidad de empaque dentro de una `.ogj-page` (bajo encabezado). */
    private const PAGE_UNITS = 58;

    /** Techo para meter firmas en la misma `.ogj-page` que el último cuerpo. */
    private const MAX_COMBINED_UNITS = 48;

    private const CLOSING_UNITS = 13;

    private const WITNESSES_UNITS = 10;

    private const OPENING_UNITS = 9;

    private const CHARGES_BASE_UNITS = 7;

    private const EVIDENCE_UNITS = 12;

    /** @var int Caracteres por línea en Dompdf con fuente 12px / 7.5in de ancho útil. */
    private const CHARS_PER_LINE = 60;

    /** @var float Factor de holgura para absorber interlineado, márgenes y espaciado real de Dompdf. */
    private const TEXT_GROWTH_FACTOR = 1.3;

    /** @var float Unidades base por cada artículo activo (li con su texto formal). */
    private const UNITS_PER_ARTICLE = 3.5;

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
        // 1. Estimación de Cargos con factor de holgura (1.3) para prever márgenes
        //    e interlineado real de Dompdf.
        $chargesLines = $this->estimateTextLines((string) ($context['chargesDescription'] ?? ''));
        $chargesExtra = max(0, (int) ceil(($chargesLines - 1) * self::TEXT_GROWTH_FACTOR));

        $locationExtras = 0;
        $location = (string) ($context['locationText'] ?? '');
        if ($location !== '' && ! ($context['blankForDownload'] ?? false)) {
            $locationLines = $this->estimateTextLines($location);
            $locationExtras = max(0, (int) ceil(($locationLines - 2) * self::TEXT_GROWTH_FACTOR));
        }

        // 2. Cálculo dinámico de artículos (Fallas disciplinarias):
        //    Cada artículo (li) tiene un bloque de texto fijo largo. Se asigna un
        //    costo base realista (~4.5 uds) por cada artículo activo, más líneas
        //    extra si los numerales son largos y desbordan el renglón.
        $activeArticlesCount = 0;
        $articlesExtraLines = 0;

        foreach (['article66Numerals', 'article68Numerals', 'article76Numerals'] as $field) {
            $val = trim((string) ($context[$field] ?? ''));
            if ($val !== '') {
                $activeArticlesCount++;
                $articlesExtraLines += max(0, $this->estimateTextLines($val) - 1);
            }
        }

        $articlesBaseUnits = $activeArticlesCount * self::UNITS_PER_ARTICLE;
        $articlesExtraUnits = (int) ceil($articlesExtraLines * self::TEXT_GROWTH_FACTOR);

        return [
            ['id' => self::SECTION_OPENING, 'units' => self::OPENING_UNITS + $locationExtras],
            ['id' => self::SECTION_CHARGES, 'units' => self::CHARGES_BASE_UNITS + $chargesExtra],
            ['id' => self::SECTION_ARTICLES, 'units' => (int) ceil($articlesBaseUnits + $articlesExtraUnits)],
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
            $id = $item['id'];

            $mustBreakBeforeEvidence = $id === self::SECTION_EVIDENCE
                && in_array(self::SECTION_OPENING, $current, true);

            if (($mustBreakBeforeEvidence || ($units > $remaining && $current !== []))) {
                $pages[] = [
                    'sections' => $current,
                    'used' => $used,
                    'showClosing' => false,
                ];
                $current = [];
                $used = 0;
                $remaining = self::PAGE_UNITS;
            }

            $current[] = $id;
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
        $sharesOpening = in_array(self::SECTION_OPENING, $pages[$lastIdx]['sections'], true);

        // Nunca firmas en la misma hoja que el inicio del cuerpo (Dompdf rebalsa).
        if ($sharesOpening || $combined > self::MAX_COMBINED_UNITS) {
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
