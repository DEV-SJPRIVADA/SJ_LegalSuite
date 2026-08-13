<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\InformeSubmissionStatus;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\InformeSubmission;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryCaseService;
use App\Services\Disciplinary\FoGj03CitationService;
use App\Services\Disciplinary\FoGj03DraftService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\FieldDisciplinaryTestHelpers;
use Tests\TestCase;

class FoGj03DraftTest extends TestCase
{
    use FieldDisciplinaryTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_new_cases_use_gj_pd_case_number_format(): void
    {
        $lawyer = $this->makeLawyer();
        $employee = Employee::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'document_number' => '1234567890',
        ]);

        $case = app(DisciplinaryCaseService::class)->create($lawyer, [
            'employee_id' => $employee->id,
            'opened_at' => now()->toDateString(),
        ]);

        $this->assertMatchesRegularExpression('/^GJ-PD:\d{6}$/', $case->case_number);
    }

    public function test_fo_gj_03_pdf_blocked_until_draft_completed(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeReadyCaseWithoutDraft();

        $this->actingAs($lawyer)
            ->get(route('disciplinary.cases.fo-gj-03.pdf', $case))
            ->assertForbidden();
    }

    public function test_fo_gj_03_pdf_inline_for_preview_modal(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeReadyCaseWithoutDraft();
        $this->completeDraft($case, $lawyer);

        $response = $this->actingAs($lawyer)
            ->get(route('disciplinary.cases.fo-gj-03.pdf', ['case' => $case, 'inline' => 1]));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_fo_gj_03_can_generate_after_draft_and_signature(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeReadyCaseWithoutDraft();
        $this->completeDraft($case, $lawyer);

        $service = app(FoGj03CitationService::class);
        $this->assertTrue($service->canGenerate($case->fresh()));
    }

    public function test_fo_gj_03_build_view_data_uses_virtual_link_when_selected(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeReadyCaseWithoutDraft();
        $this->completeDraft($case, $lawyer, [
            'modality' => 'virtual',
            'virtual_meeting_link' => 'https://meet.example.com/abc',
        ]);

        $data = app(FoGj03CitationService::class)->buildViewData($case->fresh());

        $this->assertSame('virtual', $data['modality']);
        $this->assertSame('https://meet.example.com/abc', $data['locationText']);
        $this->assertNotNull($data['signatureDataUri']);
    }

    public function test_fo_gj_03_save_draft_rejects_empty_charges_description(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeReadyCaseWithoutDraft();
        $path = 'signatures/'.$lawyer->id.'/signature.png';
        Storage::disk('local')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        $lawyer->forceFill([
            'signature_path' => $path,
            'signature_disk' => 'local',
        ])->save();

        try {
            app(FoGj03DraftService::class)->saveDraft($case->fresh(), $lawyer, [
                'hearing_time' => '10:30',
                'modality' => 'presencial',
                'virtual_meeting_link' => '',
                'breach_date' => now()->subWeek()->toDateString(),
                'charges_description' => '   ',
                'statute_articles' => [
                    ['article_number' => '74', 'numerals' => '1, 3, 4'],
                    ['article_number' => '76', 'numerals' => '10, 34'],
                    ['article_number' => '79', 'numerals' => '3, 12, 15'],
                ],
            ]);
            $this->fail('Expected ValidationException for empty charges_description.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('foGj03ChargesDescription', $e->errors());
        }
    }

    public function test_fo_gj_03_build_view_data_includes_official_charges_paragraph_fields(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeReadyCaseWithoutDraft();
        $this->completeDraft($case, $lawyer, [
            'charges_description' => 'No diligenció la minuta de rondas asignadas.',
        ]);

        $data = app(FoGj03CitationService::class)->buildViewData($case->fresh());

        $this->assertSame('01/05/2026', $data['informeReportDate']);
        $this->assertSame(
            now()->subWeek()->timezone('America/Bogota')->format('d/m/Y'),
            $data['breachDate'],
        );
        $this->assertSame('No diligenció la minuta de rondas asignadas.', $data['chargesDescription']);
    }

    public function test_fo_gj_03_persists_and_renders_additional_evidence_items(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeReadyCaseWithoutDraft();
        $this->completeDraft($case, $lawyer, [
            'evidence_items' => [
                ['text' => 'Video de cámara del puesto Norte'],
                '  ',
                ['text' => 'Testimonio del supervisor de zona'],
            ],
        ]);

        $payload = $case->fresh()->fo_gj_03_payload;
        $this->assertSame([
            'Video de cámara del puesto Norte',
            'Testimonio del supervisor de zona',
        ], $payload['evidence_items'] ?? null);

        $data = app(FoGj03CitationService::class)->buildViewData($case->fresh());
        $this->assertSame([
            'Video de cámara del puesto Norte',
            'Testimonio del supervisor de zona',
        ], $data['additionalEvidenceItems']);

        $html = view('disciplinary.forms.partials.fo-gj-03-evidence', [
            'blankForDownload' => false,
            'informeReportDate' => '01/05/2026',
            'additionalEvidenceItems' => $data['additionalEvidenceItems'],
            'evidenceShowLead' => true,
            'evidenceChunk' => 'Traslado de pruebas.',
        ])->render();

        $this->assertStringContainsString('Informes Disciplinarios', $html);
        $this->assertStringContainsString('Video de cámara del puesto Norte', $html);
        $this->assertStringContainsString('Testimonio del supervisor de zona', $html);
    }

    /** @return array{case: DisciplinaryCase, lawyer: User} */
    private function makeReadyCaseWithoutDraft(): array
    {
        $lawyer = $this->makeLawyer();
        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Test',
            'document_number' => '9100'.random_int(100000, 999999),
            'gender' => 'masculino',
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'GJ-PD:000099',
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::CITACION_PROGRAMADA,
            'opened_at' => now()->toDateString(),
            'citation_confirmed_date' => now()->addDays(3)->toDateString(),
            'citation_confirmed_time' => '09:00:00',
            'citation_confirmed_by' => $lawyer->id,
            'notification_information_completed_at' => now(),
            'notification_date' => now()->addDay()->toDateString(),
            'notification_shift' => 'Mañana',
            'notification_zone' => 'Norte',
            'notification_supervision_zone_id' => $this->seedSupervisionZone()->id,
        ]);

        InformeSubmission::query()->create([
            'submitted_by' => $lawyer->id,
            'employee_id' => $employee->id,
            'status' => InformeSubmissionStatus::AUTORIZADO,
            'storage_disk' => 'local',
            'storage_path' => 'test/informe.pdf',
            'reviewed_by' => $lawyer->id,
            'reviewed_at' => now()->subDays(2),
            'disciplinary_case_id' => $case->id,
            'form_snapshot' => [
                'fo51_report_dd' => '01',
                'fo51_report_mm' => '05',
                'fo51_report_yyyy' => '2026',
            ],
        ]);

        return ['case' => $case, 'lawyer' => $lawyer];
    }

    /** @param  array<string, mixed>  $overrides */
    private function completeDraft(DisciplinaryCase $case, User $lawyer, array $overrides = []): void
    {
        $path = 'signatures/'.$lawyer->id.'/signature.png';
        Storage::disk('local')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
        $lawyer->forceFill([
            'signature_path' => $path,
            'signature_disk' => 'local',
        ])->save();

        app(FoGj03DraftService::class)->saveDraft($case->fresh(), $lawyer, array_merge([
            'hearing_time' => '10:30',
            'modality' => 'presencial',
            'virtual_meeting_link' => '',
            'breach_date' => now()->subWeek()->toDateString(),
            'charges_description' => 'Incumplimiento de consignas operativas en ronda asignada.',
            'statute_articles' => [
                ['article_number' => '74', 'numerals' => '1, 3, 4'],
                ['article_number' => '76', 'numerals' => '10, 34'],
                ['article_number' => '79', 'numerals' => '3, 12, 15'],
            ],
        ], $overrides));
    }

    private function makeLawyer(): User
    {
        $user = User::factory()->create([
            'email' => 'lawyer-fo03-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->assignRole('nivel6');

        return $user;
    }
}
