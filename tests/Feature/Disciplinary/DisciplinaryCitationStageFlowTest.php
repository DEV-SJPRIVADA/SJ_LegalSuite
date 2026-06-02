<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\AgendaMessageKind;
use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryAgendaThreadService;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
use App\Services\Disciplinary\FoGj03CitationService;
use App\Support\Disciplinary\CitationStageProgress;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DisciplinaryCitationStageFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_full_b1_flow_chat_planning_slots_confirm(): void
    {
        $lawyer = $this->user('abogado', 'flow-lawyer@test.local');
        $planner = $this->user('planeacion', 'flow-planner@test.local');
        $case = $this->caseWithThread($lawyer);

        $agenda = app(DisciplinaryAgendaThreadService::class);
        $agenda->postLawyerMessage($case->fresh(['agendaThread']), $lawyer, 'Necesito fechas para diligencia');

        $case = $agenda->postPlanningMessage(
            $case->fresh(['agendaThread']),
            $planner,
            'Opciones disponibles',
            [['date' => now()->addDays(5)->toDateString(), 'time' => '10:00', 'notes' => 'Sala 1']],
            [],
        )->thread->case()->first();

        $this->assertTrue($case->hasPlanningProposedSlots());

        $message = $case->agendaThread->messages()->where('message_kind', AgendaMessageKind::PLANNING_RESPONSE)->first();
        $case = $agenda->confirmCitationSlot($case->fresh(), $lawyer, $message->id, 0);

        $this->assertNotNull($case->citation_confirmed_date);
    }

    public function test_lawyer_case_detail_shows_chat_composer(): void
    {
        $lawyer = $this->user('abogado', 'lw-ui@test.local');
        $case = $this->caseWithThread($lawyer);

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case])
            ->assertSee('Escribir en el chat')
            ->set('agendaLawyerBody', 'Hola planeación')
            ->call('postAgendaLawyer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('disciplinary_agenda_messages', [
            'body' => 'Hola planeación',
            'message_kind' => AgendaMessageKind::GENERAL->value,
        ]);
    }

    public function test_planning_chat_without_slots_is_general(): void
    {
        $lawyer = $this->user('abogado', 'chat-lawyer@test.local');
        $planner = $this->user('planeacion', 'chat-planner@test.local');
        $case = $this->caseWithThread($lawyer);

        $msg = app(DisciplinaryAgendaThreadService::class)->postPlanningMessage(
            $case->fresh(['agendaThread']),
            $planner,
            'Recibido, reviso disponibilidad',
            [],
            [],
        );

        $this->assertSame(AgendaMessageKind::GENERAL, $msg->message_kind);
        $this->assertFalse($case->fresh()->hasPlanningProposedSlots());
    }

    public function test_close_coordination_blocked_with_pending_notification(): void
    {
        $lawyer = $this->user('abogado', 'close@test.local');
        $case = $this->caseWithThread($lawyer);
        $agenda = app(DisciplinaryAgendaThreadService::class);
        $notification = app(DisciplinaryCitationNotificationService::class);

        $planner = $this->user('planeacion', 'close-planner@test.local');
        $agenda->postPlanningMessage(
            $case->fresh(['agendaThread']),
            $planner,
            'Fechas',
            [['date' => now()->addDays(3)->toDateString(), 'time' => '09:00', 'notes' => null]],
            [],
        );
        $message = $case->fresh()->agendaThread->messages()->where('message_kind', AgendaMessageKind::PLANNING_RESPONSE)->first();
        $case = $agenda->confirmCitationSlot($case->fresh(), $lawyer, $message->id, 0);

        $notification->requestNotificationInformation($case->fresh(['agendaThread']), $lawyer);

        $blockers = app(CitationStageProgress::class)->blockersBeforeClosingCoordination($case->fresh(['agendaThread.messages']));
        $this->assertNotEmpty($blockers);
    }

    public function test_fo_gj_03_requires_full_b2_before_generate(): void
    {
        $lawyer = $this->user('abogado', 'fo03@test.local');
        $case = $this->caseWithThread($lawyer);
        $case->forceFill([
            'citation_confirmed_date' => now()->addDays(2)->toDateString(),
            'citation_confirmed_time' => '09:00:00',
        ])->save();

        $this->assertFalse(app(FoGj03CitationService::class)->canGenerate($case->fresh()));
    }

    public function test_abogado_disciplinarios_nav_url_points_to_cases_index(): void
    {
        $lawyer = $this->user('abogado', 'nav-lawyer@test.local');

        $this->assertSame(route('disciplinary.cases.index'), $lawyer->disciplinaryCasesNavUrl());
        $this->assertSame(route('disciplinary.dashboard'), $lawyer->disciplinaryPortalUrl());
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

    private function caseWithThread(User $lawyer): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'Flow',
            'last_name' => 'Test',
            'document_number' => '9300'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-FLOW-'.random_int(1000, 9999),
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

        return $case->fresh(['agendaThread']);
    }
}
