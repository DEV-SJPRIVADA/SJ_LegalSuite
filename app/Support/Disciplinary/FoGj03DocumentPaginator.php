<?php

namespace App\Support\Disciplinary;

/**
 * Reparte FO-GJ-03 en páginas Letter explícitas (una `.ogj-page` = una hoja física).
 *
 * Reglas de producto:
 * 1) El cuerpo fluye continuo: lo que no cabe en la hoja N sigue en N+1 (cargos se trocean).
 * 2) Cada hoja lleva encabezado HTML + “Página N de M”.
 * 3) Único bloque atómico: firmas. Si no caben enteras en la última hoja de cuerpo,
 *    pasan completas a una hoja nueva (nunca partidas a la mitad).
 */
final class FoGj03DocumentPaginator
{
    /** Capacidad bajo el encabezado (cuerpo típico + traslado + firmas + safety). */
    private const PAGE_UNITS = 74;

    /**
     * Holgura extra antes de colgar firmas en la misma hoja que el cuerpo.
     * Evita que Dompdf rebalse y parta la tabla de firmas (o cree hoja sin header).
     */
    private const CLOSING_SAFETY_UNITS = 5;

    private const OPENING_UNITS = 9;

    private const CHARGES_LEAD_UNITS = 4;

    private const CHARGES_TAIL_UNITS = 3;

    private const ARTICLES_BASE_UNITS = 2;

    private const UNITS_PER_ARTICLE = 3.2;

    /** Intro + lista de informes (cabe en huecos pequeños de p.1). */
    private const EVIDENCE_LEAD_UNITS = 4;

    private const CLOSING_UNITS = 11;

    private const WITNESSES_UNITS = 10;

    private const CHARS_PER_LINE = 60;

    private const TEXT_GROWTH_FACTOR = 1.25;

    /** Párrafo de traslado (texto legal fijo); se trocea como los cargos. */
    private const EVIDENCE_TRASLADO_TEXT = 'Se corre traslado al trabajador de todas y cada una de las pruebas que fundamentan los cargos formulados. Se le hace saber que, el llamamiento a la diligencia de descargos no es propia de sanción disciplinaria, por el contrario, con ella buscamos garantizar el debido proceso, el derecho a la contradicción y a la defensa, conforme lo cual, podrá usted asistir con dos (02) testigos, controvertir las pruebas en su contra y allegar las pruebas que considere pertinentes informando por escrito al correo relacioneslaborales@sjsp.com.co con mínimo dos (02) horas de anticipación a la diligencia. En caso de tener alguna situación que imposibilite su presencia, deberá remitir dentro de los dos (2) días hábiles siguientes, la debida excusa para fijar nueva fecha, de lo contrario se entiende su renuncia al derecho a la defensa y se tendrán por cierto los hechos que motivaron la apertura del presente proceso disciplinario.';

