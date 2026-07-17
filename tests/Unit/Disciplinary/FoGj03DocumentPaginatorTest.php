<?php

namespace Tests\Unit\Disciplinary;

use App\Support\Disciplinary\FoGj03DocumentPaginator;
use Tests\TestCase;

class FoGj03DocumentPaginatorTest extends TestCase
{
    private FoGj03DocumentPaginator $paginator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paginator = new FoGj03DocumentPaginator;
    }

    public function test_typical_citation_fits_one_planned_page(): void
    {
        $pages = $this->paginator->plan($this->typicalContext());

        $this->assertCount(1, $pages);
        $this->assertSame('Página 1 de 1', $pages[0]['pageLine']);
        $this->assertTrue($pages[0]['showOpening']);
        $this->assertTrue($pages[0]['showCharges']);
        $this->assertTrue($pages[0]['chargesShowLead']);
        $this->assertTrue($pages[0]['chargesShowTail']);
        $this->assertTrue($pages[0]['showArticles']);
        $this->assertTrue($pages[0]['showEvidence']);
        $this->assertTrue($pages[0]['showClosing']);
        $this->assertFalse($pages[0]['chargesIsContinuation']);
    }

    public function test_blank_form_fits_one_planned_page(): void
    {
        $pages = $this->paginator->plan([
            'blankForDownload' => true,
            'chargesDescription' => '',
        ]);

        $this->assertCount(1, $pages);
        $this->assertTrue($pages[0]['showOpening']);
        $this->assertTrue($pages[0]['showCharges']);
        $this->assertTrue($pages[0]['showArticles']);
        $this->assertTrue($pages[0]['showEvidence']);
        $this->assertTrue($pages[0]['showClosing']);
    }

    public function test_long_charges_span_multiple_pages_with_header_sections(): void
    {
        $pages = $this->paginator->plan([
            ...$this->typicalContext(),
            'chargesDescription' => str_repeat('Descripción extendida del cargo disciplinario. ', 160),
        ]);

        $this->assertGreaterThanOrEqual(2, count($pages));
        $this->assertTrue($pages[0]['showOpening']);
        $this->assertTrue($pages[0]['showCharges']);
        $this->assertTrue($pages[0]['chargesShowLead']);

        $continuation = collect($pages)->first(fn (array $page): bool => $page['chargesIsContinuation']);
        $this->assertNotNull($continuation);
        $this->assertNotSame('', $continuation['chargesChunk']);

        $last = $pages[array_key_last($pages)];
        $this->assertTrue($last['showClosing']);
        $this->assertSame('Página '.count($pages).' de '.count($pages), $last['pageLine']);

        $joined = collect($pages)->pluck('chargesChunk')->filter()->implode(' ');
        $this->assertStringContainsString('Descripción extendida del cargo disciplinario.', $joined);
    }

    public function test_closing_can_move_to_own_page_when_body_is_full(): void
    {
        $pages = $this->paginator->plan([
            ...$this->typicalContext(),
            'chargesDescription' => str_repeat('Cargo. ', 200),
            'evidenceType' => 'refused_witnesses',
            'witnesses' => [['name' => 'A'], ['name' => 'B']],
        ]);

        $this->assertGreaterThanOrEqual(2, count($pages));
        $closingPages = array_values(array_filter($pages, fn (array $p): bool => $p['showClosing']));
        $this->assertCount(1, $closingPages);
    }

    public function test_medium_long_charges_keep_evidence_on_first_page_when_room(): void
    {
        $pages = $this->paginator->plan([
            ...$this->typicalContext(),
            // Más largo que el canónico, pero no tanto como para llenar 2 hojas de cargos.
            'chargesDescription' => str_repeat('Descripción del cargo disciplinario reportado. ', 28),
        ]);

        $this->assertTrue($pages[0]['showOpening']);
        $this->assertTrue($pages[0]['showCharges']);
        $this->assertTrue($pages[0]['showArticles']);
        $this->assertTrue(
            $pages[0]['showEvidence'],
            'La evidencia no debe saltar a p.2 si aún cabe en el hueco inferior de p.1',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function typicalContext(): array
    {
        return [
            'blankForDownload' => false,
            'chargesDescription' => 'Incumplimiento de obligaciones laborales según el informe; falta reiterada de presentación al puesto.',
            'article66Numerals' => '1, 3, 4, 6, 8, 9, 20, 29, 30, 39, 41, 42',
            'article68Numerals' => '10, 34',
            'article76Numerals' => '3, 12, 15, 22, 25, 36, 64, 98, 103, 112',
            'locationText' => 'en las instalaciones de la empresa SJ Seguridad Privada Ltda. en Cali',
            'evidenceType' => 'signed',
        ];
    }
}
