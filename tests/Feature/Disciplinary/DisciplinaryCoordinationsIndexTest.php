<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\AgendaMessageKind;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryAgendaThreadService;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DisciplinaryCoordinationsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_planeacion_sees_register_notification_after_posting_diligence_slots(): void
    {
        $planner = $this->makeUser('planeacion', 'planner-coord@test.local');
        $lawyer = $this->makeUser('abogado', 'lawyer-coord@test.local');
        $case = $this->makeCaseWithOpenThread($lawyer, CaseStatus::CITACION_PROGRAMADA);

        app(DisciplinaryAgendaThreadService::class)->postPlanningMessage(
            $case->fresh(['agendaThread']),
            $planner,
            'Fechas disponibles',
            [['date' => now()->addDays(5)->toDateString(), 'time' => '10:00', 'notes' => null]],
            [],
        );

        Livewire::actingAs($planner)
            ->test(\App\Livewire\Disciplinary\Coordinations\Index::class)
            ->assertOk()
            ->assertSee('Registrar notificación y supervisor');
    }

    public function test_planeacion_can_view_coordinations_when_case_left_citacion_stage(): void
    {
        $planner = $this->makeUser('planeacion', 'planner-stale@test.local');
        $lawyer = $this->makeUser('abogado', 'lawyer-stale@test.local');
        $case = $this->makeCaseWithOpenThread($lawyer, CaseStatus::DILIGENCIA);

        $case->forceFill([
            'notification_requested_at' => now(),
            'notification_requested_by' => $lawyer->id,
        ])->save();

        Livewire::actingAs($planner)
            ->test(\App\Livewire\Disciplinary\Coordinations\Index::class)
            ->assertOk()
            ->assertSee('Coordinaciones abiertas');
    }

    public function test_planeacion_publishes_decision_planning_with_separate_suspension_and_notification_slots(): void
    {
        $planner = $this->makeUser('planeacion', 'planner-decision@test.local');
        $lawyer = $this->makeUser('abogado', 'lawyer-decision@test.local');
        $supervisor = $this->makeUser('supervisor', 'supervisor-decision@test.local');
        $case = $this->makeDecisionCaseWithOpenThread($lawyer, Decision::SUSPENSION);
        $thread = $case->agendaThread;
        $suspensionStart = now()->addDay()->toDateString();
        $suspensionEnd = now()->addDays(10)->toDateString();
        $notifyDate = now()->addDays(3)->toDateString();

        Livewire::actingAs($planner)
            ->test(\App\Livewire\Disciplinary\Coordinations\Index::class)
            ->set('selectedThread', (string) $thread->id)
            ->call('openDecisionPlanningModal')
            ->set('decisionSuspensionStart', $suspensionStart)
            ->set('decisionSuspensionEnd', $suspensionEnd)
            ->set('decisionNotificationSlots', [[
                'date' => $notifyDate,
                'time' => '09:00',
                'notes' => 'Mañana',
                'zone' => 'Zona Norte',
                'supervisor_user_id' => $supervisor->id,
            ]])
            ->call('submitDecisionPlanningModal')
            ->assertHasNoErrors();

        $message = $case->fresh(['agendaThread.messages'])->agendaThread->messages->last();
        $this->assertSame(AgendaMessageKind::DECISION_PLANNING_RESPONSE, $message->message_kind);

        $measurePayload = $message->normalizedNotificationPayload();
        $this->assertSame($suspensionStart, $measurePayload['suspension_start']);
        $this->assertSame($suspensionEnd, $measurePayload['suspension_end']);

        $slots = $message->normalizedProposedSlots();
        $this->assertCount(1, $slots);
        $this->assertSame($notifyDate, $slots[0]['date']);
        $this->assertSame('09:00', $slots[0]['time']);
        $this->assertSame('Mañana', $slots[0]['notes']);
        $this->assertSame('Zona Norte', $slots[0]['zone']);
        $this->assertSame($supervisor->id, $slots[0]['supervisor_user_id']);
        $this->assertSame($supervisor->name, $slots[0]['supervisor_name']);
    }

    private function makeUser(string $role, string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
            'read_only' => false,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function makeCaseWithOpenThread(User $lawyer, CaseStatus $status): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Coord',
            'document_number' => '9200'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-COORD-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => $status,
            'opened_at' => now()->toDateString(),
            'coordination_started_at' => now(),
            'citation_confirmed_date' => now()->addDays(2)->toDateString(),
        ]);

        DisciplinaryAgendaThread::query()->create([
            'disciplinary_case_id' => $case->id,
            'opened_by' => $lawyer->id,
            'coordination_started_at' => now(),
            'coordination_status' => 'open',
        ]);

        return $case;
    }

    private function makeDecisionCaseWithOpenThread(User $lawyer, Decision $decision): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Decision',
            'document_number' => '9300'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-DEC-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::DECISION,
            'opened_at' => now()->toDateString(),
            'decision' => $decision,
            'decision_coordination_started_at' => now(),
            'coordination_started_at' => now()->subWeek(),
        ]);

        DisciplinaryAgendaThread::query()->create([
            'disciplinary_case_id' => $case->id,
            'opened_by' => $lawyer->id,
            'coordination_started_at' => now(),
            'coordination_status' => 'open',
        ]);

        return $case->fresh(['agendaThread']);
    }
}
