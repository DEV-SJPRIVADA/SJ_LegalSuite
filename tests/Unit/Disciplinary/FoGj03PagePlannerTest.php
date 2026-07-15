<?php

namespace Tests\Unit\Disciplinary;

use App\Support\Disciplinary\FoGj03PagePlanner;
use PHPUnit\Framework\TestCase;

class FoGj03PagePlannerTest extends TestCase
{
    private FoGj03PagePlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->planner = new FoGj03PagePlanner;
    }

    public function test_blank_template_has_header_pages_and_closing_on_last(): void
    {
        $pages = $this->planner->plan(['blankForDownload' => true]);

        $this->assertGreaterThanOrEqual(1, count($pages));
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);
        $this->assertSame('Página '.count($pages).' de '.count($pages), $pages[array_key_last($pages)]['pageLine']);

        $allSections = [];
        foreach ($pages as $page) {
            $allSections = array_merge($allSections, $page['sections']);
        }
        $this->assertSame(FoGj03PagePlanner::BODY_SECTIONS, $allSections);
    }

    public function test_short_filled_still_keeps_sections_and_closing(): void
    {
        $pages = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => 'Llegó tarde al turno.',
            'article66Numerals' => '1, 3',
            'article68Numerals' => '10',
            'article76Numerals' => '3',
            'locationText' => 'Av. 4 Nte. #26N - 39',
        ]);

        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);
        $this->assertContains(FoGj03PagePlanner::SECTION_OPENING, $pages[0]['sections']);
    }

    public function test_long_charges_split_across_pages_with_coherent_numbering(): void
    {
        $pages = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => str_repeat('Incidente largo con detalle del incumplimiento. ', 40),
            'article66Numerals' => '1, 3, 4, 6, 8, 9, 20, 29, 30, 39, 41, 42',
            'article68Numerals' => '10, 34',
            'article76Numerals' => '3, 12, 15, 22, 25, 36, 64, 98, 103, 112',
            'locationText' => 'Sede Cali Av. 4 Nte. #26N - 39 B/ San Vicente',
        ]);

        $this->assertGreaterThan(1, count($pages));
        $this->assertFalse($pages[0]['showClosing']);
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);

        foreach ($pages as $index => $page) {
            $this->assertSame('Página '.($index + 1).' de '.count($pages), $page['pageLine']);
        }

        $allSections = [];
        foreach ($pages as $page) {
            $allSections = array_merge($allSections, $page['sections']);
        }
        $this->assertSame(FoGj03PagePlanner::BODY_SECTIONS, $allSections);
    }

    public function test_every_planned_page_can_carry_sections_or_closing_only(): void
    {
        $pages = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => str_repeat('Hecho disciplinario detallado. ', 30),
            'evidenceType' => 'refused_witnesses',
            'witnesses' => [['name' => 'A'], ['name' => 'B']],
        ]);

        foreach ($pages as $page) {
            $this->assertTrue(
                $page['sections'] !== [] || $page['showClosing'],
                'Cada página debe tener cuerpo y/o cierre',
            );
        }
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);
    }
}
