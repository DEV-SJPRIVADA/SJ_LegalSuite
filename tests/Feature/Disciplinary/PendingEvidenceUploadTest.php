<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\AgendaMessageKind;
use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Enums\Disciplinary\InformeSubmissionStatus;
use App\Livewire\Disciplinary\Supervisor\PendingEvidenceIndex;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryDocument;
use App\Models\Disciplinary\InformeSubmission;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryAgendaThreadService;
use App\Services\Disciplinary\DisciplinaryCitationNotificationService;
use App\Services\Disciplinary\FoGj03DraftService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\FieldDisciplinaryTestHelpers;
use Tests\TestCase;

class PendingEvidenceUploadTest extends TestCase
{
    use FieldDisciplinaryTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_supervisor_can_preview_and_confirm_scanned_evidence_pdf(): void
    {
        ['case' => $case, 'nivel7' => $supervisor] = $this->makeCaseReadyForSupervisorQueue();
        $file = UploadedFile::fake()->create('citacion-firmada.pdf', 120, 'application/pdf');

        Livewire::actingAs($supervisor)
            ->test(PendingEvidenceIndex::class)
            ->set('citationEvidenceFileByCase.'.$case->id, $file)
            ->assertSet('evidencePreviewCaseId', $case->id)
            ->call('confirmEvidenceUpload')
            ->assertHasNoErrors()
            ->assertSet('evidencePreviewCaseId', null);

        $case->refresh();
        $this->assertNotNull($case->citation_evidence_uploaded_at);
        $this->assertDatabaseHas('disciplinary_documents', [
            'disciplinary_case_id' => $case->id,
            'document_type' => DocumentType::CITACION->value,
        ]);
    }

    public function test_supervisor_can_upload_refused_notification_with_two_witnesses(): void
    {
        ['case' => $case, 'nivel7' => $supervisor] = $this->makeCaseReadyForSupervisorQueue();
        $signature = $this->sampleWorkerSignatureDataUri();

        Livewire::actingAs($supervisor)
            ->test(PendingEvidenceIndex::class)
            ->call('openNotificationModal', $case->id)
            ->set('notificationEvidenceType', 'refused_witnesses')
            ->set('witness1Name', 'Testigo Uno')
            ->set('witness1Document', '1234567890')
            ->set('witness2Name', 'Testigo Dos')
            ->set('witness2Document', '9876543210')
            ->call('openWitnessSignaturePad', 1)
            ->call('saveCapturedSignature', $signature)
            ->call('openWitnessSignaturePad', 2)
            ->call('saveCapturedSignature', $signature)
            ->call('acceptSignedNotificationPreview')
            ->call('confirmSignedNotificationUpload')
            ->assertHasNoErrors();

        $case->refresh();
        $this->assertNotNull($case->citation_evidence_uploaded_at);
        $this->assertSame('refused_witnesses', $case->citation_evidence_type?->value);
        $this->assertDatabaseHas('disciplinary_documents', [
            'disciplinary_case_id' => $case->id,
            'original_name' => 'FO-GJ-03-notificacion-rechazo-testigos-'.$case->case_number.'.pdf',
        ]);
    }

    public function test_supervisor_can_sign_notification_html_and_upload_signed_pdf(): void
    {
        ['case' => $case, 'nivel7' => $supervisor] = $this->makeCaseReadyForSupervisorQueue();
        $signature = $this->sampleWorkerSignatureDataUri();

        Livewire::actingAs($supervisor)
            ->test(PendingEvidenceIndex::class)
            ->call('openNotificationModal', $case->id)
            ->assertSet('notificationCaseId', $case->id)
            ->call('saveWorkerSignature', $signature)
            ->assertSet('workerSignatureDataUri', $signature)
            ->call('acceptSignedNotificationPreview')
            ->call('confirmSignedNotificationUpload')
            ->assertHasNoErrors();

        $case->refresh();
        $this->assertNotNull($case->citation_evidence_uploaded_at);
        $this->assertDatabaseHas('disciplinary_documents', [
            'disciplinary_case_id' => $case->id,
            'original_name' => 'FO-GJ-03-notificacion-firmada-'.$case->case_number.'.pdf',
        ]);
    }

