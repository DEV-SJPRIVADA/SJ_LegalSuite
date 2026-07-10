<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Livewire\Disciplinary\Administrativa\PendingDecisionHrIndex;
use App\Livewire\Disciplinary\Supervisor\PendingEvidenceIndex;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryDecisionWorkflowService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DecisionStageCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_supervisor_can_sign_decision_notification_and_upload_pdf(): void
    {
        ['case' => $case, 'nivel7' => $supervisor] = $this->makeDecisionSupervisorQueueCase();
        $signature = $this->sampleSignatureDataUri();

        Livewire::actingAs($supervisor)
            ->test(PendingEvidenceIndex::class)
            ->call('openDecisionNotificationModal', $case->id)
            ->assertSet('decisionNotificationCaseId', $case->id)
            ->call('saveWorkerSignature', $signature)
            ->call('acceptSignedNotificationPreview')
            ->call('confirmSignedNotificationUpload')
            ->assertHasNoErrors();

        $case->refresh();
        $this->assertNotNull($case->decision_evidence_uploaded_at);
        $this->assertDatabaseHas('disciplinary_documents', [
            'disciplinary_case_id' => $case->id,
            'original_name' => 'FO-GJ-DECISION-firmado-'.$case->case_number.'.pdf',
        ]);
    }

    public function test_hr_must_upload_annex_before_completing_review(): void
    {
        $admin = $this->user('nivel4', 'hr-decision@test.local');
        $case = $this->makeTerminationCaseForHr();

        Livewire::actingAs($admin)
            ->test(PendingDecisionHrIndex::class)
            ->call('completeHrReview', $case->id)
            ->assertForbidden();

        $file = UploadedFile::fake()->create('liquidacion.pdf', 100, 'application/pdf');

        Livewire::actingAs($admin)
            ->test(PendingDecisionHrIndex::class)
            ->set('hrAnnexFileByCase.'.$case->id, $file)
            ->call('uploadHrAnnex', $case->id)
            ->assertHasNoErrors()
            ->call('completeHrReview', $case->id)
            ->assertHasNoErrors();

        $case->refresh();
        $this->assertNotNull($case->decision_hr_review_completed_at);
        $this->assertTrue($case->hasDecisionHrAnnex());
    }

    public function test_finalize_archived_decision_moves_case_to_archivado_status(): void
    {
        $lawyer = $this->user('nivel6', 'finalize-archived@test.local');
        $case = $this->makeDecisionReadyToFinalize($lawyer, Decision::ARCHIVADO);

        app(DisciplinaryDecisionWorkflowService::class)->finalizeCase($case, $lawyer);

        $this->assertSame(CaseStatus::ARCHIVADO, $case->fresh()->current_status);
    }

    public function test_finalize_absuelto_moves_case_to_finalizado_status(): void
    {
        $lawyer = $this->user('nivel6', 'finalize-absuelto@test.local');
        $case = $this->makeDecisionReadyToFinalize($lawyer, Decision::ABSUELTO);

        app(DisciplinaryDecisionWorkflowService::class)->finalizeCase($case, $lawyer);

        $this->assertSame(CaseStatus::FINALIZADO, $case->fresh()->current_status);
    }

    /** @return array{case: DisciplinaryCase, supervisor: User} */
    private function makeDecisionSupervisorQueueCase(): array
    {
        $supervisor = $this->user('nivel7', 'sup-decision-'.random_int(1000, 9999).'@test.local');
        $lawyer = $this->user('nivel6', 'law-decision-'.random_int(1000, 9999).'@test.local');
        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Decision',
            'document_number' => '9700'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-DEC-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::DECISION,
            'opened_at' => now()->toDateString(),
            'decision' => Decision::AMONESTACION_ESCRITA,
            'decision_payload' => [
                'subject' => 'Comunicado de amonestación',
                'body_narrative' => 'Se comunica la decisión adoptada.',
            ],
            'decision_draft_completed_at' => now(),
            'decision_comunicado_generated_at' => now(),
            'decision_notification_supervisor_user_id' => $supervisor->id,
        ]);

        DisciplinaryAgendaThread::query()->create([
            'disciplinary_case_id' => $case->id,
            'opened_by' => $lawyer->id,
            'coordination_started_at' => now()->subDay(),
            'coordination_status' => 'closed',
        ]);

        return ['case' => $case->fresh(), 'nivel7' => $supervisor];
    }

    private function makeTerminationCaseForHr(): DisciplinaryCase
    {
        $lawyer = $this->user('nivel6', 'law-hr-'.random_int(1000, 9999).'@test.local');

        return DisciplinaryCase::query()->create([
            'case_number' => 'DISC-HR-'.random_int(1000, 9999),
            'employee_id' => Employee::query()->create([
                'first_name' => 'Term',
                'last_name' => 'Worker',
                'document_number' => '9800'.random_int(100000, 999999),
            ])->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::DECISION,
            'opened_at' => now()->toDateString(),
            'decision' => Decision::TERMINACION_CONTRATO,
            'decision_comunicado_generated_at' => now(),
        ]);
    }

    private function makeDecisionReadyToFinalize(User $lawyer, Decision $decision): DisciplinaryCase
    {
        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-FIN-'.random_int(1000, 9999),
            'employee_id' => Employee::query()->create([
                'first_name' => 'Fin',
                'last_name' => 'Case',
                'document_number' => '9900'.random_int(100000, 999999),
            ])->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::DECISION,
            'opened_at' => now()->toDateString(),
            'decision' => $decision,
            'decision_comunicado_generated_at' => now(),
            'decision_evidence_uploaded_at' => now(),
        ]);

        if ($decision === Decision::TERMINACION_CONTRATO) {
            $workflow = app(DisciplinaryDecisionWorkflowService::class);
            $admin = $this->user('nivel4', 'hr-fin-'.random_int(1000, 9999).'@test.local');
            $file = UploadedFile::fake()->create('anexo.pdf', 80, 'application/pdf');
            $workflow->uploadHrAnnex($case, $admin, $file);
            $workflow->completeHrReview($case->fresh(['documents']), $admin);
            $case = $case->fresh();
        }

        return $case;
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

    private function sampleSignatureDataUri(): string
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
}
