<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\InformeSubmissionStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\InformeSubmission;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DisciplinaryOperacionesCaseScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_operaciones_without_review_all_sees_only_assigned_reviewer_cases(): void
    {
        $tatiana = $this->makeOperacionesReviewer('tatiana@test.local');
        $otherReviewer = $this->makeOperacionesReviewer('other-reviewer@test.local');
        $lawyer = $this->makeLawyer('lawyer@test.local');

        $owned = $this->makeCaseWithAssignedReviewer($tatiana, 'DISC-OWN-001', $lawyer->id);
        $alien = $this->makeCaseWithAssignedReviewer($otherReviewer, 'DISC-ALIEN-002', $lawyer->id);

        $ids = DisciplinaryCase::query()
            ->forDisciplinaryActor($tatiana)
            ->pluck('id')
            ->all();

        $this->assertContains($owned->id, $ids);
        $this->assertNotContains($alien->id, $ids);
        $this->assertTrue($tatiana->can('view', $owned));
        $this->assertFalse($tatiana->can('view', $alien));
    }

    public function test_operaciones_with_review_inform_all_sees_all_cases(): void
    {
        $director = $this->makeOperacionesReviewer('director@test.local');
        $director->givePermissionTo('disciplinary.review-inform-all');

        $otherReviewer = $this->makeOperacionesReviewer('other@test.local');
        $lawyer = $this->makeLawyer('lawyer2@test.local');

        $this->makeCaseWithAssignedReviewer($otherReviewer, 'DISC-EXT-003', $lawyer->id);

        $count = DisciplinaryCase::query()->forDisciplinaryActor($director)->count();

        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function test_cases_index_livewire_hides_alien_cases_for_operaciones_reviewer(): void
    {
        $tatiana = $this->makeOperacionesReviewer('tatiana2@test.local');
        $otherReviewer = $this->makeOperacionesReviewer('other2@test.local');
        $lawyer = $this->makeLawyer('lawyer3@test.local');

        $owned = $this->makeCaseWithAssignedReviewer($tatiana, 'DISC-OWN-010', $lawyer->id);
        $this->makeCaseWithAssignedReviewer($otherReviewer, 'DISC-ALIEN-011', $lawyer->id);

        $this->actingAs($tatiana);

        Livewire::test(\App\Livewire\Disciplinary\Cases\CasesIndex::class)
            ->assertSee($owned->case_number)
            ->assertDontSee('DISC-ALIEN-011');
    }

    private function makeOperacionesReviewer(string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('nivel2');

        return $user;
    }

    private function makeLawyer(string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('nivel6');

        return $user;
    }

    private function makeCaseWithAssignedReviewer(User $assignedReviewer, string $number, int $lawyerId): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'document_number' => '8000'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => $number,
            'employee_id' => $employee->id,
            'current_status' => CaseStatus::CITACION_PROGRAMADA,
            'opened_at' => now()->toDateString(),
            'assigned_lawyer_id' => $lawyerId,
        ]);

        InformeSubmission::query()->create([
            'submitted_by' => $assignedReviewer->id,
            'assigned_reviewer_id' => $assignedReviewer->id,
            'employee_id' => $employee->id,
            'status' => InformeSubmissionStatus::AUTORIZADO,
            'storage_disk' => 'local',
            'storage_path' => 'test/'.$number.'.pdf',
            'reviewed_by' => $assignedReviewer->id,
            'reviewed_at' => now(),
            'disciplinary_case_id' => $case->id,
        ]);

        return $case;
    }

    public function test_operaciones_does_not_see_case_when_only_reviewed_by_matches_not_assigned(): void
    {
        $tatiana = $this->makeOperacionesReviewer('tatiana-approve@test.local');
        $carlos = $this->makeOperacionesReviewer('carlos-assigned@test.local');
        $lawyer = $this->makeLawyer('lawyer4@test.local');

        $employee = Employee::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'document_number' => '800099999999',
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-MISMATCH-001',
            'employee_id' => $employee->id,
            'current_status' => CaseStatus::CITACION_PROGRAMADA,
            'opened_at' => now()->toDateString(),
            'assigned_lawyer_id' => $lawyer->id,
        ]);

        InformeSubmission::query()->create([
            'submitted_by' => $carlos->id,
            'assigned_reviewer_id' => $carlos->id,
            'employee_id' => $employee->id,
            'status' => InformeSubmissionStatus::AUTORIZADO,
            'storage_disk' => 'local',
            'storage_path' => 'test/mismatch.pdf',
            'reviewed_by' => $tatiana->id,
            'reviewed_at' => now(),
            'disciplinary_case_id' => $case->id,
        ]);

        $this->assertFalse($tatiana->can('view', $case));
        $this->assertNotContains(
            $case->id,
            DisciplinaryCase::query()->forDisciplinaryActor($tatiana)->pluck('id')->all()
        );
    }
}
