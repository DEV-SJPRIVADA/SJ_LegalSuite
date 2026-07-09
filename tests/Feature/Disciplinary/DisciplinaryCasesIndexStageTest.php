<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\StageType;
use App\Livewire\Disciplinary\Cases\CasesIndex;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\Fault;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DisciplinaryCasesIndexStageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\FaultsCatalogSeeder::class);
    }

    public function test_cases_index_shows_stage_rail_with_counts(): void
    {
        $admin = $this->makeAdmin();
        $lawyer = $this->makeLawyer('lawyer-rail@test.local');

        $this->makeCase($lawyer, 'STAGE-D-001', StageType::DECISION, CaseStatus::DECISION);
        $this->makeCase(null, 'STAGE-A-001', StageType::INFORME, CaseStatus::INFORME);

        Livewire::actingAs($admin)
            ->test(CasesIndex::class)
            ->assertSee('Procesos disciplinarios')
            ->assertSee('Cerrados')
            ->assertSee('Todos');
    }

    public function test_stage_filter_d_shows_only_decision_cases(): void
    {
        $admin = $this->makeAdmin();
        $lawyer = $this->makeLawyer('lawyer-filter@test.local');

        $decision = $this->makeCase($lawyer, 'FILTER-D-001', StageType::DECISION, CaseStatus::DECISION);
        $this->makeCase($lawyer, 'FILTER-C-001', StageType::DILIGENCIA, CaseStatus::DILIGENCIA);

        Livewire::actingAs($admin)
            ->test(CasesIndex::class)
            ->call('setStage', 'D')
            ->assertSet('stage', 'D')
            ->assertSee($decision->case_number)
            ->assertDontSee('FILTER-C-001');
    }

    public function test_stage_filter_toggles_off_on_second_click(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin)
            ->test(CasesIndex::class)
            ->call('setStage', 'B')
            ->assertSet('stage', 'B')
            ->call('setStage', 'B')
            ->assertSet('stage', '');
    }

    public function test_lawyer_sees_pool_case_in_stage_a_rail_count(): void
    {
        $lawyer = $this->makeLawyer('lawyer-pool@test.local');
        $this->makeCase(null, 'POOL-RAIL-001', StageType::INFORME, CaseStatus::INFORME);

        $rail = Livewire::actingAs($lawyer)
            ->test(CasesIndex::class)
            ->instance()
            ->stageRail;

        $stageA = collect($rail['stages'])->firstWhere('letter', 'A');
        $this->assertSame(1, $stageA['count']);
    }

    public function test_closed_stage_filter_shows_finalized_cases(): void
    {
        $admin = $this->makeAdmin();
        $lawyer = $this->makeLawyer('lawyer-closed@test.local');

        $closed = $this->makeCase($lawyer, 'CLOSED-001', StageType::DECISION, CaseStatus::FINALIZADO);
        $open = $this->makeCase($lawyer, 'OPEN-001', StageType::DECISION, CaseStatus::DECISION);

        Livewire::actingAs($admin)
            ->test(CasesIndex::class)
            ->call('setStage', 'cerrados')
            ->assertSee($closed->case_number)
            ->assertDontSee($open->case_number);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        return $user;
    }

    private function makeLawyer(string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('abogado');

        return $user;
    }

    private function makeCase(?User $lawyer, string $number, StageType $stage, CaseStatus $status): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Worker',
            'document_number' => (string) random_int(1000000000, 9999999999),
        ]);

        $fault = Fault::query()->where('code', 'F-001')->firstOrFail();

        $case = DisciplinaryCase::query()->create([
            'case_number' => $number,
            'employee_id' => $employee->id,
            'current_status' => $status,
            'current_stage_type' => $stage,
            'opened_at' => now()->toDateString(),
            'assigned_lawyer_id' => $lawyer?->id,
        ]);
        $case->faults()->attach($fault->id);

        return $case;
    }
}
