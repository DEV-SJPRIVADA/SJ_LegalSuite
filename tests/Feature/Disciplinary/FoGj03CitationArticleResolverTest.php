<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\Fault;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\FoGj03CitationArticleResolver;
use App\Services\Settings\CitationFaultTemplateService;
use Database\Seeders\FaultsCatalogSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoGj03CitationArticleResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FaultsCatalogSeeder::class);
    }

    public function test_single_fault_prefills_articles_and_numerals_from_template(): void
    {
        $fault = Fault::query()->where('code', 'F-001')->firstOrFail();
        app(CitationFaultTemplateService::class)->saveTemplateForFault($fault, [
            [
                'article_number' => '74',
                'numerals' => ['1', '2', '3'],
            ],
            [
                'article_number' => '76',
                'numerals' => ['32', '35'],
            ],
        ]);

        $case = $this->makeCaseWithFaults([$fault->id]);
        $blocks = app(FoGj03CitationArticleResolver::class)->resolveForCase($case);

        $this->assertCount(2, $blocks);
        $this->assertSame('74', $blocks[0]['article_number']);
        $this->assertSame('1, 2, 3', $blocks[0]['numerals']);
        $this->assertSame('76', $blocks[1]['article_number']);
        $this->assertSame('32, 35', $blocks[1]['numerals']);
    }

    public function test_single_fault_with_articles_only_leaves_numerals_empty(): void
    {
        $fault = Fault::query()->where('code', 'F-006')->firstOrFail();
        app(CitationFaultTemplateService::class)->saveTemplateForFault($fault, [
            ['article_number' => '74', 'numerals' => []],
            ['article_number' => '79', 'numerals' => []],
        ]);

        $case = $this->makeCaseWithFaults([$fault->id]);
        $blocks = app(FoGj03CitationArticleResolver::class)->resolveForCase($case);

        $this->assertCount(2, $blocks);
        $this->assertSame('', $blocks[0]['numerals']);
        $this->assertSame('', $blocks[1]['numerals']);
    }

    public function test_multiple_faults_union_articles_without_numerals(): void
    {
        $retardo = Fault::query()->where('code', 'F-001')->firstOrFail();
        $alicoramiento = Fault::query()->where('code', 'F-004')->firstOrFail();

        $service = app(CitationFaultTemplateService::class);
        $service->saveTemplateForFault($retardo, [
            ['article_number' => '74', 'numerals' => ['1', '2']],
            ['article_number' => '76', 'numerals' => ['32']],
        ]);
        $service->saveTemplateForFault($alicoramiento, [
            ['article_number' => '55', 'numerals' => ['1']],
            ['article_number' => '57', 'numerals' => ['17']],
        ]);

        $case = $this->makeCaseWithFaults([$retardo->id, $alicoramiento->id]);
        $blocks = app(FoGj03CitationArticleResolver::class)->resolveForCase($case);

        $this->assertSame(['55', '57', '74', '76'], array_column($blocks, 'article_number'));
        foreach ($blocks as $block) {
            $this->assertSame('', $block['numerals']);
        }
    }

    /** @param  list<int>  $faultIds */
    private function makeCaseWithFaults(array $faultIds): DisciplinaryCase
    {
        $lawyer = User::factory()->create();
        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Articles',
            'document_number' => '9400'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'GJ-PD:000120',
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::CITACION_PROGRAMADA,
            'opened_at' => now()->toDateString(),
        ]);

        $case->faults()->sync($faultIds);

        return $case->fresh(['faults']);
    }
}
