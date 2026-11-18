<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryDiligenceWorkflowService;
use App\Services\Disciplinary\FoGj04DiligenceActaService;
use App\Services\Disciplinary\FoGj04DraftService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FoGj04DraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_fo_gj_04_catalog_question_text_survives_catalog_deletion(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->attachSignature($lawyer);

        $catalogQuestion = \App\Models\Disciplinary\DiligenceActaQuestion::query()->create([
            'text' => 'Tenía conocimiento de sus obligaciones laborales',
            'sort_order' => 1,
        ]);

        app(FoGj04DraftService::class)->saveDraft($case->fresh(), $lawyer, [
            'worker_manifestation' => FoGj04DraftService::MANIFESTATION_WANTS_TO_RESPOND,
            'opening_time' => '10:00 AM',
            'closing_time' => '11:30 AM',
            'questions' => [
                [
                    'question' => $catalogQuestion->text,
                    'answer' => 'Sí, las conocía.',
                    'source' => 'catalog',
                    'catalog_question_id' => $catalogQuestion->id,
                ],
            ],
        ]);

        $catalogQuestion->delete();

        $payload = $case->fresh()->fo_gj_04_payload;
        $this->assertSame('catalog', $payload['questions'][0]['source'] ?? null);
        $this->assertStringContainsString('obligaciones laborales', $payload['questions'][0]['question'] ?? '');

        $data = app(FoGj04DiligenceActaService::class)->buildViewData($case->fresh());
        $this->assertStringContainsString('obligaciones laborales', $data['questions'][0]['question'] ?? '');
    }

    public function test_fo_gj_04_defaults_start_with_empty_questions(): void
    {
        ['case' => $case] = $this->makeDiligenceCase();

        $defaults = app(FoGj04DraftService::class)->defaultsForCase($case->fresh());

        $this->assertSame([], $defaults['questions']);
    }

    public function test_fo_gj_04_pdf_blocked_until_draft_completed(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();

        $this->actingAs($lawyer)
            ->get(route('disciplinary.cases.fo-gj-04.pdf', $case))
            ->assertForbidden();
    }

    public function test_fo_gj_04_pdf_inline_for_preview_modal(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->completeDraft($case, $lawyer);

        $response = $this->actingAs($lawyer)
            ->get(route('disciplinary.cases.fo-gj-04.pdf', ['case' => $case, 'inline' => 1]));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_fo_gj_04_can_generate_after_draft_and_signature(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->completeDraft($case, $lawyer);
        $this->saveWorkerSignature($case, $lawyer);

        $service = app(FoGj04DiligenceActaService::class);
        $this->assertTrue($service->canGenerate($case->fresh()));
    }

    public function test_fo_gj_04_manifestation_options_in_view_data(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->completeDraft($case, $lawyer, ['worker_manifestation' => FoGj04DraftService::MANIFESTATION_REFUSES_TO_RESPOND]);

        $data = app(FoGj04DiligenceActaService::class)->buildViewData($case->fresh());

        $this->assertSame('no', $data['workerManifestation']);
        $this->assertSame('15', $data['breachDay']);
        $this->assertSame('enero', $data['breachMonth']);
        $this->assertSame('2026', $data['breachYear']);
        $this->assertStringContainsString('incumplimiento', $data['chargesDescription']);
        $this->assertSame('Sí, los reconozco parcialmente.', $data['questions'][0]['answer'] ?? null);
    }

    public function test_fo_gj_04_format_question_marks_adds_spanish_punctuation(): void
    {
        $this->assertSame(
            '¿Reconoce los hechos?',
            FoGj04DraftService::formatQuestionMarks('Reconoce los hechos'),
        );
        $this->assertSame(
            '¿Ya tiene signos?',
            FoGj04DraftService::formatQuestionMarks('¿Ya tiene signos???'),
        );
    }

    public function test_fo_gj_04_save_draft_rejects_question_without_answer(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->attachSignature($lawyer);

        try {
            app(FoGj04DraftService::class)->saveDraft($case->fresh(), $lawyer, [
                'worker_manifestation' => FoGj04DraftService::MANIFESTATION_WANTS_TO_RESPOND,
                'opening_time' => '10:00 AM',
                'closing_time' => '11:00 AM',
                'questions' => [
                    ['question' => '¿Reconoce los hechos?', 'answer' => ''],
                ],
            ]);
            $this->fail('Expected ValidationException for missing answer.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('foGj04Questions', $e->errors());
        }
    }

    public function test_fo_gj_04_save_draft_rejects_empty_questions(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $case = $case->fresh();
        $this->attachSignature($lawyer);

        try {
            app(FoGj04DraftService::class)->saveDraft($case->fresh(), $lawyer, [
                'worker_manifestation' => FoGj04DraftService::MANIFESTATION_WANTS_TO_RESPOND,
                'opening_time' => '10:00 AM',
                'closing_time' => '11:00 AM',
                'questions' => [],
            ]);
            $this->fail('Expected ValidationException for empty questions.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('foGj04Questions', $e->errors());
        }
    }

    public function test_fo_gj_04_can_upload_signed_without_digital_signature(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->completeDraft($case, $lawyer);

        $service = app(FoGj04DiligenceActaService::class);
        $this->assertTrue($service->canUploadSigned($case->fresh()));
        $this->assertFalse($service->canGenerate($case->fresh()));
    }

    public function test_fo_gj_04_upload_signed_stores_document_and_marks_generated(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->completeDraft($case, $lawyer);

        $file = UploadedFile::fake()->create('fo-gj-04-firmado.pdf', 120, 'application/pdf');
        $case = app(FoGj04DiligenceActaService::class)->uploadSignedAndStore($case->fresh(), $file, $lawyer);

        $this->assertNotNull($case->fo_gj_04_generated_at);
        $this->assertDatabaseHas('disciplinary_documents', [
            'disciplinary_case_id' => $case->id,
            'document_type' => DocumentType::ACTA_DILIGENCIA->value,
            'notes' => DisciplinaryCase::NOTE_FO_GJ_04_UPLOADED,
        ]);
    }

    public function test_fo_gj_04_upload_signed_allows_advance_without_digital_signature(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->completeDraft($case, $lawyer);

        $file = UploadedFile::fake()->create('fo-gj-04-firmado.pdf', 120, 'application/pdf');
        app(FoGj04DiligenceActaService::class)->uploadSignedAndStore($case->fresh(), $file, $lawyer);

        $missing = app(DisciplinaryDiligenceWorkflowService::class)
            ->missingAdvanceToDecisionRequirements($case->fresh());

        $this->assertSame([], $missing);
    }

    public function test_lawyer_can_confirm_uploaded_signed_acta_via_livewire(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase();
        $this->completeDraft($case, $lawyer);
        $file = UploadedFile::fake()->create('fo-gj-04-firmado.pdf', 120, 'application/pdf');

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->set('foGj04SignedUploadFile', $file)
            ->assertSet('showFoGj04SignedUploadPreview', true)
            ->call('confirmFoGj04SignedUpload')
            ->assertHasNoErrors();

        $case->refresh();
        $this->assertNotNull($case->fo_gj_04_generated_at);
    }

    public function test_fo_gj_04_partial_draft_saved_before_attendance_is_registered(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase(attendance: null);

        $service = app(FoGj04DraftService::class);

        $case = $service->saveDraft($case->fresh(), $lawyer, [
            'worker_manifestation' => '',
            'opening_time' => '10:00 AM',
            'closing_time' => '',
            'questions' => [
                ['question' => 'Reconoce los hechos descritos en la citación', 'answer' => ''],
                ['question' => '', 'answer' => ''],
            ],
        ], asDraft: true);

        $this->assertCount(1, $case->fo_gj_04_payload['questions'] ?? []);
        $this->assertNull($case->fo_gj_04_draft_completed_at);
        $this->assertTrue($service->hasDraft($case));
        $this->assertTrue($service->isPendingDraft($case));
        $this->assertFalse($service->hasDraftCompleted($case));
        $this->assertNotNull($service->draftSavedAt($case));
        $this->assertFalse($service->isReadyForPdf($case));
    }

    public function test_fo_gj_04_partial_draft_does_not_require_lawyer_signature(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase(attendance: null);

        $this->assertFalse($lawyer->hasSignature());

        app(FoGj04DraftService::class)->saveDraft($case->fresh(), $lawyer, [
            'opening_time' => '',
            'closing_time' => '',
            'questions' => [],
        ], asDraft: true);

        $this->assertTrue(app(FoGj04DraftService::class)->hasDraft($case->fresh()));
    }

    public function test_fo_gj_04_definitive_save_still_requires_attendance(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase(attendance: null);
        $this->attachSignature($lawyer);

        try {
            app(FoGj04DraftService::class)->saveDraft($case->fresh(), $lawyer, [
                'worker_manifestation' => FoGj04DraftService::MANIFESTATION_WANTS_TO_RESPOND,
                'opening_time' => '10:00 AM',
                'closing_time' => '11:00 AM',
                'questions' => [['question' => '¿Reconoce los hechos?', 'answer' => 'Sí.']],
            ]);
            $this->fail('Expected ValidationException for missing attendance.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fo_gj_04', $e->errors());
            $this->assertStringContainsString('asistencia', (string) $e->errors()['fo_gj_04'][0]);
        }
    }

    public function test_lawyer_can_open_and_save_draft_before_attendance_via_livewire(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase(attendance: null);

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case->fresh()])
            ->call('openStageCard', 'c')
            ->assertSee('Preparar FO-GJ-04')
            ->call('openFoGj04DraftModal')
            ->assertSet('showFoGj04DraftModal', true)
            ->call('addFoGj04Question')
            ->set('foGj04Questions.0.question', 'Reconoce los hechos descritos en la citación')
            ->call('saveFoGj04PartialDraft')
            ->assertHasNoErrors()
            ->assertSet('showFoGj04DraftModal', false);

        $case->refresh();
        $this->assertTrue(app(FoGj04DraftService::class)->isPendingDraft($case));
        $this->assertNull($case->fo_gj_04_draft_completed_at);
        $this->assertNull($case->diligence_attendance);
    }

    public function test_registering_absence_discards_fo_gj_04_draft(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase(attendance: null);

        app(FoGj04DraftService::class)->saveDraft($case->fresh(), $lawyer, [
            'opening_time' => '10:00 AM',
            'closing_time' => '',
            'questions' => [['question' => 'Reconoce los hechos descritos en la citación', 'answer' => 'Sí.']],
        ], asDraft: true);

        $this->assertTrue(app(FoGj04DraftService::class)->hasDraft($case->fresh()));

        app(\App\Services\Disciplinary\DiligenceAttendanceService::class)
            ->register($case->fresh(), $lawyer, \App\Enums\Disciplinary\DiligenceAttendance::ABSENT);

        $case->refresh();
        $this->assertNull($case->fo_gj_04_payload);
        $this->assertFalse(app(FoGj04DraftService::class)->hasDraft($case));
        $this->assertFalse($lawyer->can('editFoGj04Draft', $case));
    }

    public function test_fo_gj_04_partial_draft_is_kept_when_worker_attends(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDiligenceCase(attendance: null);

        app(FoGj04DraftService::class)->saveDraft($case->fresh(), $lawyer, [
            'opening_time' => '10:00 AM',
            'closing_time' => '',
            'questions' => [['question' => 'Reconoce los hechos descritos en la citación', 'answer' => '']],
        ], asDraft: true);

        app(\App\Services\Disciplinary\DiligenceAttendanceService::class)
            ->register($case->fresh(), $lawyer, \App\Enums\Disciplinary\DiligenceAttendance::ATTENDED);

        $case->refresh();
        $this->assertTrue(app(FoGj04DraftService::class)->isPendingDraft($case));

        $defaults = app(FoGj04DraftService::class)->defaultsForCase($case);
        $this->assertSame('10:00 AM', $defaults['opening_time']);
        $this->assertCount(1, $defaults['questions']);
    }

    /** @return array{case: DisciplinaryCase, lawyer: User} */
    private function makeDiligenceCase(?\App\Enums\Disciplinary\DiligenceAttendance $attendance = \App\Enums\Disciplinary\DiligenceAttendance::ATTENDED): array
    {
        $lawyer = $this->makeLawyer();
        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Acta',
            'document_number' => '9200'.random_int(100000, 999999),
            'job_title' => 'Operador',
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'GJ-PD:000088',
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::DILIGENCIA,
            'opened_at' => now()->toDateString(),
            'citation_confirmed_date' => now()->addDays(2)->toDateString(),
            'citation_confirmed_time' => '09:00:00',
            'citation_confirmed_by' => $lawyer->id,
            'diligence_attendance' => $attendance,
            'diligence_attendance_registered_at' => $attendance !== null ? now() : null,
            'diligence_attendance_registered_by' => $attendance !== null ? $lawyer->id : null,
            'fo_gj_03_payload' => [
                'breach_date' => '2026-01-15',
                'breach_date_display' => '15/01/2026',
                'charges_description' => 'no diligenció la minuta de rondas asignadas, constituyendo incumplimiento operativo',
            ],
        ]);

        return ['case' => $case, 'lawyer' => $lawyer];
    }

    /** @param  array<string, mixed>  $overrides */
    private function completeDraft(DisciplinaryCase $case, User $lawyer, array $overrides = []): void
    {
        $this->attachSignature($lawyer);

        app(FoGj04DraftService::class)->saveDraft($case->fresh(), $lawyer, array_merge([
            'worker_manifestation' => FoGj04DraftService::MANIFESTATION_WANTS_TO_RESPOND,
            'opening_time' => '10:00 AM',
            'closing_time' => '11:30 AM',
            'questions' => [
                [
                    'question' => 'Reconoce los hechos descritos en la citación',
                    'answer' => 'Sí, los reconozco parcialmente.',
                ],
                [
                    'question' => 'Desea agregar alguna aclaración',
                    'answer' => 'No tengo más que agregar.',
                ],
            ],
        ], $overrides));
    }

    private function attachSignature(User $lawyer): void
    {
        $path = 'signatures/'.$lawyer->id.'/signature.png';
        Storage::disk('local')->put($path, str_repeat('PNG', 40));
        $lawyer->forceFill([
            'signature_path' => $path,
            'signature_disk' => 'local',
        ])->save();
    }

    private function saveWorkerSignature(DisciplinaryCase $case, User $lawyer): void
    {
        $lawyer = $lawyer->fresh();
        $binary = Storage::disk($lawyer->signature_disk ?? 'local')->get((string) $lawyer->signature_path);
        $dataUri = 'data:image/png;base64,'.base64_encode($binary);

        app(FoGj04DraftService::class)->saveWorkerSignature($case->fresh(), $lawyer, $dataUri);
    }

    private function makeLawyer(): User
    {
        $user = User::factory()->create([
            'email' => 'lawyer-fo04-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->assignRole('nivel6');

        return $user;
    }
}
