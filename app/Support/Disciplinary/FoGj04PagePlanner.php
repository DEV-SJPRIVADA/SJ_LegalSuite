<?php

namespace App\Support\Disciplinary;

/**
 * Reparte el cuestionario FO-GJ-04 en páginas Letter con capacidad estimada (unidades de línea).
 * El bloque de cierre + firmas es atómico: si no cabe entero con el intro/cuerpo,
 * pasa completo a la hoja siguiente (evita “Página 1 de 1” con 2 hojas físicas Dompdf).
 *
 * Constantes calibradas para --ogj-font-body: 12px (escala unificada FO-GJ).
 */
final class FoGj04PagePlanner
{
    private const PAGE_UNITS = 70;

    /** Intro FO-GJ-04 (términos 1–5 + cargos) es alto en Dompdf; 45 subestimaba. */
    private const INTRO_OVERHEAD = 58;

    private const CONTINUATION_OVERHEAD = 8;

    private const QUESTION_BASE_UNITS = 2;

    private const CLOSING_BLOCK_UNITS = 14;

    /** Holgura extra antes de colgar firmas en la misma hoja (anti-rebalse Dompdf). */
    private const CLOSING_SAFETY_UNITS = 6;

    private const CHARS_PER_LINE = 77;

    /**
     * @param  list<array{question: string, answer: string}>  $questions
     * @return list<array{
     *     pageNumber: int,
     *     totalPages: int,
     *     pageLine: string,
     *     showIntro: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>
     * }>
     */
    public function plan(array $questions, bool $blankForDownload = false): array
    {
        $pages = $this->distributeQuestions($questions);
        $pages = $this->ensureClosingFits($pages);

        if ($blankForDownload && $questions === [] && $pages !== []) {
            $pages[0]['showIntro'] = true;
            // Firmas las ubica ensureClosingFits (no forzarlas en p.1: Dompdf rebalsa).
        }

        return $this->finalizePageMeta($pages);
    }

    /**
     * @param  list<array{question: string, answer: string}>  $questions
     * @return list<array{showIntro: bool, showClosing: bool, questions: list<array{number: int, question: string, answer: string}>}>
     */
    private function distributeQuestions(array $questions): array
    {
        if ($questions === []) {
            return [
                [
                    'showIntro' => true,
                    'showClosing' => false,
                    'questions' => [],
                ],
            ];
        }

        $pages = [];
        $showIntro = true;
        $remaining = self::PAGE_UNITS - self::INTRO_OVERHEAD;
        $currentQuestions = [];
        $questionNumber = 1;

        foreach ($questions as $item) {
            $units = $this->estimateQuestionUnits($item);

            if ($units > $remaining && $currentQuestions !== []) {
                $pages[] = [
                    'showIntro' => $showIntro,
                    'showClosing' => false,
                    'questions' => $currentQuestions,
                ];
                $showIntro = false;
                $currentQuestions = [];
                $remaining = self::PAGE_UNITS - self::CONTINUATION_OVERHEAD;
            }

            if ($units > $remaining && $currentQuestions === []) {
                $pages[] = [
                    'showIntro' => $showIntro,
                    'showClosing' => false,
                    'questions' => [],
                ];
                $showIntro = false;
                $remaining = self::PAGE_UNITS - self::CONTINUATION_OVERHEAD;
            }

            $currentQuestions[] = [
                'number' => $questionNumber,
                'question' => $item['question'],
                'answer' => $item['answer'],
            ];
            $questionNumber++;
            $remaining -= $units;
        }

        if ($currentQuestions !== [] || $showIntro) {
            $pages[] = [
                'showIntro' => $showIntro,
                'showClosing' => false,
                'questions' => $currentQuestions,
            ];
        }

        return $pages === [] ? [[
            'showIntro' => true,
            'showClosing' => false,
            'questions' => [],
        ]] : $pages;
    }

    /**
     * @param  list<array{showIntro: bool, showClosing: bool, questions: list<array{number: int, question: string, answer: string}>}>  $pages
     * @return list<array{showIntro: bool, showClosing: bool, questions: list<array{number: int, question: string, answer: string}>}>
     */
    private function ensureClosingFits(array $pages): array
    {
        while ($pages !== [] && ! $this->pageHasRoomForClosing($pages[array_key_last($pages)])) {
            $lastIdx = array_key_last($pages);
            $lastQuestions = $pages[$lastIdx]['questions'];

            if ($lastQuestions === []) {
                $pages[] = [
                    'showIntro' => false,
                    'showClosing' => true,
                    'questions' => [],
                ];

                break;
            }

            $moved = array_pop($lastQuestions);
            $pages[$lastIdx]['questions'] = $lastQuestions;

            if ($lastQuestions === [] && ! ($pages[$lastIdx]['showIntro'] ?? false)) {
                array_pop($pages);
            }

            $pages[] = [
                'showIntro' => false,
                'showClosing' => false,
                'questions' => [$moved],
            ];
        }

        if ($pages === []) {
            $pages[] = [
                'showIntro' => true,
                'showClosing' => true,
                'questions' => [],
            ];
        } else {
            $lastIdx = array_key_last($pages);
            $pages[$lastIdx]['showClosing'] = true;
        }

        return $pages;
    }

    /**
     * @param  array{showIntro: bool, showClosing: bool, questions: list<array{number: int, question: string, answer: string}>}  $page
     */
    private function pageHasRoomForClosing(array $page): bool
    {
        $overhead = ($page['showIntro'] ?? false)
            ? self::INTRO_OVERHEAD
            : self::CONTINUATION_OVERHEAD;

        $used = $overhead;
        foreach ($page['questions'] as $item) {
            $used += $this->estimateQuestionUnits($item);
        }

        return (self::PAGE_UNITS - $used) >= (self::CLOSING_BLOCK_UNITS + self::CLOSING_SAFETY_UNITS);
    }

    /**
     * @param  array{question: string, answer: string}  $item
     */
    public function estimateQuestionUnits(array $item): int
    {
        $questionLines = $this->estimateTextLines((string) ($item['question'] ?? ''));
        $answerLines = $this->estimateTextLines((string) ($item['answer'] ?? ''));

        return self::QUESTION_BASE_UNITS + $questionLines + $answerLines;
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
     * @param  list<array{showIntro: bool, showClosing: bool, questions: list<array{number: int, question: string, answer: string}>}>  $pages
     * @return list<array{
     *     pageNumber: int,
     *     totalPages: int,
     *     pageLine: string,
     *     showIntro: bool,
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
                'showClosing' => (bool) ($page['showClosing'] ?? false),
                'questions' => $page['questions'] ?? [],
            ];
        }, $pages, array_keys($pages)));
    }
}
