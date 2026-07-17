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

    public function test_intro_and_closing_do_not_share_page_when_intro_is_full(): void
    {
        // Intro FO-GJ-04 + firmas no caben juntos en Dompdf: firmas van a hoja 2.
        $pages = $this->planner->plan([
            ['question' => '¿esta es la primera pregunta?', 'answer' => 'si'],
        ]);

        $this->assertGreaterThanOrEqual(2, count($pages));
        $this->assertTrue($pages[0]['showIntro']);
        $this->assertFalse($pages[0]['showClosing']);
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);
        $this->assertSame('Página 1 de '.count($pages), $pages[0]['pageLine']);
        $this->assertSame(
            'Página '.count($pages).' de '.count($pages),
            $pages[array_key_last($pages)]['pageLine'],
        );
    }

    public function test_blank_template_splits_closing_when_intro_fills_page(): void
    {
        $pages = $this->planner->plan([], true);

        $this->assertGreaterThanOrEqual(2, count($pages));
        $this->assertTrue($pages[0]['showIntro']);
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);
        $this->assertSame('Página 1 de '.count($pages), $pages[0]['pageLine']);
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
