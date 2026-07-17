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
                || $page['showIntroTerms']
                || $page['showIntroTail']
                || $page['showClosingText']
                || $page['showClosing']
                || $page['questions'] !== [];
            $this->assertTrue($hasBody);
        }
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

        $totalQuestions = array_sum(array_map(
            fn (array $page): int => count($page['questions']),
            $pages,
        ));
        $this->assertSame(8, $totalQuestions);
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
