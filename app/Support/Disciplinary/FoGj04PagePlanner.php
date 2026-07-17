<?php

namespace App\Support\Disciplinary;

/**
 * Reparte FO-GJ-04 en páginas Letter explícitas (una `.ogj-page` = una hoja física).
 *
 * Mismo contrato que FoGj03DocumentPaginator:
 * 1) Cuerpo continuo: casi todo se trocea (cargos, términos, cola intro, respuestas).
 * 2) Cada hoja lleva encabezado HTML + “Página N de M”.
 * 3) Único bloque atómico: tabla de firmas.
 *
 * Constantes calibradas contra Dompdf Letter + --ogj-font-body: 12px.
 */
final class FoGj04PagePlanner
{
    /**
     * Capacidad Dompdf Letter.
     * Probe corto: con PAGE alto + body denso, p.1 tragaba preguntas+cierre y Dompdf
     * abría hoja física extra (planned=2 phys=3). Bajar PAGE y dejar cola intro en p.1.
     */
    private const PAGE_UNITS = 62;

    private const CLOSING_SAFETY_UNITS = 5;

    private const INTRO_LEAD_UNITS = 14;

    private const CHARGES_TAIL_UNITS = 2;

    private const TERMS_LEAD_UNITS = 2;

    private const INTRO_MANIFESTATION_UNITS = 2;

    private const INTRO_QUIZ_LEAD_UNITS = 3;

    private const QUESTION_TITLE_UNITS = 3;

    private const CLOSING_TEXT_UNITS = 3;

    private const SIGNATURES_UNITS = 12;

    private const CHARS_PER_LINE = 64;

    private const TEXT_GROWTH_FACTOR = 1.28;

    /**
     * Textos legales de los términos (Blade debe coincidir).
     *
     * @return array<int, string>
     */
    public static function termTexts(): array
    {
        return [
            1 => '1. Su asistencia a esta diligencia es de carácter meramente administrativo laboral y de manera voluntaria.',
            2 => '2. En garantía de su Derecho de Defensa y Debido Proceso tiene derecho a no declarar contra sí mismo, por lo que está en libertad de responder o no responder a los cargos que se le imputarán y hechos que se le expondrán.',
            3 => '3. Si decide responder, se le pide que lo haga de manera espontánea, concreta y fiel con la realidad de los hechos tal como a su forma de ser sucedieron, aceptando o no aceptando los cargos que se le imputarán, o, dando las explicaciones que considere, pudiendo solicitar las pruebas que tiendan a justificar, atenuar, o demostrar su no participación en los hechos que se le expondrán como soporte de dichos cargos.',
            4 => '4. Una vez iniciada esta diligencia, en cualquier momento podrá darla por terminada manifestado que no continuará respondiendo, por lo que esta quedará en el estado en que se encuentre, sin que pueda retirar, aclarar o adicionar lo que hasta ese instante hubiese manifestado.',
            5 => '5. Si por cualquier motivo se negare a firmar el acta de esta diligencia, EL EMPLEADOR recurrirá a dos (2) trabajadores testigos que darán fe con su firma de la veracidad de tal situación.',
        ];
    }

