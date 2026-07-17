<?php

namespace App\Support\Disciplinary;

/**
 * Reparte FO-GJ-04 en páginas Letter explícitas (una `.ogj-page` = una hoja física).
 *
 * Reglas (mismo contrato que FO-GJ-03):
 * 1) El cuerpo fluye continuo: intro → preguntas → párrafo de cierre.
 * 2) Cada hoja lleva encabezado HTML + “Página N de M”.
 * 3) Único bloque atómico: tabla de firmas. Si no caben enteras en la última hoja
 *    de cuerpo, pasan completas a una hoja nueva (nunca partidas).
 *
 * Constantes calibradas contra Dompdf Letter + --ogj-font-body: 12px.
 */
final class FoGj04PagePlanner
{
    private const PAGE_UNITS = 70;

    /**
     * El intro (términos 1–5 + cargos) llena casi la hoja Letter en Dompdf.
     * Dejar ~6 u. permite a lo sumo una pregunta muy corta en p.1; dos ya rebalsan.
     */
    private const INTRO_OVERHEAD = 64;

    private const CONTINUATION_OVERHEAD = 2;

    private const QUESTION_BASE_UNITS = 2;

    /** Párrafo “No siendo otro el motivo…” (fluye con el cuerpo, no es atómico). */
    private const CLOSING_TEXT_UNITS = 3;

    /** Tabla de firmas (bloque atómico). */
    private const SIGNATURES_UNITS = 11;

    private const CLOSING_SAFETY_UNITS = 5;

    private const CHARS_PER_LINE = 72;

    /** Dompdf envuelve más ancho que el conteo ideal de caracteres. */
    private const TEXT_GROWTH_FACTOR = 1.35;

    /**
     * @param  list<array{question: string, answer: string}>  $questions
     * @return list<array{
     *     pageNumber: int,
     *     totalPages: int,
     *     pageLine: string,
     *     showIntro: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>
     * }>
     */
    public function plan(array $questions, bool $blankForDownload = false): array
    {
        $pages = $this->packBody($questions, $blankForDownload);
        $pages = $this->attachSignaturesAtomically($pages);

        return $this->finalizePageMeta($pages);
    }

    /**
     * @param  list<array{question: string, answer: string}>  $questions
     * @return list<array{
     *     showIntro: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }>
     */
    private function packBody(array $questions, bool $blankForDownload): array
    {
        $pages = [];
        $current = $this->emptyPage();
        $current['showIntro'] = true;
        $current['used'] = self::INTRO_OVERHEAD;
        $remaining = self::PAGE_UNITS - self::INTRO_OVERHEAD;
        $questionNumber = 1;

        if ($blankForDownload && $questions === []) {
            // Marcador “(…)” en Blade: reserva mínima bajo el intro.
            $current['used'] += 2;
            $remaining -= 2;
        }

        foreach ($questions as $item) {
            $units = $this->estimateQuestionUnits($item);

            if ($units > $remaining && ! $this->pageIsEmptyBody($current)) {
                $pages[] = $current;
                $current = $this->emptyPage();
                $current['used'] = self::CONTINUATION_OVERHEAD;
                $remaining = self::PAGE_UNITS - self::CONTINUATION_OVERHEAD;
            }

            $current['questions'][] = [
                'number' => $questionNumber,
                'question' => $item['question'],
                'answer' => $item['answer'],
            ];
            $questionNumber++;
            $current['used'] += $units;
            $remaining -= $units;
        }

        $closingText = self::CLOSING_TEXT_UNITS;
        if ($closingText > $remaining && ! $this->pageIsEmptyBody($current)) {
            $pages[] = $current;
            $current = $this->emptyPage();
            $current['used'] = self::CONTINUATION_OVERHEAD;
            $remaining = self::PAGE_UNITS - self::CONTINUATION_OVERHEAD;
        }

        $current['showClosingText'] = true;
        $current['used'] += $closingText;

        $pages[] = $current;

        return $pages;
    }

    /**
     * @param  list<array{
     *     showIntro: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }>  $pages
     * @return list<array{
     *     showIntro: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }>
     */
    private function attachSignaturesAtomically(array $pages): array
    {
        if ($pages === []) {
            $page = $this->emptyPage();
            $page['showClosingText'] = true;
            $page['showClosing'] = true;
            $page['used'] = self::CLOSING_TEXT_UNITS + self::SIGNATURES_UNITS;

            return [$page];
        }

        $lastIdx = array_key_last($pages);
        $used = (int) $pages[$lastIdx]['used'];
        $need = self::SIGNATURES_UNITS + self::CLOSING_SAFETY_UNITS;

        if (($used + $need) <= self::PAGE_UNITS) {
            $pages[$lastIdx]['showClosing'] = true;
            $pages[$lastIdx]['used'] = $used + self::SIGNATURES_UNITS;

            return $pages;
        }

        $sigPage = $this->emptyPage();
        $sigPage['showClosing'] = true;
        $sigPage['used'] = self::SIGNATURES_UNITS;
        $pages[] = $sigPage;

        return $pages;
    }

    /**
     * @return array{
     *     showIntro: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }
     */
    private function emptyPage(): array
    {
        return [
            'showIntro' => false,
            'showClosingText' => false,
            'showClosing' => false,
            'questions' => [],
            'used' => 0,
        ];
    }

    /**
     * @param  array{
     *     showIntro: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }  $page
     */
    private function pageIsEmptyBody(array $page): bool
    {
        return ! ($page['showIntro'] ?? false)
            && ($page['questions'] ?? []) === []
            && ! ($page['showClosingText'] ?? false);
    }

    /**
     * @param  array{question: string, answer: string}  $item
     */
    public function estimateQuestionUnits(array $item): int
    {
        $questionLines = $this->estimateTextLines((string) ($item['question'] ?? ''));
        $answerLines = $this->estimateTextLines((string) ($item['answer'] ?? ''));
        $raw = self::QUESTION_BASE_UNITS + $questionLines + $answerLines;

        return max(1, (int) ceil($raw * self::TEXT_GROWTH_FACTOR));
    }

    private function estimateTextLines(string $text): int
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
     *     showIntro: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }>  $pages
     * @return list<array{
     *     pageNumber: int,
     *     totalPages: int,
     *     pageLine: string,
     *     showIntro: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>
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
                'showIntro' => (bool) ($page['showIntro'] ?? false),
                'showClosingText' => (bool) ($page['showClosingText'] ?? false),
                'showClosing' => (bool) ($page['showClosing'] ?? false),
                'questions' => $page['questions'] ?? [],
            ];
        }, $pages, array_keys($pages)));
    }
}
