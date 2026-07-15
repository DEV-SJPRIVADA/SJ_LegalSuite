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

    /** Caso típico: hoja 1 = apertura/cargos/faltas; hoja 2 = traslado + cierre (con header). */
    public function test_typical_case_splits_evidence_from_opening(): void
    {
        $pages = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => 'Incumplimiento de obligaciones laborales según el informe; falta reiterada de presentación al puesto.',
            'article66Numerals' => '1, 3, 4, 6, 8, 9, 20, 29, 30, 39, 41, 42',
            'article68Numerals' => '10, 34',
            'article76Numerals' => '3, 12, 15, 22, 25, 36, 64, 98, 103, 112',
            'locationText' => 'en las instalaciones de la empresa SJ Seguridad Privada Ltda. en Cali en la dirección Av. 4 Nte. #26N - 39 B/ San Vicente',
        ]);

        $this->assertCount(2, $pages);
        $this->assertContains(FoGj03PagePlanner::SECTION_OPENING, $pages[0]['sections']);
        $this->assertContains(FoGj03PagePlanner::SECTION_CHARGES, $pages[0]['sections']);
        $this->assertContains(FoGj03PagePlanner::SECTION_ARTICLES, $pages[0]['sections']);
        $this->assertNotContains(FoGj03PagePlanner::SECTION_EVIDENCE, $pages[0]['sections']);
        $this->assertFalse($pages[0]['showClosing']);

        $this->assertSame([FoGj03PagePlanner::SECTION_EVIDENCE], $pages[1]['sections']);
        $this->assertTrue($pages[1]['showClosing']);
        $this->assertSame('Página 1 de 2', $pages[0]['pageLine']);
        $this->assertSame('Página 2 de 2', $pages[1]['pageLine']);
    }

    public function test_evidence_never_shares_page_with_opening(): void
    {
        $pages = $this->planner->plan([
            'blankForDownload' => true,
        ]);

        foreach ($pages as $page) {
            $hasOpening = in_array(FoGj03PagePlanner::SECTION_OPENING, $page['sections'], true);
            $hasEvidence = in_array(FoGj03PagePlanner::SECTION_EVIDENCE, $page['sections'], true);
            $this->assertFalse($hasOpening && $hasEvidence);
        }
    }

    public function test_very_long_charges_keep_coherent_numbering(): void
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
            $hasOpening = in_array(FoGj03PagePlanner::SECTION_OPENING, $page['sections'], true);
            $hasEvidence = in_array(FoGj03PagePlanner::SECTION_EVIDENCE, $page['sections'], true);
            $this->assertFalse($hasOpening && $hasEvidence);
        }

        $allSections = [];
        foreach ($pages as $page) {
            $allSections = array_merge($allSections, $page['sections']);
        }
        $this->assertSame(FoGj03PagePlanner::BODY_SECTIONS, $allSections);
    }
}