    /**
     * Texto legal del traslado (Blade + paginador deben coincidir).
     */
    public static function evidenceTrasladoText(): string
    {
        return self::EVIDENCE_TRASLADO_TEXT;
    }

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
     *     showOpening: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showArticles: bool,
     *     showEvidence: bool,
     *     evidenceShowLead: bool,
     *     evidenceIsContinuation: bool,
     *     evidenceChunk: string,
     *     showClosing: bool,
     * }>
     */
    public function plan(array $context = []): array
    {
        $bodyBlocks = $this->buildBodyBlocks($context);
        $pages = $this->packBodyBlocks($bodyBlocks);
        $pages = $this->attachClosingAtomically($pages, $this->closingUnits($context));

        return $this->finalizePageMeta($pages);
    }

    /**
     * Solo bloques de cuerpo (sin firmas). Las firmas se adjuntan al final.
     *
     * @param  array<string, mixed>  $context
     * @return list<array{type: string, units: int, text?: string}>
     */
    public function buildBodyBlocks(array $context): array
    {
        $blank = (bool) ($context['blankForDownload'] ?? false);
        $chargesText = trim((string) ($context['chargesDescription'] ?? ''));

        $locationExtras = 0;
        $location = trim((string) ($context['locationText'] ?? ''));
        if ($location !== '' && ! $blank) {
            $locationExtras = max(0, (int) ceil(($this->estimateTextLines($location) - 2) * self::TEXT_GROWTH_FACTOR));
        }

        $blocks = [
            ['type' => 'opening', 'units' => self::OPENING_UNITS + $locationExtras],
            ['type' => 'charges_lead', 'units' => self::CHARGES_LEAD_UNITS],
        ];

        if ($blank) {
            $blocks[] = ['type' => 'charges_text', 'units' => 2, 'text' => ''];
        } elseif ($chargesText !== '') {
            $blocks[] = [
                'type' => 'charges_text',
                'units' => max(1, (int) ceil($this->estimateTextLines($chargesText) * self::TEXT_GROWTH_FACTOR)),
                'text' => $chargesText,
            ];
        } else {
            $blocks[] = ['type' => 'charges_text', 'units' => 1, 'text' => ''];
        }

        $blocks[] = ['type' => 'charges_tail', 'units' => self::CHARGES_TAIL_UNITS];
        $blocks[] = ['type' => 'articles', 'units' => $this->articlesUnits($context)];
        $blocks[] = ['type' => 'evidence_lead', 'units' => self::EVIDENCE_LEAD_UNITS];
        $blocks[] = [
            'type' => 'evidence_text',
            'units' => max(1, (int) ceil($this->estimateTextLines(self::EVIDENCE_TRASLADO_TEXT) * self::TEXT_GROWTH_FACTOR)),
            'text' => self::EVIDENCE_TRASLADO_TEXT,
        ];

        return $blocks;
    }

    /**
     * @deprecated Use buildBodyBlocks(); kept for callers/tests that aún esperan el nombre antiguo.
     *
     * @param  array<string, mixed>  $context
     * @return list<array{type: string, units: int, text?: string}>
     */
    public function buildBlocks(array $context): array
    {
        return $this->buildBodyBlocks($context);
    }

    /**
     * Empaca solo el cuerpo: llena cada hoja y continúa en la siguiente.
     *
     * @param  list<array{type: string, units: int, text?: string}>  $blocks
     * @return list<array{
     *     showOpening: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showArticles: bool,
     *     showEvidence: bool,
     *     showClosing: bool,
     *     used: int,
     * }>
     */
    private function packBodyBlocks(array $blocks): array
    {
        $pages = [];
        $current = $this->emptyPage();
        $remaining = self::PAGE_UNITS;

        foreach ($blocks as $block) {
            $type = $block['type'];

            if ($type === 'charges_text' || $type === 'evidence_text') {
                $text = (string) ($block['text'] ?? '');
                $isEvidence = $type === 'evidence_text';

                if ($text === '') {
                    if ($remaining < 1 && ! $this->pageIsEmpty($current)) {
                        $pages[] = $current;
                        $current = $this->emptyPage();
                        $remaining = self::PAGE_UNITS;
                    }
                    if ($isEvidence) {
                        $current['showEvidence'] = true;
                    } else {
                        $current['showCharges'] = true;
                    }
                    $current['used'] += 1;
                    $remaining -= 1;

                    continue;
                }

                while ($text !== '') {
                    if ($remaining < 2 && ! $this->pageIsEmpty($current)) {
                        $pages[] = $current;
                        $current = $this->emptyPage();
                        $remaining = self::PAGE_UNITS;
                    }

                    $maxUnits = max(1, $remaining);
                    [$chunk, $rest] = $this->takeTextForUnits($text, $maxUnits);

                    if ($chunk === '' && ! $this->pageIsEmpty($current)) {
                        $pages[] = $current;
                        $current = $this->emptyPage();
                        $remaining = self::PAGE_UNITS;

                        continue;
                    }

                    if ($chunk === '') {
                        [$chunk, $rest] = $this->takeTextForUnits($text, max(4, (int) ceil(self::PAGE_UNITS / 4)));
                    }

                    $chunkUnits = max(1, (int) ceil($this->estimateTextLines($chunk) * self::TEXT_GROWTH_FACTOR));
                    $chunkUnits = min($chunkUnits, max(1, $remaining));

                    if ($isEvidence) {
                        $current['showEvidence'] = true;
                        $current['evidenceChunk'] = trim($current['evidenceChunk'].' '.$chunk);
                        if (! $current['evidenceShowLead']) {
                            $current['evidenceIsContinuation'] = true;
                        }
                    } else {
                        $current['showCharges'] = true;
                        $current['chargesChunk'] = trim($current['chargesChunk'].' '.$chunk);
                        if (! $current['chargesShowLead']) {
                            $current['chargesIsContinuation'] = true;
                        }
                    }

                    $current['used'] += $chunkUnits;
                    $remaining -= $chunkUnits;
                    $text = $rest;
                }

                continue;
            }

            $units = max(1, (int) $block['units']);

            if ($units > $remaining && ! $this->pageIsEmpty($current)) {
                $pages[] = $current;
                $current = $this->emptyPage();
                $remaining = self::PAGE_UNITS;
            }

            $this->applyBodyBlock($current, $type);
            $current['used'] += $units;
            $remaining -= $units;
        }

        if (! $this->pageIsEmpty($current) || $pages === []) {
            $pages[] = $current;
        }

        return $pages;
    }

    /**
     * @param  array{
     *     showOpening: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showArticles: bool,
     *     showEvidence: bool,
     *     showClosing: bool,
     *     used: int,
     * }  $page
     */
    private function applyBodyBlock(array &$page, string $type): void
    {
        match ($type) {
            'opening' => $page['showOpening'] = true,
            'charges_lead' => (function () use (&$page): void {
                $page['showCharges'] = true;
                $page['chargesShowLead'] = true;
            })(),
            'charges_tail' => (function () use (&$page): void {
                $page['showCharges'] = true;
                $page['chargesShowTail'] = true;
            })(),
            'articles' => $page['showArticles'] = true,
            'evidence_lead' => (function () use (&$page): void {
                $page['showEvidence'] = true;
                $page['evidenceShowLead'] = true;
            })(),
            default => null,
        };
    }

    /**
     * Adjunta el bloque de firmas entero en la última hoja si cabe con holgura;
     * si no, hoja nueva solo con firmas (+ encabezado en Blade).
     *
     * @param  list<array{
     *     showOpening: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showArticles: bool,
     *     showEvidence: bool,
     *     showClosing: bool,
     *     used: int,
     * }>  $pages
     * @return list<array{
     *     showOpening: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showArticles: bool,
     *     showEvidence: bool,
     *     showClosing: bool,
     *     used: int,
     * }>
     */
    private function attachClosingAtomically(array $pages, int $closingUnits): array
    {
        if ($pages === []) {
            $page = $this->emptyPage();
            $page['showClosing'] = true;
            $page['used'] = $closingUnits;

            return [$page];
        }

        $lastIdx = array_key_last($pages);
        $used = (int) $pages[$lastIdx]['used'];

        if (($used + $closingUnits + self::CLOSING_SAFETY_UNITS) <= self::PAGE_UNITS) {
            $pages[$lastIdx]['showClosing'] = true;
            $pages[$lastIdx]['used'] = $used + $closingUnits;

            return $pages;
        }

        $closingPage = $this->emptyPage();
        $closingPage['showClosing'] = true;
        $closingPage['used'] = $closingUnits;
        $pages[] = $closingPage;

        return $pages;
    }

    /**
     * @return array{
     *     showOpening: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showArticles: bool,
     *     showEvidence: bool,
     *     showClosing: bool,
     *     used: int,
     * }
     */
    private function emptyPage(): array
    {
        return [
            'showOpening' => false,
            'showCharges' => false,
            'chargesShowLead' => false,
            'chargesIsContinuation' => false,
            'chargesChunk' => '',
            'chargesShowTail' => false,
            'showArticles' => false,
            'showEvidence' => false,
            'evidenceShowLead' => false,
            'evidenceIsContinuation' => false,
            'evidenceChunk' => '',
            'showClosing' => false,
            'used' => 0,
        ];
    }

    /**
     * @param  array{
     *     showOpening: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showArticles: bool,
     *     showEvidence: bool,
     *     evidenceShowLead: bool,
     *     evidenceIsContinuation: bool,
     *     evidenceChunk: string,
     *     showClosing: bool,
     *     used: int,
     * }  $page
     */
    private function pageIsEmpty(array $page): bool
    {
        return ! $page['showOpening']
            && ! $page['showCharges']
            && ! $page['showArticles']
            && ! $page['showEvidence']
            && ! $page['showClosing']
            && trim($page['chargesChunk']) === ''
            && trim($page['evidenceChunk']) === '';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function articlesUnits(array $context): int
    {
        $blank = (bool) ($context['blankForDownload'] ?? false);
        $active = 0;
        $extraLines = 0;

        foreach (['article66Numerals', 'article68Numerals', 'article76Numerals'] as $field) {
            $val = trim((string) ($context[$field] ?? ''));
            if ($blank || $val !== '') {
                $active++;
                if ($val !== '') {
                    $extraLines += max(0, $this->estimateTextLines($val) - 1);
                }
            }
        }

        $active = max(1, $active);

        return (int) ceil(self::ARTICLES_BASE_UNITS + ($active * self::UNITS_PER_ARTICLE) + ($extraLines * self::TEXT_GROWTH_FACTOR));
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

    /**
     * @return array{0: string, 1: string}
     */
    private function takeTextForUnits(string $text, int $maxUnits): array
    {
        $text = trim($text);
        if ($text === '' || $maxUnits < 1) {
            return ['', $text];
        }

        $maxChars = max(20, $maxUnits * self::CHARS_PER_LINE);
        if (mb_strlen($text) <= $maxChars) {
            return [$text, ''];
        }

        $slice = mb_substr($text, 0, $maxChars);
        $breakAt = mb_strrpos($slice, ' ');
        if ($breakAt !== false && $breakAt > (int) ($maxChars * 0.4)) {
            $slice = mb_substr($slice, 0, $breakAt);
        }

        $slice = rtrim($slice);
        $rest = ltrim(mb_substr($text, mb_strlen($slice)));

        return [$slice, $rest];
    }

    public function estimateTextLines(string $text): int
    {
        $text = trim($text);
        if ($text === '') {
            return 1;
        }

        $lines = 0;
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
     * @param  list<array{
     *     showOpening: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showArticles: bool,
     *     showEvidence: bool,
     *     showClosing: bool,
     *     used: int,
     * }>  $pages
     * @return list<array{
     *     pageNumber: int,
     *     totalPages: int,
     *     pageLine: string,
     *     showOpening: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showArticles: bool,
     *     showEvidence: bool,
     *     showClosing: bool,
     * }>
     */
    private function finalizePageMeta(array $pages): array
    {
        $total = count($pages);

        return array_values(array_map(function (array $page, int $index) use ($total) {
            $pageNumber = $index + 1;
            $chunk = trim((string) ($page['chargesChunk'] ?? ''));
            $evidenceChunk = trim((string) ($page['evidenceChunk'] ?? ''));
            $showCharges = (bool) ($page['showCharges'] ?? false)
                || (bool) ($page['chargesShowLead'] ?? false)
                || (bool) ($page['chargesShowTail'] ?? false)
                || $chunk !== '';
            $showEvidence = (bool) ($page['showEvidence'] ?? false)
                || (bool) ($page['evidenceShowLead'] ?? false)
                || $evidenceChunk !== '';

            return [
                'pageNumber' => $pageNumber,
                'totalPages' => $total,
                'pageLine' => 'Página '.$pageNumber.' de '.$total,
                'showOpening' => (bool) ($page['showOpening'] ?? false),
                'showCharges' => $showCharges,
                'chargesShowLead' => (bool) ($page['chargesShowLead'] ?? false),
                'chargesIsContinuation' => (bool) ($page['chargesIsContinuation'] ?? false) && ! (bool) ($page['chargesShowLead'] ?? false),
                'chargesChunk' => $chunk,
                'chargesShowTail' => (bool) ($page['chargesShowTail'] ?? false),
                'showArticles' => (bool) ($page['showArticles'] ?? false),
                'showEvidence' => $showEvidence,
                'evidenceShowLead' => (bool) ($page['evidenceShowLead'] ?? false),
                'evidenceIsContinuation' => (bool) ($page['evidenceIsContinuation'] ?? false) && ! (bool) ($page['evidenceShowLead'] ?? false),
                'evidenceChunk' => $evidenceChunk,
                'showClosing' => (bool) ($page['showClosing'] ?? false),
            ];
        }, $pages, array_keys($pages)));
    }
}
