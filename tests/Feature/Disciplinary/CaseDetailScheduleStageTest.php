<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\StageStatus;
use App\Enums\Disciplinary\StageType;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use App\Models\Disciplinary\DisciplinaryAction;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryStage;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CaseDetailScheduleStageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_save_schedule_persists_dates_and_logs_action(): void
    {
        $lawyer = $this->lawyer('schedule-save@test.local');
        $case = $this->caseWithCitationStage($lawyer);
        $stage = $case->stages()->firstOrFail();

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case])
            ->call('openScheduleStage', $stage->id)
            ->set('scheduleAt', '2026-11-20T09:30')
            ->set('scheduleDeadline', '2026-11-25')
            ->set('scheduleNote', 'Reprogramación por solicitud del trabajador')
            ->call('saveSchedule')
            ->assertHasNoErrors()
            ->assertSet('showScheduleModal', false);

        $fresh = $stage->fresh();
        $this->assertSame('2026-11-20 09:30:00', $fresh->scheduled_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-25', $fresh->deadline_at?->format('Y-m-d'));

        $action = DisciplinaryAction::query()
            ->where('disciplinary_case_id', $case->id)
            ->where('action_type', ActionType::FECHA_ETAPA_ACTUALIZADA)
            ->firstOrFail();
        $this->assertSame('Reprogramación por solicitud del trabajador', $action->description);
        $this->assertSame($lawyer->id, $action->user_id);
    }

    public function test_save_schedule_clears_dates_when_fields_are_empty(): void
    {
        $lawyer = $this->lawyer('schedule-clear@test.local');
        $case = $this->caseWithCitationStage($lawyer);
        $stage = $case->stages()->firstOrFail();

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case])
            ->call('openScheduleStage', $stage->id)
            ->set('scheduleAt', '')
            ->set('scheduleDeadline', '')
            ->call('saveSchedule')
            ->assertHasNoErrors();

        $fresh = $stage->fresh();
        $this->assertNull($fresh->scheduled_at);
        $this->assertNull($fresh->deadline_at);
    }

    private function lawyer(string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        // nivel9 (planeación) es quien tiene disciplinary.assign-date y puede abrir este modal.
        $user->assignRole('nivel9');

        return $user;
    }

    private function caseWithCitationStage(User $lawyer): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'Schedule',
            'last_name' => 'Stage',
            'document_number' => '9300'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-SCHED-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::CITACION_PROGRAMADA,
            'opened_at' => now()->toDateString(),
            'coordination_started_at' => now(),
        ]);

        DisciplinaryAgendaThread::query()->create([
            'disciplinary_case_id' => $case->id,
            'opened_by' => $lawyer->id,
            'coordination_started_at' => now(),
            'coordination_status' => 'open',
        ]);

        DisciplinaryStage::query()->create([
            'disciplinary_case_id' => $case->id,
            'stage_type' => StageType::CITACION,
            'form_code' => StageType::CITACION->formCode(),
            'status' => StageStatus::EN_CURSO,
            'sequence' => 1,
        ]);

        return $case->fresh(['stages', 'agendaThread']);
    }
}