    /**
     * @param  array{
     *     questions?: list<array{question: string, answer: string}>,
     *     chargesDescription?: string,
     *     blankForDownload?: bool,
     * }  $context
     * @return list<array{
     *     pageNumber: int,
     *     totalPages: int,
     *     pageLine: string,
     *     showIntroLead: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showTermsLead: bool,
     *     termChunks: list<array{number: int, text: string, isContinuation: bool}>,
     *     showIntroManifestation: bool,
     *     showIntroQuizLead: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string, showTitle: bool, isAnswerContinuation: bool}>
     * }>
     */
    public function plan(array $context = []): array
    {
        $bodyBlocks = $this->buildBodyBlocks($context);
        $pages = $this->packBodyBlocks($bodyBlocks);
        $pages = $this->attachSignaturesAtomically($pages);

        return $this->finalizePageMeta($pages);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array{type: string, units: int, text?: string, number?: int, question?: string, termNumber?: int}>
     */
    public function buildBodyBlocks(array $context): array
    {
        $blank = (bool) ($context['blankForDownload'] ?? false);
        $chargesText = trim((string) ($context['chargesDescription'] ?? ''));
        $questions = $this->normalizeQuestions($context['questions'] ?? []);

        $blocks = [
            ['type' => 'intro_lead', 'units' => self::INTRO_LEAD_UNITS],
            ['type' => 'charges_lead', 'units' => 3],
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
        $blocks[] = ['type' => 'terms_lead', 'units' => self::TERMS_LEAD_UNITS];

        foreach (self::termTexts() as $termNumber => $termText) {
            $blocks[] = [
                'type' => 'term_text',
                'units' => max(1, (int) ceil($this->estimateTextLines($termText) * self::TEXT_GROWTH_FACTOR)),
                'text' => $termText,
                'termNumber' => $termNumber,
            ];
        }

        $blocks[] = ['type' => 'intro_manifestation', 'units' => self::INTRO_MANIFESTATION_UNITS];
        $blocks[] = ['type' => 'intro_quiz_lead', 'units' => self::INTRO_QUIZ_LEAD_UNITS];

        $number = 1;
        foreach ($questions as $item) {
            $blocks[] = [
                'type' => 'question_title',
                'units' => self::QUESTION_TITLE_UNITS + max(0, $this->estimateTextLines($item['question']) - 1),
                'number' => $number,
                'question' => $item['question'],
            ];

            $answer = $item['answer'];
            if ($blank) {
                $blocks[] = [
                    'type' => 'answer_text',
                    'units' => 2,
                    'text' => '',
                    'number' => $number,
                ];
            } elseif ($answer !== '') {
                $blocks[] = [
                    'type' => 'answer_text',
                    'units' => max(1, (int) ceil($this->estimateTextLines($answer) * self::TEXT_GROWTH_FACTOR)),
                    'text' => $answer,
                    'number' => $number,
                ];
            } else {
                $blocks[] = [
                    'type' => 'answer_text',
                    'units' => 1,
                    'text' => '',
                    'number' => $number,
                ];
            }
            $number++;
        }

        $blocks[] = ['type' => 'closing_text', 'units' => self::CLOSING_TEXT_UNITS];

        return $blocks;
    }

    /**
     * @param  list<array{type: string, units: int, text?: string, number?: int, question?: string, termNumber?: int}>  $blocks
     * @return list<array<string, mixed>>
     */
    private function packBodyBlocks(array $blocks): array
    {
        $pages = [];
        $current = $this->emptyPage();
        $remaining = self::PAGE_UNITS;

        foreach ($blocks as $block) {
            $type = (string) $block['type'];

            if ($type === 'charges_text' || $type === 'term_text' || $type === 'answer_text') {
                $this->packChunkableText($pages, $current, $remaining, $block);

                continue;
            }

            $units = max(1, (int) $block['units']);

            if ($units > $remaining && ! $this->pageIsEmpty($current)) {
                $pages[] = $current;
                $current = $this->emptyPage();
                $remaining = self::PAGE_UNITS;
            }

            $this->applyBodyBlock($current, $block);
            $current['used'] += $units;
            $remaining -= $units;
        }

        if (! $this->pageIsEmpty($current) || $pages === []) {
            $pages[] = $current;
        }

        return $pages;
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @param  array<string, mixed>  $current
     * @param  array{type: string, units: int, text?: string, number?: int, termNumber?: int}  $block
     */
    private function packChunkableText(array &$pages, array &$current, int &$remaining, array $block): void
    {
        $type = (string) $block['type'];
        $text = (string) ($block['text'] ?? '');
        $termNumber = (int) ($block['termNumber'] ?? 0);
        $questionNumber = (int) ($block['number'] ?? 0);

        if ($text === '') {
            if ($remaining < 1 && ! $this->pageIsEmpty($current)) {
                $pages[] = $current;
                $current = $this->emptyPage();
                $remaining = self::PAGE_UNITS;
            }
            $this->appendEmptyChunk($current, $type, $termNumber, $questionNumber);
            $current['used'] += 1;
            $remaining -= 1;

            return;
        }

        $firstChunk = true;
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

            $this->appendTextChunk($current, $type, $chunk, ! $firstChunk, $termNumber, $questionNumber);
            $current['used'] += $chunkUnits;
            $remaining -= $chunkUnits;
            $text = $rest;
            $firstChunk = false;
        }
    }

    /**
     * @param  array<string, mixed>  $page
     */
    private function appendEmptyChunk(array &$page, string $type, int $termNumber, int $questionNumber): void
    {
        if ($type === 'charges_text') {
            $page['showCharges'] = true;

            return;
        }

        if ($type === 'term_text') {
            $page['termChunks'][] = [
                'number' => $termNumber,
                'text' => '',
                'isContinuation' => false,
            ];

            return;
        }

        if ($type === 'answer_text') {
            $page['questions'][] = [
                'number' => $questionNumber,
                'question' => '',
                'answer' => '',
                'showTitle' => false,
                'isAnswerContinuation' => false,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $page
     */
    private function appendTextChunk(
        array &$page,
        string $type,
        string $chunk,
        bool $isContinuation,
        int $termNumber,
        int $questionNumber,
    ): void {
        if ($type === 'charges_text') {
            $page['showCharges'] = true;
            if (! $page['chargesShowLead']) {
                $page['chargesIsContinuation'] = true;
            }
            $page['chargesChunk'] = trim($page['chargesChunk'].' '.$chunk);

            return;
        }

        if ($type === 'term_text') {
            $page['termChunks'][] = [
                'number' => $termNumber,
                'text' => $chunk,
                'isContinuation' => $isContinuation,
            ];

            return;
        }

        if ($type === 'answer_text') {
            $page['questions'][] = [
                'number' => $questionNumber,
                'question' => '',
                'answer' => $chunk,
                'showTitle' => false,
                'isAnswerContinuation' => $isContinuation,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $page
     * @param  array{type: string, units: int, text?: string, number?: int, question?: string, termNumber?: int}  $block
     */
    private function applyBodyBlock(array &$page, array $block): void
    {
        match ((string) $block['type']) {
            'intro_lead' => $page['showIntroLead'] = true,
            'charges_lead' => (function () use (&$page): void {
                $page['showCharges'] = true;
                $page['chargesShowLead'] = true;
            })(),
            'charges_tail' => (function () use (&$page): void {
                $page['showCharges'] = true;
                $page['chargesShowTail'] = true;
            })(),
            'terms_lead' => $page['showTermsLead'] = true,
            'intro_manifestation' => $page['showIntroManifestation'] = true,
            'intro_quiz_lead' => $page['showIntroQuizLead'] = true,
            'closing_text' => $page['showClosingText'] = true,
            'question_title' => $page['questions'][] = [
                'number' => (int) ($block['number'] ?? count($page['questions']) + 1),
                'question' => (string) ($block['question'] ?? ''),
                'answer' => '',
                'showTitle' => true,
                'isAnswerContinuation' => false,
            ],
            default => null,
        };
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return list<array<string, mixed>>
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

        if (($used + self::SIGNATURES_UNITS + self::CLOSING_SAFETY_UNITS) <= self::PAGE_UNITS) {
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
     * @return array<string, mixed>
     */
    private function emptyPage(): array
    {
        return [
            'showIntroLead' => false,
            'showCharges' => false,
            'chargesShowLead' => false,
            'chargesIsContinuation' => false,
            'chargesChunk' => '',
            'chargesShowTail' => false,
            'showTermsLead' => false,
            'termChunks' => [],
            'showIntroManifestation' => false,
            'showIntroQuizLead' => false,
            'showClosingText' => false,
            'showClosing' => false,
            'questions' => [],
            'used' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     */
    private function pageIsEmpty(array $page): bool
    {
        return ! $page['showIntroLead']
            && ! $page['showCharges']
            && ! $page['showTermsLead']
            && $page['termChunks'] === []
            && ! $page['showIntroManifestation']
            && ! $page['showIntroQuizLead']
            && ! $page['showClosingText']
            && ! $page['showClosing']
            && $page['questions'] === []
            && trim((string) $page['chargesChunk']) === '';
    }

    /**
     * @param  mixed  $questions
     * @return list<array{question: string, answer: string}>
     */
    private function normalizeQuestions(mixed $questions): array
    {
        if (! is_array($questions)) {
            return [];
        }

        $out = [];
        foreach ($questions as $q) {
            if (! is_array($q)) {
                continue;
            }
            $question = trim((string) ($q['question'] ?? $q['text'] ?? ''));
            if ($question === '') {
                continue;
            }
            $out[] = [
                'question' => $question,
                'answer' => trim((string) ($q['answer'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @param  array{question: string, answer: string}  $item
     */
    public function estimateQuestionUnits(array $item): int
    {
        $questionLines = $this->estimateTextLines((string) ($item['question'] ?? ''));
        $answerLines = $this->estimateTextLines((string) ($item['answer'] ?? ''));
        $raw = self::QUESTION_TITLE_UNITS + $questionLines + $answerLines;

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

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return list<array<string, mixed>>
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
                'showIntroLead' => (bool) ($page['showIntroLead'] ?? false),
                'showCharges' => (bool) ($page['showCharges'] ?? false),
                'chargesShowLead' => (bool) ($page['chargesShowLead'] ?? false),
                'chargesIsContinuation' => (bool) ($page['chargesIsContinuation'] ?? false),
                'chargesChunk' => (string) ($page['chargesChunk'] ?? ''),
                'chargesShowTail' => (bool) ($page['chargesShowTail'] ?? false),
                'showTermsLead' => (bool) ($page['showTermsLead'] ?? false),
                'termChunks' => array_values($page['termChunks'] ?? []),
                'showIntroManifestation' => (bool) ($page['showIntroManifestation'] ?? false),
                'showIntroQuizLead' => (bool) ($page['showIntroQuizLead'] ?? false),
                'showClosingText' => (bool) ($page['showClosingText'] ?? false),
                'showClosing' => (bool) ($page['showClosing'] ?? false),
                'questions' => $page['questions'] ?? [],
            ];
        }, $pages, array_keys($pages)));
    }
}
