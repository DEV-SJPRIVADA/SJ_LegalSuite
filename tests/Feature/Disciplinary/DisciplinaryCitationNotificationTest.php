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
use App\Services\Disciplinary\DisciplinaryAgendaThreadService;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
use App\Services\Disciplinary\FoGj03CitationService;
use App\Services\Disciplinary\FoGj03DraftService;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\Support\FieldDisciplinaryTestHelpers;
use Tests\TestCase;

class DisciplinaryCitationNotificationTest extends TestCase
{
    use FieldDisciplinaryTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_planner_can_update_notification_information(): void
    {
        ['case' => $case, 'planner' => $planner] = $this->makeCitacionCaseWithCoordination();
        $supervisorA = $this->makeSupervisor('supervisor-a@test.local');
        $supervisorB = $this->makeSupervisor('supervisor-b@test.local');
        $service = app(DisciplinaryCitationNotificationService::class);

        Notification::fake();

        $service->completeNotificationInformation($case->fresh(['agendaThread', 'assignedLawyer']), $planner, [
            'notification_date' => now()->addDay()->toDateString(),
            'notification_shift' => 'Mañana',
            'notification_zone' => 'Zona Norte',
            'notification_supervisor_user_id' => $supervisorA->id,
        ]);

        $service->completeNotificationInformation($case->fresh(['agendaThread', 'assignedLawyer']), $planner, [
            'notification_date' => now()->addDays(2)->toDateString(),
            'notification_shift' => 'Tarde',
            'notification_zone' => 'Zona Sur',
            'notification_supervisor_user_id' => $supervisorB->id,
        ]);

        $case->refresh();
        $this->assertSame('Tarde', $case->notification_shift);
        $this->assertSame('Zona Sur', $case->notification_zone);
        $this->assertSame($supervisorB->id, $case->notification_supervisor_user_id);
    }

    public function test_planner_can_complete_notification_before_diligence_slots(): void
    {
        ['case' => $case, 'lawyer' => $lawyer, 'planner' => $planner] = $this->makeCitacionCaseWithCoordination();
        $supervisor = $this->makeSupervisor('supervisor-a@test.local');

        $this->assertTrue(
            app(DisciplinaryCitationNotificationService::class)->canPlanningRegisterNotification($case->fresh())
        );

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

    public function test_diligence_slots_blocked_until_notification_completed(): void
    {
        ['case' => $case, 'planner' => $planner] = $this->makeCitacionCaseWithCoordination();

        $this->expectException(\InvalidArgumentException::class);
        app(DisciplinaryAgendaThreadService::class)->postPlanningMessage(
            $case->fresh(['agendaThread']),
            $planner,
            'Fechas de diligencia',
            [['date' => now()->addDays(4)->toDateString(), 'time' => '10:00', 'notes' => null]],
            [],
        );
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

    public function test_lawyer_manual_notification_request_blocked_when_planning_can_register(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeCitacionCaseWithCoordination();

        $this->expectException(\InvalidArgumentException::class);
        app(DisciplinaryCitationNotificationService::class)
            ->requestNotificationInformation($case->fresh(['agendaThread']), $lawyer);
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
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeCitacionCaseWithNotificationCompleted();
        $this->completeFoGj03DraftForLawyer($case, $lawyer);
        $service = app(FoGj03CitationService::class);

        $this->assertTrue($service->canGenerate($case->fresh()));
    }

    /** @param  array<string, mixed>  $overrides */
    private function completeFoGj03DraftForLawyer(DisciplinaryCase $case, User $lawyer, array $overrides = []): void
    {
        Storage::fake('local');
        $path = 'signatures/'.$lawyer->id.'/signature.png';
        Storage::disk('local')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        $lawyer->forceFill([
            'signature_path' => $path,
            'signature_disk' => 'local',
        ])->save();

        app(FoGj03DraftService::class)->saveDraft($case->fresh(), $lawyer, array_merge([
            'hearing_time' => '09:00',
            'modality' => 'presencial',
            'virtual_meeting_link' => '',
            'breach_date' => now()->subDays(5)->toDateString(),
            'charges_description' => 'Hechos objeto de la citación disciplinaria.',
            'statute_articles' => [
                ['article_number' => '74', 'numerals' => '1, 3'],
                ['article_number' => '76', 'numerals' => '10'],
                ['article_number' => '79', 'numerals' => '3, 12'],
            ],
        ], $overrides));
    }

    public function test_assigned_supervisor_can_upload_citation_evidence(): void
    {
        ['case' => $case, 'nivel7' => $supervisor] = $this->makeCaseReadyForEvidenceUpload();

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
        $lawyer = $this->makeUserWithRole('nivel6', 'lawyer@test.local');
        $planner = $this->makeUserWithRole('nivel3', 'planner@test.local');
        $employee = $this->seedGuardaEmployee('9100'.random_int(100000, 999999));

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-NOTIF-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'municipality_code' => $employee->municipality_code,
            'city' => 'SANTIAGO DE CALI',
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
        $reviewer = $this->makeUserWithRole('nivel2', 'reviewer@test.local');

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
            ->completeNotificationInformation($context['case']->fresh(['agendaThread', 'assignedLawyer']), $context['planner'], [
                'notification_date' => now()->addDay()->toDateString(),
                'notification_shift' => 'Tarde',
                'notification_zone' => 'Centro',
                'notification_supervisor_user_id' => $supervisor->id,
            ]);

        app(DisciplinaryAgendaThreadService::class)->postPlanningMessage(
            $context['case']->fresh(['agendaThread']),
            $context['planner'],
            'Fechas de diligencia',
            [['date' => now()->addDays(4)->toDateString(), 'time' => '10:00', 'notes' => null]],
            [],
        );

        $planningMessage = $context['case']->fresh(['agendaThread.messages'])
            ->agendaThread
            ->messages()
            ->where('message_kind', AgendaMessageKind::PLANNING_RESPONSE)
            ->first();

        $confirmedCase = app(DisciplinaryAgendaThreadService::class)->confirmCitationSlot(
            $context['case']->fresh(),
            $context['lawyer'],
            (int) $planningMessage->id,
            0,
        );

        return array_merge($context, [
            'nivel7' => $supervisor,
            'reviewer' => $reviewer,
            'case' => $confirmedCase->fresh(),
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
            'nivel7' => $context['nivel7'],
            'reviewer' => $context['reviewer'],
        ];
    }

    private function makeSupervisor(string $email): User
    {
        return $this->seedFieldUserWithCities('nivel7', ['76001']);
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
