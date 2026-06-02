<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\ActionType;
use App\Enums\Disciplinary\AgendaMessageKind;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\InformeSubmissionStatus;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryDocument;
use App\Models\Disciplinary\InformeSubmission;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
use App\Services\Disciplinary\FoGj03CitationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DisciplinaryCitationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_lawyer_can_request_notification_information(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeCitacionCaseWithCoordination();

        $message = app(DisciplinaryCitationNotificationService::class)
            ->requestNotificationInformation($case->fresh(['agendaThread']), $lawyer);

        $case->refresh();
        $this->assertNotNull($case->notification_requested_at);
        $this->assertSame($lawyer->id, $case->notification_requested_by);
        $this->assertSame(AgendaMessageKind::LAWYER_NOTIFICATION_REQUEST, $message->message_kind);
    }

    public function test_planner_can_complete_notification_and_assign_supervisor(): void
    {
        ['case' => $case, 'lawyer' => $lawyer, 'planner' => $planner] = $this->makeCitacionCaseWithCoordination();
        $supervisor = $this->makeSupervisor('supervisor-a@test.local');

        app(DisciplinaryCitationNotificationService::class)
            ->requestNotificationInformation($case->fresh(['agendaThread']), $lawyer);

        Notification::fake();

        $message = app(DisciplinaryCitationNotificationService::class)
            ->completeNotificationInformation($case->fresh(['agendaThread', 'assignedLawyer']), $planner, [
                'notification_date' => now()->addDay()->toDateString(),
                'notification_shift' => 'Mañana',
                'notification_zone' => 'Zona Norte',
                'notification_supervisor_user_id' => $supervisor->id,
                'notification_notes' => 'Ingreso por puerta principal',
            ]);

        $case->refresh();
        $this->assertNotNull($case->notification_information_completed_at);
        $this->assertSame($supervisor->id, $case->notification_supervisor_user_id);
        $this->assertSame('Mañana', $case->notification_shift);
        $this->assertSame(AgendaMessageKind::NOTIFICATION_COORDINATION, $message->message_kind);

        $this->assertDatabaseHas('disciplinary_actions', [
            'disciplinary_case_id' => $case->id,
            'user_id' => $planner->id,
            'action_type' => ActionType::NOTIFICACION_COORDINADA->value,
        ]);
        $this->assertDatabaseHas('disciplinary_actions', [
            'disciplinary_case_id' => $case->id,
            'user_id' => $planner->id,
            'action_type' => ActionType::SUPERVISOR_NOTIFICADOR_ASIGNADO->value,
        ]);
    }

    public function test_operations_reviewer_can_reassign_notification_supervisor(): void
    {
        $context = $this->makeCitacionCaseWithNotificationCompleted();
        $case = $context['case'];
        $reviewer = $context['reviewer'];
        $newSupervisor = $this->makeSupervisor('supervisor-b@test.local');

        $updated = app(DisciplinaryCitationNotificationService::class)
            ->reassignNotificationSupervisor(
                $case->fresh(),
                $reviewer,
                $newSupervisor->id,
                'Vacaciones del supervisor anterior',
            );

        $this->assertSame($newSupervisor->id, $updated->notification_supervisor_user_id);
        $this->assertDatabaseHas('disciplinary_actions', [
            'disciplinary_case_id' => $case->id,
            'user_id' => $reviewer->id,
            'action_type' => ActionType::SUPERVISOR_NOTIFICADOR_REASIGNADO->value,
        ]);
    }

    public function test_fo_gj_03_generation_blocked_without_supervisor(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeCitacionCaseWithCoordination();
        $service = app(FoGj03CitationService::class);

        $this->assertFalse($service->canGenerate($case->fresh()));

        $this->expectException(ValidationException::class);
        $service->generateAndStore($case->fresh(), $lawyer);
    }

    public function test_fo_gj_03_can_generate_when_notification_complete(): void
    {
        ['case' => $case] = $this->makeCitacionCaseWithNotificationCompleted();
        $service = app(FoGj03CitationService::class);

        $this->assertTrue($service->canGenerate($case->fresh()));
    }

    public function test_assigned_supervisor_can_upload_citation_evidence(): void
    {
        ['case' => $case, 'supervisor' => $supervisor] = $this->makeCaseReadyForEvidenceUpload();

        $this->assertTrue($case->canUserUploadCitationEvidence($supervisor));
    }

    public function test_non_assigned_supervisor_cannot_upload_citation_evidence(): void
    {
        ['case' => $case] = $this->makeCaseReadyForEvidenceUpload();
        $otherSupervisor = $this->makeSupervisor('other-supervisor@test.local');

        $this->assertFalse($case->canUserUploadCitationEvidence($otherSupervisor));
    }

    public function test_operations_reviewer_can_upload_citation_evidence(): void
    {
        ['case' => $case, 'reviewer' => $reviewer] = $this->makeCaseReadyForEvidenceUpload();

        $this->assertTrue($case->canUserUploadCitationEvidence($reviewer));
    }

    /** @return array{case: DisciplinaryCase, lawyer: User, planner: User} */
    private function makeCitacionCaseWithCoordination(): array
    {
        $lawyer = $this->makeUserWithRole('abogado', 'lawyer@test.local');
        $planner = $this->makeUserWithRole('planeacion', 'planner@test.local');
        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Test',
            'document_number' => '9100'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-NOTIF-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::CITACION_PROGRAMADA,
            'opened_at' => now()->toDateString(),
            'coordination_started_at' => now(),
            'citation_confirmed_date' => now()->addDays(3)->toDateString(),
            'citation_confirmed_time' => '09:00:00',
            'citation_confirmed_by' => $lawyer->id,
        ]);

        DisciplinaryAgendaThread::query()->create([
            'disciplinary_case_id' => $case->id,
            'opened_by' => $lawyer->id,
            'coordination_started_at' => now(),
            'coordination_status' => 'open',
        ]);

        return ['case' => $case, 'lawyer' => $lawyer, 'planner' => $planner];
    }

    /** @return array{case: DisciplinaryCase, lawyer: User, planner: User, supervisor: User, reviewer: User} */
    private function makeCitacionCaseWithNotificationCompleted(): array
    {
        $context = $this->makeCitacionCaseWithCoordination();
        $supervisor = $this->makeSupervisor('assigned-supervisor@test.local');
        $reviewer = $this->makeUserWithRole('operaciones', 'reviewer@test.local');

        InformeSubmission::query()->create([
            'submitted_by' => $supervisor->id,
            'employee_id' => $context['case']->employee_id,
            'status' => InformeSubmissionStatus::AUTORIZADO,
            'storage_disk' => 'local',
            'storage_path' => 'test/informe.pdf',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'disciplinary_case_id' => $context['case']->id,
        ]);

        app(DisciplinaryCitationNotificationService::class)
            ->requestNotificationInformation($context['case']->fresh(['agendaThread']), $context['lawyer']);

        app(DisciplinaryCitationNotificationService::class)
            ->completeNotificationInformation($context['case']->fresh(['agendaThread', 'assignedLawyer']), $context['planner'], [
                'notification_date' => now()->addDay()->toDateString(),
                'notification_shift' => 'Tarde',
                'notification_zone' => 'Centro',
                'notification_supervisor_user_id' => $supervisor->id,
            ]);

        return array_merge($context, [
            'supervisor' => $supervisor,
            'reviewer' => $reviewer,
            'case' => $context['case']->fresh(),
        ]);
    }

    /** @return array{case: DisciplinaryCase, supervisor: User, reviewer: User} */
    private function makeCaseReadyForEvidenceUpload(): array
    {
        $context = $this->makeCitacionCaseWithNotificationCompleted();
        $case = $context['case'];

        $case->forceFill([
            'fo_gj_03_generated_at' => now(),
            'fo_gj_03_generated_by' => $context['lawyer']->id,
        ])->save();

        DisciplinaryDocument::query()->create([
            'disciplinary_case_id' => $case->id,
            'uploaded_by' => $context['lawyer']->id,
            'document_type' => DocumentType::CITACION,
            'original_name' => 'fo-gj-03.pdf',
            'disk' => 'local',
            'path' => 'disciplinary/test/fo-gj-03.pdf',
            'mime_type' => 'application/pdf',
            'notes' => DisciplinaryCase::NOTE_FO_GJ_03_GENERATED,
        ]);

        return [
            'case' => $case->fresh(['informeSubmission']),
            'supervisor' => $context['supervisor'],
            'reviewer' => $context['reviewer'],
        ];
    }

    private function makeSupervisor(string $email): User
    {
        return $this->makeUserWithRole('supervisor', $email);
    }

    private function makeUserWithRole(string $role, string $email): User
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
}