    /** @return array{case: DisciplinaryCase, supervisor: User, lawyer: User} */
    private function makeCaseReadyForSupervisorQueue(): array
    {
        $lawyer = $this->makeUserWithRole('nivel6', 'lawyer-pe-'.random_int(1000, 9999).'@test.local');
        $planner = $this->makeUserWithRole('nivel3', 'planner-pe-'.random_int(1000, 9999).'@test.local');

        $employee = $this->seedGuardaEmployee('9300'.random_int(100000, 999999));
        $supervisor = $this->seedFieldUserWithCities('nivel7', ['76001']);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'GJ-PD:000077',
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

        InformeSubmission::query()->create([
            'submitted_by' => $supervisor->id,
            'employee_id' => $employee->id,
            'status' => InformeSubmissionStatus::AUTORIZADO,
            'storage_disk' => 'local',
            'storage_path' => 'test/informe.pdf',
            'reviewed_by' => $lawyer->id,
            'reviewed_at' => now(),
            'disciplinary_case_id' => $case->id,
        ]);

        app(DisciplinaryCitationNotificationService::class)
            ->completeNotificationInformation($case->fresh(['agendaThread', 'assignedLawyer']), $planner, [
                'notification_date' => now()->addDay()->toDateString(),
                'notification_shift' => 'Tarde',
                'notification_zone' => 'Centro',
                'notification_supervisor_user_id' => $supervisor->id,
            ]);

        app(DisciplinaryAgendaThreadService::class)->postPlanningMessage(
            $case->fresh(['agendaThread']),
            $planner,
            'Fechas de diligencia',
            [['date' => now()->addDays(4)->toDateString(), 'time' => '10:00', 'notes' => null]],
            [],
        );

        $planningMessage = $case->fresh(['agendaThread.messages'])
            ->agendaThread
            ->messages()
            ->where('message_kind', AgendaMessageKind::PLANNING_RESPONSE)
            ->first();

        $case = app(DisciplinaryAgendaThreadService::class)->confirmCitationSlot(
            $case->fresh(),
            $lawyer,
            (int) $planningMessage->id,
            0,
        );

        $path = 'signatures/'.$lawyer->id.'/signature.png';
        Storage::disk('local')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        $lawyer->forceFill([
            'signature_path' => $path,
            'signature_disk' => 'local',
        ])->save();

        app(FoGj03DraftService::class)->saveDraft($case->fresh(), $lawyer, [
            'hearing_time' => '09:30',
            'modality' => 'presencial',
            'virtual_meeting_link' => '',
            'breach_date' => now()->subWeek()->toDateString(),
            'charges_description' => 'Incumplimiento reportado en informe disciplinario.',
            'statute_articles' => [
                ['article_number' => '74', 'numerals' => '1, 3'],
                ['article_number' => '76', 'numerals' => '10'],
                ['article_number' => '79', 'numerals' => '12'],
            ],
        ]);

        $case->forceFill([
            'fo_gj_03_generated_at' => now(),
            'fo_gj_03_generated_by' => $lawyer->id,
        ])->save();

        DisciplinaryDocument::query()->create([
            'disciplinary_case_id' => $case->id,
            'uploaded_by' => $lawyer->id,
            'document_type' => DocumentType::CITACION,
            'form_code' => 'FO-GJ-03',
            'original_name' => 'FO-GJ-03-citacion.pdf',
            'disk' => 'local',
            'path' => 'disciplinary/'.$case->id.'/fo-gj-03.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1200,
            'notes' => DisciplinaryCase::NOTE_FO_GJ_03_GENERATED,
        ]);

        return ['case' => $case->fresh(), 'nivel7' => $supervisor, 'lawyer' => $lawyer];
    }

    private function sampleWorkerSignatureDataUri(): string
    {
        $image = imagecreatetruecolor(120, 48);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 17, 24, 39);
        imagefilledrectangle($image, 0, 0, 119, 47, $white);
        imageline($image, 12, 30, 108, 18, $ink);

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
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
