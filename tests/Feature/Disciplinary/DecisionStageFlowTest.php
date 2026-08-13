<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use App\Models\User;
use App\Support\Disciplinary\CaseOverviewStageStack;
use App\Support\Disciplinary\DecisionBranch;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DecisionStageFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_decision_stage_panel_appears_after_advance(): void
    {
        $lawyer = $this->user('nivel6', 'decision-panel@test.local');
        $case = $this->caseInDecision($lawyer);

        $stack = app(CaseOverviewStageStack::class);
        $this->assertSame(['d', 'c', 'a'], $stack->stagesForCase($case));

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case])
            ->call('openStageCard', 'd')
            ->assertSee('Etapa D · Comunicado de decisión')
            ->assertSee('Registrar tipo de decisión');
    }

    public function test_select_decision_type_starts_coordination(): void
    {
        $lawyer = $this->user('nivel6', 'decision-type@test.local');
        $case = $this->caseInDecision($lawyer);

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case])
            ->call('openDecisionTypeModal')
            ->set('decisionBranchSelection', DecisionBranch::NOTICE)
            ->set('decisionTypeSelection', Decision::AMONESTACION_ESCRITA->value)
            ->call('confirmDecisionType')
            ->assertHasNoErrors();

        $case = $case->fresh(['agendaThread']);
        $this->assertSame(Decision::AMONESTACION_ESCRITA, $case->decision);
        $this->assertNotNull($case->decision_coordination_started_at);
        $this->assertSame('open', $case->agendaThread?->coordination_status);
    }

    public function test_generate_comunicado_blocked_without_draft(): void
    {
        $lawyer = $this->user('nivel6', 'decision-gen@test.local');
        $case = $this->caseInDecision($lawyer);
        $case->forceFill([
            'decision' => Decision::AMONESTACION_ESCRITA,
            'decision_coordination_started_at' => now(),
            'decision_notification_completed_at' => now(),
            'decision_notification_supervisor_user_id' => User::factory()->create(['is_active' => true])->id,
            'decision_notification_shift' => 'Mañana',
            'decision_notification_zone' => 'Norte',
        ])->save();

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->call('generateDecisionComunicado')
            ->assertForbidden();
    }

    public function test_finalize_button_not_shown_without_requirements(): void
    {
        $lawyer = $this->user('nivel6', 'decision-finalize-blocked@test.local');
        $case = $this->caseInDecision($lawyer);
        $case->forceFill([
            'decision' => Decision::AMONESTACION_ESCRITA,
            'decision_coordination_started_at' => now(),
            'decision_comunicado_generated_at' => now(),
        ])->save();

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->assertDontSee('Finalizar proceso');
    }

    private function user(string $role, string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function caseInDecision(User $lawyer): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'Decision',
            'last_name' => 'Stage',
            'document_number' => '9600'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-D-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::DECISION,
            'opened_at' => now()->toDateString(),
            'coordination_started_at' => now()->subDays(5),
            'citation_confirmed_date' => now()->subDays(3)->toDateString(),
            'diligence_attendance' => DiligenceAttendance::ATTENDED,
            'fo_gj_04_generated_at' => now()->subDay(),
        ]);

        DisciplinaryAgendaThread::query()->create([
            'disciplinary_case_id' => $case->id,
            'opened_by' => $lawyer->id,
            'coordination_started_at' => now()->subDays(5),
            'coordination_status' => 'closed',
        ]);

        return $case->fresh(['agendaThread']);
    }
}
