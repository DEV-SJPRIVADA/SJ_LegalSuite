<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
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
use Tests\Support\FieldDisciplinaryTestHelpers;
use Tests\TestCase;

class DecisionStageCompletionTest extends TestCase
{
    use FieldDisciplinaryTestHelpers;
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
            'original_name' => 'FO-GJ-46-firmado-'.$case->case_number.'.pdf',
        ]);
    }

    public function test_lawyer_uploads_termination_package_and_finalizes_with_conclusion(): void
    {
        $lawyer = $this->user('nivel6', 'finalize-term@test.local');
        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-FIN-TERM-'.random_int(1000, 9999),
            'employee_id' => Employee::query()->create([
                'first_name' => 'Fin',
                'last_name' => 'Term',
                'document_number' => '9901'.random_int(100000, 999999),
            ])->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::DECISION,
            'opened_at' => now()->toDateString(),
            'decision' => Decision::TERMINACION_CONTRATO,
            'decision_comunicado_generated_at' => now(),
        ]);

        $workflow = app(DisciplinaryDecisionWorkflowService::class);
        $file = UploadedFile::fake()->create('paquete-terminacion.pdf', 120, 'application/pdf');
        $workflow->uploadTerminationPackage($case, $lawyer, $file);

        $finalized = $workflow->finalizeCase($case->fresh(['documents']), $lawyer, 'Se termina el contrato y se archiva el expediente tras notificación.');

        $this->assertSame(CaseStatus::FINALIZADO, $finalized->current_status);
        $this->assertStringContainsString('Se termina el contrato', (string) $finalized->decision_notes);
    }

    public function test_finalize_notice_with_evidence_and_conclusion(): void
    {
        $lawyer = $this->user('nivel6', 'finalize-notice@test.local');
        $case = $this->makeDecisionReadyToFinalize($lawyer, Decision::AMONESTACION_ESCRITA);

        $finalized = app(DisciplinaryDecisionWorkflowService::class)->finalizeCase(
            $case,
            $lawyer,
            'Se notifica llamado de atención y se cierra el proceso disciplinario.',
        );

        $this->assertSame(CaseStatus::FINALIZADO, $finalized->current_status);
    }

    /** @return array{case: DisciplinaryCase, nivel7: User} */
    private function makeDecisionSupervisorQueueCase(): array
    {
        $lawyer = $this->user('nivel6', 'law-decision-'.random_int(1000, 9999).'@test.local');
        $employee = $this->seedGuardaEmployee('9700'.random_int(100000, 999999));
        $supervisor = $this->seedFieldUserWithCities('nivel7', ['76001']);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-DEC-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'municipality_code' => $employee->municipality_code,
            'current_status' => CaseStatus::DECISION,
            'opened_at' => now()->toDateString(),
            'decision' => Decision::AMONESTACION_ESCRITA,
            'decision_payload' => [
                'document_code' => 'FO-GJ-46',
                'hearing_lead' => 'surtida',
                'facts_narrative' => 'incurrió en incumplimiento de obligaciones laborales.',
                'articles_55' => '1, 2',
                'articles_57' => '3',
                'articles_60' => '1',
                'signer_name' => 'María Pérez',
                'signer_title' => 'DIRECTORA DE GESTIÓN HUMANA',
                'modality' => 'presencial',
                'hearing_day' => '10',
                'hearing_month' => 'enero',
                'hearing_year' => '2026',
                'breach_day' => '5',
                'breach_month' => 'enero',
                'breach_year' => '2026',
            ],
            'decision_draft_completed_at' => now(),
            'decision_comunicado_generated_at' => now(),
            'decision_notification_supervision_zone_id' => $supervisor->currentSupervisionZone()->id,
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
            $file = UploadedFile::fake()->create('paquete.pdf', 80, 'application/pdf');
            $workflow->uploadTerminationPackage($case, $lawyer, $file);
            $case = $case->fresh(['documents']);
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
