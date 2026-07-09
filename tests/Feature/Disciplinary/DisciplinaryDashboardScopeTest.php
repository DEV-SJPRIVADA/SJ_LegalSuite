<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\StageType;
use App\Livewire\Disciplinary\Dashboard;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\Fault;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryDashboardService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DisciplinaryDashboardScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\FaultsCatalogSeeder::class);
    }

    public function test_lawyer_dashboard_counts_only_assigned_cases(): void
    {
        $lawyer = $this->makeLawyer('lawyer-dash@test.local');
        $other = $this->makeLawyer('other-dash@test.local');

        $this->makeCase($lawyer, 'ASSIGNED-001', StageType::DECISION, CaseStatus::DECISION);
        $this->makeCase(null, 'POOL-001', StageType::INFORME, CaseStatus::INFORME);
        $this->makeCase($other, 'OTHER-001', StageType::DILIGENCIA, CaseStatus::DILIGENCIA);

        $service = app(DisciplinaryDashboardService::class);
        $data = $service->build($lawyer);

        $this->assertTrue($data['assignedOnly']);
        $this->assertSame(1, $data['workflowDonuts']['total']);
        $this->assertSame(1, $data['kpis']['total']);
        $this->assertSame(1, $data['workflowDonuts']['stages'][3]['count']);
    }

    public function test_admin_dashboard_counts_all_cases(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $admin->assignRole('admin');

        $lawyer = $this->makeLawyer('lawyer-admin-dash@test.local');
        $this->makeCase($lawyer, 'A-001', StageType::DECISION, CaseStatus::DECISION);
        $this->makeCase(null, 'P-001', StageType::INFORME, CaseStatus::INFORME);

        $data = app(DisciplinaryDashboardService::class)->build($admin);

        $this->assertFalse($data['assignedOnly']);
        $this->assertSame(2, $data['workflowDonuts']['total']);
    }

    public function test_lawyer_dashboard_view_shows_my_workload_and_top_municipalities(): void
    {
        $lawyer = $this->makeLawyer('lawyer-ui@test.local');
        $this->makeCase($lawyer, 'UI-001', StageType::DECISION, CaseStatus::DECISION);

        Livewire::actingAs($lawyer)
            ->test(Dashboard::class)
            ->assertSee('Mi tablero')
            ->assertSee('Top municipios')
            ->assertSee('Casos por tipo de falta')
            ->assertSee('Mi carga')
            ->assertDontSee('Carga por abogado');
    }

    public function test_admin_dashboard_view_shows_lawyer_workload_ranking(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $admin->assignRole('admin');

        $lawyer = $this->makeLawyer('lawyer-rank@test.local');
        $this->makeCase($lawyer, 'R-001', StageType::DECISION, CaseStatus::DECISION);

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertSee('Dashboard')
            ->assertSee('Carga por abogado')
            ->assertDontSee('Mi carga');
    }

    public function test_dashboard_includes_all_active_faults_with_zeros(): void
    {
        $lawyer = $this->makeLawyer('lawyer-faults@test.local');
        $this->makeCase($lawyer, 'FAULT-001', StageType::DECISION, CaseStatus::DECISION);

        $catalogCount = Fault::query()->active()->count();
        $byFault = app(DisciplinaryDashboardService::class)->build($lawyer)['byFault'];

        $this->assertCount($catalogCount, $byFault);
        $this->assertSame(1, collect($byFault)->firstWhere('code', 'F-001')['total']);
        $this->assertGreaterThan(0, collect($byFault)->where('total', 0)->count());
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
