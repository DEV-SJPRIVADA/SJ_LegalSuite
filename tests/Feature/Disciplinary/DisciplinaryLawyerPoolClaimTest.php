<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Exceptions\Disciplinary\CaseAlreadyClaimedException;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryCaseService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisciplinaryLawyerPoolClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_abogado_can_view_and_claim_informe_pool_case(): void
    {
        $lawyer = $this->makeLawyer('lawyer-a@test.local');
        $case = $this->makePoolCase();

        $this->assertTrue($lawyer->can('view', $case));
        $this->assertTrue($lawyer->can('claim', $case));
        $this->assertFalse($lawyer->can('update', $case));

        $claimed = app(DisciplinaryCaseService::class)->claimByLawyer($case, $lawyer);

        $this->assertSame($lawyer->id, $claimed->assigned_lawyer_id);
        $this->assertDatabaseHas('disciplinary_actions', [
            'disciplinary_case_id' => $case->id,
            'user_id' => $lawyer->id,
            'action_type' => ActionType::CASO_ACEPTADO_ABOGADO->value,
        ]);
        $this->assertTrue($lawyer->fresh()->can('update', $claimed));
        $this->assertFalse($lawyer->can('claim', $claimed));
    }

    public function test_second_claim_fails_atomically(): void
    {
        $first = $this->makeLawyer('first@test.local');
        $second = $this->makeLawyer('second@test.local');
        $case = $this->makePoolCase();

        app(DisciplinaryCaseService::class)->claimByLawyer($case, $first);

        $this->expectException(CaseAlreadyClaimedException::class);
        app(DisciplinaryCaseService::class)->claimByLawyer($case->fresh(), $second);
    }

    public function test_abogado_scope_includes_pool_and_assigned_cases(): void
    {
        $lawyer = $this->makeLawyer('scope@test.local');
        $pool = $this->makePoolCase('DISC-POOL-001');
        $assigned = $this->makePoolCase('DISC-OWN-002');
        $assigned->forceFill(['assigned_lawyer_id' => $lawyer->id])->save();

        $other = $this->makePoolCase('DISC-OTHER-003');
        $other->forceFill([
            'current_status' => CaseStatus::CITACION_PROGRAMADA,
            'assigned_lawyer_id' => null,
        ])->save();

        $ids = DisciplinaryCase::query()
            ->forDisciplinaryActor($lawyer)
            ->pluck('id')
            ->all();

        $this->assertContains($pool->id, $ids);
        $this->assertContains($assigned->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_auditor_sees_pool_case_but_cannot_claim(): void
    {
        $auditor = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $auditor->assignRole('auditor');

        $case = $this->makePoolCase();

        $this->assertTrue($auditor->can('view', $case));
        $this->assertFalse($auditor->can('claim', $case));
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

    private function makePoolCase(string $number = 'DISC-TEST-000001'): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Worker',
            'document_number' => '9000'.random_int(100000, 999999),
        ]);

        return DisciplinaryCase::query()->create([
            'case_number' => $number,
            'employee_id' => $employee->id,
            'current_status' => CaseStatus::INFORME,
            'opened_at' => now()->toDateString(),
            'assigned_lawyer_id' => null,
        ]);
    }
}
