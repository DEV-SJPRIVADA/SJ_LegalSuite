<?php

namespace App\Support\Disciplinary;

/**
 * Reparte FO-GJ-04 en páginas Letter explícitas (una `.ogj-page` = una hoja física).
 *
 * Mismo contrato que FoGj03DocumentPaginator:
 * 1) Cuerpo continuo: intro (cargos + términos se trocean) → preguntas → párrafo de cierre.
 * 2) Cada hoja lleva encabezado HTML + “Página N de M”.
 * 3) Único bloque atómico: tabla de firmas.
 *
 * Constantes calibradas contra Dompdf Letter + --ogj-font-body: 12px.
 */
final class FoGj04PagePlanner
{
    private const PAGE_UNITS = 70;

    private const CLOSING_SAFETY_UNITS = 5;

    private const INTRO_LEAD_UNITS = 24;

    private const CHARGES_TAIL_UNITS = 2;

    private const TERMS_LEAD_UNITS = 3;

    /** Unidades por numeral 1–5 (texto legal fijo; suma ~25 con lead). */
    private const TERM_UNITS = [
        1 => 3,
        2 => 4,
        3 => 6,
        4 => 5,
        5 => 4,
    ];

    private const INTRO_TAIL_UNITS = 8;

    private const QUESTION_BASE_UNITS = 2;

    private const CLOSING_TEXT_UNITS = 3;

    private const SIGNATURES_UNITS = 11;

    private const CHARS_PER_LINE = 72;

    private const TEXT_GROWTH_FACTOR = 1.35;

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
     *     termNumbers: list<int>,
     *     showIntroTail: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>
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
     * @return list<array{type: string, units: int, text?: string, number?: int, question?: string, answer?: string, termNumber?: int}>
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

        foreach (self::TERM_UNITS as $termNumber => $units) {
            $blocks[] = [
                'type' => 'term_item',
                'units' => $units,
                'termNumber' => $termNumber,
            ];
        }

        $blocks[] = ['type' => 'intro_tail', 'units' => self::INTRO_TAIL_UNITS];

        $number = 1;
        foreach ($questions as $item) {
            $blocks[] = [
                'type' => 'question',
                'units' => $this->estimateQuestionUnits($item),
                'number' => $number,
                'question' => $item['question'],
                'answer' => $item['answer'],
            ];
            $number++;
        }

        $blocks[] = ['type' => 'closing_text', 'units' => self::CLOSING_TEXT_UNITS];

        return $blocks;
    }

    /**
     * @param  list<array{type: string, units: int, text?: string, number?: int, question?: string, answer?: string, termNumber?: int}>  $blocks
     * @return list<array{
     *     showIntroLead: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showTermsLead: bool,
     *     termNumbers: list<int>,
     *     showIntroTail: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }>
     */
    private function packBodyBlocks(array $blocks): array
    {
        $pages = [];
        $current = $this->emptyPage();
        $remaining = self::PAGE_UNITS;

        foreach ($blocks as $block) {
            $type = (string) $block['type'];

            if ($type === 'charges_text') {
                $text = (string) ($block['text'] ?? '');

                if ($text === '') {
                    if ($remaining < 1 && ! $this->pageIsEmpty($current)) {
                        $pages[] = $current;
                        $current = $this->emptyPage();
                        $remaining = self::PAGE_UNITS;
                    }
                    $current['showCharges'] = true;
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

                    $current['showCharges'] = true;
                    if (! $current['chargesShowLead']) {
                        $current['chargesIsContinuation'] = true;
                    }
                    $current['chargesChunk'] = trim($current['chargesChunk'].' '.$chunk);
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
     * @param  array{
     *     showIntroLead: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showTermsLead: bool,
     *     termNumbers: list<int>,
     *     showIntroTail: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }  $page
     * @param  array{type: string, units: int, text?: string, number?: int, question?: string, answer?: string, termNumber?: int}  $block
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
            'term_item' => $page['termNumbers'][] = (int) ($block['termNumber'] ?? 0),
            'intro_tail' => $page['showIntroTail'] = true,
            'closing_text' => $page['showClosingText'] = true,
            'question' => $page['questions'][] = [
                'number' => (int) ($block['number'] ?? count($page['questions']) + 1),
                'question' => (string) ($block['question'] ?? ''),
                'answer' => (string) ($block['answer'] ?? ''),
            ],
            default => null,
        };
    }

    /**
     * @param  list<array{
     *     showIntroLead: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showTermsLead: bool,
     *     termNumbers: list<int>,
     *     showIntroTail: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }>  $pages
     * @return list<array{
     *     showIntroLead: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showTermsLead: bool,
     *     termNumbers: list<int>,
     *     showIntroTail: bool,
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
     * @return array{
     *     showIntroLead: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showTermsLead: bool,
     *     termNumbers: list<int>,
     *     showIntroTail: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }
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
            'termNumbers' => [],
            'showIntroTail' => false,
            'showClosingText' => false,
            'showClosing' => false,
            'questions' => [],
            'used' => 0,
        ];
    }

    /**
     * @param  array{
     *     showIntroLead: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showTermsLead: bool,
     *     termNumbers: list<int>,
     *     showIntroTail: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }  $page
     */
    private function pageIsEmpty(array $page): bool
    {
        return ! $page['showIntroLead']
            && ! $page['showCharges']
            && ! $page['showTermsLead']
            && $page['termNumbers'] === []
            && ! $page['showIntroTail']
            && ! $page['showClosingText']
            && ! $page['showClosing']
            && $page['questions'] === []
            && trim($page['chargesChunk']) === '';
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
     * @param  list<array{
     *     showIntroLead: bool,
     *     showCharges: bool,
     *     chargesShowLead: bool,
     *     chargesIsContinuation: bool,
     *     chargesChunk: string,
     *     chargesShowTail: bool,
     *     showTermsLead: bool,
     *     termNumbers: list<int>,
     *     showIntroTail: bool,
     *     showClosingText: bool,
     *     showClosing: bool,
     *     questions: list<array{number: int, question: string, answer: string}>,
     *     used: int
     * }>  $pages
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
     *     termNumbers: list<int>,
     *     showIntroTail: bool,
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
                'showIntroLead' => (bool) ($page['showIntroLead'] ?? false),
                'showCharges' => (bool) ($page['showCharges'] ?? false),
                'chargesShowLead' => (bool) ($page['chargesShowLead'] ?? false),
                'chargesIsContinuation' => (bool) ($page['chargesIsContinuation'] ?? false),
                'chargesChunk' => (string) ($page['chargesChunk'] ?? ''),
                'chargesShowTail' => (bool) ($page['chargesShowTail'] ?? false),
                'showTermsLead' => (bool) ($page['showTermsLead'] ?? false),
                'termNumbers' => array_values(array_map('intval', $page['termNumbers'] ?? [])),
                'showIntroTail' => (bool) ($page['showIntroTail'] ?? false),
                'showClosingText' => (bool) ($page['showClosingText'] ?? false),
                'showClosing' => (bool) ($page['showClosing'] ?? false),
                'questions' => $page['questions'] ?? [],
            ];
        }, $pages, array_keys($pages)));
    }
}
