<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Enums\Disciplinary\DocumentType;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DiligenceAttendanceService;
use App\Services\Disciplinary\FoGj54DraftService;
use App\Services\Disciplinary\FoGj54ReprogramacionService;
use App\Support\Disciplinary\FoGj03Modality;
use App\Support\Disciplinary\FoGj54RescheduleCause;
use App\Workflow\Disciplinary\TransitionMap;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class DiligenceOperationalRescheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_transition_map_allows_diligence_to_reprogramado_and_back(): void
    {
        $this->assertTrue(TransitionMap::canTransition(CaseStatus::DILIGENCIA, CaseStatus::REPROGRAMADO));
        $this->assertTrue(TransitionMap::canTransition(CaseStatus::REPROGRAMADO, CaseStatus::DILIGENCIA));
    }

    public function test_generate_normalizes_am_pm_hearing_time_for_mysql(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->attachSignature($lawyer);

        $case = app(FoGj54DraftService::class)->saveDraft($case->fresh(), $lawyer, $this->validDraftInput([
            'new_hearing_time' => '08:00 am',
        ]));

        $this->assertSame('08:00', $case->fo_gj_54_payload['new_hearing_time'] ?? null);

        $result = app(FoGj54ReprogramacionService::class)
            ->generateOperationalRescheduleAndStore($case->fresh(), $lawyer);

        $this->assertSame(CaseStatus::REPROGRAMADO, $result->current_status);
        $this->assertSame('08:00:00', (string) $result->citation_confirmed_time);
    }

    public function test_stage_c_shows_generate_after_operational_draft_saved(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->attachSignature($lawyer);

        $case = app(FoGj54DraftService::class)->saveDraft($case->fresh(), $lawyer, $this->validDraftInput());

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case->fresh()])
            ->call('openStageCard', 'c')
            ->assertSee('Generar FO-GJ-54')
            ->assertSee('Vista previa / descargar FO-GJ-54')
            ->assertSee('Editar FO-GJ-54')
            ->assertDontSee('Asistió')
            ->assertDontSee('Reprogramar diligencia');
    }

    public function test_stage_c_shows_reschedule_button_before_attendance(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();

        Livewire::actingAs($lawyer)
            ->test(\App\Livewire\Disciplinary\Cases\CaseDetail::class, ['case' => $case->fresh()])
            ->call('openStageCard', 'c')
            ->assertSee('Reprogramar diligencia')
            ->assertSee('Asistió')
            ->assertSee('No asistió');
    }

    public function test_operational_reschedule_blocked_after_attendance(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->attachSignature($lawyer);
        app(DiligenceAttendanceService::class)->register($case->fresh(), $lawyer, DiligenceAttendance::ATTENDED);

        $this->expectException(ValidationException::class);
        app(FoGj54DraftService::class)->saveDraft($case->fresh(), $lawyer, $this->validDraftInput());
    }

    public function test_operational_reschedule_preserves_fo_gj_03_and_stays_reprogramado_until_evidence(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->attachSignature($lawyer);

        $foGj03At = $case->fo_gj_03_generated_at;
        $evidenceAt = $case->citation_evidence_uploaded_at;

        $drafts = app(FoGj54DraftService::class);
        $case = $drafts->saveDraft($case->fresh(), $lawyer, $this->validDraftInput([
            'reschedule_cause' => FoGj54RescheduleCause::NovedadOperativa->value,
            'new_hearing_date' => now()->addDays(10)->toDateString(),
            'new_hearing_time' => '11:00',
        ]));

        $this->assertSame(FoGj54DraftService::MODE_OPERATIONAL, $case->fo_gj_54_payload['mode'] ?? null);
        $this->assertSame(FoGj54RescheduleCause::NovedadOperativa->value, $case->fo_gj_54_payload['reschedule_cause'] ?? null);

        $result = app(FoGj54ReprogramacionService::class)
            ->generateOperationalRescheduleAndStore($case->fresh(), $lawyer);

        $this->assertSame(CaseStatus::REPROGRAMADO, $result->current_status);
        $this->assertTrue($result->isOperationalReschedulePending());
        $this->assertNull($result->diligence_attendance);
        $this->assertNotNull($result->fo_gj_54_generated_at);
        $this->assertNull($result->fo_gj_54_evidence_uploaded_at);
        $this->assertSame(now()->addDays(10)->toDateString(), $result->citation_confirmed_date?->toDateString());
        $this->assertNotNull($result->fo_gj_03_generated_at);
        $this->assertTrue($result->fo_gj_03_generated_at->equalTo($foGj03At));
        $this->assertNotNull($result->citation_evidence_uploaded_at);
        $this->assertTrue($result->citation_evidence_uploaded_at->equalTo($evidenceAt));
        $this->assertTrue(
            $result->documents()->where('notes', DisciplinaryCase::NOTE_FO_GJ_54_GENERATED)->exists()
        );

        $pdf = app(FoGj54ReprogramacionService::class)->downloadPdf($result->fresh(), $lawyer);
        $this->assertNotEmpty($pdf);
        $view = app(FoGj54ReprogramacionService::class)->buildViewData($result->fresh());
        $this->assertStringContainsString('novedad operativa', $view['rescheduleCausePhrase']);
        $this->assertSame('presencial', $view['modality']);
        $this->assertStringContainsString('presuntamente usted no se presentó', $view['chargesDescription']);
        $this->assertStringContainsString('julio', $view['informeReportDateLong']);
    }

    public function test_upload_fo_gj_54_evidence_returns_to_diligence(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->attachSignature($lawyer);

        $drafts = app(FoGj54DraftService::class);
        $case = $drafts->saveDraft($case->fresh(), $lawyer, $this->validDraftInput([
            'reschedule_cause' => FoGj54RescheduleCause::SolicitudTrabajador->value,
            'new_hearing_date' => now()->addDays(8)->toDateString(),
            'new_hearing_time' => '09:00',
        ]));

        $fo54 = app(FoGj54ReprogramacionService::class);
        $case = $fo54->generateOperationalRescheduleAndStore($case->fresh(), $lawyer);

        $this->assertSame(CaseStatus::REPROGRAMADO, $case->current_status);
        $this->assertTrue($fo54->canUploadReceiptEvidence($case));

        $file = UploadedFile::fake()->create('fo-gj-54-recibido.pdf', 120, 'application/pdf');
        $result = $fo54->uploadReceiptEvidenceAndReturnToDiligence($case->fresh(), $lawyer, $file);

        $this->assertSame(CaseStatus::DILIGENCIA, $result->current_status);
        $this->assertNotNull($result->fo_gj_54_evidence_uploaded_at);
        $this->assertNotNull($result->fo_gj_03_generated_at);
        $this->assertNotNull($result->citation_evidence_uploaded_at);
        $this->assertTrue(
            $result->documents()
                ->where('document_type', DocumentType::EVIDENCIA)
                ->where('notes', FoGj54ReprogramacionService::NOTE_FO_GJ_54_EVIDENCE)
                ->exists()
        );
    }

    public function test_operational_reschedule_can_defer_date_to_planning_without_generating(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->attachSignature($lawyer);

        $originalDate = $case->citation_confirmed_date?->toDateString();

        $result = app(FoGj54ReprogramacionService::class)
            ->beginOperationalRescheduleWithPlanning(
                $case->fresh(),
                $lawyer,
                FoGj54RescheduleCause::NovedadOperativa->value,
            );

        $this->assertSame(CaseStatus::REPROGRAMADO, $result->current_status);
        $this->assertTrue($result->isOperationalReschedulePending());
        $this->assertNull($result->citation_confirmed_date);
        $this->assertNull($result->fo_gj_54_generated_at);
        $this->assertNull($result->fo_gj_54_draft_completed_at);
        $this->assertTrue((bool) ($result->fo_gj_54_payload['defer_date_to_planning'] ?? false));
        $this->assertSame($originalDate, $result->fo_gj_54_payload['original_hearing_date'] ?? null);
        $this->assertNotNull($result->fo_gj_03_generated_at);
    }

    public function test_after_defer_lawyer_can_complete_draft_generate_and_return_via_evidence(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->attachSignature($lawyer);

        $fo54 = app(FoGj54ReprogramacionService::class);
        $drafts = app(FoGj54DraftService::class);

        $case = $fo54->beginOperationalRescheduleWithPlanning(
            $case->fresh(),
            $lawyer,
            FoGj54RescheduleCause::NovedadOperativa->value,
        );

        $case->forceFill([
            'citation_confirmed_date' => now()->addDays(12)->toDateString(),
            'citation_confirmed_time' => '14:30:00',
            'citation_confirmed_by' => $lawyer->id,
        ])->save();

        $case = $drafts->saveDraft($case->fresh(), $lawyer, $this->validDraftInput([
            'reschedule_cause' => FoGj54RescheduleCause::NovedadOperativa->value,
            'modality' => FoGj03Modality::Virtual->value,
            'virtual_meeting_link' => 'https://teams.microsoft.com/l/meetup-join/test',
            'new_hearing_date' => now()->addDays(12)->toDateString(),
            'new_hearing_time' => '14:30',
        ]));

        $this->assertSame(FoGj03Modality::Virtual->value, $case->fo_gj_54_payload['modality'] ?? null);

        $case = $fo54->generateOperationalRescheduleAndStore($case->fresh(), $lawyer);
        $this->assertSame(CaseStatus::REPROGRAMADO, $case->current_status);
        $this->assertNotNull($case->fo_gj_54_generated_at);

        $view = $fo54->buildViewData($case->fresh());
        $this->assertSame('virtual', $view['modality']);
        $this->assertStringContainsString('teams.microsoft.com', $view['modalityLocationText']);

        $file = UploadedFile::fake()->create('recibo.pdf', 80, 'application/pdf');
        $result = $fo54->uploadReceiptEvidenceAndReturnToDiligence($case->fresh(), $lawyer, $file);

        $this->assertSame(CaseStatus::DILIGENCIA, $result->current_status);
        $this->assertTrue($drafts->canEditDraft($result));
    }

    /** @param  array<string, mixed>  $overrides */
    private function validDraftInput(array $overrides = []): array
    {
        return array_merge([
            'reschedule_cause' => FoGj54RescheduleCause::NovedadOperativa->value,
            'modality' => FoGj03Modality::Presencial->value,
            'virtual_meeting_link' => '',
            'new_hearing_date' => now()->addDays(5)->toDateString(),
            'new_hearing_time' => '10:00',
            'defer_date_to_planning' => false,
        ], $overrides);
    }

    private function attachSignature(User $lawyer): void
    {
        $path = 'signatures/'.$lawyer->id.'/signature.png';
        Storage::disk('local')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        $lawyer->forceFill([
            'signature_path' => $path,
            'signature_disk' => 'local',
        ])->save();
    }

    /** @return array{case: DisciplinaryCase, lawyer: User} */
    private function makeDiligenceCase(): array
    {
        $lawyer = User::factory()->create([
            'email' => 'lawyer-ops-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $lawyer->assignRole('nivel6');

        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Ops',
            'document_number' => '9400'.random_int(100000, 999999),
            'gender' => 'masculino',
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'DISC-OPS-'.random_int(1000, 9999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::DILIGENCIA,
            'opened_at' => now()->toDateString(),
            'citation_confirmed_date' => now()->addDay()->toDateString(),
            'citation_confirmed_time' => '10:00:00',
            'fo_gj_03_generated_at' => now()->subDay(),
            'citation_evidence_uploaded_at' => now()->subDay(),
            'fo_gj_03_payload' => [
                'informe_report_date' => '05/07/2026',
                'breach_date' => '2026-07-05',
                'breach_date_display' => '05/07/2026',
                'charges_description' => 'presuntamente usted no se presentó a cumplir con el turno asignado, afectando la relación comercial con el cliente',
                'modality' => 'presencial',
            ],
            'fo_gj_03_draft_completed_at' => now()->subDay(),
        ]);

        return ['case' => $case, 'lawyer' => $lawyer];
    }
}
