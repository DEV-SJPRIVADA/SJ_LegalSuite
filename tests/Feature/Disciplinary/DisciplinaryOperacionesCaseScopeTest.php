<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\InformeSubmissionStatus;
use App\Enums\Disciplinary\StageType;
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

    public function test_operaciones_sees_only_cases_they_authorized(): void
    {
        $tatiana = $this->makeOperacionesReviewer('tatiana@test.local');
        $otherReviewer = $this->makeOperacionesReviewer('other-reviewer@test.local');
        $lawyer = $this->makeLawyer('lawyer@test.local');

        $owned = $this->makeAuthorizedOpenCase($tatiana, 'DISC-OWN-001', $lawyer->id);
        $alien = $this->makeAuthorizedOpenCase($otherReviewer, 'DISC-ALIEN-002', $lawyer->id);

        $ids = DisciplinaryCase::query()
            ->forDisciplinaryActor($tatiana)
            ->pluck('id')
            ->all();

        $this->assertContains($owned->id, $ids);
        $this->assertNotContains($alien->id, $ids);
        $this->assertTrue($tatiana->can('view', $owned));
        $this->assertFalse($tatiana->can('view', $alien));
    }

    public function test_operaciones_with_review_inform_all_sees_open_cases_not_closed(): void
    {
        $director = $this->makeOperacionesReviewer('director@test.local');
        $director->givePermissionTo('disciplinary.review-inform-all');

        $otherReviewer = $this->makeOperacionesReviewer('other@test.local');
        $lawyer = $this->makeLawyer('lawyer2@test.local');

        $open = $this->makeAuthorizedOpenCase($otherReviewer, 'DISC-EXT-003', $lawyer->id);
        $closed = $this->makeAuthorizedOpenCase(
            $otherReviewer,
            'DISC-CLOSED-004',
            $lawyer->id,
            CaseStatus::FINALIZADO
        );

        $ids = DisciplinaryCase::query()->forDisciplinaryActor($director)->pluck('id')->all();

        $this->assertContains($open->id, $ids);
        $this->assertNotContains($closed->id, $ids);
        $this->assertFalse($director->can('view', $closed));
    }

    public function test_cases_index_livewire_hides_alien_cases_for_operaciones_reviewer(): void
    {
        $tatiana = $this->makeOperacionesReviewer('tatiana2@test.local');
        $otherReviewer = $this->makeOperacionesReviewer('other2@test.local');
        $lawyer = $this->makeLawyer('lawyer3@test.local');

        $owned = $this->makeAuthorizedOpenCase($tatiana, 'DISC-OWN-010', $lawyer->id);
        $this->makeAuthorizedOpenCase($otherReviewer, 'DISC-ALIEN-011', $lawyer->id);

        $this->actingAs($tatiana);

        Livewire::test(\App\Livewire\Disciplinary\Cases\CasesIndex::class)
            ->assertSee($owned->case_number)
            ->assertDontSee('DISC-ALIEN-011');
    }

    public function test_operaciones_sees_case_when_reviewed_by_matches_even_if_not_assigned(): void
    {
        $tatiana = $this->makeOperacionesReviewer('tatiana-approve@test.local');
        $carlos = $this->makeOperacionesReviewer('carlos-assigned@test.local');
        $lawyer = $this->makeLawyer('lawyer4@test.local');

        $case = $this->makeCaseWithReviewers(
            assignedReviewer: $carlos,
            reviewedBy: $tatiana,
            number: 'DISC-AUTH-001',
            lawyerId: $lawyer->id,
        );

        $this->assertTrue($tatiana->can('view', $case));
        $this->assertFalse($carlos->can('view', $case));
        $this->assertContains(
            $case->id,
            DisciplinaryCase::query()->forDisciplinaryActor($tatiana)->pluck('id')->all()
        );
    }

    public function test_operaciones_does_not_see_closed_cases_they_authorized(): void
    {
        $tatiana = $this->makeOperacionesReviewer('tatiana-closed@test.local');
        $lawyer = $this->makeLawyer('lawyer5@test.local');

        $closed = $this->makeAuthorizedOpenCase(
            $tatiana,
            'DISC-FIN-001',
            $lawyer->id,
            CaseStatus::ARCHIVADO
        );

        $this->assertFalse($tatiana->can('view', $closed));
        $this->assertNotContains(
            $closed->id,
            DisciplinaryCase::query()->forDisciplinaryActor($tatiana)->pluck('id')->all()
        );
    }

    public function test_operaciones_cannot_view_official_formats(): void
    {
        $tatiana = $this->makeOperacionesReviewer('tatiana-formats@test.local');

        $this->assertFalse($tatiana->can('viewOfficialForms', DisciplinaryCase::class));

        $this->actingAs($tatiana)
            ->get(route('disciplinary.formats.index'))
            ->assertForbidden();
    }

    public function test_operaciones_case_detail_shows_follow_up_not_full_gestion(): void
    {
        $tatiana = $this->makeOperacionesReviewer('tatiana-detail@test.local');
        $lawyer = $this->makeLawyer('lawyer6@test.local');
        $case = $this->makeAuthorizedOpenCase($tatiana, 'DISC-DET-001', $lawyer->id);

        $this->actingAs($tatiana);

        Livewire::test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case])
            ->assertSee('En trámite · Etapa B')
            ->assertSee('Citación a diligencia')
            ->assertSee('Seguimiento · Operaciones')
            ->assertDontSee('Línea de tiempo')
            ->assertDontSee('Actuaciones');
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

    private function makeAuthorizedOpenCase(
        User $reviewer,
        string $number,
        int $lawyerId,
        CaseStatus $status = CaseStatus::CITACION_PROGRAMADA,
    ): DisciplinaryCase {
        return $this->makeCaseWithReviewers(
            assignedReviewer: $reviewer,
            reviewedBy: $reviewer,
            number: $number,
            lawyerId: $lawyerId,
            status: $status,
        );
    }

    private function makeCaseWithReviewers(
        User $assignedReviewer,
        User $reviewedBy,
        string $number,
        int $lawyerId,
        CaseStatus $status = CaseStatus::CITACION_PROGRAMADA,
    ): DisciplinaryCase {
        $employee = Employee::query()->create([
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'document_number' => '8000'.random_int(100000, 999999),
        ]);

        $stageType = match ($status) {
            CaseStatus::INFORME, CaseStatus::BORRADOR => StageType::INFORME,
            CaseStatus::DILIGENCIA, CaseStatus::COMITE_DISCIPLINARIO => StageType::DILIGENCIA,
            CaseStatus::DECISION, CaseStatus::FINALIZADO, CaseStatus::ARCHIVADO => StageType::DECISION,
            default => StageType::CITACION,
        };

        $case = DisciplinaryCase::query()->create([
            'case_number' => $number,
            'employee_id' => $employee->id,
            'current_status' => $status,
            'current_stage_type' => $stageType,
            'opened_at' => now()->toDateString(),
            'assigned_lawyer_id' => $lawyerId,
            'closed_at' => $status->isTerminal() ? now()->toDateString() : null,
        ]);

        InformeSubmission::query()->create([
            'submitted_by' => $assignedReviewer->id,
            'assigned_reviewer_id' => $assignedReviewer->id,
            'employee_id' => $employee->id,
            'status' => InformeSubmissionStatus::AUTORIZADO,
            'storage_disk' => 'local',
            'storage_path' => 'test/'.$number.'.pdf',
            'reviewed_by' => $reviewedBy->id,
            'reviewed_at' => now(),
            'disciplinary_case_id' => $case->id,
        ]);

        return $case;
    }
}
