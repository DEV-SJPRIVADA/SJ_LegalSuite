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

    /** Caso Hostinger típico: cuerpo lleno en hoja 1; firmas en hoja 2 planificada (con header). */
    public function test_typical_case_puts_closing_on_its_own_planned_page(): void
    {
        $pages = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => 'Incumplimiento de obligaciones laborales según el informe; falta reiterada de presentación al puesto.',
            'article66Numerals' => '1, 3, 4, 6, 8, 9, 20, 29, 30, 39, 41, 42',
            'article68Numerals' => '10, 34',
            'article76Numerals' => '3, 12, 15, 22, 25, 36, 64, 98, 103, 112',
            'locationText' => 'en las instalaciones de la empresa SJ Seguridad Privada Ltda. en Cali en la dirección Av. 4 Nte. #26N - 39 B/ San Vicente',
        ]);

        $this->assertGreaterThanOrEqual(2, count($pages));
        $this->assertSame(FoGj03PagePlanner::BODY_SECTIONS, $pages[0]['sections']);
        $this->assertFalse($pages[0]['showClosing']);
        $this->assertSame([], $pages[array_key_last($pages)]['sections']);
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);
        $this->assertSame('Página 1 de '.count($pages), $pages[0]['pageLine']);
        $this->assertSame(
            'Página '.count($pages).' de '.count($pages),
            $pages[array_key_last($pages)]['pageLine'],
        );
    }

    public function test_blank_template_has_closing_on_last_page(): void
    {
        $pages = $this->planner->plan(['blankForDownload' => true]);

        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);
        $allSections = [];
        foreach ($pages as $page) {
            $allSections = array_merge($allSections, $page['sections']);
        }
        $this->assertSame(FoGj03PagePlanner::BODY_SECTIONS, $allSections);
    }

    public function test_very_short_citation_may_share_closing_on_one_page(): void
    {
        $pages = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => 'Tarde.',
            'article66Numerals' => '1',
            'article68Numerals' => '10',
            'article76Numerals' => '3',
            'locationText' => 'Cali',
        ]);

        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);
        $allSections = [];
        foreach ($pages as $page) {
            $allSections = array_merge($allSections, $page['sections']);
        }
        $this->assertSame(FoGj03PagePlanner::BODY_SECTIONS, $allSections);
    }

    public function test_very_long_charges_split_body_with_coherent_numbering(): void
    {
        $pages = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => str_repeat('Incidente largo con detalle del incumplimiento laboral y contexto operativo. ', 80),
            'article66Numerals' => '1, 3, 4, 6, 8, 9, 20, 29, 30, 39, 41, 42',
            'article68Numerals' => '10, 34',
            'article76Numerals' => '3, 12, 15, 22, 25, 36, 64, 98, 103, 112',
            'locationText' => 'Sede Cali Av. 4 Nte. #26N - 39 B/ San Vicente',
        ]);

        $this->assertGreaterThan(1, count($pages));
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
}
