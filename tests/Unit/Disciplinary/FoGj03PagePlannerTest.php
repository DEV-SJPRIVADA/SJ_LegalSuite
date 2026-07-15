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

    public function test_blank_template_fits_on_single_page(): void
    {
        $pages = $this->planner->plan(['blankForDownload' => true]);

        $this->assertCount(1, $pages);
        $this->assertTrue($pages[0]['showBody']);
        $this->assertTrue($pages[0]['showClosing']);
        $this->assertSame('Página 1 de 1', $pages[0]['pageLine']);
    }

    public function test_short_filled_citation_fits_on_single_page(): void
    {
        $pages = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => 'Llegó tarde al turno.',
            'article66Numerals' => '1, 3',
            'article68Numerals' => '10',
            'article76Numerals' => '3',
            'locationText' => 'Av. 4 Nte. #26N - 39',
        ]);

        $this->assertCount(1, $pages);
        $this->assertTrue($pages[0]['showClosing']);
    }

    public function test_long_charges_move_closing_to_second_page(): void
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
        $this->assertTrue($pages[0]['showBody']);
        $this->assertFalse($pages[0]['showClosing']);
        $this->assertFalse($pages[array_key_last($pages)]['showBody']);
        $this->assertTrue($pages[array_key_last($pages)]['showClosing']);
        $this->assertSame('Página '.count($pages).' de '.count($pages), $pages[array_key_last($pages)]['pageLine']);
    }

    public function test_witnesses_increase_closing_and_can_force_second_page(): void
    {
        $without = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => str_repeat('Hecho. ', 25),
            'evidenceType' => 'signed',
        ]);

        $with = $this->planner->plan([
            'blankForDownload' => false,
            'chargesDescription' => str_repeat('Hecho. ', 25),
            'evidenceType' => 'refused_witnesses',
            'witnesses' => [['name' => 'A'], ['name' => 'B']],
        ]);

        $this->assertGreaterThanOrEqual(count($without), 1);
        $this->assertTrue($with[array_key_last($with)]['showClosing']);
        if (count($without) === 1) {
            $this->assertGreaterThan(1, count($with));
        }
    }
}
