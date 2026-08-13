<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\AgendaMessageKind;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryDocument;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryAgendaThreadService;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
use App\Services\Disciplinary\FoGj03CitationService;
use App\Support\Disciplinary\CitationStageProgress;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\FieldDisciplinaryTestHelpers;
use Tests\TestCase;

class DisciplinaryCitationStageFlowTest extends TestCase
{
    use FieldDisciplinaryTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_full_b1_flow_notification_then_planning_slots_confirm(): void
    {
        $lawyer = $this->user('nivel6', 'flow-lawyer@test.local');
        $planner = $this->user('nivel3', 'flow-planner@test.local');
        $case = $this->caseWithThread($lawyer);
        $supervisor = $this->seedFieldUserWithCities('nivel7', ['76001']);

        $agenda = app(DisciplinaryAgendaThreadService::class);
        $agenda->postLawyerMessage($case->fresh(['agendaThread']), $lawyer, 'Necesito fechas para diligencia');

        app(DisciplinaryCitationNotificationService::class)->completeNotificationInformation(
            $case->fresh(['agendaThread', 'assignedLawyer']),
            $planner,
            [
                'notification_date' => now()->addDay()->toDateString(),
                'notification_shift' => 'Mañana',
                'notification_zone' => 'Norte',
                'notification_supervision_zone_id' => $supervisor->currentSupervisionZone()->id,
            ],
        );

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

    public function test_lawyer_can_post_chat_message_with_image_attachment(): void
    {
        $lawyer = $this->user('nivel6', 'lawyer-img@test.local');
        $case = $this->caseWithThread($lawyer);

        $file = \Illuminate\Http\UploadedFile::fake()->image('evidencia-chat.jpg', 80, 80);

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case])
            ->call('openPlanningChatModal')
            ->set('agendaLawyerBody', 'Adjunto foto')
            ->set('agendaLawyerUploads', [$file])
            ->call('postAgendaLawyer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('disciplinary_agenda_attachments', [
            'original_name' => 'evidencia-chat.jpg',
        ]);
    }

    public function test_lawyer_case_detail_shows_chat_composer(): void
    {
        $lawyer = $this->user('nivel6', 'lw-ui@test.local');
        $case = $this->caseWithThread($lawyer);

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case])
            ->call('openPlanningChatModal')
            ->assertSee('Escriba un mensaje para Planeación')
            ->assertSee('Adjuntar archivo')
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
        $lawyer = $this->user('nivel6', 'chat-lawyer@test.local');
        $planner = $this->user('nivel3', 'chat-planner@test.local');
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

    public function test_chat_composer_remains_after_notification_registered_by_planning(): void
    {
        $lawyer = $this->user('nivel6', 'chat-after-notif@test.local');
        $planner = $this->user('nivel3', 'planner-after-notif@test.local');
        $case = $this->caseWithThread($lawyer);
        $supervisor = $this->seedFieldUserWithCities('nivel7', ['76001']);
        $agenda = app(DisciplinaryAgendaThreadService::class);

        app(DisciplinaryCitationNotificationService::class)->completeNotificationInformation(
            $case->fresh(['agendaThread', 'assignedLawyer']),
            $planner,
            [
                'notification_date' => now()->addDay()->toDateString(),
                'notification_shift' => 'Mañana',
                'notification_zone' => 'Norte',
                'notification_supervision_zone_id' => $supervisor->currentSupervisionZone()->id,
            ],
        );

        $agenda->postPlanningMessage(
            $case->fresh(['agendaThread']),
            $planner,
            'Fechas',
            [['date' => now()->addDays(4)->toDateString(), 'time' => '10:00', 'notes' => null]],
            [],
        );

        $message = $case->fresh()->agendaThread->messages()->where('message_kind', AgendaMessageKind::PLANNING_RESPONSE)->first();
        $case = $agenda->confirmCitationSlot($case->fresh(), $lawyer, $message->id, 0);

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case->fresh(['agendaThread.messages'])])
            ->call('openPlanningChatModal')
            ->assertSee('Escriba un mensaje para Planeación')
            ->call('openStageCard', 'b')
            ->assertSee('Zona de supervisión')
            ->assertSee('Mañana')
            ->assertSee('Norte');
    }

    public function test_advancing_to_diligencia_closes_coordination_thread(): void
    {
        $lawyer = $this->user('nivel6', 'advance-close@test.local');
        $planner = $this->user('nivel3', 'planner-advance@test.local');
        $case = $this->caseWithThread($lawyer);
        $supervisor = $this->seedFieldUserWithCities('nivel7', ['76001']);
        $agenda = app(DisciplinaryAgendaThreadService::class);

        app(DisciplinaryCitationNotificationService::class)->completeNotificationInformation(
            $case->fresh(['agendaThread', 'assignedLawyer']),
            $planner,
            [
                'notification_date' => now()->addDay()->toDateString(),
                'notification_shift' => 'Tarde',
                'notification_zone' => 'Centro',
                'notification_supervision_zone_id' => $supervisor->currentSupervisionZone()->id,
            ],
        );

        $agenda->postPlanningMessage(
            $case->fresh(['agendaThread']),
            $planner,
            'Fechas',
            [['date' => now()->addDays(3)->toDateString(), 'time' => '09:00', 'notes' => null]],
            [],
        );
        $message = $case->fresh()->agendaThread->messages()->where('message_kind', AgendaMessageKind::PLANNING_RESPONSE)->first();
        $case = $agenda->confirmCitationSlot($case->fresh(), $lawyer, $message->id, 0);

        $case->forceFill([
            'fo_gj_03_generated_at' => now(),
            'fo_gj_03_generated_by' => $lawyer->id,
            'citation_evidence_uploaded_at' => now(),
            'citation_evidence_type' => 'signed',
        ])->save();

        DisciplinaryDocument::query()->create([
            'disciplinary_case_id' => $case->id,
            'uploaded_by' => $lawyer->id,
            'document_type' => DocumentType::CITACION,
            'original_name' => 'fo-gj-03.pdf',
            'disk' => 'local',
            'path' => 'disciplinary/test/fo-gj-03.pdf',
            'mime_type' => 'application/pdf',
            'notes' => DisciplinaryCase::NOTE_FO_GJ_03_GENERATED,
        ]);

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case->fresh(['agendaThread'])])
            ->call('confirmAdvanceFromCitacion')
            ->assertHasNoErrors();

        $case->refresh();
        $this->assertSame(CaseStatus::DILIGENCIA, $case->current_status);
        $this->assertSame('closed', $case->agendaThread->coordination_status);
    }

    public function test_close_coordination_blocked_with_pending_notification(): void
    {
        $lawyer = $this->user('nivel6', 'close@test.local');
        $case = $this->caseWithThread($lawyer);

        $blockers = app(CitationStageProgress::class)->blockersBeforeClosingCoordination($case->fresh(['agendaThread.messages']));
        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('notificación física', implode(' ', $blockers));
    }

    public function test_fo_gj_03_requires_full_b2_before_generate(): void
    {
        $lawyer = $this->user('nivel6', 'fo03@test.local');
        $case = $this->caseWithThread($lawyer);
        $case->forceFill([
            'citation_confirmed_date' => now()->addDays(2)->toDateString(),
            'citation_confirmed_time' => '09:00:00',
        ])->save();

        $this->assertFalse(app(FoGj03CitationService::class)->canGenerate($case->fresh()));
    }

    public function test_abogado_disciplinarios_nav_url_points_to_cases_index(): void
    {
        $lawyer = $this->user('nivel6', 'nav-lawyer@test.local');

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
        $employee = $this->seedGuardaEmployee('9300'.random_int(100000, 999999));

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-FLOW-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'municipality_code' => $employee->municipality_code,
            'city' => 'SANTIAGO DE CALI',
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
