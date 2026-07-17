<?php

namespace Tests\Unit\Disciplinary;

use App\Support\Disciplinary\FoGj04PagePlanner;
use PHPUnit\Framework\TestCase;

class FoGj04PagePlannerTest extends TestCase
{
    private FoGj04PagePlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planner = new FoGj04PagePlanner;
    }

    public function test_short_acta_keeps_signatures_atomic_on_last_page(): void
    {
        $pages = $this->planner->plan([
            'chargesDescription' => 'Incumplimiento de obligaciones laborales según el informe.',
            'questions' => [
                ['question' => '¿esta es la primera pregunta?', 'answer' => 'si'],
            ],
        ]);

        $this->assertGreaterThanOrEqual(1, count($pages));
        $this->assertTrue($pages[0]['showIntroLead']);
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);
        $this->assertSame('Página 1 de '.count($pages), $pages[0]['pageLine']);

        foreach ($pages as $index => $page) {
            $isLast = $index === count($pages) - 1;
            $this->assertSame($isLast, $page['showClosing']);
        }
    }

    public function test_long_charges_are_chunked_across_pages_with_header_meta(): void
    {
        $pages = $this->planner->plan([
            'chargesDescription' => str_repeat(
                'Falta grave por incumplimiento reiterado de turnos, protocolos y consignas operativas del puesto. ',
                20,
            ),
            'questions' => [
                ['question' => '¿Reconoce los hechos?', 'answer' => 'Sí'],
            ],
        ]);

        $this->assertGreaterThan(1, count($pages));
        $this->assertTrue($pages[0]['showIntroLead']);
        $this->assertTrue($pages[0]['showCharges']);

        $chargesPages = array_values(array_filter(
            $pages,
            fn (array $page): bool => $page['showCharges'] && trim($page['chargesChunk']) !== '',
        ));
        $this->assertNotEmpty($chargesPages);

        $joined = trim(implode(' ', array_map(
            fn (array $page): string => $page['chargesChunk'],
            $chargesPages,
        )));
        $this->assertStringContainsString('Falta grave', $joined);
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);
    }

    public function test_blank_template_places_signatures_without_orphan_middle_page(): void
    {
        $pages = $this->planner->plan([
            'blankForDownload' => true,
            'questions' => [],
            'chargesDescription' => '',
        ]);

        $this->assertGreaterThanOrEqual(1, count($pages));
        $this->assertTrue($pages[0]['showIntroLead']);
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);

        foreach ($pages as $page) {
            $hasBody = $page['showIntroLead']
                || $page['showCharges']
                || $page['showTermsLead']
                || $page['termChunks'] !== []
                || $page['showIntroManifestation']
                || $page['showIntroQuizLead']
                || $page['showClosingText']
                || $page['showClosing']
                || $page['questions'] !== [];
            $this->assertTrue($hasBody);
        }
    }

    public function test_long_charges_fill_remaining_space_with_chunked_terms(): void
    {
        $pages = $this->planner->plan([
            'chargesDescription' => str_repeat(
                'Falta grave por incumplimiento reiterado de turnos, protocolos y consignas operativas del puesto. ',
                12,
            ),
            'questions' => [
                ['question' => '¿Reconoce los hechos?', 'answer' => 'Sí'],
            ],
        ]);

        $this->assertGreaterThan(1, count($pages));
        $this->assertTrue($pages[0]['showCharges']);

        $termNumbers = [];
        foreach ($pages as $page) {
            foreach ($page['termChunks'] as $chunk) {
                $termNumbers[(int) $chunk['number']] = true;
            }
        }
        $nums = array_keys($termNumbers);
        sort($nums);
        $this->assertSame([1, 2, 3, 4, 5], $nums);

        // Si tras el cierre de cargos queda hueco real, algún término lo llena; si la hoja se agotó, van a p.2.
        $termsOnFirst = $pages[0]['termChunks'];
        $termsOnSecond = $pages[1]['termChunks'] ?? [];
        $this->assertTrue(
            $termsOnFirst !== [] || $termsOnSecond !== [],
            'Los términos deben aparecer en p.1 (llenando hueco) o continuar en p.2',
        );
    }

    public function test_long_term_three_can_split_across_pages(): void
    {
        $pages = $this->planner->plan([
            'chargesDescription' => str_repeat('Cargo operativo con detalle. ', 25),
            'questions' => [],
        ]);

        $term3Chunks = [];
        foreach ($pages as $page) {
            foreach ($page['termChunks'] as $chunk) {
                if ((int) $chunk['number'] === 3) {
                    $term3Chunks[] = $chunk;
                }
            }
        }

        $this->assertNotEmpty($term3Chunks);
        $joined = trim(implode(' ', array_column($term3Chunks, 'text')));
        $this->assertStringContainsString('Si decide responder', $joined);
    }

    public function test_many_questions_create_additional_pages(): void
    {
        $questions = [];
        for ($i = 1; $i <= 8; $i++) {
            $questions[] = [
                'question' => '¿Pregunta '.$i.' con texto adicional para ocupar espacio en la hoja?',
                'answer' => str_repeat('Respuesta detallada del trabajador. ', 12),
            ];
        }

        $pages = $this->planner->plan([
            'chargesDescription' => 'Incumplimiento breve.',
            'questions' => $questions,
        ]);

        $this->assertGreaterThan(1, count($pages));
        $this->assertTrue($pages[0]['showIntroLead']);
        $this->assertFalse($pages[0]['showClosing']);
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);

        $titles = [];
        foreach ($pages as $page) {
            foreach ($page['questions'] as $item) {
                if ($item['showTitle'] ?? false) {
                    $titles[(int) $item['number']] = true;
                }
            }
        }
        $this->assertCount(8, $titles);
    }

    public function test_short_charges_page_one_reaches_later_terms(): void
    {
        $pages = $this->planner->plan([
            'chargesDescription' => 'Incumplimiento breve del puesto.',
            'questions' => [
                ['question' => '¿PRIMERA?', 'answer' => 'si'],
            ],
        ]);

        $this->assertTrue($pages[0]['showIntroLead']);
        $termNumsOnFirst = array_values(array_unique(array_map(
            static fn (array $chunk): int => (int) $chunk['number'],
            $pages[0]['termChunks'],
        )));
        $this->assertNotEmpty($termNumsOnFirst);
        // Con INTRO_LEAD ~16, la p.1 debe llegar al menos al término 3 (antes con 24 cortaba antes).
        $this->assertGreaterThanOrEqual(3, max($termNumsOnFirst));
    }

    public function test_short_charges_can_pull_manifestation_onto_page_one(): void
    {
        $pages = $this->planner->plan([
            'chargesDescription' => 'Incumplimiento breve del puesto.',
            'questions' => [
                ['question' => '¿PRIMERA?', 'answer' => 'si'],
                ['question' => '¿SEGUNDA?', 'answer' => 'si'],
            ],
        ]);

        // Preferible: manifestación o lead del cuestionario en p.1 si cabe (menos hueco en p.2).
        $page1HasTail = $pages[0]['showIntroManifestation'] || $pages[0]['showIntroQuizLead'];
        $page1HasLateTerm = max(array_map(
            static fn (array $c): int => (int) $c['number'],
            $pages[0]['termChunks'] ?: [['number' => 0]],
        )) >= 4;

        $this->assertTrue(
            $page1HasTail || $page1HasLateTerm,
            'Tras calibrar INTRO_LEAD, p.1 debe retener más cuerpo (término ≥4 o cola intro)',
        );
    }

    public function test_closing_text_flows_before_atomic_signatures(): void
    {
        $pages = $this->planner->plan([
            'chargesDescription' => 'Falta leve.',
            'questions' => [
                ['question' => '¿Una?', 'answer' => 'Una respuesta.'],
                ['question' => '¿Dos?', 'answer' => 'Dos respuestas.'],
            ],
        ]);

        $closingTextPages = array_values(array_filter(
            $pages,
            fn (array $page): bool => $page['showClosingText'],
        ));
        $this->assertNotEmpty($closingTextPages);

        foreach ($pages as $index => $page) {
            $isLast = $index === count($pages) - 1;
            $this->assertSame($isLast, $page['showClosing']);
        }
    }
}
