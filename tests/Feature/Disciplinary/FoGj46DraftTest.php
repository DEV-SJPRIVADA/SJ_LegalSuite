<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Enums\Disciplinary\DiligenceAttendance;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\FoGj46DraftService;
use App\Support\Disciplinary\DecisionBranch;
use App\Support\Disciplinary\FoGj46HearingLead;
use App\Support\Disciplinary\OfficialFormsCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FoGj46DraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_notice_branch_only_allows_llamado_de_atencion(): void
    {
        $this->assertSame(
            [Decision::AMONESTACION_ESCRITA],
            DecisionBranch::choicesForBranch(DecisionBranch::NOTICE),
        );
        $this->assertSame(DecisionBranch::NOTICE, DecisionBranch::forDecision(Decision::AMONESTACION_ESCRITA));
        $this->assertSame(DecisionBranch::CLOSURE, DecisionBranch::forDecision(Decision::ARCHIVADO));
        $this->assertSame('Llamado de atención', Decision::AMONESTACION_ESCRITA->label());
    }

    public function test_catalog_includes_fo_gj_46_blank_pdf(): void
    {
        $this->assertTrue(OfficialFormsCatalog::hasBlankPdf('FO-GJ-46'));
        $this->assertSame(
            'disciplinary.forms.fo-gj-46-blank-download',
            OfficialFormsCatalog::htmlBlankPdfView('FO-GJ-46'),
        );
    }

    public function test_save_draft_persists_fo_gj_46_payload(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDecisionCaseReadyForDraft();

        $saved = app(FoGj46DraftService::class)->saveDraft($case, $lawyer, [
            'hearing_lead' => FoGj46HearingLead::Surtida->value,
            'facts_narrative' => 'incurrió en falta grave al reglamento.',
            'articles_55' => '1',
            'articles_57' => '2',
            'articles_60' => '3',
            'signer_name' => 'Ana Gómez',
            'signer_title' => 'DIRECTORA DE GESTIÓN HUMANA',
        ]);

        $this->assertNotNull($saved->decision_draft_completed_at);
        $this->assertSame(FoGj46DraftService::DOCUMENT_CODE, $saved->decision_payload['document_code'] ?? null);
        $this->assertSame('surtida', $saved->decision_payload['hearing_lead'] ?? null);
        $this->assertSame('Ana Gómez', $saved->decision_payload['signer_name'] ?? null);
        $this->assertTrue(app(FoGj46DraftService::class)->isReadyForPdf($saved));
    }

    public function test_save_draft_requires_hearing_lead(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDecisionCaseReadyForDraft();

        $this->expectException(ValidationException::class);

        app(FoGj46DraftService::class)->saveDraft($case, $lawyer, [
            'hearing_lead' => '',
            'facts_narrative' => 'texto',
            'articles_55' => '1',
            'articles_57' => '2',
            'articles_60' => '3',
            'signer_name' => 'Ana Gómez',
        ]);
    }

    public function test_does_not_apply_to_suspension(): void
    {
        $lawyer = $this->makeLawyer();
        $case = $this->baseCase($lawyer);
        $case->forceFill(['decision' => Decision::SUSPENSION])->save();

        $this->assertFalse(app(FoGj46DraftService::class)->appliesTo($case->fresh()));
    }

    /** @return array{case: DisciplinaryCase, lawyer: User} */
    private function makeDecisionCaseReadyForDraft(): array
    {
        $lawyer = $this->makeLawyer();
        $case = $this->baseCase($lawyer);
        $case->forceFill([
            'decision' => Decision::AMONESTACION_ESCRITA,
            'decision_coordination_started_at' => now()->subDay(),
            'decision_notification_completed_at' => now(),
            'decision_notification_supervisor_user_id' => User::factory()->create(['is_active' => true])->id,
            'decision_notification_shift' => 'Mañana',
            'decision_notification_zone' => 'Norte',
            'diligence_attendance' => DiligenceAttendance::ATTENDED,
            'citation_confirmed_date' => now()->subDays(2)->toDateString(),
            'citation_confirmed_time' => '09:00:00',
            'fo_gj_03_payload' => [
                'breach_date' => now()->subDays(10)->toDateString(),
                'modality' => 'presencial',
                'charges_description' => 'Falta disciplinaria de prueba.',
                'statute_articles' => [
                    ['article_number' => '55', 'numerals' => ['1', '2']],
                    ['article_number' => '57', 'numerals' => ['3']],
                    ['article_number' => '60', 'numerals' => ['1']],
                ],
            ],
        ])->save();

        return ['case' => $case->fresh(), 'lawyer' => $lawyer];
    }

    private function baseCase(User $lawyer): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'FO46',
            'last_name' => 'Worker',
            'document_number' => '9601'.random_int(100000, 999999),
            'job_title' => 'Guardia',
        ]);

        return DisciplinaryCase::query()->create([
            'case_number' => 'GJ-PD:'.random_int(100000, 999999),
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::DECISION,
            'opened_at' => now()->toDateString(),
        ]);
    }

    private function makeLawyer(): User
    {
        $user = User::factory()->create([
            'email' => 'fo46-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->assignRole('nivel6');

        return $user;
    }
}
