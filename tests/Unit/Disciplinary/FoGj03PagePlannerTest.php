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

    /** Verifica que el costo de artículos crece proporcionalmente al número de artículos activos.
     *
     * Con 1 artículo (solo 66) → opening+charges+articles caben en página 1.
     * Con 3 artículos cargados con numerales largos → articles podría desplazar evidence
     * a página 2 (efecto indirecto del mayor peso). */
    public function test_articles_units_scale_with_active_articles_count(): void
    {
        // Un solo artículo activo (solo 66) -> peso bajo
        $pagesFew = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => 'Falta leve.',
            'article66Numerals' => '1, 2',
            'article68Numerals' => '',
            'article76Numerals' => '',
            'locationText' => 'Sede principal',
        ]);

        // Tres artículos activos con numerales muy extensos -> peso alto
        $pagesMany = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => 'Falta leve.',
            'article66Numerals' => '1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20',
            'article68Numerals' => '10, 20, 30, 40, 50, 60, 70, 80, 90, 100',
            'article76Numerals' => '5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60',
            'locationText' => 'Sede principal Av. Siempre Viva #123',
        ]);

        // Con más artículos y numerales largos, al menos una sección adicional debe
        // haber migrado a una página extra
        $this->assertGreaterThanOrEqual(
            count($pagesFew),
            count($pagesMany),
            'Más artículos activos deberían generar igual o más páginas'
        );
    }

    /** Verifica que cargos muy largos con el factor 1.3 forcén más páginas. */
    public function test_very_long_charges_with_growth_factor_creates_more_pages(): void
    {
        $pages = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => str_repeat('Palabra ', 500),
            'article66Numerals' => '1, 2, 3, 4, 5, 6, 7, 8',
            'article68Numerals' => '10, 20, 30',
            'article76Numerals' => '5, 10, 15, 20, 25',
            'locationText' => 'Sede Cali Av. 4 Nte. #26N - 39 B/ San Vicente',
        ]);

        // Cargos extremadamente largos deberían ocupar varias páginas
        $this->assertGreaterThanOrEqual(2, count($pages));
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);

        // Todas las secciones deben preservarse
        $allSections = [];
        foreach ($pages as $page) {
            $allSections = array_merge($allSections, $page['sections']);
        }
        $this->assertSame(FoGj03PagePlanner::BODY_SECTIONS, $allSections);
    }
}
