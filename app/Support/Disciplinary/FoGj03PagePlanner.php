<?php

namespace App\Support\Disciplinary;

/**
 * Decide si el cierre FO-GJ-03 cabe en la misma hoja Letter o va a una página propia.
 * Constantes alineadas a FoGj04PagePlanner (--ogj-font-body 12px).
 *
 * No parte el cuerpo legal: o todo en una hoja, o cuerpo en hoja 1 y firmas (y testigos) en hoja 2.
 */
final class FoGj03PagePlanner
{
    private const PAGE_UNITS = 70;

    /** Encabezado + bloque fijo de citación (sin cargos variables). */
    private const BODY_BASE_UNITS = 50;

    private const CLOSING_UNITS = 16;

    private const WITNESSES_UNITS = 12;

    private const CHARS_PER_LINE = 77;

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
     *     showBody: bool,
     *     showClosing: bool,
     * }>
     */
    public function plan(array $context = []): array
    {
        $blank = (bool) ($context['blankForDownload'] ?? false);
        $evidenceType = (string) ($context['evidenceType'] ?? 'signed');
        $hasWitnesses = ! $blank && $evidenceType === 'refused_witnesses';

        $bodyUnits = $this->estimateBodyUnits($context);
        $closingUnits = self::CLOSING_UNITS + ($hasWitnesses ? self::WITNESSES_UNITS : 0);

        if ($bodyUnits + $closingUnits <= self::PAGE_UNITS) {
            $pages = [[
                'showBody' => true,
                'showClosing' => true,
            ]];
        } else {
            $pages = [
                [
                    'showBody' => true,
                    'showClosing' => false,
                ],
                [
                    'showBody' => false,
                    'showClosing' => true,
                ],
            ];
        }

        return $this->finalizePageMeta($pages);
    }

    /**
     * @param  array{
     *     chargesDescription?: string,
     *     article66Numerals?: string,
     *     article68Numerals?: string,
     *     article76Numerals?: string,
     *     locationText?: string,
     *     blankForDownload?: bool,
     * }  $context
     */
    public function estimateBodyUnits(array $context): int
    {
        $units = self::BODY_BASE_UNITS;

        $units += max(0, $this->estimateTextLines((string) ($context['chargesDescription'] ?? '')) - 1);
        $units += max(0, $this->estimateTextLines((string) ($context['article66Numerals'] ?? '')) - 1);
        $units += max(0, $this->estimateTextLines((string) ($context['article68Numerals'] ?? '')) - 1);
        $units += max(0, $this->estimateTextLines((string) ($context['article76Numerals'] ?? '')) - 1);

        $location = (string) ($context['locationText'] ?? '');
        if ($location !== '' && ! ($context['blankForDownload'] ?? false)) {
            $units += max(0, $this->estimateTextLines($location) - 2);
        }

        return $units;
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
     * @param  list<array{showBody: bool, showClosing: bool}>  $pages
     * @return list<array{
     *     pageNumber: int,
     *     totalPages: int,
     *     pageLine: string,
     *     showBody: bool,
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
                'showBody' => (bool) ($page['showBody'] ?? false),
                'showClosing' => (bool) ($page['showClosing'] ?? false),
            ];
        }, $pages, array_keys($pages)));
    }
}
