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

    public function test_single_short_question_fits_closing_on_first_page(): void
    {
        $pages = $this->planner->plan([
            ['question' => '¿Reconoce los hechos?', 'answer' => 'Sí, los reconozco.'],
        ]);

        $this->assertCount(1, $pages);
        $this->assertTrue($pages[0]['showIntro']);
        $this->assertTrue($pages[0]['showClosing']);
        $this->assertSame('Página 1 de 1', $pages[0]['pageLine']);
        $this->assertCount(1, $pages[0]['questions']);
    }

    public function test_blank_template_defaults_to_single_page(): void
    {
        $pages = $this->planner->plan([], true);

        $this->assertCount(1, $pages);
        $this->assertTrue($pages[0]['showIntro']);
        $this->assertTrue($pages[0]['showClosing']);
        $this->assertSame('Página 1 de 1', $pages[0]['pageLine']);
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

        $pages = $this->planner->plan($questions);

        $this->assertGreaterThan(1, count($pages));
        $this->assertTrue($pages[0]['showIntro']);
        $this->assertFalse($pages[0]['showClosing']);
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);

        $lastPage = $pages[array_key_last($pages)];
        $this->assertSame('Página '.count($pages).' de '.count($pages), $lastPage['pageLine']);

        $totalQuestions = array_sum(array_map(
            fn (array $page): int => count($page['questions']),
            $pages,
        ));
        $this->assertSame(8, $totalQuestions);
    }

    public function test_closing_only_on_last_page(): void
    {
        $pages = $this->planner->plan([
            ['question' => '¿Una?', 'answer' => 'Una respuesta.'],
            ['question' => '¿Dos?', 'answer' => 'Dos respuestas.'],
        ]);

        foreach ($pages as $index => $page) {
            $isLast = $index === count($pages) - 1;
            $this->assertSame($isLast, $page['showClosing']);
        }
    }
}
