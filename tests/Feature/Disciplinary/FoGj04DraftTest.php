<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\FoGj04DiligenceActaService;
use App\Services\Disciplinary\FoGj04DraftService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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

    /** @return array{case: DisciplinaryCase, lawyer: User} */
    private function makeDiligenceCase(): array
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
        Storage::disk('local')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        $lawyer->forceFill([
            'signature_path' => $path,
            'signature_disk' => 'local',
        ])->save();
    }

    private function makeLawyer(): User
    {
        $user = User::factory()->create([
            'email' => 'lawyer-fo04-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->assignRole('abogado');

        return $user;
    }
}
