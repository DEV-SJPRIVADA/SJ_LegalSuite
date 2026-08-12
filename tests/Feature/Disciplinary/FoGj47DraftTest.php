<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Models\Disciplinary\DisciplinaryAgendaThread;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\FoGj47DraftService;
use App\Support\Disciplinary\OfficialFormsCatalog;
use App\Support\Disciplinary\SuspensionPeriodCalculator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FoGj47DraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_catalog_includes_fo_gj_47_blank_pdf(): void
    {
        $this->assertTrue(OfficialFormsCatalog::hasBlankPdf('FO-GJ-47'));
        $this->assertSame(
            'disciplinary.forms.fo-gj-47-blank-download',
            OfficialFormsCatalog::htmlBlankPdfView('FO-GJ-47'),
        );
    }

    public function test_suspension_period_calculator_uses_calendar_days(): void
    {
        $period = SuspensionPeriodCalculator::fromStartAndDays(
            Carbon::parse('2024-06-04', 'America/Bogota'),
            1,
        );

        $this->assertSame('2024-06-04', $period['end']->toDateString());
        $this->assertSame('2024-06-05', $period['return_date']->toDateString());
        $this->assertSame('UN (1) DÍA', $period['days_phrase']);

        $period3 = SuspensionPeriodCalculator::fromStartAndDays(
            Carbon::parse('2024-06-04', 'America/Bogota'),
            3,
        );
        $this->assertSame('2024-06-06', $period3['end']->toDateString());
        $this->assertSame('2024-06-07', $period3['return_date']->toDateString());
        $this->assertSame('TRES (3) DÍAS', $period3['days_phrase']);
    }

    public function test_save_draft_persists_calculated_dates(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeSuspensionCaseReadyForDraft();

        $saved = app(FoGj47DraftService::class)->saveDraft($case, $lawyer, [
            'opening_narrative' => 'Por medio de la presente me permito comunicarle que, dentro del trámite disciplinario…',
            'suspension_days' => 1,
            'suspension_start' => '2024-06-04',
            'articles_55' => '1',
            'articles_57' => '2',
            'articles_60' => '3',
            'signer_name' => 'Ligia María Álvarez',
            'signer_title' => 'DIRECTORA DE GESTIÓN HUMANA',
        ]);

        $this->assertNotNull($saved->decision_draft_completed_at);
        $this->assertSame(FoGj47DraftService::DOCUMENT_CODE, $saved->decision_payload['document_code'] ?? null);
        $this->assertSame(1, (int) ($saved->decision_payload['suspension_days'] ?? 0));
        $this->assertSame('2024-06-04', $saved->decision_payload['suspension_start'] ?? null);
        $this->assertSame('2024-06-04', $saved->decision_payload['suspension_end'] ?? null);
        $this->assertSame('2024-06-05', $saved->decision_payload['suspension_return'] ?? null);
        $this->assertTrue(app(FoGj47DraftService::class)->isReadyForPdf($saved));
    }

    public function test_save_draft_requires_days(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeSuspensionCaseReadyForDraft();

        $this->expectException(ValidationException::class);

        app(FoGj47DraftService::class)->saveDraft($case, $lawyer, [
            'opening_narrative' => 'Texto',
            'suspension_days' => 0,
            'suspension_start' => '2024-06-04',
            'articles_55' => '1',
            'articles_57' => '2',
            'articles_60' => '3',
            'signer_name' => 'Ligia',
        ]);
    }

    /** @return array{case: DisciplinaryCase, lawyer: User} */
    private function makeSuspensionCaseReadyForDraft(): array
    {
        $lawyer = User::factory()->create([
            'email' => 'fo47-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $lawyer->assignRole('nivel6');

        $employee = Employee::query()->create([
            'first_name' => 'FO47',
            'last_name' => 'Worker',
            'document_number' => '9602'.random_int(100000, 999999),
            'job_title' => 'Guardia',
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'GJ-PD:'.random_int(100000, 999999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::DECISION,
            'opened_at' => now()->toDateString(),
            'decision' => Decision::SUSPENSION,
            'decision_coordination_started_at' => now()->subDay(),
            'decision_notification_completed_at' => now(),
            'decision_notification_supervisor_user_id' => User::factory()->create(['is_active' => true])->id,
            'decision_notification_shift' => 'Mañana',
            'decision_notification_zone' => 'Norte',
            'diligence_attendance' => DiligenceAttendance::ATTENDED,
            'citation_confirmed_date' => now()->subDays(2)->toDateString(),
            'fo_gj_03_payload' => [
                'breach_date' => now()->subDays(10)->toDateString(),
                'modality' => 'presencial',
                'statute_articles' => [
                    ['article_number' => '55', 'numerals' => ['1']],
                    ['article_number' => '57', 'numerals' => ['2']],
                    ['article_number' => '60', 'numerals' => ['3']],
                ],
            ],
        ]);

        DisciplinaryAgendaThread::query()->create([
            'disciplinary_case_id' => $case->id,
            'opened_by' => $lawyer->id,
            'coordination_started_at' => now()->subDay(),
            'coordination_status' => 'open',
        ]);

        return ['case' => $case->fresh(), 'lawyer' => $lawyer];
    }
}
