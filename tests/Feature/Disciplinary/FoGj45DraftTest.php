<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\Decision;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Employee;
use App\Models\User;
use App\Services\Disciplinary\FoGj45DraftService;
use App\Support\Disciplinary\DecisionBranch;
use App\Support\Disciplinary\OfficialFormsCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FoGj45DraftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_closure_branch_maps_verbal_absuelto_archivado(): void
    {
        $this->assertSame(
            [Decision::AMONESTACION_VERBAL, Decision::ABSUELTO, Decision::ARCHIVADO],
            DecisionBranch::choicesForBranch(DecisionBranch::CLOSURE),
        );
        $this->assertSame(DecisionBranch::CLOSURE, DecisionBranch::forDecision(Decision::ARCHIVADO));
        $this->assertSame(DecisionBranch::CLOSURE, DecisionBranch::forDecision(Decision::ABSUELTO));
        $this->assertSame(DecisionBranch::CLOSURE, DecisionBranch::forDecision(Decision::AMONESTACION_VERBAL));
    }

    public function test_catalog_includes_fo_gj_45_blank_pdf(): void
    {
        $this->assertTrue(OfficialFormsCatalog::hasBlankPdf('FO-GJ-45'));
        $this->assertSame(
            'disciplinary.forms.fo-gj-45-blank-download',
            OfficialFormsCatalog::htmlBlankPdfView('FO-GJ-45'),
        );
    }

    public function test_save_draft_persists_fo_gj_45_payload(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDecisionCaseReadyForDraft(Decision::ARCHIVADO);

        $saved = app(FoGj45DraftService::class)->saveDraft($case, $lawyer, [
            'body_paragraph' => 'Por medio de la presente, me permito comunicarle que, dando cumplimiento al debido proceso en el marco del trámite disciplinario iniciado con el informe de fecha 10 de mayo de 2024, derivado de una falta menor, esta Dirección ha RESUELTO:',
            'resolutive_first' => 'NO IMPONER SANCIÓN DISCIPLINARIA',
            'resolutive_second' => 'ARCHIVAR el presente proceso',
            'signer_name' => 'Ana Gómez',
            'signer_title' => 'DIRECTORA GESTIÓN HUMANA',
        ]);

        $this->assertNotNull($saved->decision_draft_completed_at);
        $this->assertSame(FoGj45DraftService::DOCUMENT_CODE, $saved->decision_payload['document_code'] ?? null);
        $this->assertSame('Ana Gómez', $saved->decision_payload['signer_name'] ?? null);
        $this->assertStringContainsString('informe de fecha', (string) ($saved->decision_payload['body_paragraph'] ?? ''));
        $this->assertTrue(app(FoGj45DraftService::class)->isReadyForPdf($saved));
    }

    public function test_save_draft_requires_body_paragraph(): void
    {
        ['case' => $case, 'lawyer' => $lawyer] = $this->makeDecisionCaseReadyForDraft(Decision::ABSUELTO);

        $this->expectException(ValidationException::class);

        app(FoGj45DraftService::class)->saveDraft($case, $lawyer, [
            'body_paragraph' => '',
            'resolutive_first' => 'NO IMPONER SANCIÓN DISCIPLINARIA',
            'resolutive_second' => 'ARCHIVAR el presente proceso',
            'signer_name' => 'Ana Gómez',
        ]);
    }

    public function test_applies_to_all_closure_decisions_not_notice(): void
    {
        $lawyer = $this->makeLawyer();
        $service = app(FoGj45DraftService::class);

        foreach ([Decision::ARCHIVADO, Decision::ABSUELTO, Decision::AMONESTACION_VERBAL] as $decision) {
            $case = $this->baseCase($lawyer);
            $case->forceFill(['decision' => $decision])->save();
            $this->assertTrue($service->appliesTo($case->fresh()), $decision->value);
        }

        $notice = $this->baseCase($lawyer);
        $notice->forceFill(['decision' => Decision::AMONESTACION_ESCRITA])->save();
        $this->assertFalse($service->appliesTo($notice->fresh()));
    }

    /** @return array{case: DisciplinaryCase, lawyer: User} */
    private function makeDecisionCaseReadyForDraft(Decision $decision): array
    {
        $lawyer = $this->makeLawyer();
        $case = $this->baseCase($lawyer);
        $case->forceFill([
            'decision' => $decision,
            'decision_coordination_started_at' => now()->subDay(),
            'decision_notification_completed_at' => now(),
            'decision_notification_supervisor_user_id' => User::factory()->create(['is_active' => true])->id,
            'decision_notification_shift' => 'Mañana',
            'decision_notification_zone' => 'Norte',
        ])->save();

        return ['case' => $case->fresh(), 'lawyer' => $lawyer];
    }

    private function baseCase(User $lawyer): DisciplinaryCase
    {
        $employee = Employee::query()->create([
            'first_name' => 'FO45',
            'last_name' => 'Worker',
            'document_number' => '9501'.random_int(100000, 999999),
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
            'email' => 'fo45-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $user->assignRole('nivel6');

        return $user;
    }
}
